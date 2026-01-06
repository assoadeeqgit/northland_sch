// Professional AJAX Filter System for Academics Management
class AcademicsFilter {
    constructor() {
        this.currentTab = 'assignments';
        this.currentPage = 1;
        this.filters = {
            class_filter: '',
            department_filter: '',
            search: ''
        };
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRealTimeFiltering();
        this.loadInitialData();
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Filter form submission
        const filterForm = document.querySelector('form[method="GET"]');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }

        // Clear filters
        const clearBtn = document.querySelector('a[href="academics-management.php"]');
        if (clearBtn && clearBtn.textContent.includes('Clear')) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearFilters();
            });
        }
    }

    setupRealTimeFiltering() {
        // Real-time filtering on select change
        const classSelect = document.querySelector('select[name="class_filter"]');
        const deptSelect = document.querySelector('select[name="department_filter"]');

        if (classSelect) {
            classSelect.addEventListener('change', () => {
                this.filters.class_filter = classSelect.value;
                this.debounceFilter();
            });
        }

        if (deptSelect) {
            deptSelect.addEventListener('change', () => {
                this.filters.department_filter = deptSelect.value;
                this.debounceFilter();
            });
        }

        // Add search input if it doesn't exist
        this.addSearchInput();
    }

    addSearchInput() {
        // Only add to main filter containers, not modals
        const filterContainer = document.querySelector('.flex.flex-wrap.gap-4:not(.modal *):not([id*="modal"] *)');
        if (!filterContainer || document.querySelector('#searchInput')) return;

        // Additional check to ensure we're not in a modal
        if (filterContainer.closest('.modal, [id*="modal"], .fixed.inset-0')) return;

        const searchDiv = document.createElement('div');
        searchDiv.className = 'flex-1 min-w-[200px]';
        searchDiv.innerHTML = `
            <label class="block text-gray-700 mb-2 text-sm font-medium">Search</label>
            <input type="text" id="searchInput" placeholder="Search subjects, teachers..." 
                   class="w-full px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm">
        `;

        // Safely append to end instead of inserting at specific position
        filterContainer.appendChild(searchDiv);

        // Add search functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
            this.filters.search = searchInput.value;
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

    applyFilters() {
        const formData = new FormData(document.querySelector('form[method="GET"]'));
        this.filters.class_filter = formData.get('class_filter') || '';
        this.filters.department_filter = formData.get('department_filter') || '';
        this.currentPage = 1;
        this.loadData();
    }

    clearFilters() {
        this.filters = { class_filter: '', department_filter: '', search: '' };
        this.currentPage = 1;
        
        // Reset form elements
        document.querySelector('select[name="class_filter"]').value = '';
        document.querySelector('select[name="department_filter"]').value = '';
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';
        
        this.loadData();
        this.updateURL();
    }

    switchTab(tab) {
        if (this.isLoading) return;
        
        this.currentTab = tab;
        this.currentPage = 1;
        
        // Update tab UI
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'bg-nskblue', 'text-white');
            btn.classList.add('border-gray-300', 'text-gray-700');
        });
        
        const activeBtn = document.querySelector(`[data-tab="${tab}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active', 'bg-nskblue', 'text-white');
            activeBtn.classList.remove('border-gray-300', 'text-gray-700');
        }
        
        // Show/hide tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        const targetTab = document.getElementById(tab + 'Tab');
        if (targetTab) {
            targetTab.classList.remove('hidden');
        }
        
        this.loadData();
    }

    loadInitialData() {
        // Get current filters from URL
        const urlParams = new URLSearchParams(window.location.search);
        this.filters.class_filter = urlParams.get('class_filter') || '';
        this.filters.department_filter = urlParams.get('department_filter') || '';
        this.currentTab = urlParams.get('tab') || 'assignments';
        this.currentPage = parseInt(urlParams.get('page')) || 1;
        
        this.loadData();
    }

    loadData() {
        if (this.isLoading) return;
        
        this.showLoading();
        
        const params = new URLSearchParams({
            ajax: '1',
            tab: this.currentTab,
            page: this.currentPage,
            ...this.filters
        });

        fetch(`academics-management.php?${params}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.renderData(result);
                    this.updateURL();
                } else {
                    this.showError(result.message || 'Failed to load data');
                }
            })
            .catch(error => {
                console.error('Filter Error:', error);
                this.showError('Network error occurred');
            })
            .finally(() => {
                this.hideLoading();
            });
    }

    showLoading() {
        this.isLoading = true;
        const container = document.querySelector(`#${this.currentTab}Tab .overflow-x-auto`);
        if (container) {
            container.style.opacity = '0.6';
            container.style.pointerEvents = 'none';
        }

        // Show loading spinner
        this.showLoadingSpinner();
    }

    hideLoading() {
        this.isLoading = false;
        const container = document.querySelector(`#${this.currentTab}Tab .overflow-x-auto`);
        if (container) {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        }

        this.hideLoadingSpinner();
    }

    showLoadingSpinner() {
        let spinner = document.getElementById('loadingSpinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'loadingSpinner';
            spinner.className = 'fixed top-4 right-4 bg-white rounded-lg shadow-lg p-3 z-50 flex items-center';
            spinner.innerHTML = `
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-nskblue mr-2"></div>
                <span class="text-sm text-gray-600">Loading...</span>
            `;
            document.body.appendChild(spinner);
        }
        spinner.style.display = 'flex';
    }

    hideLoadingSpinner() {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
    }

    renderData(result) {
        if (this.currentTab === 'assignments') {
            this.renderAssignments(result.data);
        } else if (this.currentTab === 'subjects') {
            this.renderSubjects(result.data);
        }
        
        this.renderPagination(result.pagination);
        this.updateResultsCount(result.pagination);
    }

    renderAssignments(assignments) {
        const tbody = document.querySelector('#assignmentsTab tbody');
        if (!tbody) return;

        if (assignments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-500">
                        <i class="fas fa-search text-4xl mb-4 block"></i>
                        <p>No assignments found matching your filters</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = assignments.map(assignment => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 border-b border-gray-200">
                    <div class="font-semibold text-nsknavy">${assignment.class_name}</div>
                    <div class="text-sm text-gray-600">${assignment.class_code || ''}</div>
                </td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <div class="font-semibold">${assignment.subject_name}</div>
                    <div class="text-sm text-gray-600">${assignment.subject_code}</div>
                </td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <div class="font-semibold">${assignment.teacher_name || 'Not Assigned'}</div>
                    <div class="text-sm text-gray-600">${assignment.teacher_id || ''}</div>
                </td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <span class="px-2 py-1 text-xs rounded-full ${assignment.category === 'Core' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">
                        ${assignment.category}
                    </span>
                </td>
                <td class="px-6 py-4 border-b border-gray-200 text-sm text-gray-600">
                    ${new Date(assignment.created_at).toLocaleDateString()}
                </td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <button onclick="editAssignment(${assignment.id})" class="text-nskblue hover:text-nsknavy mr-2">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteAssignment(${assignment.id})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    renderSubjects(subjects) {
        const tbody = document.querySelector('#subjectsTab tbody');
        if (!tbody) return;

        if (subjects.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-12 text-gray-500">
                        <i class="fas fa-search text-4xl mb-4 block"></i>
                        <p>No subjects found matching your filters</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = subjects.map(subject => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 border-b border-gray-200">
                    <div class="font-semibold text-nsknavy">${subject.subject_name}</div>
                    <div class="text-sm text-gray-600">${subject.subject_code}</div>
                </td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <span class="px-2 py-1 text-xs rounded-full ${subject.category === 'Core' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">
                        ${subject.category}
                    </span>
                </td>
                <td class="px-6 py-4 border-b border-gray-200 text-sm text-gray-600">
                    ${subject.description || 'No description'}
                </td>
                <td class="px-6 py-4 border-b border-gray-200 text-sm text-gray-600">
                    ${new Date(subject.created_at).toLocaleDateString()}
                </td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <button onclick="editSubject(${subject.id})" class="text-nskblue hover:text-nsknavy mr-2">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteSubject(${subject.id})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    renderPagination(pagination) {
        // Update existing pagination or create new one
        const paginationContainer = document.querySelector('.flex.space-x-2');
        if (!paginationContainer || !pagination) return;

        const { current_page, total_pages, has_prev, has_next } = pagination;
        
        let paginationHTML = '';
        
        if (has_prev) {
            paginationHTML += `<button onclick="academicsFilter.goToPage(${current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Previous</button>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === current_page;
            paginationHTML += `
                <button onclick="academicsFilter.goToPage(${i})" 
                        class="px-3 py-1 border rounded text-sm ${isActive ? 'bg-nskblue text-white' : 'hover:bg-gray-100'}">
                    ${i}
                </button>
            `;
        }
        
        if (has_next) {
            paginationHTML += `<button onclick="academicsFilter.goToPage(${current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Next</button>`;
        }
        
        paginationContainer.innerHTML = paginationHTML;
    }

    updateResultsCount(pagination) {
        if (!pagination) return;
        
        const { total_items, current_page, per_page } = pagination;
        const start = ((current_page - 1) * per_page) + 1;
        const end = Math.min(current_page * per_page, total_items);
        
        // Update or create results count display
        let countDisplay = document.getElementById('resultsCount');
        if (!countDisplay) {
            countDisplay = document.createElement('div');
            countDisplay.id = 'resultsCount';
            countDisplay.className = 'text-sm text-gray-600 mb-2 ml-auto';
            
            // Find better placement - add to existing filter section or create inline with table
            const filterSection = document.querySelector('.filter-section .flex.flex-wrap');
            const tableContainer = document.querySelector(`#${this.currentTab}Tab .overflow-x-auto`);
            
            if (filterSection) {
                // Add as inline element in filter section
                const countWrapper = document.createElement('div');
                countWrapper.className = 'flex items-center';
                countWrapper.appendChild(countDisplay);
                filterSection.appendChild(countWrapper);
            } else if (tableContainer) {
                // Add just before table with minimal margin
                tableContainer.parentNode.insertBefore(countDisplay, tableContainer);
            }
        }
        
        countDisplay.textContent = `Showing ${start}-${end} of ${total_items} results`;
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadData();
    }

    updateURL() {
        const params = new URLSearchParams();
        params.set('tab', this.currentTab);
        if (this.currentPage > 1) params.set('page', this.currentPage);
        if (this.filters.class_filter) params.set('class_filter', this.filters.class_filter);
        if (this.filters.department_filter) params.set('department_filter', this.filters.department_filter);
        if (this.filters.search) params.set('search', this.filters.search);
        
        const newURL = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newURL);
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
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);
    }
}

// Initialize the filter system
let academicsFilter;
document.addEventListener('DOMContentLoaded', function() {
    academicsFilter = new AcademicsFilter();
});
