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
            <ul class="sidebar-menu">
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/payment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/fees.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'fees.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Fees</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/students.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>"><i class="fas fa-box-open"></i> Inventory</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/expenses.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave"></i> Expenses</a></li>
                <li><a href="<?php echo BASE_URL . $dash_prefix; ?>/reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> Reports</a></li>
            </ul>

            <div class="sidebar-footer" style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="<?php echo BASE_URL; ?>/logout.php" style="display: flex; align-items: center; color: #ff8a80; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-sign-out-alt" style="margin-right: 12px; width: 20px; text-align: center;"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="toggle-btn" style="cursor: pointer; color: var(--brand-navy); font-size: 1.2rem;">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="user-profile">
                    <span style="font-weight: 600; color: var(--brand-navy);"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?> <i class="fas fa-chevron-down" style="color: var(--brand-orange); margin-left: 5px;"></i></span>
                </div>
            </header>
