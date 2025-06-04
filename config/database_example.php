<?php
/**
 * ARQUIVO DE EXEMPLO - CONFIGURAÇÃO DO BANCO DE DADOS
 * 
 * 1. Copie este arquivo para database.php
 * 2. Configure suas credenciais reais
 * 3. Nunca commite o arquivo database.php no Git
 */

// Configurações do banco de dados
define('DB_HOST', 'seu_host_aqui');        // Ex: localhost
define('DB_USER', 'seu_usuario_aqui');     // Ex: root
define('DB_PASS', 'sua_senha_aqui');       // Ex: minhasenha123
define('DB_NAME', 'seu_banco_aqui');       // Ex: proeis_db

// Função para obter conexão com o banco de dados
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Verificar conexão
        if ($conn->connect_error) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
        
        // Definir charset para utf8mb4
        $conn->set_charset("utf8mb4");
        
        return $conn;
        
    } catch (Exception $e) {
        // Log do erro se a função existir
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('Erro de conexão com banco de dados', $e->getMessage());
        }
        
        // Em produção, não expor detalhes do erro
        throw new Exception("Erro de conexação com o banco de dados");
    }
}

// Função para testar conexão
function testConnection() {
    try {
        $conn = getConnection();
        $conn->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
