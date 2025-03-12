/**
 * Dependências:
 * - express: Framework web para Node.js.
 * - whatsapp-web.js: Biblioteca para interagir com o WhatsApp Web.
 * - mysql2: Cliente MySQL para Node.js.
 * - qrcode-terminal: Para exibir o QR Code no terminal.
 * - path: Módulo para manipulação de caminhos de arquivos.
 * - fs: Módulo para manipulação de arquivos.
 * - async-mutex: Para controle de concorrência.
 * - dotenv: (Recomendado) Para carregar variáveis de ambiente de um arquivo .env
 */

// Importando os módulos
const express = require("express");
const { Client, LocalAuth, MessageMedia } = require("whatsapp-web.js");
const qrcode = require("qrcode-terminal");
const mysql = require("mysql2/promise");
const path = require("path");
const fs = require("fs");
const { Mutex } = require("async-mutex"); // Importante para controle de concorrência
const { default: puppeteer } = require("puppeteer");

// Carrega variáveis de ambiente do arquivo .env (se existir)
require("dotenv").config(); //  Adicione 'dotenv'  e crie um arquivo .env na raiz do projeto

// -------------------------------------
// Configurações Iniciais
// -------------------------------------

const app = express();
app.use(express.json());

// Configuração do CORS (Cross-Origin Resource Sharing) - Mantido
app.use((req, res, next) => {
	res.header("Access-Control-Allow-Origin", "*");
	res.header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
	res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization"); // Inclui Authorization
	if (req.method === "OPTIONS") {
		return res.sendStatus(200);
	}
	next();
});

// Configuração do banco de dados (usando variáveis de ambiente) - Melhorado
const dbConfig = {
	host: process.env.DB_HOST || "127.0.0.1",
	user: process.env.DB_USER || "root",
	password: process.env.DB_PASSWORD || "MoonOfIvansia",
	database: process.env.DB_DATABASE || "balcao",
	waitForConnections: true, // Importante para lidar com pool de conexões
	connectionLimit: 10, // Aumenta o limite de conexões (ajuste conforme necessário)
	queueLimit: 0, // 0 = sem limite de fila (importante para produção)
	port: 3306,
};

// Cria um pool de conexões (melhor prática para produção)
const pool = mysql.createPool(dbConfig);

// Diretório para armazenar sessões do WhatsApp Web - Mantido
const SESSION_DIR = path.join(__dirname, ".wwebjs_auth");

// Armazenar clientes WhatsApp Web ativos (Map<deviceId, Client>) - Mantido
const clients = new Map();

// Configurações de processamento em lote e rate limiting - Aprimorado
const CHUNK_SIZE = 5; // Aumentei o tamanho do lote, mas *monitore* o uso de memória
const RATE_LIMIT_MS = 2000; //  2 segundos por cliente (ajustável)
const GLOBAL_RATE_LIMIT_MS = 500; // Limite global de 500ms entre mensagens (proteção extra)
let lastGlobalRequestTime = 0;

// Mutexes para controle de concorrência
const clientMutexes = new Map(); // Mutex por cliente (deviceId)
const globalMutex = new Mutex(); // Mutex global

// -------------------------------------
// Funções Auxiliares
// -------------------------------------

// Cria o diretório de sessão se ele não existir - Mantido
if (!fs.existsSync(SESSION_DIR)) {
	fs.mkdirSync(SESSION_DIR, { recursive: true });
	console.log(`Diretório de sessão criado: ${SESSION_DIR}`);
}

/**
 * Atualiza o status de um dispositivo no banco de dados.  Usa o pool de conexões.
 */
async function updateDeviceStatus(deviceId, status, qrCode = null) {
	let connection;
	try {
		connection = await pool.getConnection(); // Obtém conexão do pool
		const sql = qrCode
			? "UPDATE dispositivos SET status = ?, qr_code = ? WHERE device_id = ?"
			: "UPDATE dispositivos SET status = ?, qr_code = NULL WHERE device_id = ?";
		const values = qrCode ? [status, qrCode, deviceId] : [status, deviceId];
		await connection.execute(sql, values);
		console.log(`Status do dispositivo ${deviceId} atualizado para: ${status}`);
	} catch (error) {
		console.error(`Erro ao atualizar status do dispositivo ${deviceId}: ${error.message}`);
	} finally {
		if (connection) connection.release(); // Libera a conexão de volta para o pool
	}
}

// Sanitização do deviceId - Mantido
function sanitizeDeviceId(deviceId) {
	return deviceId.replace(/[^a-zA-Z0-9_-]/g, "_");
}

// Limpeza de sessão - Mantido
async function clearSession(deviceId) {
	const sanitizedDeviceId = sanitizeDeviceId(deviceId);
	const sessionDir = path.join(SESSION_DIR, `session-${sanitizedDeviceId}`);
	if (fs.existsSync(sessionDir)) {
		fs.rmSync(sessionDir, { recursive: true, force: true });
		console.log(`Sessão do dispositivo ${deviceId} removida.`);
	}
}

// Validação de userId - Mantido
function isValidUserId(userId) {
	return Number.isInteger(userId) && userId > 0;
}

// Validação de número de telefone - Aprimorada
function isValidPhoneNumber(number) {
	const cleanedNumber = number.replace(/\D/g, "");
	return /^(?:\+?\d{1,3})?\d{10,13}$/.test(cleanedNumber);
}

// Função para obter um cliente com tratamento de erros
function getClient(deviceId) {
	const client = clients.get(deviceId);
	if (!client) {
		throw new Error("Dispositivo não encontrado ou não conectado");
	}
	if (client.status !== "CONNECTED") {
		// Adicionei uma verificação de status
		throw new Error("Dispositivo não está pronto para enviar mensagens");
	}
	return client;
}

/**
 * Processa uma única mensagem da fila.  Agora com tratamento de erros mais robusto e logging.
 */
async function processMessage(message, connection, client) {
	let formattedNumber = message.numero.replace(/\D/g, "");
	if (!formattedNumber.startsWith("55")) {
		formattedNumber = "55" + formattedNumber;
	}
	formattedNumber = `${formattedNumber}@c.us`;

	const messageId = message.id; // Para logging
	console.log(`[${messageId}] Processando mensagem para: ${formattedNumber}`);

	try {
		// Verifica se o número está registrado *antes* de tentar enviar
		const isRegistered = await client.isRegisteredUser(formattedNumber);
		if (!isRegistered) {
			console.warn(`[${messageId}] Número não registrado: ${formattedNumber}`);
			throw new Error("Número não registrado no WhatsApp"); // Lança para o tratamento de erro
		}

		// Envio de mídia (com tratamento de erro específico)
		if (message.arquivo_path && fs.existsSync(message.arquivo_path)) {
			try {
				const media = MessageMedia.fromFilePath(message.arquivo_path);
				await client.sendMessage(formattedNumber, media);
				console.log(`[${messageId}] Mídia enviada para ${formattedNumber}`);
				await new Promise((resolve) => setTimeout(resolve, 2000)); // Pausa após mídia
			} catch (mediaError) {
				console.error(`[${messageId}] Erro ao enviar mídia:`, mediaError);
				// Não lança o erro aqui, continua para a mensagem de texto, se houver
			}
		}

		// Envio de mensagem de texto (com tratamento de erro)
		if (message.mensagem && message.mensagem.trim()) {
			try {
				await client.sendMessage(formattedNumber, message.mensagem);
				console.log(`[${messageId}] Mensagem de texto enviada para ${formattedNumber}`);
			} catch (textError) {
				console.error(`[${messageId}] Erro ao enviar mensagem de texto:`, textError);
				throw textError; // Lança para o tratamento de erro principal
			}
		}

		// Atualiza o status no banco de dados (com tratamento de erro)
		await connection.execute('UPDATE fila_mensagens SET status = "ENVIADO", updated_at = NOW() WHERE id = ?', [
			messageId,
		]);
		console.log(`[${messageId}] Mensagem marcada como ENVIADA.`);
	} catch (error) {
		console.error(`[${messageId}] Erro ao processar mensagem:`, error);
		const errorMessage = error.message ? error.message.substring(0, 255) : "Erro desconhecido";
		await connection.execute(
			'UPDATE fila_mensagens SET status = "ERRO", error_message = ?, updated_at = NOW() WHERE id = ?',
			[errorMessage, messageId],
		);
	}
}

// -------------------------------------
// Gerenciamento de Clientes WhatsApp
// -------------------------------------

/**
 * Cria um novo cliente WhatsApp Web.  Agora com tratamento de erros e mutex.
 */
async function createWhatsAppClient(deviceId) {
	const sanitizedDeviceId = sanitizeDeviceId(deviceId);
	console.log(`Iniciando criação do cliente WhatsApp para deviceId: ${sanitizedDeviceId}`);

	// Adiciona um mutex para este deviceId
	if (!clientMutexes.has(sanitizedDeviceId)) {
		clientMutexes.set(sanitizedDeviceId, new Mutex());
	}

	const release = await clientMutexes.get(sanitizedDeviceId).acquire(); // Adquire o mutex

	try {
		await clearSession(sanitizedDeviceId);

		const client = new Client({
			authStrategy: new LocalAuth({
				clientId: sanitizedDeviceId,
				dataPath: SESSION_DIR,
			}),
			puppeteer: {
				args: [
					"--no-sandbox",
					"--disable-setuid-sandbox",
					"--disable-dev-shm-usage",
					"--disable-accelerated-2d-canvas",
					"--no-first-run",
					"--no-zygote",
					"--disable-gpu",
				],
				headless: "new", // Use o novo modo headless
				timeout: 60000,
				executablePath: "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe",
			},
		});

		// Eventos do cliente (com tratamento de erros)
		client.on("qr", async (qr) => {
			console.log(`Novo QR Code gerado para deviceId: ${sanitizedDeviceId}`);
			console.log("QR Code: ", qr);

			await updateDeviceStatus(sanitizedDeviceId, "WAITING_QR", qr);
		});

		client.on("ready", async () => {
			console.log(`Cliente WhatsApp pronto para deviceId: ${sanitizedDeviceId}`);
			client.status = "CONNECTED"; // Define o status do cliente
			await updateDeviceStatus(sanitizedDeviceId, "CONNECTED");
		});

		client.on("auth_failure", async () => {
			console.log(`Falha na autenticação para deviceId: ${sanitizedDeviceId}`);
			client.status = "AUTH_FAILURE";
			await updateDeviceStatus(sanitizedDeviceId, "AUTH_FAILURE");
			await clearSession(sanitizedDeviceId);
			clients.delete(sanitizedDeviceId);
		});

		client.on("disconnected", async () => {
			console.log(`Cliente desconectado para deviceId: ${sanitizedDeviceId}`);
			client.status = "DISCONNECTED";
			await updateDeviceStatus(sanitizedDeviceId, "DISCONNECTED");
			await clearSession(sanitizedDeviceId);
			clients.delete(sanitizedDeviceId);
		});

		// Inicializa o cliente (com tratamento de erro)
		try {
			await client.initialize();
			console.log("linha seguinte");
			clients.set(sanitizedDeviceId, client);
			console.log(`Cliente WhatsApp inicializado para deviceId: ${sanitizedDeviceId}`);
		} catch (initError) {
			console.error(`Erro ao inicializar cliente ${sanitizedDeviceId}:`, initError);
			client.status = "ERROR";
			await updateDeviceStatus(sanitizedDeviceId, "ERROR");
			throw initError; // Propaga o erro
		}

		return client;
	} finally {
		release(); // Libera o mutex
	}
}

// -------------------------------------
// Endpoints da API
// -------------------------------------

/**
 * Endpoint para verificar a saúde do servidor.  Não requer autenticação.
 */
app.get("/status", (req, res) => {
	res.json({ status: "OK", uptime: process.uptime() });
});

/**
 * Endpoint para processar a fila de mensagens de um usuário.  Agora com mutex e tratamento de erros.
 */

app.post("/process-message", async (req, res) => {
	const now = Date.now();

	if (now - lastGlobalRequestTime < GLOBAL_RATE_LIMIT_MS) {
		const waitTime = GLOBAL_RATE_LIMIT_MS - (now - lastGlobalRequestTime);
		console.warn(`Aplicando rate limit global.  Aguardando ${waitTime}ms`);
		await new Promise((resolve) => setTimeout(resolve, waitTime));
	}

	lastGlobalRequestTime = Date.now();

	const message = req.body.message;

	let formattedNumber = message.numero.replace(/\D/g, "");
	if (!formattedNumber.startsWith("55")) {
		formattedNumber = "55" + formattedNumber;
	}
	formattedNumber = `${formattedNumber}@c.us`;

	const messageId = message.id; // Para logging
	console.log(`[${messageId}] Processando mensagem para: ${formattedNumber}`);

	const client = getClient(dispositivo_id);

	try {
		// Verifica se o número está registrado *antes* de tentar enviar
		const isRegistered = await client.isRegisteredUser(formattedNumber);
		if (!isRegistered) {
			console.warn(`[${messageId}] Número não registrado: ${formattedNumber}`);
			throw new Error("Número não registrado no WhatsApp"); // Lança para o tratamento de erro
		}

		// Envio de mídia (com tratamento de erro específico)
		/**
		 * TODO ajustar caminho de arquivo
		 */
		if (message.arquivo_path && fs.existsSync(message.arquivo_path)) {
			try {
				const media = MessageMedia.fromFilePath(message.arquivo_path);
				await client.sendMessage(formattedNumber, media);
				console.log(`[${messageId}] Mídia enviada para ${formattedNumber}`);
				await new Promise((resolve) => setTimeout(resolve, 2000)); // Pausa após mídia
			} catch (mediaError) {
				console.error(`[${messageId}] Erro ao enviar mídia:`, mediaError);
				// Não lança o erro aqui, continua para a mensagem de texto, se houver
			}
		}

		// Envio de mensagem de texto (com tratamento de erro)
		if (message.mensagem && message.mensagem.trim()) {
			try {
				await client.sendMessage(formattedNumber, message.mensagem);
				console.log(`[${messageId}] Mensagem de texto enviada para ${formattedNumber}`);
			} catch (textError) {
				console.error(`[${messageId}] Erro ao enviar mensagem de texto:`, textError);
				throw textError; // Lança para o tratamento de erro principal
			}
		}

		res.json({ success: true });
	} catch (error) {
		console.error(`[${messageId}] Erro ao processar mensagem:`, error);
		const errorMessage = error.message ? error.message.substring(0, 255) : "Erro desconhecido";

		res.status(500).json({ success: false, error: errorMessage });
	}
});

app.post("/process-queue", async (req, res) => {
	const { usuario_id, dispositivo_id } = req.body;

	if (!isValidUserId(usuario_id)) {
		console.error(`Tentativa de acesso com usuario_id inválido: ${usuario_id}`);
		return res.status(400).json({ success: false, message: "usuario_id inválido." });
	}

	const sanitizedDeviceId = sanitizeDeviceId(dispositivo_id);

	// Adquire o mutex global
	const globalRelease = await globalMutex.acquire();

	// Adquire o mutex do cliente
	if (!clientMutexes.has(sanitizedDeviceId)) {
		clientMutexes.set(sanitizedDeviceId, new Mutex());
	}
	const clientRelease = await clientMutexes.get(sanitizedDeviceId).acquire();

	try {
		// Aplica rate limiting global
		const now = Date.now();
		if (now - lastGlobalRequestTime < GLOBAL_RATE_LIMIT_MS) {
			const waitTime = GLOBAL_RATE_LIMIT_MS - (now - lastGlobalRequestTime);
			console.warn(`Aplicando rate limit global.  Aguardando ${waitTime}ms`);
			await new Promise((resolve) => setTimeout(resolve, waitTime));
		}
		lastGlobalRequestTime = Date.now();

		// Verifica se o cliente existe e está conectado
		try {
			getClient(sanitizedDeviceId); // Usa a função getClient
		} catch (clientError) {
			return res.status(400).json({ success: false, message: clientError.message });
		}

		console.log(`Requisição para processar fila. usuario_id: ${usuario_id}, dispositivo_id: ${sanitizedDeviceId}`);
		const result = await processMessageQueue(usuario_id, sanitizedDeviceId);

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
	} finally {
		clientRelease(); // Libera o mutex do cliente
		globalRelease(); // Libera o mutex global
	}
});

/**
 * Processa a fila de mensagens para um determinado usuário.  Agora com tratamento de erros e usando o pool.
 */
async function processMessageQueue(usuario_id, dispositivo_id) {
	console.log(`Iniciando processamento da fila para usuário: ${usuario_id}, dispositivo: ${dispositivo_id}`);
	let connection;

	try {
		connection = await pool.getConnection(); // Obtém conexão do pool
		const client = getClient(dispositivo_id); // Obtém o cliente

		while (true) {
			const [messages] = await connection.execute(
				'SELECT * FROM fila_mensagens WHERE usuario_id = ? AND dispositivo_id = ? AND status = "PENDENTE" ORDER BY created_at ASC LIMIT ?',
				[usuario_id, dispositivo_id, CHUNK_SIZE],
			);

			if (messages.length === 0) {
				console.log(`Fila vazia para usuário: ${usuario_id}, dispositivo: ${dispositivo_id}`);
				break;
			}

			console.log(
				`Processando ${messages.length} mensagens para usuário: ${usuario_id}, dispositivo: ${dispositivo_id}`,
			);

			for (const message of messages) {
				await processMessage(message, connection, client);
			}
		}

		console.log(`Processamento da fila concluído para usuário: ${usuario_id}, dispositivo: ${dispositivo_id}`);
		return { success: true };
	} catch (error) {
		console.error(`Erro ao processar fila para usuário ${usuario_id}, dispositivo ${dispositivo_id}:`, error);
		return { success: false, error: error.message };
	} finally {
		if (connection) connection.release(); // Libera a conexão de volta para o pool
	}
}

/**
 * Endpoint para obter o progresso da fila de um usuário. Usa o pool de conexões.
 */
app.get("/queue-progress/:usuario_id", async (req, res) => {
	const usuario_id = parseInt(req.params.usuario_id);

	if (!isValidUserId(usuario_id)) {
		console.error(`Tentativa de acesso com usuario_id inválido: ${usuario_id}`);
		return res.status(400).json({ success: false, message: "usuario_id inválido." });
	}
	let connection;
	try {
		connection = await pool.getConnection();
		const [result] = await connection.execute(
			`
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'ENVIADO' THEN 1 ELSE 0 END) as enviados,
                SUM(CASE WHEN status = 'ERRO' THEN 1 ELSE 0 END) as erros,
                SUM(CASE WHEN status = 'PENDENTE' THEN 1 ELSE 0 END) as pendentes
            FROM fila_mensagens
            WHERE usuario_id = ?
        `,
			[usuario_id],
		);

		console.log(`Progresso da fila para usuario_id ${usuario_id} obtido com sucesso.`);
		res.json(result[0]);
	} catch (error) {
		console.error(`Erro ao obter progresso da fila para usuario_id ${usuario_id}: ${error.message}`);
		res.status(500).json({ error: error.message });
	} finally {
		if (connection) connection.release();
	}
});

/**
 * Endpoint para obter o status da fila de mensagens de um usuário. Usa o pool.
 */
app.get("/queue-status/:usuario_id", async (req, res) => {
	const usuario_id = parseInt(req.params.usuario_id);

	if (!isValidUserId(usuario_id)) {
		return res.status(400).json({ success: false, message: "usuario_id inválido." });
	}

	let connection;
	try {
		connection = await pool.getConnection();
		const [rows] = await connection.execute(
			`SELECT
                COUNT(CASE WHEN status = 'PENDENTE' THEN 1 END) as pendentes,
                COUNT(CASE WHEN status = 'ENVIADO' THEN 1 END) as enviados,
                COUNT(CASE WHEN status = 'ERRO' THEN 1 END) as erros
            FROM fila_mensagens
            WHERE usuario_id = ?`,
			[usuario_id],
		);

		console.log(`Status da fila para usuario_id ${usuario_id} obtido com sucesso.`);
		res.json({ success: true, status: rows[0] });
	} catch (error) {
		console.error(`Erro ao obter status da fila para usuario_id ${usuario_id}: ${error.message}`);
		res.status(500).json({ success: false, message: error.message });
	} finally {
		if (connection) connection.release();
	}
});

/**
 * Endpoint para enviar uma mensagem.  Agora com tratamento de erros, mutex e validações.
 */
app.post("/send-message", async (req, res) => {
	const { deviceId, number, message, mediaPath } = req.body;
	const sanitizedDeviceId = sanitizeDeviceId(deviceId);

	// Adquire o mutex global
	const globalRelease = await globalMutex.acquire();

	// Adquire o mutex do cliente
	if (!clientMutexes.has(sanitizedDeviceId)) {
		clientMutexes.set(sanitizedDeviceId, new Mutex());
	}
	const clientRelease = await clientMutexes.get(sanitizedDeviceId).acquire();

	try {
		// Aplica rate limiting global
		const now = Date.now();
		if (now - lastGlobalRequestTime < GLOBAL_RATE_LIMIT_MS) {
			const waitTime = GLOBAL_RATE_LIMIT_MS - (now - lastGlobalRequestTime);
			console.warn(`Aplicando rate limit global.  Aguardando ${waitTime}ms`);
			await new Promise((resolve) => setTimeout(resolve, waitTime));
		}
		lastGlobalRequestTime = Date.now();

		// Validações
		if (!isValidPhoneNumber(number)) {
			console.error(`Tentativa de envio para número inválido: ${number}`);
			return res.status(400).json({ success: false, message: "Número de telefone inválido." });
		}

		// Verifica se o cliente existe e está conectado
		const client = getClient(sanitizedDeviceId);

		let formattedNumber = number;
		if (!formattedNumber.includes("@c.us")) {
			formattedNumber = `${formattedNumber}@c.us`;
		}
		console.log(`Enviando mensagem para: ${formattedNumber}`);

		// Envio de mídia (com tratamento de erro)
		if (mediaPath) {
			try {
				if (!fs.existsSync(mediaPath)) {
					throw new Error("Arquivo não encontrado: " + mediaPath);
				}
				const media = MessageMedia.fromFilePath(mediaPath);
				await client.sendMessage(formattedNumber, media);
				console.log("Mídia enviada com sucesso");
				await new Promise((resolve) => setTimeout(resolve, 2000)); // Pausa
			} catch (mediaError) {
				console.error("Erro ao enviar mídia:", mediaError);
				// Não retorna, tenta enviar a mensagem de texto
			}
		}

		// Envio de mensagem de texto (com tratamento de erro)
		if (message && message.trim()) {
			try {
				await client.sendMessage(formattedNumber, message);
				console.log("Mensagem de texto enviada com sucesso");
			} catch (messageError) {
				console.error("Erro ao enviar mensagem de texto:", messageError);
				return res
					.status(500)
					.json({ success: false, message: "Erro ao enviar mensagem de texto: " + messageError.message });
			}
		}

		console.log(`Mensagem enviada com sucesso para ${formattedNumber}`);
		res.json({ success: true });
	} catch (error) {
		console.error(`Erro ao enviar mensagem para ${number}: ${error.message}`);
		res.status(500).json({ success: false, message: error.message });
	} finally {
		clientRelease(); // Libera o mutex do cliente
		globalRelease(); // Libera o mutex global
	}
});

/**
 * Endpoint para verificar o status de um dispositivo. Usa o pool.
 */
app.get("/check-status/:deviceId", async (req, res) => {
	const { deviceId } = req.params;
	const sanitizedDeviceId = sanitizeDeviceId(deviceId);
	let connection;
	try {
		connection = await pool.getConnection();
		const [rows] = await connection.execute("SELECT status FROM dispositivos WHERE device_id = ?", [
			sanitizedDeviceId,
		]);

		if (rows.length > 0) {
			console.log(`Status do dispositivo ${sanitizedDeviceId} consultado: ${rows[0].status}`);
			res.json({ success: true, status: rows[0].status });
		} else {
			console.log(`Dispositivo ${sanitizedDeviceId} não encontrado.`);
			res.status(404).json({ success: false, message: "Dispositivo não encontrado" });
		}
	} catch (error) {
		console.error(`Erro ao verificar status do dispositivo ${sanitizedDeviceId}: ${error.message}`);
		res.status(500).json({ success: false, message: error.message });
	} finally {
		if (connection) connection.release();
	}
});

/**
 * Endpoint para iniciar um dispositivo.  Agora com tratamento de erros e mutex.
 */
app.post("/init-device", async (req, res) => {
	const { deviceId } = req.body;
	if (!deviceId) {
		return res.status(400).json({ success: false, message: "deviceId é obrigatório" });
	}

	const sanitizedDeviceId = sanitizeDeviceId(deviceId);

	// Adquire o mutex global
	const globalRelease = await globalMutex.acquire();

	try {
		// Destruir cliente existente, se houver (com mutex)
		if (clients.has(sanitizedDeviceId)) {
			console.log(`Destruindo cliente existente para deviceId: ${sanitizedDeviceId}`);
			if (!clientMutexes.has(sanitizedDeviceId)) {
				clientMutexes.set(sanitizedDeviceId, new Mutex());
			}
			const clientRelease = await clientMutexes.get(sanitizedDeviceId).acquire();
			try {
				const existingClient = clients.get(sanitizedDeviceId);
				await existingClient.destroy();
				clients.delete(sanitizedDeviceId);
			} finally {
				clientRelease();
			}
		}

		// Limpar sessão antiga
		await clearSession(sanitizedDeviceId);

		console.log(`Iniciando novo cliente para deviceId: ${sanitizedDeviceId}`);
		await createWhatsAppClient(sanitizedDeviceId); // A criação já lida com o mutex

		res.json({ success: true });
	} catch (error) {
		console.error(`Erro ao iniciar dispositivo ${sanitizedDeviceId}: ${error.message}`);
		res.status(500).json({ success: false, message: error.message });
	} finally {
		globalRelease(); // Libera o mutex global
	}
});

/**
 * Endpoint para obter o QR code de um dispositivo. Usa o pool.
 */
app.get("/get-qr/:deviceId", async (req, res) => {
	const deviceId = req.params.deviceId;
	const sanitizedDeviceId = sanitizeDeviceId(deviceId);
	let connection;

	try {
		connection = await pool.getConnection();
		const [rows] = await connection.execute("SELECT qr_code, status FROM dispositivos WHERE device_id = ?", [
			sanitizedDeviceId,
		]);

		if (rows.length > 0) {
			const device = rows[0];

			// Verifica se está conectado e se o cliente existe
			if (device.status === "CONNECTED" && clients.has(sanitizedDeviceId)) {
				const client = clients.get(sanitizedDeviceId);
				if (client && client.info) {
					console.log(`Dispositivo ${sanitizedDeviceId} conectado. Retornando status.`);
					return res.json({ success: true, status: "CONNECTED" });
				}
			}

			console.log(`Retornando QR code para dispositivo ${sanitizedDeviceId}. Status: ${device.status}`);
			res.json({ success: true, qr: device.qr_code, status: device.status });
		} else {
			console.log(`Dispositivo ${sanitizedDeviceId} não encontrado para obter QR code.`);
			res.status(404).json({ success: false, message: "Dispositivo não encontrado" });
		}
	} catch (error) {
		console.error(`Erro ao obter QR code para dispositivo ${sanitizedDeviceId}: ${error.message}`);
		res.status(500).json({ success: false, message: error.message });
	} finally {
		if (connection) connection.release();
	}
});

// -------------------------------------
// Inicialização do Servidor
// -------------------------------------

const port = process.env.PORT || 3000; // Usa a porta definida no ambiente ou 3000
app.listen(port, () => {
	console.log(`Servidor rodando na porta ${port}`);
});

// Tratamento de erros aprimorado
process.on("unhandledRejection", (reason, promise) => {
	console.error("Unhandled Rejection at:", promise, "reason:", reason);
});

process.on("uncaughtException", (error) => {
	console.error("Uncaught Exception:", error);
	process.exit(1); // Sai do processo em caso de exceção não tratada (recomendado)
});

// Limpeza na saída (SIGINT - Ctrl+C) - Aprimorado
process.on("SIGINT", async () => {
	console.log("Encerrando servidor...");

	// Destroi todos os clientes (usando os mutexes)
	for (const [deviceId, client] of clients) {
		if (clientMutexes.has(deviceId)) {
			const release = await clientMutexes.get(deviceId).acquire();
			try {
				await client.destroy();
				console.log(`Cliente ${deviceId} destruído`);
			} catch (error) {
				console.error(`Erro ao destruir cliente ${deviceId}:`, error);
			} finally {
				release();
			}
		}
	}

	// Fecha o pool de conexões
	try {
		await pool.end();
		console.log("Pool de conexões fechado.");
	} catch (error) {
		console.error("Erro ao fechar pool de conexões:", error);
	}

	process.exit(0);
});

// Adicionei um endpoint simples para verificar se o servidor está online
app.get("/", (req, res) => {
	res.send("Servidor WhatsApp online!");
});
