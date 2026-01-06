// Clean UI/UX Filter Fix - Non-intrusive approach
class CleanFilter {
    constructor(config = {}) {
        this.config = {
            endpoint: window.location.pathname,
            tableSelector: 'tbody',
            ...config
        };

        this.currentPage = 1;
        this.filters = {};
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupExistingFilters();
        this.addCleanSearch();
        this.setupPagination();
    }

    setupExistingFilters() {
        // Work with existing filter elements without modifying layout
        document.querySelectorAll('select[name*="filter"]').forEach(select => {
            const filterName = select.name;
            this.filters[filterName] = select.value;

            select.addEventListener('change', () => {
                this.filters[filterName] = select.value;
                this.debounceFilter();
            });
        });

        // Handle existing filter forms
        const filterForm = document.querySelector('form[method="GET"]');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }
    }

    addCleanSearch() {
        // Only add search if there's a clear place for it
        const filterForm = document.querySelector('form[method="GET"]');
        if (!filterForm || document.querySelector('#cleanSearch')) return;

        // Find the submit button to place search before it
        const submitBtn = filterForm.querySelector('button[type="submit"]');
        if (!submitBtn) return;

        const searchDiv = document.createElement('div');
        searchDiv.className = 'flex-1 min-w-[200px]';
        searchDiv.innerHTML = `
            <label class="block text-gray-700 mb-2 text-sm font-medium">Search</label>
            <input type="text" id="cleanSearch" placeholder="Search..." 
                   class="w-full px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm">
        `;

        // Insert before submit button's parent
        submitBtn.parentElement.parentElement.insertBefore(searchDiv, submitBtn.parentElement);

        document.getElementById('cleanSearch').addEventListener('input', (e) => {
            this.filters.search = e.target.value;
            this.debounceFilter();
        });
    }

    debounceFilter() {
        clearTimeout(this.filterTimeout);
        this.filterTimeout = setTimeout(() => {
            this.currentPage = 1;
            this.loadData();
        }, 300);
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
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('No AJAX support');
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    this.renderTable(result.data);
                    this.updatePagination(result.pagination);
                    this.showResultsCount(result.pagination);
                } else {
                    this.showError(result.message || 'Failed to load data');
                }
            })
            .catch(error => {
                console.warn('AJAX not available, using form submission');
                this.fallbackToForm();
            })
            .finally(() => {
                this.hideLoading();
            });
    }

    renderTable(data) {
        const tbody = document.querySelector(this.config.tableSelector);
        if (!tbody) return;

        if (data.length === 0) {
            const colCount = tbody.closest('table')?.querySelector('thead tr')?.children.length || 5;
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colCount}" class="text-center py-8 text-gray-500">
                        <i class="fas fa-search text-2xl mb-2 block"></i>
                        <p>No results found</p>
                    </td>
                </tr>
            `;
            return;
        }

        // Use custom renderer if provided
        if (this.config.renderRow) {
            tbody.innerHTML = data.map(this.config.renderRow).join('');
        } else {
            // Generic renderer - preserve existing row structure
            const existingRow = tbody.querySelector('tr:not(.no-results)');
            if (existingRow) {
                const template = existingRow.outerHTML;
                tbody.innerHTML = data.map(item => {
                    let row = template;
                    Object.keys(item).forEach(key => {
                        row = row.replace(new RegExp(`{{${key}}}`, 'g'), item[key] || '');
                    });
                    return row;
                }).join('');
            }
        }
    }

    showResultsCount(pagination) {
        if (!pagination) return;

        const { total_items, current_page, per_page } = pagination;
        const start = ((current_page - 1) * per_page) + 1;
        const end = Math.min(current_page * per_page, total_items);

        // Show count in a non-intrusive way
        let countDisplay = document.getElementById('cleanResultsCount');
        if (!countDisplay) {
            countDisplay = document.createElement('div');
            countDisplay.id = 'cleanResultsCount';
            countDisplay.className = 'fixed bottom-4 right-4 bg-white shadow-lg rounded-lg px-3 py-2 text-sm text-gray-600 border z-40';
            document.body.appendChild(countDisplay);

            // Auto-hide after 3 seconds
            setTimeout(() => {
                if (countDisplay) {
                    countDisplay.style.opacity = '0.7';
                }
            }, 3000);
        }

        countDisplay.textContent = `${start}-${end} of ${total_items}`;
        countDisplay.style.opacity = '1';
    }

    updatePagination(pagination) {
        // Update existing pagination without breaking layout
        const paginationLinks = document.querySelectorAll('a[href*="page="]');
        if (paginationLinks.length === 0) return;

        const { current_page, total_pages } = pagination;

        paginationLinks.forEach(link => {
            const pageMatch = link.href.match(/page=(\d+)/);
            if (pageMatch) {
                const pageNum = parseInt(pageMatch[1]);
                link.onclick = (e) => {
                    e.preventDefault();
                    this.goToPage(pageNum);
                };

                // Update active state
                if (pageNum === current_page) {
                    link.classList.add('bg-nskblue', 'text-white');
                } else {
                    link.classList.remove('bg-nskblue', 'text-white');
                }
            }
        });
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadData();
    }

    showLoading() {
        this.isLoading = true;
        const table = document.querySelector('table');
        if (table) {
            table.style.opacity = '0.7';
            table.style.pointerEvents = 'none';
        }
    }

    hideLoading() {
        this.isLoading = false;
        const table = document.querySelector('table');
        if (table) {
            table.style.opacity = '1';
            table.style.pointerEvents = 'auto';
        }
    }

    fallbackToForm() {
        const form = document.querySelector('form[method="GET"]');
        if (form) {
            Object.keys(this.filters).forEach(key => {
                let input = form.querySelector(`[name="${key}"]`);
                if (!input && this.filters[key]) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    form.appendChild(input);
                }
                if (input) input.value = this.filters[key];
            });
            form.submit();
        }
    }

    showError(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 5000);
    }
}

// Auto-initialize clean filter
// Auto-initialize clean filter
let cleanFilter;

function initCleanFilter() {
    // Only if no other filters are active and no existing search functionality
    const hasExistingSearch = document.querySelector('#searchInput, input[name="search"]');
    const hasCustomAjax = document.querySelector('script').textContent.includes('performAjaxSearch');

    if (!window.academicsFilter && !window.studentsFilter && !window.teachersFilter &&
        !window.universalFilter && !hasExistingSearch && !hasCustomAjax) {

        const hasTable = document.querySelector('table tbody');
        const hasFilters = document.querySelector('select[name*="filter"], form[method="GET"]');

        if (hasTable && hasFilters) {
            cleanFilter = new CleanFilter();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCleanFilter);
} else {
    initCleanFilter();
}


