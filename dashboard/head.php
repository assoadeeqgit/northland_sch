<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Northland Schools Kano - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <link rel="stylesheet" href="sidebar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body { font-family: 'Montserrat', sans-serif; background: #f8fafc; }
        .logo-container { background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); }
        .dashboard-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
