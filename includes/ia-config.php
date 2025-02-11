<?php
class AssistenteIA {
    private $anthropic_key;
    private $usuario_dados;
    
    public function __construct($usuario_id) {
        global $pdo;
        
        // Carregar dados do usuário
        $stmt = $pdo->prepare("
            SELECT 
                u.*, 
                p.nome as plano_nome,
                p.tem_ia,
                (SELECT COUNT(*) FROM leads_enviados WHERE usuario_id = u.id) as total_leads,
                (SELECT COUNT(*) FROM envios_em_massa WHERE usuario_id = u.id) as total_envios
            FROM usuarios u
            JOIN planos p ON u.plano_id = p.id
            WHERE u.id = ?
        ");
        $stmt->execute([$usuario_id]);
        $this->usuario_dados = $stmt->fetch();
        
        $this->anthropic_key = 'sua_chave_api_aqui';
    }
    
    public function verificarPermissao() {
        return $this->usuario_dados['tem_ia'] === true;
    }
    
    public function gerarContexto() {
        return "Você é um assistente especializado em marketing digital para o ZapLocal.
                Dados do usuário:
                - Nome: {$this->usuario_dados['nome']}
                - Email: {$this->usuario_dados['email']}
                - Empresa: {$this->usuario_dados['empresa']}
                - Plano: {$this->usuario_dados['plano_nome']}
                - Total de Leads: {$this->usuario_dados['total_leads']}
                - Total de Envios: {$this->usuario_dados['total_envios']}
                
                Com base nesses dados, você deve:
                1. Fornecer sugestões personalizadas de marketing
                2. Ajudar a otimizar campanhas
                3. Sugerir melhorias baseadas nos resultados
                4. Oferecer dicas específicas para o segmento do usuário";
    }
}