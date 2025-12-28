<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Northland Schools Kano - Financial System</title>
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.jpeg" alt="NSK Logo" class="brand-logo">
                <h3>NORTHLAND<br>SCHOOLS KANO</h3>
                <div class="school-acronym">EST. 2025</div>
            </div>
            
            <?php 
            $dash_prefix = ($_SESSION['user_type'] ?? '') == 'accountant' ? '/accountant-dashboard' : '';
            ?>
            <ul class="sidebar-menu" style="display: flex; flex-direction: column; height: calc(100% - 140px);">
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/payment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/fees.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'fees.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Fees</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/students.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>"><i class="fas fa-box-open"></i> Inventory</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/expenses.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave"></i> Expenses</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> Reports</a></li>
                <li style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="<?php echo BASE_URL; ?>/logout.php<?php echo isset($_SESSION['session_token']) ? '?token=' . urlencode($_SESSION['session_token']) : ''; ?>" onclick="return confirm('Are you sure you want to logout?')" style="color: #ff8a80;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="toggle-btn" style="cursor: pointer; color: var(--brand-navy); font-size: 1.2rem;">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="user-profile" style="display: flex; align-items: center; gap: 15px;">
                    <div class="user-info" style="text-align: right; line-height: 1.2;">
                        <span style="display: block; font-weight: 600; color: var(--brand-navy); font-size: 0.95rem;">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </span>
                        <span style="display: block; color: var(--text-light); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Accountant</span>
                    </div>
                    <div style="width: 35px; height: 35px; background: var(--brand-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-user"></i>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/logout.php<?php echo isset($_SESSION['session_token']) ? '?token=' . urlencode($_SESSION['session_token']) : ''; ?>" class="btn" style="padding: 6px 12px; font-size: 0.8rem; background-color: #fce4ec; color: #d32f2f; border: 1px solid #ffcdd2;" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>
