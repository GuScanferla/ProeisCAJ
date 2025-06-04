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
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço #<?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: white;
        }
        .order-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 20px;
        }
        .logo {
            max-height: 100px;
            margin-bottom: 10px;
        }
        .order-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #3498db;
        }
        .order-number {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .order-content {
            border: 2px solid #ddd;
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        .field-row {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .field-label {
            font-weight: bold;
            min-width: 200px;
            color: #3498db;
        }
        .field-value {
            flex: 1;
            padding: 5px 10px;
            border-bottom: 1px solid #ccc;
        }
        .signature-container {
            margin-top: 50px;
            text-align: center;
            border-top: 2px solid #3498db;
            padding-top: 30px;
        }
        .signature-name {
            margin-top: 20px;
            font-weight: bold;
            font-size: 16px;
        }
        .checkbox-field {
            display: inline-block;
            margin-right: 30px;
        }
        .btn-print {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-print:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print mb-3">
            <button class="btn btn-primary btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <div class="order-header">
            <img src="assets/img/logo.png" alt="PROEIS" class="logo">
            <div class="order-title">SISTEMA DE ACOMPANHAMENTO PROEIS</div>
            <div class="order-number">ORDEM DE SERVIÇO Nº <?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?></div>
        </div>
        
        <div class="order-content">
            <div class="field-row">
                <div class="field-label">DATA:</div>
                <div class="field-value"><?php echo date('d/m/Y H:i', strtotime($order['data'])); ?></div>
            </div>
            
            <div class="field-row">
                <div class="field-label">EQUIPE PROEIS:</div>
                <div class="field-value"><?php echo htmlspecialchars($order['equipe']); ?></div>
            </div>
            
            <div class="field-row">
                <div class="field-label">SETOR:</div>
                <div class="field-value">
                    <span class="checkbox-field">
                        ( <?php echo $order['setor'] === 'GSC' ? 'X' : ' '; ?> ) GSC
                    </span>
                    <span class="checkbox-field">
                        ( <?php echo $order['setor'] === 'PERDAS' ? 'X' : ' '; ?> ) PERDAS
                    </span>
                </div>
            </div>

            <?php if (isset($order['equipe_apoio'])): ?>
            <div class="field-row">
                <div class="field-label">EQUIPE QUE O PROEIS DEU APOIO:</div>
                <div class="field-value"><?php echo htmlspecialchars($order['equipe_apoio']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="field-row">
                <div class="field-label">IRREGULARIDADE:</div>
                <div class="field-value">
                    <span class="checkbox-field">
                        ( <?php echo (isset($order['tem_irregularidade']) && $order['tem_irregularidade'] === 'sim') ? 'X' : ' '; ?> ) SIM
                    </span>
                    <span class="checkbox-field">
                        ( <?php echo (!isset($order['tem_irregularidade']) || $order['tem_irregularidade'] === 'não') ? 'X' : ' '; ?> ) NÃO
                    </span>
                </div>
            </div>

            <?php if (isset($order['tipos_irregularidade']) && !empty($order['tipos_irregularidade'])): ?>
            <div class="field-row">
                <div class="field-label">TIPOS DE IRREGULARIDADE:</div>
                <div class="field-value"><?php echo htmlspecialchars($order['tipos_irregularidade']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="field-row">
                <div class="field-label">LNA:</div>
                <div class="field-value">
                    <span class="checkbox-field">
                        ( <?php echo (isset($order['tem_lna']) && $order['tem_lna'] === 'sim') ? 'X' : ' '; ?> ) SIM
                    </span>
                    <span class="checkbox-field">
                        ( <?php echo (!isset($order['tem_lna']) || $order['tem_lna'] === 'não') ? 'X' : ' '; ?> ) NÃO
                    </span>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">RELIGADO:</div>
                <div class="field-value">
                    <span class="checkbox-field">
                        ( <?php echo $order['religado'] === 'sim' ? 'X' : ' '; ?> ) SIM
                    </span>
                    <span class="checkbox-field">
                        ( <?php echo $order['religado'] === 'não' ? 'X' : ' '; ?> ) NÃO
                    </span>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">R.O:</div>
                <div class="field-value">
                    <span class="checkbox-field">
                        ( <?php echo $order['ro'] === 'sim' ? 'X' : ' '; ?> ) SIM
                    </span>
                    <span class="checkbox-field">
                        ( <?php echo $order['ro'] === 'não' ? 'X' : ' '; ?> ) NÃO
                    </span>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">OBSERVAÇÃO:</div>
                <div class="field-value"><?php echo nl2br(htmlspecialchars($order['observacao'])); ?></div>
            </div>
            
            <div class="signature-container">
                <div class="signature-name">TÉCNICO DE CAMPO: <?php echo htmlspecialchars($order['tecnico_nome']); ?></div>
                <div style="margin-top: 50px; border-bottom: 1px solid #000; width: 300px; margin-left: auto; margin-right: auto;"></div>
                <div style="margin-top: 10px; font-size: 12px;">Assinatura</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
