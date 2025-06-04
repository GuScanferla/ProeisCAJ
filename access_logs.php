<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Definir fuso horário para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$conn = getConnection();

// Debug mode - remover após testar
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($debug) {
    echo "<h3>DEBUG MODE</h3>";
    debugAccessLogs();
    echo "<hr>";
}

// Filtros
$user_filter = $_GET['user_filter'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';
$action_filter = $_GET['action_filter'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];
$types = '';

if (!empty($user_filter)) {
    $where_conditions[] = "(username LIKE ? OR user_name LIKE ?)";
    $params[] = "%$user_filter%";
    $params[] = "%$user_filter%";
    $types .= 'ss';
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(access_time) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if (!empty($action_filter)) {
    $where_conditions[] = "action = ?";
    $params[] = $action_filter;
    $types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Verificar se a tabela existe antes de fazer consultas
$table_exists = false;
try {
    $result = $conn->query("SHOW TABLES LIKE 'user_access_logs'");
    $table_exists = $result->num_rows > 0;
} catch (Exception $e) {
    $table_exists = false;
}

$total_records = 0;
$total_pages = 0;
$logs = [];
$stats = ['total_today' => 0, 'logins_today' => 0, 'unique_users_today' => 0, 'last_access' => null];

if ($table_exists) {
    // Contar total de registros
    $count_sql = "SELECT COUNT(*) as total FROM user_access_logs $where_clause";
    $stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $per_page);
    $stmt->close();

    // Obter logs com paginação
    $sql = "SELECT * FROM user_access_logs 
            $where_clause
            ORDER BY access_time DESC 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();

    // Obter estatísticas
    $stats = getAccessStats($conn);
}

// Gerar Excel se solicitado
if (isset($_GET['export']) && $_GET['export'] === 'excel' && $table_exists) {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="logs_acesso_proeis_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // BOM para UTF-8
    echo "<table border='1'>";
    echo "<tr style='background-color: #3498db; color: white; font-weight: bold;'>";
    echo "<th>Data/Hora</th><th>Usuário</th><th>Nome Completo</th><th>Ação</th><th>IP Address</th>";
    echo "</tr>";
    
    // Buscar todos os registros para exportação
    $export_sql = "SELECT * FROM user_access_logs $where_clause ORDER BY access_time DESC";
    $stmt = $conn->prepare($export_sql);
    if (!empty($where_conditions)) {
        // Remover os últimos 2 parâmetros (LIMIT e OFFSET) para exportação
        $export_params = array_slice($params, 0, -2);
        $export_types = substr($types, 0, -2);
        if (!empty($export_params)) {
            $stmt->bind_param($export_types, ...$export_params);
        }
    }
    $stmt->execute();
    $export_result = $stmt->get_result();
    
    while ($row = $export_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('d/m/Y H:i:s', strtotime($row['access_time'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_name'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['action']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    $stmt->close();
    $conn->close();
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema PROEIS - Logs de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.primary { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-card.success { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .stat-card.info { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .stat-card.warning { background: linear-gradient(135deg, #f39c12, #e67e22); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .action-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .action-login { background: #d4edda; color: #155724; }
        .action-logout { background: #d1ecf1; color: #0c5460; }
        .action-timeout { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="header-nav d-flex justify-content-between align-items-center">
            <a href="dashboard.php" class="nav-brand">
                <i class="fas fa-arrow-left me-2"></i>Sistema PROEIS
            </a>
            <div>
                <span class="text-dark">
                    <i class="fas fa-users me-2"></i>Logs de Acesso dos Usuários
                </span>
            </div>
        </div>

        <?php if (!$table_exists): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Tabela de logs não encontrada.</strong> 
                Faça login/logout algumas vezes para criar os primeiros registros.
                <a href="?debug=1" class="btn btn-sm btn-outline-primary ms-2">Debug</a>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <div class="stat-number"><?php echo $stats['total_today']; ?></div>
                <div>Acessos Hoje</div>
            </div>
            <div class="stat-card success">
                <i class="fas fa-sign-in-alt fa-2x mb-2"></i>
                <div class="stat-number"><?php echo $stats['logins_today']; ?></div>
                <div>Logins Hoje</div>
            </div>
            <div class="stat-card info">
                <i class="fas fa-users fa-2x mb-2"></i>
                <div class="stat-number"><?php echo $stats['unique_users_today']; ?></div>
                <div>Usuários Únicos</div>
            </div>
            <div class="stat-card warning">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <div class="stat-number">
                    <?php echo $stats['last_access'] ? date('H:i', strtotime($stats['last_access'])) : '--:--'; ?>
                </div>
                <div>Último Acesso</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filtros de Pesquisa
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="user_filter" class="form-label">Usuário</label>
                            <input type="text" class="form-control" id="user_filter" name="user_filter" 
                                   value="<?php echo htmlspecialchars($user_filter); ?>" placeholder="Nome ou usuário">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="date_filter" class="form-label">Data</label>
                            <input type="date" class="form-control" id="date_filter" name="date_filter" 
                                   value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="action_filter" class="form-label">Ação</label>
                            <select class="form-select" id="action_filter" name="action_filter">
                                <option value="">Todas as Ações</option>
                                <option value="LOGIN" <?php echo $action_filter === 'LOGIN' ? 'selected' : ''; ?>>Login</option>
                                <option value="LOGOUT" <?php echo $action_filter === 'LOGOUT' ? 'selected' : ''; ?>>Logout</option>
                                <option value="LOGOUT_TIMEOUT" <?php echo $action_filter === 'LOGOUT_TIMEOUT' ? 'selected' : ''; ?>>Logout por Timeout</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filtrar
                                </button>
                                <a href="access_logs.php" class="btn btn-secondary">
                                    <i class="fas fa-undo me-2"></i>Limpar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-success" onclick="exportLogs()">
                            <i class="fas fa-file-excel me-2"></i>Exportar Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela de Logs -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Logs de Acesso
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_records; ?> registros</span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Nome Completo</th>
                            <th>Ação</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">
                                        <?php if (!$table_exists): ?>
                                            Nenhum log encontrado. Faça login/logout para gerar os primeiros registros.
                                        <?php else: ?>
                                            Nenhum log encontrado com os filtros aplicados.
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d/m/Y H:i:s', strtotime($log['access_time'])); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($log['user_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $action_class = 'action-login';
                                        $action_text = $log['action'];
                                        
                                        if ($log['action'] === 'LOGOUT') {
                                            $action_class = 'action-logout';
                                            $action_text = 'Logout';
                                        } elseif ($log['action'] === 'LOGOUT_TIMEOUT') {
                                            $action_class = 'action-timeout';
                                            $action_text = 'Logout (Timeout)';
                                        } elseif ($log['action'] === 'LOGIN') {
                                            $action_text = 'Login';
                                        }
                                        ?>
                                        <span class="action-badge <?php echo $action_class; ?>">
                                            <?php echo $action_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                Mostrando <?php echo ($offset + 1); ?> a <?php echo min($offset + $per_page, $total_records); ?> 
                de <?php echo $total_records; ?> registros
            </div>
            <nav>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = 'access_logs.php?' + params.toString();
        }
    </script>
</body>
</html>
