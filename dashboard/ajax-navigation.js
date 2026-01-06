/**
 * AJAX (PJAX-style) Navigation System
 * 
 * Features:
 * - Sidebar doesn't reload
 * - Only main content updates
 * - URLs update correctly
 * - Browser back/forward works
 * - Auth remains secure
 * - Graceful fallback to full reload
 */

class AjaxNavigation {
    constructor() {
        this.contentContainer = '#main-content';
        this.isNavigating = false;
        this.fallbackToFullLoad = false;

        // Bind methods to maintain 'this' context
        this.handleLinkClick = this.handleLinkClick.bind(this);
        this.handlePopState = this.handlePopState.bind(this);
        this.loadContent = this.loadContent.bind(this);
        this.showLoadingState = this.showLoadingState.bind(this);
        this.hideLoadingState = this.hideLoadingState.bind(this);
    }

    /**
     * Initialize the AJAX navigation system
     */
    init() {
        // Set up popstate handler for browser back/forward
        window.addEventListener('popstate', this.handlePopState);

        // Intercept all navigation links
        this.attachLinkHandlers();

        // Save initial state
        const initialState = {
            url: window.location.href,
            title: document.title
        };
        history.replaceState(initialState, document.title, window.location.href);

        console.log('[AJAX Nav] Initialized');
    }

    /**
     * Attach click handlers to all navigation links
     */
    attachLinkHandlers() {
        // Use event delegation on the document
        document.addEventListener('click', (e) => {
            // Find the closest <a> tag
            const link = e.target.closest('a[data-ajax-link]');

            if (link) {
                this.handleLinkClick(e, link);
            }
        });
    }

    /**
     * Handle link clicks
     */
    handleLinkClick(e, link) {
        // Don't intercept if:
        // - Ctrl/Cmd/Shift key is pressed (user wants new tab)
        // - Middle mouse button
        // - External link
        // - Download link
        // - Logout link
        if (
            e.ctrlKey ||
            e.metaKey ||
            e.shiftKey ||
            e.button === 1 ||
            link.hasAttribute('download') ||
            link.classList.contains('logout-link') ||
            link.classList.contains('no-ajax') ||
            link.target === '_blank'
        ) {
            return; // Let default behavior happen
        }

        const href = link.getAttribute('href');

        // Don't intercept hash links or javascript: links
        if (!href || href === '#' || href.startsWith('javascript:')) {
            return;
        }

        // Don't intercept external links
        const url = new URL(href, window.location.origin);
        if (url.origin !== window.location.origin) {
            return;
        }

        // Prevent default navigation
        e.preventDefault();

        // Load content via AJAX
        this.loadContent(url.href);
    }

    /**
     * Handle browser back/forward button
     */
    handlePopState(e) {
        if (e.state && e.state.url) {
            // Load content without adding to history
            this.loadContent(e.state.url, false);
        } else {
            // Fallback to full page reload
            window.location.reload();
        }
    }

    /**
     * Load content via AJAX
     * 
     * @param {string} url - The URL to load
     * @param {boolean} pushState - Whether to push to history (default: true)
     */
    async loadContent(url, pushState = true) {
        // Prevent concurrent navigation
        if (this.isNavigating) {
            console.log('[AJAX Nav] Navigation already in progress, ignoring');
            return;
        }

        this.isNavigating = true;
        this.showLoadingState();

        try {
            // Add AJAX header to request
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-AJAX-Navigation': '1'
                },
                credentials: 'same-origin'
            });

            // Check for errors
            if (!response.ok) {
                if (response.status === 401) {
                    // Authentication required - redirect to login
                    console.log('[AJAX Nav] Authentication required, redirecting to login');
                    window.location.href = '../login-form.php?return_url=' + encodeURIComponent(url);
                    return;
                }

                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Check content type
            const contentType = response.headers.get('Content-Type');

            // If server returns JSON (e.g., error response)
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();

                if (data.redirect) {
                    // Server requested redirect
                    window.location.href = data.redirect;
                    return;
                }

                if (!data.success) {
                    throw new Error(data.message || 'Unknown error');
                }

                // Should not happen, but just in case
                throw new Error('Server returned JSON instead of HTML');
            }

            // Get HTML content
            const html = await response.text();

            // If response contains full HTML (<!DOCTYPE), fallback to full reload
            if (html.trim().toLowerCase().startsWith('<!doctype') || html.trim().toLowerCase().startsWith('<html')) {
                console.warn('[AJAX Nav] Received full HTML page instead of content partial, falling back to full page load');
                window.location.href = url;
                return;
            }

            // Update the content
            const container = document.querySelector(this.contentContainer);

            if (!container) {
                console.error('[AJAX Nav] Content container not found:', this.contentContainer);
                // Fallback to full page reload
                window.location.href = url;
                return;
            }

            // Replace content
            container.innerHTML = html;

            // Update browser history
            if (pushState) {
                const state = {
                    url: url,
                    title: document.title
                };
                history.pushState(state, document.title, url);
            }

            // Update page title if there's an h1 in the content
            const h1 = container.querySelector('h1');
            if (h1) {
                document.title = h1.textContent + ' - Northland Schools Kano';
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Re-initialize any JavaScript that the new content might need
            this.reinitializeScripts();

            // Update active state in sidebar
            this.updateSidebarActiveState(url);

            console.log('[AJAX Nav] Content loaded successfully:', url);

        } catch (error) {
            console.error('[AJAX Nav] Error loading content:', error);

            // Show error message
            this.showError(error.message);

            // Fallback to full page reload after a short delay
            setTimeout(() => {
                console.log('[AJAX Nav] Falling back to full page load');
                window.location.href = url;
            }, 1500);

        } finally {
            this.isNavigating = false;
            this.hideLoadingState();
        }
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        // Add loading class to body
        document.body.classList.add('ajax-loading');

        // Show loading indicator
        let loader = document.getElementById('ajax-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'ajax-loader';
            loader.className = 'ajax-loader';
            loader.innerHTML = `
                <div class="ajax-loader-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading...</span>
                </div>
            `;
            document.body.appendChild(loader);
        }
        loader.classList.add('active');
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        document.body.classList.remove('ajax-loading');

        const loader = document.getElementById('ajax-loader');
        if (loader) {
            loader.classList.remove('active');
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        // Check if SweetAlert2 is available
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Navigation Error',
                text: message,
                footer: 'Loading full page instead...'
            });
        } else {
            alert('Navigation Error: ' + message + '\n\nLoading full page instead...');
        }
    }

    /**
     * Update sidebar active state
     */
    updateSidebarActiveState(url) {
        try {
            // Get the page filename from URL
            const urlObj = new URL(url);
            const pageName = urlObj.pathname.split('/').pop();

            // Remove active class from all sidebar links
            document.querySelectorAll('.sidebar .nav-item').forEach(link => {
                link.classList.remove('bg-nskblue', 'text-white');
                link.classList.add('hover:bg-nskblue', 'hover:text-white');
            });

            // Add active class to matching link
            document.querySelectorAll('.sidebar a[href*="' + pageName + '"]').forEach(link => {
                if (!link.classList.contains('dropdown-toggle')) {
                    link.classList.add('bg-nskblue', 'text-white');
                    link.classList.remove('hover:bg-nskblue', 'hover:text-white');
                }
            });
        } catch (e) {
            console.warn('[AJAX Nav] Could not update sidebar active state:', e);
        }
    }

    /**
     * Re-initialize scripts after content load
     */
    reinitializeScripts() {
        // Re-run any inline scripts in the loaded content
        const container = document.querySelector(this.contentContainer);
        if (container) {
            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');

                // Copy attributes
                Array.from(oldScript.attributes).forEach(attr => {
                    newScript.setAttribute(attr.name, attr.value);
                });

                // Copy content
                newScript.textContent = oldScript.textContent;

                // Replace old script with new one (to execute it)
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        }

        // Trigger custom event for other scripts to hook into
        window.dispatchEvent(new CustomEvent('ajaxNavigationComplete', {
            detail: { container: this.contentContainer }
        }));
    }
}

// CSS for loading indicator
const style = document.createElement('style');
style.textContent = `
    .ajax-loader {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(3px);
    }

    .ajax-loader.active {
        display: flex;
    }

    .ajax-loader-spinner {
        background: white;
        padding: 2rem 3rem;
        border-radius: 1rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .ajax-loader-spinner i {
        font-size: 2rem;
        color: #1e40af;
    }

    .ajax-loader-spinner span {
        font-size: 1rem;
        color: #1e3a8a;
        font-weight: 600;
    }

    body.ajax-loading {
        cursor: wait;
    }
`;
document.head.appendChild(style);

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ajaxNavigation = new AjaxNavigation();
        window.ajaxNavigation.init();
    });
} else {
    window.ajaxNavigation = new AjaxNavigation();
    window.ajaxNavigation.init();
}
