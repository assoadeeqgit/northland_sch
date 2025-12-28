/**
 * SPA (Single Page Application) Loader
 * Prevents sidebar/footer reloading by using AJAX navigation
 * 
 * @author Northland Schools Admin System
 * @version 1.0.0
 */

class SPALoader {
    constructor() {
        this.contentArea = null;
        this.loadingIndicator = null;
        this.currentPage = '';
        this.cache = new Map();
        this.maxCacheSize = 10;

        // Configuration
        this.config = {
            contentSelector: '#main-content-area',
            loadingClass: 'loading',
            fadeSpeed: 200,
            enableCache: true,
            enableHistory: true,
            contentFolder: 'content/'
        };
    }

    /**
     * Initialize the SPA system
     */
    init() {
        console.log('🚀 Initializing SPA Loader...');

        // Find content area
        this.contentArea = document.querySelector(this.config.contentSelector);

        if (!this.contentArea) {
            console.error('❌ Content area not found:', this.config.contentSelector);
            return;
        }

        // Create loading indicator
        this.createLoadingIndicator();

        // Setup navigation listeners
        this.setupNavigationListeners();

        // Setup browser back/forward buttons
        if (this.config.enableHistory) {
            this.setupHistoryNavigation();
        }

        // Load initial page from URL
        const initialPage = this.getPageFromURL();
        if (initialPage && initialPage !== 'admin-dashboard.php') {
            this.loadPage(initialPage, false); // Don't push initial state
        }

        console.log('✅ SPA Loader initialized successfully');
    }

    /**
     * Create loading indicator element
     */
    createLoadingIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'spa-loading-indicator';
        indicator.className = 'fixed top-0 left-0 w-full h-1 bg-blue-600 transform scale-x-0 transition-transform duration-300 z-50';
        indicator.innerHTML = `
            <div class="h-full bg-gradient-to-r from-blue-400 via-blue-600 to-blue-400 animate-pulse"></div>
        `;
        document.body.appendChild(indicator);
        this.loadingIndicator = indicator;
    }

    /**
     * Setup click listeners for all navigation links
     */
    setupNavigationListeners() {
        // Delegate event handling for better performance
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-spa-link]');

            if (link && !link.classList.contains('external-link')) {
                e.preventDefault();
                const page = link.getAttribute('href');

                if (page && page !== '#') {
                    this.loadPage(page);
                }
            }
        });

        console.log('📌 Navigation listeners attached');
    }

    /**
     * Setup browser history navigation (back/forward buttons)
     */
    setupHistoryNavigation() {
        // Store initial state
        const initialPage = this.getPageFromURL() || 'admin-dashboard.php';
        window.history.replaceState({ page: initialPage }, '', `?page=${initialPage}`);

        // Handle popstate (back/forward)
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.page) {
                this.loadPage(e.state.page, false); // Don't push state again
            }
        });

        console.log('🔙 History navigation enabled');
    }

    /**
     * Get page parameter from URL
     */
    getPageFromURL() {
        const params = new URLSearchParams(window.location.search);
        return params.get('page');
    }

    /**
     * Load a page via AJAX
     * @param {string} page - Page filename
     * @param {boolean} pushState - Whether to update browser history
     */
    async loadPage(page, pushState = true) {
        // Prevent loading same page
        if (page === this.currentPage) {
            console.log('⏭️  Already on page:', page);
            return;
        }

        console.log('📄 Loading page:', page);

        try {
            // Show loading indicator
            this.showLoading();

            // Check cache first
            let content;
            if (this.config.enableCache && this.cache.has(page)) {
                console.log('💾 Loading from cache:', page);
                content = this.cache.get(page);
            } else {
                // Fetch content from server
                content = await this.fetchPageContent(page);

                // Cache the content
                if (this.config.enableCache) {
                    this.cacheContent(page, content);
                }
            }

            // Update the content area with fade effect
            await this.updateContent(content);

            // Update browser URL and history
            if (pushState && this.config.enableHistory) {
                const url = `?page=${page}`;
                window.history.pushState({ page: page }, '', url);
            }

            // Update current page
            this.currentPage = page;

            // Update active menu items
            this.updateActiveMenuItem(page);

            // Hide loading indicator
            this.hideLoading();

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Trigger custom event for other scripts
            this.triggerPageLoadEvent(page);

            console.log('✅ Page loaded successfully:', page);

        } catch (error) {
            console.error('❌ Error loading page:', error);
            this.showError(error.message);
            this.hideLoading();
        }
    }

    /**
     * Fetch page content from server
     * @param {string} page - Page filename
     * @returns {Promise<string>} - HTML content
     */
    async fetchPageContent(page) {
        // Construct the fetch URL - append ?ajax=1 to get content-only version
        const url = `${this.config.contentFolder}${page}?ajax=1&t=${Date.now()}`;

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const content = await response.text();

        // Validate content
        if (!content || content.trim().length === 0) {
            throw new Error('Empty response from server');
        }

        return content;
    }

    /**
     * Update content area with new HTML
     * @param {string} html - New content HTML
     */
    async updateContent(html) {
        return new Promise((resolve) => {
            // Fade out
            this.contentArea.style.opacity = '0';
            this.contentArea.style.transition = `opacity ${this.config.fadeSpeed}ms ease-in-out`;

            setTimeout(() => {
                // Update content
                this.contentArea.innerHTML = html;

                // Execute any scripts in the new content
                this.executeScripts(this.contentArea);

                // Fade in
                setTimeout(() => {
                    this.contentArea.style.opacity = '1';
                    setTimeout(resolve, this.config.fadeSpeed);
                }, 50);
            }, this.config.fadeSpeed);
        });
    }

    /**
     * Execute scripts in newly loaded content
     * @param {HTMLElement} container - Container with scripts
     */
    executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    /**
     * Cache content for faster subsequent loads
     * @param {string} page - Page identifier
     * @param {string} content - HTML content
     */
    cacheContent(page, content) {
        // Remove oldest if cache is full
        if (this.cache.size >= this.maxCacheSize) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
        this.cache.set(page, content);
        console.log('💾 Cached:', page, `(${this.cache.size}/${this.maxCacheSize})`);
    }

    /**
     * Update active state in sidebar menu
     * @param {string} page - Current page
     */
    updateActiveMenuItem(page) {
        // Remove all active classes
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active', 'bg-nskblue', 'text-white');
        });

        // Add active class to current page link
        const activeLink = document.querySelector(`a[href="${page}"]`);
        if (activeLink) {
            activeLink.classList.add('active', 'bg-nskblue', 'text-white');

            // If it's in a dropdown, open the dropdown
            const dropdown = activeLink.closest('.dropdown-menu');
            if (dropdown) {
                dropdown.classList.remove('hidden');
                const toggle = dropdown.previousElementSibling;
                if (toggle && toggle.querySelector('.dropdown-arrow')) {
                    toggle.querySelector('.dropdown-arrow').classList.add('rotate-180');
                }
            }
        }
    }

    /**
     * Show loading indicator
     */
    showLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.transform = 'scaleX(1)';
        }
        this.contentArea.classList.add(this.config.loadingClass);
    }

    /**
     * Hide loading indicator
     */
    hideLoading() {
        if (this.loadingIndicator) {
            setTimeout(() => {
                this.loadingIndicator.style.transform = 'scaleX(0)';
            }, 300);
        }
        this.contentArea.classList.remove(this.config.loadingClass);
    }

    /**
     * Show error message
     * @param {string} message - Error message
     */
    showError(message) {
        const errorHTML = `
            <div class="flex items-center justify-center min-h-screen">
                <div class="bg-red-50 border border-red-200 rounded-lg p-8 max-w-md">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-exclamation-circle text-red-600 text-3xl mr-4"></i>
                        <h3 class="text-xl font-bold text-red-900">Error Loading Page</h3>
                    </div>
                    <p class="text-red-700 mb-4">${message}</p>
                    <button onclick="window.location.reload()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        <i class="fas fa-redo mr-2"></i>Reload Page
                    </button>
                </div>
            </div>
        `;
        this.contentArea.innerHTML = errorHTML;
    }

    /**
     * Trigger custom event when page loads
     * @param {string} page - Page that was loaded
     */
    triggerPageLoadEvent(page) {
        const event = new CustomEvent('spaPageLoaded', {
            detail: { page: page }
        });
        document.dispatchEvent(event);
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
        console.log('🗑️  Cache cleared');
    }

    /**
     * Preload a page
     * @param {string} page - Page to preload
     */
    async preloadPage(page) {
        if (!this.cache.has(page)) {
            try {
                const content = await this.fetchPageContent(page);
                this.cacheContent(page, content);
                console.log('⚡ Preloaded:', page);
            } catch (error) {
                console.warn('Failed to preload:', page, error);
            }
        }
    }
}

// Create global instance
window.spaLoader = new SPALoader();

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.spaLoader.init());
} else {
    window.spaLoader.init();
}

// Expose for debugging
window.SPALoader = SPALoader;
