<?php
// Incluir arquivo de inicialização
require_once 'config/init.php';

// Redirecionar para o painel se já estiver logado
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$timeout = isset($_GET['timeout']) && $_GET['timeout'] == 1;

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Preencha todos os campos";
    } else {
        try {
            $conn = getConnection();
            
            // Buscar usuário no banco de dados
            $stmt = $conn->prepare("SELECT id, username, password, name, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verificar senha
                if (password_verify($password, $user['password'])) {
                    // Login bem-sucedido
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['login_time'] = time();
                    
                    // Registrar login
                    logUserAccess($user['id'], $user['username'], $user['name'], 'LOGIN');
                    
                    $stmt->close();
                    $conn->close();
                    
                    // Redirecionar para o dashboard
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = "Credenciais inválidas";
                }
            } else {
                $error = "Credenciais inválidas";
            }
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            $error = "Erro interno do sistema. Tente novamente.";
            error_log("Erro no login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema PROEIS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        
        .login-logo {
            max-width: 200px;
            max-height: 200px;
            margin: 0 auto 20px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card shadow">
            <div class="card-header">
                <h3 class="mb-0">Sistema de Acompanhamento PROEIS</h3>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="assets/img/logo.png" alt="Águas de Juturnaíba" class="login-logo">
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($timeout): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        Sua sessão expirou por inatividade. Por favor, faça login novamente.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-2"></i>Usuário
                        </label>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Senha
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Entre em contato com o administrador para obter suas credenciais de acesso.
                    </small>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">
                Sistema PROEIS - Águas de Juturnaíba<br>
                <?php echo date('Y'); ?> - Todos os direitos reservados
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
