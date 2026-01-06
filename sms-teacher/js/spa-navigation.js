/**
 * SPA Navigation for Teacher Dashboard
 * Handles AJAX navigation to prevent full page reloads.
 */

(function () {
    // Prevent double initialization
    if (window.spaInitialized) return;
    window.spaInitialized = true;

    // Initialization logic
    document.addEventListener('DOMContentLoaded', initSpa);

    // Also run init if DOM is already loaded
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        initSpa();
    }

    function initSpa() {
        // Select core elements
        const mainContent = document.querySelector('.main-content');
        const sidebar = document.querySelector('.sidebar');

        if (!mainContent || !sidebar) return;

        // Expose handleSpaNavigation globally
        window.handleSpaNavigation = (url) => {
            loadPage(url);
        };

        // Initial Active Link Highlight
        updateSidebarLinks(window.location.href);

        // Intercept Links
        document.body.addEventListener('click', (e) => {
            const link = e.target.closest('a');

            // Validation
            if (!link || !link.href) return;

            // Ignore external, hash, download, target="_blank", or explicitly excluded links
            if (
                link.getAttribute('target') === '_blank' ||
                link.hasAttribute('download') ||
                link.dataset.noAjax ||
                link.href.includes('logout.php') ||
                link.href.includes('#') ||
                !link.href.startsWith(window.location.origin)
            ) {
                return;
            }

            // Prevent default navigation
            e.preventDefault();

            // Close Mobile Sidebar if Open
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('mobile-show');
                const overlay = document.getElementById('mobileOverlay');
                if (overlay) overlay.classList.remove('active');
            }

            // Navigate
            loadPage(link.href);
        });

        // Handle Browser Back/Forward
        window.addEventListener('popstate', (e) => {
            loadPage(window.location.href, false);
        });

        /**
         * Fetch the page content via AJAX
         */
        async function loadPage(url, push = true) {
            // 1. Show Loading State
            mainContent.style.opacity = '0.6';
            mainContent.style.pointerEvents = 'none';

            // Progress Bar
            let loader = document.getElementById('spa-loader-line');
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'spa-loader-line';
                loader.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:#3b82f6;transition:width 0.3s, opacity 0.3s;z-index:9999;width:0%';
                document.body.appendChild(loader);
            }
            loader.style.opacity = '1';
            loader.style.width = '30%';

            try {
                const fetchUrl = new URL(url);
                fetchUrl.searchParams.set('ajax', '1');

                const response = await fetch(fetchUrl);
                if (!response.ok) throw new Error('Network error: ' + response.status);

                const html = await response.text();

                if (push) {
                    history.pushState({}, '', url);
                }

                loader.style.width = '100%';

                // Replace Content
                mainContent.innerHTML = html;
                mainContent.style.opacity = '1';
                mainContent.style.pointerEvents = 'auto';

                // Update UI attributes
                updateSidebarLinks(url);
                executeScripts(mainContent);
                rebindSidebarInContent();

                // Finish Loader
                setTimeout(() => {
                    loader.style.opacity = '0';
                    setTimeout(() => { loader.style.width = '0%'; }, 300);
                }, 400);

            } catch (error) {
                console.error('SPA Navigation Failed:', error);
                window.location.href = url;
            }
        }

        /**
         * Updates sidebar active state based on URL
         */
        function updateSidebarLinks(currentUrl) {
            const links = document.querySelectorAll('.sidebar-link');
            const currentPath = new URL(currentUrl).pathname;
            const currentFilename = currentPath.substring(currentPath.lastIndexOf('/') + 1);

            links.forEach(link => {
                const linkPath = new URL(link.href).pathname;
                const linkFilename = linkPath.substring(linkPath.lastIndexOf('/') + 1);

                link.classList.remove('bg-nskblue', 'text-white', 'shadow-md');
                link.classList.add('hover:bg-nskblue', 'hover:text-white');

                if (linkFilename === currentFilename) {
                    link.classList.remove('hover:bg-nskblue', 'hover:text-white');
                    link.classList.add('bg-nskblue', 'text-white', 'shadow-md');
                }
            });
        }

        /**
         * Executes scripts found in the injected HTML
         */
        function executeScripts(container) {
            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        }

        /**
         * Re-binds the Mobile Menu Toggle button
         */
        function rebindSidebarInContent() {
            const mobileToggle = document.getElementById('mobileMenuToggle');
            const mobileOverlay = document.getElementById('mobileOverlay');

            if (mobileToggle) {
                mobileToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    sidebar.classList.toggle('mobile-show');
                    if (mobileOverlay) mobileOverlay.classList.toggle('active');
                });
            }
        }
    }
})();
