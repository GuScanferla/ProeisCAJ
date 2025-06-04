<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Definir fuso horário para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$conn = getConnection();

// Filtros
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$setor = $_GET['setor'] ?? '';
$tecnico_id = $_GET['tecnico_id'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];
$types = '';

if (!empty($data_inicio)) {
    $where_conditions[] = "DATE(so.data) >= ?";
    $params[] = $data_inicio;
    $types .= 's';
}

if (!empty($data_fim)) {
    $where_conditions[] = "DATE(so.data) <= ?";
    $params[] = $data_fim;
    $types .= 's';
}

if (!empty($setor)) {
    $where_conditions[] = "so.setor = ?";
    $params[] = $setor;
    $types .= 's';
}

if (!empty($tecnico_id)) {
    $where_conditions[] = "so.tecnico_id = ?";
    $params[] = $tecnico_id;
    $types .= 'i';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Obter dados para relatório
$sql = "SELECT so.*, u.name as tecnico_nome 
        FROM service_orders so 
        JOIN users u ON so.tecnico_id = u.id 
        $where_clause
        ORDER BY so.data DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

// Calcular estatísticas
$total_orders = count($orders);
$total_irregularidades = 0;
$total_lna = 0;
$total_religados = 0;
$total_ro = 0;

foreach ($orders as $order) {
    if ($order['tem_irregularidade'] === 'sim') $total_irregularidades++;
    if ($order['tem_lna'] === 'sim') $total_lna++;
    if ($order['religado'] === 'sim') $total_religados++;
    if ($order['ro'] === 'sim') $total_ro++;
}

// Obter lista de técnicos para filtro
$stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'tecnico' ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
$tecnicos = [];
while ($row = $result->fetch_assoc()) {
    $tecnicos[] = $row;
}
$stmt->close();

// Gerar Excel se solicitado
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_proeis_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // BOM para UTF-8
    echo "<table border='1'>";
    echo "<tr style='background-color: #3498db; color: white; font-weight: bold;'>";
    echo "<th>Nº Ordem</th><th>Data/Hora</th><th>Técnico</th><th>Equipe PROEIS</th><th>Setor</th>";
    echo "<th>Equipe Apoio</th><th>Irregularidade</th><th>Tipos Irregularidade</th><th>LNA</th>";
    echo "<th>Religado</th><th>R.O</th><th>Observação</th>";
    echo "</tr>";
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . str_pad($order['id'], 3, '0', STR_PAD_LEFT) . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($order['data'])) . "</td>";
        echo "<td>" . htmlspecialchars($order['tecnico_nome']) . "</td>";
        echo "<td>" . htmlspecialchars($order['equipe']) . "</td>";
        echo "<td>" . $order['setor'] . "</td>";
        echo "<td>" . htmlspecialchars($order['equipe_apoio'] ?? '') . "</td>";
        echo "<td>" . ($order['tem_irregularidade'] === 'sim' ? 'SIM' : 'NÃO') . "</td>";
        echo "<td>" . htmlspecialchars($order['tipos_irregularidade'] ?? '') . "</td>";
        echo "<td>" . ($order['tem_lna'] === 'sim' ? 'SIM' : 'NÃO') . "</td>";
        echo "<td>" . ($order['religado'] === 'sim' ? 'SIM' : 'NÃO') . "</td>";
        echo "<td>" . ($order['ro'] === 'sim' ? 'SIM' : 'NÃO') . "</td>";
        echo "<td>" . htmlspecialchars($order['observacao'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema PROEIS - Relatórios</title>
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
        .stat-card.warning { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.success { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .stat-card.info { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .stat-card.danger { background: linear-gradient(135deg, #e74c3c, #c0392b); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .filters-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem 0.75rem;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .badge-custom {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .export-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .export-buttons {
                flex-direction: column;
            }
        }
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
                    <i class="fas fa-chart-bar me-2"></i>Relatórios Gerenciais
                </span>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total de Ordens</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $total_irregularidades; ?></div>
                <div class="stat-label">Irregularidades</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number"><?php echo $total_lna; ?></div>
                <div class="stat-label">LNA</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo $total_religados; ?></div>
                <div class="stat-label">Religados</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number"><?php echo $total_ro; ?></div>
                <div class="stat-label">R.O</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card filters-card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filtros de Pesquisa
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="data_inicio" class="form-label">Data Início</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="data_fim" class="form-label">Data Fim</label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="setor" class="form-label">Setor</label>
                            <select class="form-select" id="setor" name="setor">
                                <option value="">Todos os Setores</option>
                                <option value="GSC" <?php echo $setor === 'GSC' ? 'selected' : ''; ?>>GSC</option>
                                <option value="PERDAS" <?php echo $setor === 'PERDAS' ? 'selected' : ''; ?>>PERDAS</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="tecnico_id" class="form-label">Técnico</label>
                            <select class="form-select" id="tecnico_id" name="tecnico_id">
                                <option value="">Todos os Técnicos</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                    <option value="<?php echo $tecnico['id']; ?>" <?php echo $tecnico_id == $tecnico['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tecnico['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="export-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filtrar
                            </button>
                            <a href="reports.php" class="btn btn-secondary">
                                <i class="fas fa-undo me-2"></i>Limpar
                            </a>
                        </div>
                        <div class="export-buttons">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-success">
                                <i class="fas fa-file-excel me-2"></i>Exportar Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela de Resultados -->
        <div class="table-container">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>Ordens de Serviço
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_orders; ?> registros</span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nº</th>
                            <th>Data/Hora</th>
                            <th>Técnico</th>
                            <th>Equipe</th>
                            <th>Setor</th>
                            <th>Equipe Apoio</th>
                            <th>Irregularidade</th>
                            <th>LNA</th>
                            <th>Religado</th>
                            <th>R.O</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Nenhuma ordem de serviço encontrada com os filtros aplicados.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['data'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['tecnico_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($order['equipe']); ?></td>
                                    <td>
                                        <span class="badge badge-custom <?php echo $order['setor'] === 'GSC' ? 'bg-primary' : 'bg-info'; ?>">
                                            <?php echo $order['setor']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['equipe_apoio'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge badge-custom <?php echo $order['tem_irregularidade'] === 'sim' ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo $order['tem_irregularidade'] === 'sim' ? 'SIM' : 'NÃO'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-custom <?php echo $order['tem_lna'] === 'sim' ? 'bg-info' : 'bg-secondary'; ?>">
                                            <?php echo $order['tem_lna'] === 'sim' ? 'SIM' : 'NÃO'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-custom <?php echo $order['religado'] === 'sim' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $order['religado'] === 'sim' ? 'SIM' : 'NÃO'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-custom <?php echo $order['ro'] === 'sim' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $order['ro'] === 'sim' ? 'SIM' : 'NÃO'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="print_order.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary btn-sm" target="_blank" title="Imprimir">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
