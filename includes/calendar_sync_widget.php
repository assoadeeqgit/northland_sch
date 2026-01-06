<?php
// Include this in your admin dashboard or wherever you manage terms

function getCalendarSyncButton() {
    return '
    <div style="margin: 10px 0;">
        <button onclick="syncWithCalendar()" style="background: #007cba; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
            🗓️ Sync with Calendar
        </button>
        <div id="sync-message" style="margin-top: 10px;"></div>
    </div>
    
    <script>
    function syncWithCalendar() {
        const messageDiv = document.getElementById("sync-message");
        messageDiv.innerHTML = "Syncing...";
        
        fetch("sync_calendar.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=sync_calendar"
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.style.padding = "8px";
            messageDiv.style.borderRadius = "4px";
            messageDiv.style.marginTop = "10px";
            
            if (data.success) {
                messageDiv.style.background = "#d4edda";
                messageDiv.style.color = "#155724";
                messageDiv.style.border = "1px solid #c3e6cb";
            } else {
                messageDiv.style.background = "#f8d7da";
                messageDiv.style.color = "#721c24";
                messageDiv.style.border = "1px solid #f5c6cb";
            }
            
            messageDiv.innerHTML = data.message;
            
            // Refresh page after 2 seconds if successful
            if (data.success) {
                setTimeout(() => location.reload(), 2000);
            }
        })
        .catch(error => {
            messageDiv.innerHTML = "Sync failed: " + error.message;
        });
    }
    </script>';
}

// Usage: echo getCalendarSyncButton(); in your admin page
?>
