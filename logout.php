<?php
// Incluir arquivo de inicialização
require_once 'config/init.php';

// Verificar se o usuário está logado
if (isset($_SESSION['user_id'])) {
    // Registrar logout
    logUserAccess($_SESSION['user_id'], $_SESSION['username'], $_SESSION['name'], 'LOGOUT');
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se houver um cookie de sessão, destruí-lo também
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header("Location: index.php?logout=".time());
exit;
