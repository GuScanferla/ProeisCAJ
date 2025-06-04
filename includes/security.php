<?php
/**
 * Sistema de logs simplificado - apenas acessos de usuários
 * Foco em: login, logout e horário de Brasília
 */

// Garantir fuso horário de Brasília em todo o sistema
date_default_timezone_set('America/Sao_Paulo');

/**
 * Cria a tabela de logs de acesso simplificada
 */
function createAccessLogsTable() {
    try {
        $conn = getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS user_access_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            username VARCHAR(50) NOT NULL,
            user_name VARCHAR(100),
            action VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            access_time DATETIME NOT NULL,
            INDEX idx_username (username),
            INDEX idx_access_time (access_time),
            INDEX idx_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        $conn->close();
    } catch (Exception $e) {
        error_log("Erro ao criar tabela de logs: " . $e->getMessage());
    }
}

/**
 * Registra acesso do usuário (login/logout)
 */
function logUserAccess($user_id, $username, $user_name, $action) {
    try {
        createAccessLogsTable();
        $conn = getConnection();
        
        $ip_address = getRealIpAddress();
        $access_time = date('Y-m-d H:i:s'); // Horário de Brasília
        
        $stmt = $conn->prepare("INSERT INTO user_access_logs (user_id, username, user_name, action, ip_address, access_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $username, $user_name, $action, $ip_address, $access_time);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Erro ao registrar log de acesso: " . $e->getMessage());
    }
}

/**
 * Obtém o IP real do usuário
 */
function getRealIpAddress() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Obtém estatísticas simples de acesso - CORRIGIDO
 */
function getAccessStats($conn) {
    try {
        $stats = [
            'total_today' => 0,
            'logins_today' => 0,
            'unique_users_today' => 0,
            'last_access' => null
        ];
        
        // Verificar se a tabela existe
        $result = $conn->query("SHOW TABLES LIKE 'user_access_logs'");
        if ($result->num_rows == 0) {
            return $stats;
        }
        
        // Data atual no formato brasileiro
        $today = date('Y-m-d');
        
        // Total de acessos hoje
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_access_logs WHERE DATE(access_time) = ?");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stats['total_today'] = (int)$result->fetch_assoc()['count'];
        }
        $stmt->close();
        
        // Logins hoje
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_access_logs WHERE DATE(access_time) = ? AND action = 'LOGIN'");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stats['logins_today'] = (int)$result->fetch_assoc()['count'];
        }
        $stmt->close();
        
        // Usuários únicos hoje
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT username) as count FROM user_access_logs WHERE DATE(access_time) = ?");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stats['unique_users_today'] = (int)$result->fetch_assoc()['count'];
        }
        $stmt->close();
        
        // Último acesso
        $stmt = $conn->prepare("SELECT access_time FROM user_access_logs ORDER BY access_time DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stats['last_access'] = $result->fetch_assoc()['access_time'];
        }
        $stmt->close();
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Erro ao obter estatísticas: " . $e->getMessage());
        return [
            'total_today' => 0,
            'logins_today' => 0,
            'unique_users_today' => 0,
            'last_access' => null
        ];
    }
}

/**
 * Função para debug - verificar se os logs estão sendo salvos
 */
function debugAccessLogs() {
    try {
        $conn = getConnection();
        
        // Verificar se a tabela existe
        $result = $conn->query("SHOW TABLES LIKE 'user_access_logs'");
        echo "Tabela existe: " . ($result->num_rows > 0 ? "SIM" : "NÃO") . "<br>";
        
        if ($result->num_rows > 0) {
            // Contar total de registros
            $result = $conn->query("SELECT COUNT(*) as total FROM user_access_logs");
            $total = $result->fetch_assoc()['total'];
            echo "Total de logs: " . $total . "<br>";
            
            // Mostrar últimos 5 registros
            $result = $conn->query("SELECT * FROM user_access_logs ORDER BY access_time DESC LIMIT 5");
            echo "Últimos logs:<br>";
            while ($row = $result->fetch_assoc()) {
                echo "- " . $row['username'] . " | " . $row['action'] . " | " . $row['access_time'] . "<br>";
            }
        }
        
        $conn->close();
    } catch (Exception $e) {
        echo "Erro no debug: " . $e->getMessage();
    }
}

// Inicializar tabela na primeira execução
createAccessLogsTable();
