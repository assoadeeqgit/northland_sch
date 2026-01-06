<?php
/**
 * Base Layout Template
 * 
 * This file contains the common layout structure (sidebar, header)
 * Used by all dashboard pages
 * 
 * Usage:
 * Just include this file at the top of your content pages
 * It will handle the layout automatically for both full page and AJAX requests
 */

// This should be included AFTER auth-check.php and ajax-helper.php

// Only output layout for non-AJAX requests
if (!isAjaxRequest()):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> - Northland Schools Kano</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nskblue: '#1e40af',
                        nsklightblue: '#3b82f6',
                        nsknavy: '#1e3a8a',
                        nskgold: '#f59e0b',
                        nsklight: '#f0f9ff',
                        nskgreen: '#10b981',
                        nskred: '#ef4444'
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body { 
            font-family: 'Montserrat', sans-serif; 
            background: #f8fafc; 
        }
    </style>
    
    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="sidebar.css">
    
    <!-- Page-specific styles (can be overridden in content pages) -->
    <?php if (isset($pageStyles)): ?>
        <?= $pageStyles ?>
    <?php endif; ?>
</head>
<body>
    <!-- Sidebar -->
    <?php require_once 'sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Header -->
        <?php require_once 'header.php'; ?>
        
        <!-- Content Container (AJAX target) -->
        <div id="main-content">
<?php
endif; // End layout header
?>
