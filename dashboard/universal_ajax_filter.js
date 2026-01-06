// Universal AJAX Filter Component for Dashboard Pages
class UniversalFilter {
    constructor(config) {
        this.config = {
            endpoint: window.location.pathname,
            tableSelector: 'tbody',
            paginationSelector: '.flex.space-x-2',
            perPage: 10,
            ...config
        };
        
        this.currentPage = 1;
        this.filters = {};
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupFilters();
        this.setupSearch();
        this.setupPagination();
        this.loadInitialData();
    }

    setupFilters() {
        // Auto-detect filter selects
        document.querySelectorAll('select[name*="filter"]').forEach(select => {
            const filterName = select.name;
            this.filters[filterName] = select.value;
            
            select.addEventListener('change', () => {
                this.filters[filterName] = select.value;
                this.debounceFilter();
            });
        });

        // Auto-detect filter forms
        const filterForm = document.querySelector('form[method="GET"]');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }
    }

    setupSearch() {
        // Add universal search if not exists - but only in main content, not modals
        const filterContainer = document.querySelector('.flex.flex-wrap.gap-4:not(.modal *):not([id*="modal"] *)');
        if (!filterContainer || document.querySelector('#universalSearch')) return;

        // Additional check to ensure we're not in a modal
        if (filterContainer.closest('.modal, [id*="modal"], .fixed.inset-0')) return;

        const searchDiv = document.createElement('div');
        searchDiv.className = 'flex-1 min-w-[200px]';
        searchDiv.innerHTML = `
            <label class="block text-gray-700 mb-2 text-sm font-medium">Search</label>
            <input type="text" id="universalSearch" placeholder="Search..." 
                   class="w-full px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm">
        `;

        filterContainer.appendChild(searchDiv);

        document.getElementById('universalSearch').addEventListener('input', (e) => {
            this.filters.search = e.target.value;
            this.debounceFilter();
        });
    }

    setupPagination() {
        // Handle existing pagination clicks
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[href*="page="], button[onclick*="page"]')) {
                e.preventDefault();
                const pageMatch = e.target.href?.match(/page=(\d+)/) || 
                                 e.target.onclick?.toString().match(/page.*?(\d+)/);
                if (pageMatch) {
                    this.goToPage(parseInt(pageMatch[1]));
                }
            }
        });
    }

    debounceFilter() {
        clearTimeout(this.filterTimeout);
        this.filterTimeout = setTimeout(() => {
            this.currentPage = 1;
            this.loadData();
        }, 300);
    }

    applyFilters() {
        const formData = new FormData(document.querySelector('form[method="GET"]'));
        for (const [key, value] of formData.entries()) {
            if (key.includes('filter')) {
                this.filters[key] = value;
            }
        }
        this.currentPage = 1;
        this.loadData();
    }

    loadInitialData() {
        const urlParams = new URLSearchParams(window.location.search);
        for (const [key, value] of urlParams.entries()) {
            if (key.includes('filter') || key === 'search') {
                this.filters[key] = value;
            }
        }
        this.currentPage = parseInt(urlParams.get('page')) || 1;
    }

    loadData() {
        if (this.isLoading) return;
        
        this.showLoading();
        
        const params = new URLSearchParams({
            ajax: '1',
            page: this.currentPage,
            ...this.filters
        });

        fetch(`${this.config.endpoint}?${params}`)
            .then(response => {
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned HTML instead of JSON. AJAX not supported on this page.');
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    this.renderTable(result.data);
                    this.renderPagination(result.pagination);
                    this.updateResultsCount(result.pagination);
                } else {
                    this.showError(result.message || 'Failed to load data');
                }
            })
            .catch(error => {
                console.warn('AJAX Filter Error:', error.message);
                // Fallback to regular form submission for pages without AJAX
                if (error.message.includes('HTML instead of JSON')) {
                    this.fallbackToFormSubmission();
                } else {
                    this.showError('Network error occurred');
                }
            })
            .finally(() => {
                this.hideLoading();
            });
    }

    fallbackToFormSubmission() {
        // For pages without AJAX support, use regular form submission
        const form = document.querySelector('form[method="GET"]');
        if (form) {
            // Update form with current filters
            Object.keys(this.filters).forEach(key => {
                let input = form.querySelector(`[name="${key}"]`);
                if (!input && this.filters[key]) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    form.appendChild(input);
                }
                if (input) {
                    input.value = this.filters[key];
                }
            });
            
            // Add page parameter
            let pageInput = form.querySelector('[name="page"]');
            if (!pageInput) {
                pageInput = document.createElement('input');
                pageInput.type = 'hidden';
                pageInput.name = 'page';
                form.appendChild(pageInput);
            }
            pageInput.value = this.currentPage;
            
            // Submit form
            form.submit();
        } else {
            // No form found, redirect with URL parameters
            const params = new URLSearchParams({
                page: this.currentPage,
                ...this.filters
            });
            window.location.href = `${this.config.endpoint}?${params}`;
        }
    }

    renderTable(data) {
        const tbody = document.querySelector(this.config.tableSelector);
        if (!tbody) return;

        if (data.length === 0) {
            const colCount = tbody.closest('table')?.querySelector('thead tr')?.children.length || 5;
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colCount}" class="text-center py-12 text-gray-500">
                        <i class="fas fa-search text-4xl mb-4 block"></i>
                        <p>No results found matching your filters</p>
                    </td>
                </tr>
            `;
            return;
        }

        // Use custom renderer if provided
        if (this.config.renderRow) {
            tbody.innerHTML = data.map(this.config.renderRow).join('');
        } else {
            // Generic renderer
            tbody.innerHTML = data.map(item => {
                const cells = Object.values(item).slice(0, 5).map(value => 
                    `<td class="px-6 py-4 border-b border-gray-200">${value || 'N/A'}</td>`
                ).join('');
                return `<tr class="hover:bg-gray-50 transition-colors">${cells}</tr>`;
            }).join('');
        }
    }

    showLoading() {
        this.isLoading = true;
        const container = document.querySelector('.overflow-x-auto, table');
        if (container) {
            container.style.opacity = '0.6';
            container.style.pointerEvents = 'none';
        }
        this.showLoadingSpinner();
    }

    hideLoading() {
        this.isLoading = false;
        const container = document.querySelector('.overflow-x-auto, table');
        if (container) {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        }
        this.hideLoadingSpinner();
    }

    showLoadingSpinner() {
        let spinner = document.getElementById('universalSpinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'universalSpinner';
            spinner.className = 'fixed top-4 right-4 bg-white rounded-lg shadow-lg p-3 z-50 flex items-center';
            spinner.innerHTML = `
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-2"></div>
                <span class="text-sm text-gray-600">Loading...</span>
            `;
            document.body.appendChild(spinner);
        }
        spinner.style.display = 'flex';
    }

    hideLoadingSpinner() {
        const spinner = document.getElementById('universalSpinner');
        if (spinner) spinner.style.display = 'none';
    }

    renderPagination(pagination) {
        const paginationContainer = document.querySelector(this.config.paginationSelector);
        if (!paginationContainer || !pagination) return;

        const { current_page, total_pages, has_prev, has_next } = pagination;
        let paginationHTML = '';
        
        if (has_prev) {
            paginationHTML += `<button onclick="universalFilter.goToPage(${current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Previous</button>`;
        }
        
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === current_page;
            paginationHTML += `
                <button onclick="universalFilter.goToPage(${i})" 
                        class="px-3 py-1 border rounded text-sm ${isActive ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'}">
                    ${i}
                </button>
            `;
        }
        
        if (has_next) {
            paginationHTML += `<button onclick="universalFilter.goToPage(${current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Next</button>`;
        }
        
        paginationContainer.innerHTML = paginationHTML;
    }

    updateResultsCount(pagination) {
        if (!pagination) return;
        
        const { total_items, current_page, per_page } = pagination;
        const start = ((current_page - 1) * per_page) + 1;
        const end = Math.min(current_page * per_page, total_items);
        
        let countDisplay = document.getElementById('universalResultsCount');
        if (!countDisplay) {
            countDisplay = document.createElement('div');
            countDisplay.id = 'universalResultsCount';
            countDisplay.className = 'text-sm text-gray-600 mb-2';
            
            const filterSection = document.querySelector('.flex.flex-wrap.gap-4:not(.modal *)');
            const tableContainer = document.querySelector('.overflow-x-auto, table');
            
            if (filterSection && !filterSection.closest('.modal')) {
                const countWrapper = document.createElement('div');
                countWrapper.className = 'flex items-center ml-auto';
                countWrapper.appendChild(countDisplay);
                filterSection.appendChild(countWrapper);
            } else if (tableContainer) {
                tableContainer.parentNode.insertBefore(countDisplay, tableContainer);
            }
        }
        
        countDisplay.textContent = `Showing ${start}-${end} of ${total_items} results`;
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadData();
    }

    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
        errorDiv.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 font-bold">&times;</button>
            </div>
        `;
        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            if (errorDiv.parentElement) errorDiv.remove();
        }, 5000);
    }
}

// Auto-initialize for pages with tables
let universalFilter;
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if no specific filter exists
    if (!window.academicsFilter && !window.studentsFilter && !window.teachersFilter) {
        const hasTable = document.querySelector('table tbody');
        const hasFilters = document.querySelector('select[name*="filter"]');
        
        if (hasTable || hasFilters) {
            universalFilter = new UniversalFilter();
        }
    }
});
