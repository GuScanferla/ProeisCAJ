<?php
/**
 * Configurações gerais do sistema
 * Arquivo seguro para configurações não sensíveis
 */

// Configurações do Sistema
define('SYSTEM_NAME', 'Sistema PROEIS');
define('SYSTEM_VERSION', '2.1.0');
define('SYSTEM_AUTHOR', 'Águas de Juturnaíba');

// Configurações de Sessão
define('SESSION_LIFETIME', 3600); // 1 hora
define('SESSION_NAME', 'PROEIS_SESSION');

// Configurações de Timezone
define('DEFAULT_TIMEZONE', 'America/Sao_Paulo');

// Configurações de Paginação
define('DEFAULT_ITEMS_PER_PAGE', 15);
define('MAX_ITEMS_PER_PAGE', 100);

// Configurações de Upload (se necessário no futuro)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Configurações de Log
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_FILES', 30); // Manter logs por 30 dias

// URLs e Caminhos
define('BASE_URL', '/');
define('ASSETS_URL', '/assets/');
define('UPLOADS_PATH', 'uploads/');

// Configurações de Email (para futuras implementações)
define('MAIL_FROM_NAME', 'Sistema PROEIS');
define('MAIL_FROM_EMAIL', 'noreply@aguasdejuturnaiba.com.br');

// Configurações de Segurança
define('ENABLE_CSRF_PROTECTION', true);
define('ENABLE_XSS_PROTECTION', true);
define('FORCE_HTTPS', false); // Definir como true em produção

// Configurações de Cache
define('ENABLE_CACHE', false);
define('CACHE_LIFETIME', 3600);

// Configurações de Debug
define('DEBUG_MODE', false); // NUNCA deixar true em produção
define('SHOW_ERRORS', false); // NUNCA deixar true em produção
