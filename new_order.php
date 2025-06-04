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

$user_id = $_SESSION['user_id'];
$conn = getConnection();

// Obter informações do usuário
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$success = '';
$error = '';
$order_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipe = $_POST['equipe'] ?? '';
    $setor = $_POST['setor'] ?? '';
    $tem_irregularidade = $_POST['tem_irregularidade'] ?? 'não';
    $tipos_irregularidade = '';
    
    // Processar tipos de irregularidade se houver
    if ($tem_irregularidade === 'sim' && isset($_POST['tipos_irregularidade'])) {
        $tipos_irregularidade = implode(', ', $_POST['tipos_irregularidade']);
    }
    
    $tem_lna = $_POST['tem_lna'] ?? 'não';
    $equipe_apoio = $_POST['equipe_apoio'] ?? '';
    $religado = isset($_POST['religado']) && $_POST['religado'] === 'sim' ? 'sim' : 'não';
    $ro = isset($_POST['ro']) && $_POST['ro'] === 'sim' ? 'sim' : 'não';
    $observacao = $_POST['observacao'] ?? '';
    
    if (empty($equipe) || empty($setor) || empty($equipe_apoio)) {
        $error = "Preencha todos os campos obrigatórios";
    } else {
        // Inserir nova ordem de serviço com horário correto de Brasília
        $data_brasilia = date('Y-m-d H:i:s'); // Já está em horário de Brasília devido ao date_default_timezone_set
        
        $stmt = $conn->prepare("INSERT INTO service_orders (tecnico_id, equipe, setor, tem_irregularidade, tipos_irregularidade, tem_lna, equipe_apoio, religado, ro, observacao, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssss", $user_id, $equipe, $setor, $tem_irregularidade, $tipos_irregularidade, $tem_lna, $equipe_apoio, $religado, $ro, $observacao, $data_brasilia);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            $order_number = str_pad($order_id, 3, '0', STR_PAD_LEFT);
            $success = "Ordem de serviço registrada com sucesso! Número: " . $order_number;
        } else {
            $error = "Erro ao registrar ordem de serviço: " . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema PROEIS - Nova Ordem de Serviço</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    .conditional-field {
        display: none;
        animation: fadeIn 0.3s ease-in-out;
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
                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user['name']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-primary btn-sm ms-3">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nova Ordem de Serviço
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                            <div class="text-center mb-3">
                                <a href="print_order.php?id=<?php echo $order_id; ?>" class="btn btn-primary me-2" target="_blank">
                                    <i class="fas fa-print me-2"></i>Imprimir Ordem
                                </a>
                                <a href="new_order.php" class="btn btn-secondary">
                                    <i class="fas fa-plus me-2"></i>Nova Ordem
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($success)): ?>
                        <form method="POST" action="" id="orderForm">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="data" class="form-label">
                                        <i class="fas fa-calendar me-2"></i>Data e Hora (Brasília)
                                    </label>
                                    <input type="text" class="form-control" id="data" value="<?php echo date('d/m/Y H:i:s'); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="equipe" class="form-label">
                                        <i class="fas fa-users me-2"></i>Equipe PROEIS *
                                    </label>
                                    <input type="text" class="form-control" id="equipe" name="equipe" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-building me-2"></i>Setor *
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="setor" id="setor_gsc" value="GSC" checked onchange="updateEquipeApoio()">
                                            <label class="form-check-label" for="setor_gsc">GSC</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="setor" id="setor_perdas" value="PERDAS" onchange="updateEquipeApoio()">
                                            <label class="form-check-label" for="setor_perdas">PERDAS</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="equipe_apoio" class="form-label">
                                    <i class="fas fa-hands-helping me-2"></i>Equipe que o PROEIS deu apoio *
                                </label>
                                <select class="form-select" id="equipe_apoio" name="equipe_apoio" required>
                                    <option value="">Selecione a equipe</option>
                                </select>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Irregularidade
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tem_irregularidade" id="irregularidade_sim" value="sim" onchange="toggleIrregularidade()">
                                        <label class="form-check-label" for="irregularidade_sim">SIM</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tem_irregularidade" id="irregularidade_nao" value="não" checked onchange="toggleIrregularidade()">
                                        <label class="form-check-label" for="irregularidade_nao">NÃO</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-file-alt me-2"></i>LNA
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tem_lna" id="lna_sim" value="sim">
                                        <label class="form-check-label" for="lna_sim">SIM</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tem_lna" id="lna_nao" value="não" checked>
                                        <label class="form-check-label" for="lna_nao">NÃO</label>
                                    </div>
                                </div>
                            </div>

                            <div id="tipos_irregularidade_container" class="conditional-field mb-4">
                                <label class="form-label">
                                    <i class="fas fa-list me-2"></i>Tipos de Irregularidade
                                </label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tipos_irregularidade[]" id="bypass" value="BYPASS">
                                    <label class="form-check-label" for="bypass">BYPASS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tipos_irregularidade[]" id="ima" value="UTILIZAÇÃO DE IMÃ">
                                    <label class="form-check-label" for="ima">UTILIZAÇÃO DE IMÃ</label>
                                </div>
                                    <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tipos_irregularidade[]" id="ima" value="LIGAÇÃO CLANDESTINA">
                                    <label class="form-check-label" for="clandestina">LIGAÇÃO CLANDESTINA</label>
                                </div>
                                    <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tipos_irregularidade[]" id="ima" value="VIOLAÇÃO DE CORTE">
                                    <label class="form-check-label" for="violação">VIOLAÇÃO DE CORTE</label>
                                </div>
                                                                    <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tipos_irregularidade[]" id="ima" value="VIOLAÇÃO OU RETIRADA DO HD OU LIMITADOR ">
                                    <label class="form-check-label" for="violação">VIOLAÇÃO OU RETIRADA DO HD OU LIMITADOR </label>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-plug me-2"></i>Religado
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="religado" id="religado_sim" value="sim">
                                        <label class="form-check-label" for="religado_sim">SIM</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="religado" id="religado_nao" value="não" checked>
                                        <label class="form-check-label" for="religado_nao">NÃO</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-clipboard-check me-2"></i>R.O
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ro" id="ro_sim" value="sim">
                                        <label class="form-check-label" for="ro_sim">SIM</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ro" id="ro_nao" value="não" checked>
                                        <label class="form-check-label" for="ro_nao">NÃO</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="observacao" class="form-label">
                                    <i class="fas fa-comment me-2"></i>Observação
                                </label>
                                <textarea class="form-control" id="observacao" name="observacao" rows="4" placeholder="Digite suas observações aqui..."></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Registrar Ordem de Serviço
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Atualizar horário em tempo real
        function updateTime() {
            const now = new Date();
            const options = {
                timeZone: 'America/Sao_Paulo',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const formatter = new Intl.DateTimeFormat('pt-BR', options);
            const formattedTime = formatter.format(now);
            document.getElementById('data').value = formattedTime;
        }

        // Atualizar a cada segundo
        setInterval(updateTime, 1000);

        // Opções de equipes por setor
        const equipesGSC = [
            '01GSC01', '01GSC02', '01GSC03', '01GSC04', '01GSC05',
            '01GSC06', '01GSC07', '01GSC08', '01GSC09', '01GSC10',
            '02GSC01', '02GSC02', '02GSC03', '02GSC04', '02GSC05',
            '02GSC06', '02GSC07', '02GSC08', '02GSC09', '02GSC10',
            '02GSC11', '02GSC12', '02GSC13', '02GSC14', '02GSC15'
        ];

        const equipesPERDAS = [
            'SED-01PER-VA01', 'SED-01PER-VA05', 'SED-01PER-VA06',
            'SED-02PER-AG01', 'SED-02PER-AG02', 'SED-02PER-AG03',
            'SED-02PER-AG04', 'SED-02PER-AG05', 'SED-02PER-AG06',
            'SED-02PER-AG07', 'SED-02PER-AG08', 'SED-02PER-AG09',
            'SED-02PER-AG10', 'SED-02PER-AG11', 'SED-02PER-AG12',
            'SED-02PER-AG13', 'SED-02PER-AG14', 'SED-02PER-AG15',
            'SED-02PER-AG16', 'SED-02PER-AG17', 'SED-02PER-AG18',
            'SED-02PER-AG19', 'SED-02PER-AG20', 'SED-02PER-AG21',
            'SED-02PER-AG23', 'SED-02PER-GF01', 'SED-02PER-GF02'
        ];

        function updateEquipeApoio() {
            const setor = document.querySelector('input[name="setor"]:checked').value;
            const select = document.getElementById('equipe_apoio');
            
            // Limpar opções existentes
            select.innerHTML = '<option value="">Selecione a equipe</option>';
            
            // Adicionar novas opções baseadas no setor
            const equipes = setor === 'GSC' ? equipesGSC : equipesPERDAS;
            
            equipes.forEach(equipe => {
                const option = document.createElement('option');
                option.value = equipe;
                option.textContent = equipe;
                select.appendChild(option);
            });
        }

        function toggleIrregularidade() {
            const irregularidadeSim = document.getElementById('irregularidade_sim').checked;
            const container = document.getElementById('tipos_irregularidade_container');
            
            if (irregularidadeSim) {
                container.style.display = 'block';
                container.classList.add('conditional-field');
            } else {
                container.style.display = 'none';
                // Desmarcar todos os checkboxes
                const checkboxes = container.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(cb => cb.checked = false);
            }
        }

        // Inicializar as opções de equipe ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            updateEquipeApoio();
            toggleIrregularidade();
            updateTime(); // Atualizar horário inicial
        });
    </script>
</body>
</html>
