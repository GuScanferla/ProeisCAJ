<?php
// Configurações do banco de dados
// Use variáveis de ambiente em produção para maior segurança

// Verificar se estamos em ambiente de desenvolvimento ou produção
$is_development = (isset($_SERVER['HTTP_HOST']) && 
                  (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                   strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));

if ($is_development) {
    // Configurações para desenvolvimento local
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'proeis_local');
} else {
    // Configurações para produção - CONFIGURE SUAS CREDENCIAIS AQUI
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_USER', $_ENV['DB_USER'] ?? 'seu_usuario_db');
    define('DB_PASS', $_ENV['DB_PASS'] ?? 'sua_senha_db');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'seu_banco_db');
}

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
        throw new Exception("Erro de conexão com o banco de dados");
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
