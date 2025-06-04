<?php
// Arquivo de inicialização simplificado do sistema
// Foco apenas no essencial

// Garantir fuso horário de Brasília em TODO o sistema
date_default_timezone_set('America/Sao_Paulo');

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurações básicas de segurança da sessão
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 3600); // 1 hora
    
    session_start();
}

// Incluir arquivos necessários
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

// Verificar timeout da sessão se o usuário estiver logado
if (isset($_SESSION['user_id'])) {
    $max_lifetime = 3600; // 1 hora
    
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > $max_lifetime) {
            // Registrar logout por timeout
            logUserAccess($_SESSION['user_id'], $_SESSION['username'], $_SESSION['name'], 'LOGOUT_TIMEOUT');
            
            session_unset();
            session_destroy();
            header("Location: index.php?timeout=1");
            exit;
        }
    }
    
    $_SESSION['last_activity'] = time();
}

// Definir constantes do sistema
define('SYSTEM_NAME', 'Sistema PROEIS');
define('SYSTEM_VERSION', '2.1.0');

// Headers básicos de segurança
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
