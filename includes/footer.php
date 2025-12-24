        </main>
    </div>
    
    <!-- Scripts -->
    <script>
        // Simple sidebar toggle for demo
        document.querySelector('.toggle-btn').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar.style.marginLeft === '-250px') {
                sidebar.style.marginLeft = '0';
                mainContent.style.marginLeft = '250px';
            } else {
                sidebar.style.marginLeft = '-250px';
                mainContent.style.marginLeft = '0';
            }
        });
    </script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
