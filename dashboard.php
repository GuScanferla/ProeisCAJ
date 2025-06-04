<?php
// Incluir arquivo de inicialização
require_once 'config/init.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? $_SESSION['username'];

// Paginação para ordens recentes
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15; // Limitar a 15 registros por página
$offset = ($page - 1) * $per_page;

// Obter estatísticas
$total_orders = 0;
$total_irregularities = 0;
$total_lna = 0;
$recent_orders = [];
$total_pages = 0;

try {
    if ($role === 'admin') {
        // Administrador vê todas as ordens
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM service_orders");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_orders = $row['total'] ?? 0;
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM service_orders WHERE tem_irregularidade = 'sim'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_irregularities = $row['total'] ?? 0;
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM service_orders WHERE tem_lna = 'sim'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_lna = $row['total'] ?? 0;
        $stmt->close();
        
        // Calcular total de páginas
        $total_pages = ceil($total_orders / $per_page);
        
        // Obter as ordens de serviço com paginação
        $stmt = $conn->prepare("SELECT so.*, u.name as tecnico_nome 
                               FROM service_orders so 
                               JOIN users u ON so.tecnico_id = u.id 
                               ORDER BY so.data DESC 
                               LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $per_page, $offset);
    } else {
        // Técnico vê apenas suas ordens
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM service_orders WHERE tecnico_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_orders = $row['total'] ?? 0;
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM service_orders WHERE tecnico_id = ? AND tem_irregularidade = 'sim'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_irregularities = $row['total'] ?? 0;
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM service_orders WHERE tecnico_id = ? AND tem_lna = 'sim'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_lna = $row['total'] ?? 0;
        $stmt->close();
        
        // Calcular total de páginas
        $total_pages = ceil($total_orders / $per_page);
        
        // Obter as ordens de serviço do técnico com paginação
        $stmt = $conn->prepare("SELECT so.*, u.name as tecnico_nome 
                               FROM service_orders so 
                               JOIN users u ON so.tecnico_id = u.id 
                               WHERE so.tecnico_id = ? 
                               ORDER BY so.data DESC 
                               LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $user_id, $per_page, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Erro ao carregar dashboard: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema PROEIS - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header-nav d-flex justify-content-between align-items-center">
            <div class="nav-brand">
                <i class="fas fa-tachometer-alt me-2"></i>Sistema PROEIS
            </div>
            <div>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user_name); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="new_order.php"><i class="fas fa-plus me-2"></i>Nova Ordem</a></li>
                        <?php if ($role === 'admin'): ?>
                            <li><a class="dropdown-item" href="register.php"><i class="fas fa-user-plus me-2"></i>Registrar Usuário</a></li>
                            <li><a class="dropdown-item" href="manage_users.php"><i class="fas fa-users-cog me-2"></i>Gerenciar Usuários</a></li>
                            <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Relatórios</a></li>
                            <li><a class="dropdown-item" href="access_logs.php"><i class="fas fa-users me-2"></i>Logs de Acesso</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Ocorreu um erro: <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h2>Bem-vindo, <?php echo htmlspecialchars($user_name); ?></h2>
                        <p>Painel de controle do Sistema de Acompanhamento PROEIS</p>
                        <div class="text-muted">
                            <i class="fas fa-clock me-2"></i>
                            Horário atual: <?php echo date('d/m/Y H:i:s'); ?> (Brasília)
                        </div>
                        <?php if ($role === 'admin'): ?>
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-shield-alt me-2"></i>
                                <strong>Modo Administrador:</strong> Você tem acesso completo ao sistema, incluindo logs de acesso dos usuários.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card primary">
                    <div class="card-body">
                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                        <h5 class="stat-label">Ordens de Serviço</h5>
                        <div class="stat-number"><?php echo $total_orders; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card warning">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h5 class="stat-label">Irregularidades</h5>
                        <div class="stat-number"><?php echo $total_irregularities; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card info">
                    <div class="card-body">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <h5 class="stat-label">LNA</h5>
                        <div class="stat-number"><?php echo $total_lna; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Ordens de Serviço Recentes 
                            <span class="badge bg-light text-dark ms-2">
                                Página <?php echo $page; ?> de <?php echo max(1, $total_pages); ?>
                            </span>
                        </h5>
                        <a href="new_order.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> Nova Ordem
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nº</th>
                                        <th>Data/Hora</th>
                                        <th>Técnico</th>
                                        <th>Setor</th>
                                        <th>Equipe Apoio</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Nenhuma ordem de serviço encontrada</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <?php 
                                                    // Converter para horário de Brasília se necessário
                                                    $date = new DateTime($order['data']);
                                                    $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                                                    echo $date->format('d/m/Y H:i'); 
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['tecnico_nome']); ?></td>
                                                <td><?php echo $order['setor']; ?></td>
                                                <td><?php echo isset($order['equipe_apoio']) ? htmlspecialchars($order['equipe_apoio']) : '-'; ?></td>
                                                <td>
                                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="print_order.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary btn-sm" target="_blank">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginação -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Mostrando <?php echo ($offset + 1); ?> a <?php echo min($offset + count($recent_orders), $total_orders); ?> 
                                de <?php echo $total_orders; ?> ordens
                            </div>
                            <nav aria-label="Paginação das ordens">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>" aria-label="Anterior">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Mostrar páginas próximas
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    // Se estamos no início, mostrar mais páginas à frente
                                    if ($page <= 3) {
                                        $end_page = min($total_pages, 5);
                                    }
                                    
                                    // Se estamos no final, mostrar mais páginas atrás
                                    if ($page > $total_pages - 3) {
                                        $start_page = max(1, $total_pages - 4);
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>" aria-label="Próxima">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($role === 'admin'): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ações Administrativas</h5>
                    </div>
                    <div class="card-body admin-actions">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="register.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-user-plus"></i>
                                    Registrar Novo Usuário
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="manage_users.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-users-cog"></i>
                                    Gerenciar Usuários
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="reports.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-chart-bar"></i>
                                    Relatórios
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="access_logs.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-users"></i>
                                    Logs de Acesso
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
