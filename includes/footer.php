        </main>
    </div>
    
    <!-- AJAX Navigation Script (SPA-like experience) -->
    <script>
        // AJAX Page Navigation for Ultra-Fast Loading
        const ajaxPages = ['index.php', 'payment.php', 'fees.php', 'students.php', 'expenses.php', 'categories.php', 'reports.php', 'finance-fees.php', 'finance-income.php', 'finance-defaulters.php'];
        
        document.addEventListener('DOMContentLoaded', function() {
            // Get all sidebar navigation links
            const navLinks = document.querySelectorAll('.sidebar nav a');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                const filename = href ? href.split('/').pop() : '';
                
                // Only apply AJAX to dashboard pages
                if (ajaxPages.includes(filename)) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        loadPageAjax(href, filename);
                    });
                }
            });
        });
        
        function loadPageAjax(url, pageName) {
            // Show loading indicator
            const mainContent = document.querySelector('.main-content');
            const contentBody = mainContent.querySelector('.content-body') || mainContent;
            
            // Add loading overlay
            contentBody.style.opacity = '0.5';
            contentBody.style.pointerEvents = 'none';
            
            // Fetch page content
            fetch(url + '?ajax=1')
                .then(response => response.text())
                .then(html => {
                    // Parse HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Get the content
                    const newContent = doc.querySelector('.content-body');
                    
                    if (newContent) {
                        // Replace content
                        const currentContent = mainContent.querySelector('.content-body');
                        if (currentContent) {
                            currentContent.replaceWith(newContent);
                        } else {
                            // Find the header and insert after it
                            const header = mainContent.querySelector('.top-navbar');
                            if (header) {
                                header.insertAdjacentHTML('afterend', newContent.outerHTML);
                            }
                        }
                        
                        // Update active state in sidebar
                        updateSidebarActiveState(pageName);
                        
                        // Update browser history
                        history.pushState({page: pageName}, '', url);
                        
                        // Scroll to top
                        window.scrollTo(0, 0);
                    } else {
                        // Fallback to normal navigation
                        window.location.href = url;
                    }
                    
                    // Remove loading state
                    setTimeout(() => {
                        const newContentBody = mainContent.querySelector('.content-body');
                        if (newContentBody) {
                            newContentBody.style.opacity = '1';
                            newContentBody.style.pointerEvents = 'auto';
                        }
                    }, 100);
                })
                .catch(error => {
                    console.error('AJAX navigation error:', error);
                    // Fallback to normal navigation
                    window.location.href = url;
                });
        }
        
        function updateSidebarActiveState(pageName) {
            const navLinks = document.querySelectorAll('.sidebar nav a');
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && href.includes(pageName)) {
                    // Active State - Force Inline for Defensive CSS
                    link.style.backgroundColor = '#1e40af';
                    link.style.color = 'white';
                    link.style.fontWeight = '600';
                    link.classList.remove('hover:bg-nskblue', 'hover:text-white');
                    link.classList.add('bg-nskblue', 'text-white', 'shadow-md');
                } else {
                    // Inactive State - Reset Inline
                    link.style.backgroundColor = 'transparent';
                    link.style.color = 'rgba(255,255,255,0.8)';
                    link.style.fontWeight = 'normal'; // Reset font weight
                    link.classList.remove('bg-nskblue', 'text-white', 'shadow-md');
                    link.classList.add('hover:bg-nskblue', 'hover:text-white');
                }
            });
        }
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.page) {
                location.reload(); // Simple reload for back/forward
            }
        });
    </script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global SweetAlert Helpers
        function confirmSubmit(event, title, text, confirmButtonText = 'Yes, do it!', confirmButtonColor = '#3085d6') {
            event.preventDefault();
            const form = event.target;
            
            Swal.fire({
                title: title || 'Are you sure?',
                text: text || "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#d33',
                confirmButtonText: confirmButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }

        function confirmLink(event, title, text, confirmButtonText = 'Yes, do it!') {
            event.preventDefault(); // Prevent default link behavior
            const link = event.currentTarget.href; // Get the href from the clicked element

            Swal.fire({
                title: title || 'Are you sure?',
                text: text || "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: confirmButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = link;
                }
            });
            return false;
        }
    </script>
</body>
</html>
