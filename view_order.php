<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Definir fuso horário para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Verificar se o ID da ordem foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$conn = getConnection();

// Obter detalhes da ordem de serviço
$stmt = $conn->prepare("SELECT so.*, u.name as tecnico_nome 
                       FROM service_orders so 
                       JOIN users u ON so.tecnico_id = u.id 
                       WHERE so.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit;
}

$order = $result->fetch_assoc();

// Verificar permissão (apenas admin ou o próprio técnico pode ver)
if ($role !== 'admin' && $order['tecnico_id'] != $user_id) {
    header("Location: dashboard.php");
    exit;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema PROEIS - Visualizar Ordem #<?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .info-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--secondary-color);
        }

        .info-section h5 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            margin-bottom: 0.75rem;
        }

        .info-label {
            font-weight: 600;
            min-width: 200px;
            color: var(--dark-color);
        }

        .info-value {
            flex: 1;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav d-flex justify-content-between align-items-center">
            <a href="dashboard.php" class="nav-brand">
                <i class="fas fa-arrow-left me-2"></i>Sistema PROEIS
            </a>
            <div>
                <span class="text-dark">
                    <i class="fas fa-eye me-2"></i>Visualizar Ordem #<?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?>
                </span>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>
                            Ordem de Serviço #<?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?>
                        </h3>
                        <div>
                            <a href="print_order.php?id=<?php echo $order_id; ?>" class="btn btn-light" target="_blank">
                                <i class="fas fa-print me-2"></i>Imprimir
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-section">
                                    <h5><i class="fas fa-info-circle me-2"></i>Informações Gerais</h5>
                                    <div class="info-row">
                                        <div class="info-label">Número da Ordem:</div>
                                        <div class="info-value"><strong><?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?></strong></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Data e Hora:</div>
                                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['data'])); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Técnico:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($order['tecnico_nome']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Equipe PROEIS:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($order['equipe']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Setor:</div>
                                        <div class="info-value">
                                            <span class="badge bg-primary"><?php echo $order['setor']; ?></span>
                                        </div>
                                    </div>
                                    <?php if (isset($order['equipe_apoio'])): ?>
                                    <div class="info-row">
                                        <div class="info-label">Equipe de Apoio:</div>
                                        <div class="info-value">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($order['equipe_apoio']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-section">
                                    <h5><i class="fas fa-cogs me-2"></i>Detalhes do Serviço</h5>
                                    <div class="info-row">
                                        <div class="info-label">Irregularidade:</div>
                                        <div class="info-value">
                                            <?php if (isset($order['tem_irregularidade']) && $order['tem_irregularidade'] === 'sim'): ?>
                                                <span class="badge bg-warning">SIM</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">NÃO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (isset($order['tipos_irregularidade']) && !empty($order['tipos_irregularidade'])): ?>
                                    <div class="info-row">
                                        <div class="info-label">Tipos de Irregularidade:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($order['tipos_irregularidade']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="info-row">
                                        <div class="info-label">LNA:</div>
                                        <div class="info-value">
                                            <?php if (isset($order['tem_lna']) && $order['tem_lna'] === 'sim'): ?>
                                                <span class="badge bg-info">SIM</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">NÃO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Religado:</div>
                                        <div class="info-value">
                                            <?php if ($order['religado'] === 'sim'): ?>
                                                <span class="badge bg-success">SIM</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">NÃO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">R.O:</div>
                                        <div class="info-value">
                                            <?php if ($order['ro'] === 'sim'): ?>
                                                <span class="badge bg-success">SIM</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">NÃO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <h5><i class="fas fa-comment me-2"></i>Observação</h5>
                            <div class="p-3 bg-white rounded">
                                <?php if (empty($order['observacao'])): ?>
                                    <em class="text-muted">Nenhuma observação registrada.</em>
                                <?php else: ?>
                                    <?php echo nl2br(htmlspecialchars($order['observacao'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="dashboard.php" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                            </a>
                            <a href="print_order.php?id=<?php echo $order_id; ?>" class="btn btn-primary" target="_blank">
                                <i class="fas fa-print me-2"></i>Imprimir Ordem
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
