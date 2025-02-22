/**
 * Dependências:
 * - express: Framework web para Node.js.
 * - whatsapp-web.js: Biblioteca para interagir com o WhatsApp Web.
 * - mysql2: Cliente MySQL para Node.js.
 * - qrcode-terminal: Para exibir o QR Code no terminal.
 * - path: Módulo para manipulação de caminhos de arquivos.
 * - fs: Módulo para manipulação de arquivos.
 */

// Importando os módulos
const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const mysql = require('mysql2/promise');
const path = require('path');
const fs = require('fs');

// -------------------------------------
// Configurações Iniciais
// -------------------------------------

// Inicializando o express
const app = express();
app.use(express.json());

// Configuração do CORS (Cross-Origin Resource Sharing)
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*'); // Permite requisições de qualquer origem
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS'); // Métodos HTTP permitidos
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept'); // Cabeçalhos permitidos
    if (req.method === 'OPTIONS') {
        return res.sendStatus(200); // Responde com sucesso para requisições OPTIONS (preflight)
    }
    next();
});

// Configuração do banco de dados (usando variáveis de ambiente)
// **IMPORTANTE:** Configure as variáveis de ambiente no seu sistema operacional ou servidor.
// Exemplo (Linux/macOS):
// export DB_HOST=localhost
// export DB_USER=root
// export DB_PASSWORD=sua_senha
// export DB_DATABASE=balcao
// Exemplo (.env file - recomendado usar a biblioteca 'dotenv'):
// DB_HOST=localhost
// DB_USER=root
// DB_PASSWORD=sua_senha
// DB_DATABASE=balcao

const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_DATABASE || 'balcao'
};

// Diretório para armazenar sessões do WhatsApp Web
const SESSION_DIR = path.join(__dirname, '.wwebjs_auth');

// Armazenar clientes WhatsApp Web ativos (Map<deviceId, Client>)
const clients = new Map();

// Tamanho do lote para processamento da fila de mensagens
const CHUNK_SIZE = 1;

// Rate limiting:  Map<deviceId, lastRequestTime>
const lastRequestTimes = new Map();
const RATE_LIMIT_MS = 1000; // 1 requisição por segundo

// -------------------------------------
// Funções Auxiliares
// -------------------------------------

/**
 * Cria o diretório de sessão se ele não existir.
 */
if (!fs.existsSync(SESSION_DIR)) {
    fs.mkdirSync(SESSION_DIR, { recursive: true });
    console.log(`Diretório de sessão criado: ${SESSION_DIR}`);
}

/**
 * Atualiza o status de um dispositivo no banco de dados.
 *
 * @param {string} deviceId - ID do dispositivo.
 * @param {string} status - Novo status do dispositivo.
 * @param {string} [qrCode=null] - QR Code (opcional).
 */
async function updateDeviceStatus(deviceId, status, qrCode = null) {
    try {
        const connection = await mysql.createConnection(dbConfig);
        const sql = qrCode
            ? 'UPDATE dispositivos SET status = ?, qr_code = ? WHERE device_id = ?'
            : 'UPDATE dispositivos SET status = ?, qr_code = NULL WHERE device_id = ?';
        const values = qrCode ? [status, qrCode, deviceId] : [status, deviceId];
        await connection.execute(sql, values);
        await connection.end();
        console.log(`Status do dispositivo ${deviceId} atualizado para: ${status}`);
    } catch (error) {
        console.error(`Erro ao atualizar status do dispositivo ${deviceId}: ${error.message}`);
    }
}

/**
 * Limpa o deviceId, removendo caracteres inválidos.
 *
 * @param {string} deviceId - ID do dispositivo.
 * @returns {string} - ID do dispositivo sanitizado.
 */
function sanitizeDeviceId(deviceId) {
    // Remove caracteres inválidos, mantendo apenas alfanuméricos, underscores e hífens
    return deviceId.replace(/[^a-zA-Z0-9_-]/g, '_');
}

/**
 * Limpa a sessão de um dispositivo, removendo o diretório correspondente.
 *
 * @param {string} deviceId - ID do dispositivo.
 */
async function clearSession(deviceId) {
    const sanitizedDeviceId = sanitizeDeviceId(deviceId);
    const sessionDir = path.join(SESSION_DIR, `session-${sanitizedDeviceId}`);
    if (fs.existsSync(sessionDir)) {
        fs.rmSync(sessionDir, { recursive: true, force: true });
        console.log(`Sessão do dispositivo ${deviceId} removida.`);
    }
}

/**
 * Valida se um ID de usuário é um número inteiro positivo.
 * @param {any} userId - O ID do usuário a ser validado.
 * @returns {boolean} - Retorna true se o ID do usuário for válido, false caso contrário.
 */
function isValidUserId(userId) {
    return Number.isInteger(userId) && userId > 0;
}

/**
 * Valida um número de telefone no formato básico do WhatsApp.
 *  Verifica se tem entre 10 e 13 dígitos e começa com o código do país (opcional).
 * @param {string} number - O número de telefone a ser validado.
 * @returns {boolean} Retorna `true` se o número for válido, `false` caso contrário.
 */
function isValidPhoneNumber(number) {
    const cleanedNumber = number.replace(/\D/g, ''); // Remove caracteres não numéricos
    return /^(?:\+?\d{1,3})?\d{10,13}$/.test(cleanedNumber);
}

/**
 * Processa uma única mensagem da fila.
 *
 * @param {object} message - Objeto da mensagem da fila.
 * @param {object} connection - Conexão com o banco de dados.
 * @param {Client} client - Cliente WhatsApp Web.
 */
async function processMessage(message, connection, client) {
    try {
        let formattedNumber = message.numero.replace(/\D/g, '');
        if (!formattedNumber.startsWith('55')) {
            formattedNumber = '55' + formattedNumber;
        }
        formattedNumber = `${formattedNumber}@c.us`;

        console.log(`Processando mensagem para: ${formattedNumber}`);

        // Verificar número
        const isRegistered = await client.isRegisteredUser(formattedNumber);
        if (!isRegistered) {
            throw new Error('Número não registrado no WhatsApp');
        }

        // 1. Enviar mídia (se existir e o arquivo existir)
        if (message.arquivo_path && fs.existsSync(message.arquivo_path)) {
            const media = MessageMedia.fromFilePath(message.arquivo_path);
            await client.sendMessage(formattedNumber, media);
            console.log(`Mídia enviada para ${formattedNumber}`);
            // Aguarda 2 segundos após enviar a mídia
            await new Promise(resolve => setTimeout(resolve, 2000));
        }

        // 2. Enviar mensagem de texto logo em seguida (se existir)
        if (message.mensagem && message.mensagem.trim()) {
            await client.sendMessage(formattedNumber, message.mensagem);
            console.log(`Mensagem de texto enviada para ${formattedNumber}`);
        }

        // Atualizar status como enviado
        await connection.execute(
            'UPDATE fila_mensagens SET status = "ENVIADO", updated_at = NOW() WHERE id = ?',
            [message.id]
        );
        console.log(`Mensagem ${message.id} marcada como ENVIADA.`);

        // Intervalo aleatório entre leads (entre 1 e 3 segundos)
        await new Promise(resolve =>
            setTimeout(resolve, Math.random() * 2000 + 1000)
        );

    } catch (error) {
        await connection.execute(
            'UPDATE fila_mensagens SET status = "ERRO", error_message = ? WHERE id = ?',
            [error.message.substring(0, 255), message.id] // Limita o tamanho da mensagem de erro
        );
        console.error(`Erro ao processar mensagem ${message.id}: ${error.message}`);
    }
}

// -------------------------------------
// Gerenciamento de Clientes WhatsApp
// -------------------------------------

/**
 * Cria um novo cliente WhatsApp Web.
 *
 * @param {string} deviceId - ID do dispositivo.
 * @returns {Promise<Client>} - Cliente WhatsApp Web.
 */
async function createWhatsAppClient(deviceId) {
    console.log(`Iniciando criação do cliente WhatsApp para deviceId: ${deviceId}`);

    try {
        const sanitizedDeviceId = sanitizeDeviceId(deviceId);
        await clearSession(sanitizedDeviceId);

        const client = new Client({
            authStrategy: new LocalAuth({
                clientId: sanitizedDeviceId,
                dataPath: SESSION_DIR
            }),
            puppeteer: {
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--no-first-run',
                    '--no-zygote',
                    '--disable-gpu'
                ],
                headless: true,
                timeout: 60000 // Aumentar timeout para 60 segundos
            }
        });

        // Evento QR Code
        client.on('qr', async (qr) => {
            console.log(`Novo QR Code gerado para deviceId: ${deviceId}`);
            await updateDeviceStatus(deviceId, 'WAITING_QR', qr);
        });

        // Evento Ready
        client.on('ready', async () => {
            console.log(`Cliente WhatsApp pronto para deviceId: ${deviceId}`);
            await updateDeviceStatus(deviceId, 'CONNECTED');
        });

        // Evento de falha na autenticação
        client.on('auth_failure', async () => {
            console.log(`Falha na autenticação para deviceId: ${deviceId}`);
            await updateDeviceStatus(deviceId, 'AUTH_FAILURE');
            await clearSession(deviceId);
            clients.delete(deviceId);
        });

        // Evento de desconexão
        client.on('disconnected', async () => {
            console.log(`Cliente desconectado para deviceId: ${deviceId}`);
            await updateDeviceStatus(deviceId, 'DISCONNECTED');
            await clearSession(deviceId);
            clients.delete(deviceId);
        });

        await client.initialize();
        clients.set(deviceId, client);
        console.log(`Cliente WhatsApp inicializado para deviceId: ${deviceId}`);
        return client;

    } catch (error) {
        console.error(`Erro ao criar cliente WhatsApp para deviceId ${deviceId}: ${error.message}`);
        await updateDeviceStatus(deviceId, 'ERROR');
        throw error; // Propaga o erro para quem chamou a função
    }
}

// -------------------------------------
// Endpoints da API
// -------------------------------------

/**
 * Aplica um rate limit simples por dispositivo.
 * @param {string} deviceId - O ID do dispositivo.
 * @returns {boolean} - Retorna `true` se a requisição pode prosseguir, `false` se o limite foi atingido.
 */
function applyRateLimit(deviceId) {
    const now = Date.now();
    if (lastRequestTimes.has(deviceId)) {
        const lastRequestTime = lastRequestTimes.get(deviceId);
        if (now - lastRequestTime < RATE_LIMIT_MS) {
            console.warn(`Rate limit atingido para deviceId: ${deviceId}`);
            return false; // Limite atingido
        }
    }
    lastRequestTimes.set(deviceId, now);
    return true; // Permite a requisição
}

/**
 * Endpoint para processar a fila de mensagens de um usuário.
 *  Verifica se o usuario_id é válido antes de processar.
 * @param {Request} req - Objeto de requisição.
 * @param {Response} res - Objeto de resposta.
 */
app.post('/process-queue', async (req, res) => {
    const { usuario_id, dispositivo_id } = req.body;

    // Validação do usuario_id
    if (!isValidUserId(usuario_id)) {
        console.error(`Tentativa de acesso com usuario_id inválido: ${usuario_id}`);
        return res.status(400).json({ success: false, message: 'usuario_id inválido. Deve ser um número inteiro positivo.' });
    }

     // Aplica rate limiting
     if (!applyRateLimit(dispositivo_id)) {
        return res.status(429).json({ success: false, message: 'Muitas requisições. Tente novamente mais tarde.' });
    }

    console.log(`Requisição para processar fila recebida. usuario_id: ${usuario_id}, dispositivo_id: ${dispositivo_id}`);

    try {
        const result = await processMessageQueue(usuario_id, dispositivo_id);
        if (result.success) {
            console.log(`Fila processada com sucesso para usuario_id: ${usuario_id}`);
            res.json({ success: true });
        } else {
            console.error(`Falha ao processar fila para usuario_id: ${usuario_id}`);
            res.status(500).json({ success: false, error: result.error });
        }
    } catch (error) {
        console.error(`Erro ao processar fila para usuario_id: ${usuario_id}: ${error.message}`);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Processa a fila de mensagens para um determinado usuário.
 *
 * @param {number} usuario_id - ID do usuário.
 * @param {string} dispositivo_id - ID do dispositivo.
 * @returns {Promise<{success: boolean, error?: string}>} - Resultado do processamento.
 */
async function processMessageQueue(usuario_id, dispositivo_id) {
    console.log(`Iniciando processamento da fila para usuário: ${usuario_id}`);

    try {
        const connection = await mysql.createConnection(dbConfig);

        while (true) {
            const [messages] = await connection.execute(
                'SELECT * FROM fila_mensagens WHERE usuario_id = ? AND status = "PENDENTE" LIMIT ?',
                [usuario_id, CHUNK_SIZE]
            );

            if (messages.length === 0) {
                console.log(`Fila vazia para usuário: ${usuario_id}`);
                break;
            }

            console.log(`Processando ${messages.length} mensagens para usuário: ${usuario_id}`);

            // Processa uma mensagem por vez
            for (const message of messages) {
                await processMessage(message, connection, clients.get(dispositivo_id));
            }

            // Pequena pausa entre processamentos
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

        await connection.end();
        console.log(`Processamento da fila concluído para usuário: ${usuario_id}`);
        return { success: true };

    } catch (error) {
        console.error(`Erro ao processar fila para usuário ${usuario_id}: ${error.message}`);
        return { success: false, error: error.message };
    }
}

/**
 * Endpoint para obter o progresso da fila de um usuário.
 *  Verifica se o usuario_id é válido.
 * @param {Request} req - Objeto de requisição.
 * @param {Response} res - Objeto de resposta.
 */
app.get('/queue-progress/:usuario_id', async (req, res) => {
    const usuario_id = parseInt(req.params.usuario_id);

    // Validação do usuario_id
    if (!isValidUserId(usuario_id)) {
        console.error(`Tentativa de acesso com usuario_id inválido: ${usuario_id}`);
        return res.status(400).json({ success: false, message: 'usuario_id inválido. Deve ser um número inteiro positivo.' });
    }

    try {
        const connection = await mysql.createConnection(dbConfig);
        const [result] = await connection.execute(`
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'ENVIADO' THEN 1 ELSE 0 END) as enviados,
                SUM(CASE WHEN status = 'ERRO' THEN 1 ELSE 0 END) as erros,
                SUM(CASE WHEN status = 'PENDENTE' THEN 1 ELSE 0 END) as pendentes
            FROM fila_mensagens
            WHERE usuario_id = ?
        `, [usuario_id]);

        await connection.end();
        console.log(`Progresso da fila para usuario_id ${usuario_id} obtido com sucesso.`);
        res.json(result[0]);
    } catch (error) {
        console.error(`Erro ao obter progresso da fila para usuario_id ${usuario_id}: ${error.message}`);
        res.status(500).json({ error: error.message });
    }
});

/**
 * Endpoint para obter o status da fila de mensagens de um usuário.
 * Verifica se o usuario_id é válido.
 * @param {Request} req - Objeto de requisição.
 * @param {Response} res - Objeto de resposta.
 */
app.get('/queue-status/:usuario_id', async (req, res) => {
    const usuario_id = parseInt(req.params.usuario_id);

    // Validação do usuario_id
    if (!isValidUserId(usuario_id)) {
        console.error(`Tentativa de acesso com usuario_id inválido: ${usuario_id}`);
        return res.status(400).json({ success: false, message: 'usuario_id inválido. Deve ser um número inteiro positivo.' });
    }

    try {
        const connection = await mysql.createConnection(dbConfig);
        const [rows] = await connection.execute(
            `SELECT
                COUNT(CASE WHEN status = 'PENDENTE' THEN 1 END) as pendentes,
                COUNT(CASE WHEN status = 'ENVIADO' THEN 1 END) as enviados,
                COUNT(CASE WHEN status = 'ERRO' THEN 1 END) as erros
            FROM fila_mensagens
            WHERE usuario_id = ?`,
            [usuario_id]
        );
        await connection.end();

        console.log(`Status da fila para usuario_id ${usuario_id} obtido com sucesso.`);
        res.json({
            success: true,
            status: rows[0]
        });
    } catch (error) {
        console.error(`Erro ao obter status da fila para usuario_id ${usuario_id}: ${error.message}`);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * Endpoint para enviar uma mensagem.
 *  Valida o número de telefone e aplica rate limiting.
 * @param {Request} req - Objeto de requisição.
 * @param {Response} res - Objeto de resposta.
 */
app.post('/send-message', async (req, res) => {
    const { deviceId, number, message, mediaPath } = req.body;

    // Aplica rate limiting
    if (!applyRateLimit(deviceId)) {
        return res.status(429).json({ success: false, message: 'Muitas requisições. Tente novamente mais tarde.' });
    }

    // Validação do número de telefone
    if (!isValidPhoneNumber(number)) {
        console.error(`Tentativa de envio para número inválido: ${number}`);
        return res.status(400).json({ success: false, message: 'Número de telefone inválido.' });
    }

    try {
        const client = clients.get(deviceId);
        if (!client) {
            throw new Error('Dispositivo não encontrado ou não conectado');
        }

        let formattedNumber = number;
        if (!formattedNumber.includes('@c.us')) {
            formattedNumber = `${formattedNumber}@c.us`;
        }

        console.log(`Enviando mensagem para: ${formattedNumber}`);

        // Enviar arquivo se existir
        if (mediaPath) {
            try {
                // Verifica se o arquivo existe
                if (!fs.existsSync(mediaPath)) {
                    throw new Error('Arquivo não encontrado: ' + mediaPath);
                }

                console.log(`Enviando mídia: ${mediaPath}`);
                const media = MessageMedia.fromFilePath(mediaPath);

                // Envia a mídia primeiro
                await client.sendMessage(formattedNumber, media);

                // Pequeno intervalo após envio de mídia
                await new Promise(resolve => setTimeout(resolve, 2000));

                console.log('Mídia enviada com sucesso');
            } catch (mediaError) {
                console.error('Erro ao enviar mídia:', mediaError);
                return res.status(500).json({
                    success: false,
                    message: 'Erro ao enviar mídia: ' + mediaError.message
                });
            }
        }

        // Enviar mensagem de texto se existir
        if (message && message.trim()) {
            try {
                await client.sendMessage(formattedNumber, message);
                console.log('Mensagem de texto enviada com sucesso');
            } catch (messageError) {
                console.error('Erro ao enviar mensagem:', messageError);
                return res.status(500).json({
                    success: false,
                    message: 'Erro ao enviar mensagem: ' + messageError.message
                });
            }
        }

        console.log(`Mensagem enviada com sucesso para ${formattedNumber}`);
        res.json({ success: true });

    } catch (error) {
        console.error(`Erro ao enviar mensagem para ${number}: ${error.message}`);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * Endpoint para verificar o status de um dispositivo.
 *
 * @param {Request} req - Objeto de requisição.
 * @param {Response} res - Objeto de resposta.
 */
app.get('/check-status/:deviceId', async (req, res) => {
    const { deviceId } = req.params;
    try {
        const connection = await mysql.createConnection(dbConfig);
        const [rows] = await connection.execute(
            'SELECT status FROM dispositivos WHERE device_id = ?',
            [deviceId]
        );
        await connection.end();

        if (rows.length > 0) {
            console.log(`Status do dispositivo ${deviceId} consultado: ${rows[0].status}`);
            res.json({
                success: true,
                status: rows[0].status
            });
        } else {
            console.log(`Dispositivo ${deviceId} não encontrado.`);
            res.status(404).json({
                success: false,
                message: 'Dispositivo não encontrado'
            });
        }
    } catch (error) {
        console.error(`Erro ao verificar status do dispositivo ${deviceId}: ${error.message}`);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * Endpoint para iniciar um dispositivo.
 *  Aplica rate limiting.
 * @param {Request} req - Objeto de requisição.
 * @param {Response} res - Objeto de resposta.
 */
app.post('/init-device', async (req, res) => {
    const { deviceId } = req.body;
    if (!deviceId) {
        return res.status(400).json({
            success: false,
            message: 'deviceId é obrigatório'
        });
    }

    // Aplica rate limiting
    if (!applyRateLimit(deviceId)) {
        return res.status(429).json({ success: false, message: 'Muitas requisições. Tente novamente mais tarde.' });
    }

    try {
        // Destruir cliente existente se houver
        if (clients.has(deviceId)) {
            console.log(`Destruindo cliente existente para deviceId: ${deviceId}`);
            const existingClient = clients.get(deviceId);
            await existingClient.destroy();
            clients.delete(deviceId);
        }

        // Limpar sessão antiga
        await clearSession(deviceId);

        console.log(`Iniciando novo cliente para deviceId: ${deviceId}`);
        await createWhatsAppClient(deviceId);

        res.json({ success: true });
    } catch (error) {
        console.error(`Erro ao iniciar dispositivo ${deviceId}: ${error.message}`);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * Endpoint para obter o QR code de um dispositivo.
 *
 * @param {Request} req - Objeto de requisição.
 * @param {Response} res - Objeto de resposta.
 */
app.get('/get-qr/:deviceId', async (req, res) => {
    const deviceId = req.params.deviceId;

    try {
        const connection = await mysql.createConnection(dbConfig);
        const [rows] = await connection.execute(
            'SELECT qr_code, status FROM dispositivos WHERE device_id = ?',
            [deviceId]
        );
        await connection.end();

        if (rows.length > 0) {
            const device = rows[0];

            // Verificação mais precisa do status de conexão
            if (device.status === 'CONNECTED' && clients.has(deviceId)) {
                const client = clients.get(deviceId);
                if (client && client.info) {
                    console.log(`Dispositivo ${deviceId} conectado. Retornando status.`);
                    res.json({
                        success: true,
                        status: 'CONNECTED'
                    });
                    return;
                }
            }

            console.log(`Retornando QR code para dispositivo ${deviceId}. Status: ${device.status}`);
            res.json({
                success: true,
                qr: device.qr_code,
                status: device.status
            });
        } else {
            console.log(`Dispositivo ${deviceId} não encontrado para obter QR code.`);
            res.status(404).json({
                success: false,
                message: 'Dispositivo não encontrado'
            });
        }
    } catch (error) {
        console.error(`Erro ao obter QR code para dispositivo ${deviceId}: ${error.message}`);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// -------------------------------------
// Inicialização do Servidor
// -------------------------------------

const port = 3000;
app.listen(port, () => {
    console.log(`Servidor rodando na porta ${port}`);
});

// Tratamento de erros não capturados (unhandledRejection)
process.on('unhandledRejection', (error) => {
    console.error('Erro não tratado (unhandledRejection):', error);
});

// Tratamento de exceções não capturadas (uncaughtException)
process.on('uncaughtException', (error) => {
    console.error('Exceção não capturada (uncaughtException):', error);
});

// Limpeza na saída (SIGINT - Ctrl+C)
process.on('SIGINT', async () => {
    console.log('Encerrando servidor...');
    for (const [deviceId, client] of clients) {
        try {
            await client.destroy();
            console.log(`Cliente ${deviceId} destruído`);
        } catch (error) {
            console.error(`Erro ao destruir cliente ${deviceId}:`, error);
        }
    }
    process.exit(0); // Sai do processo com código 0 (sucesso)
});