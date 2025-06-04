<header class="bg-primary text-white py-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="dashboard.php" class="text-white text-decoration-none">
                    <h1 class="h3 mb-0">Sistema PROEIS</h1>
                </a>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div style="position: relative; z-index: 9999;">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="position: relative; z-index: 9999;">
                            <i class="fas fa-user"></i> <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : $_SESSION['username']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown" style="position: absolute; z-index: 9999;">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="new_order.php"><i class="fas fa-plus me-2"></i>Nova Ordem</a></li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="register.php"><i class="fas fa-user-plus me-2"></i>Registrar Usuário</a></li>
                                <li><a class="dropdown-item" href="manage_users.php"><i class="fas fa-users-cog me-2"></i>Gerenciar Usuários</a></li>
                                <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Relatórios</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
