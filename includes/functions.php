<?php
// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Função para verificar se o usuário é administrador
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Função para formatar data
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

// Função para sanitizar entrada
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Função para gerar token aleatório
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Função para redirecionar
function redirect($url) {
    header("Location: $url");
    exit;
}
