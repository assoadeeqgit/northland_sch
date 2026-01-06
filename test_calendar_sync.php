<!DOCTYPE html>
<html>
<head>
    <title>Calendar Sync Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 15px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007cba; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .result { margin-top: 10px; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Calendar Sync Test</h1>
    <p>Today: <strong><?php echo date('Y-m-d (l)'); ?></strong></p>
    
    <div class="test-section">
        <h3>Current Status</h3>
        <button class="btn btn-primary" onclick="checkStatus()">Check Current Status</button>
        <div id="status-result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test Manual Changes</h3>
        <button class="btn btn-danger" onclick="setWrongTerm()">Set Wrong Term (Third Term)</button>
        <button class="btn btn-success" onclick="syncCalendar()">Sync with Calendar</button>
        <div id="test-result"></div>
    </div>

    <script>
        function checkStatus() {
            fetch('api/check_status.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('status-result').innerHTML = 
                        `<div class="result success">
                            <strong>Current Session:</strong> ${data.session}<br>
                            <strong>Active Term:</strong> ${data.term}<br>
                            <strong>Term Dates:</strong> ${data.start_date} to ${data.end_date}
                        </div>`;
                });
        }
        
        function setWrongTerm() {
            fetch('api/set_wrong_term.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('test-result').innerHTML = 
                        `<div class="result error">Set ${data.term} as active (wrong for current date)</div>`;
                });
        }
        
        function syncCalendar() {
            fetch('sync_calendar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=sync_calendar'
            })
            .then(response => response.json())
            .then(data => {
                const className = data.success ? 'success' : 'error';
                document.getElementById('test-result').innerHTML = 
                    `<div class="result ${className}">${data.message}</div>`;
            });
        }
        
        // Load status on page load
        checkStatus();
    </script>
</body>
</html>
