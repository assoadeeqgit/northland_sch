// Professional AJAX Filter for Teachers Management
class TeachersFilter {
    constructor() {
        this.currentPage = 1;
        this.filters = {
            department_filter: '',
            search: ''
        };
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.addSearchInput();
        this.loadInitialData();
    }

    setupEventListeners() {
        const deptSelect = document.querySelector('select[name="department_filter"]');
        
        if (deptSelect) {
            deptSelect.addEventListener('change', () => {
                this.filters.department_filter = deptSelect.value;
                this.debounceFilter();
            });
        }

        const filterForm = document.querySelector('form[method="GET"]');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }
    }

    addSearchInput() {
        // Only add to main filter containers, not modals
        const filterContainer = document.querySelector('.flex.flex-wrap.gap-4:not(.modal *):not([id*="modal"] *)');
        if (!filterContainer || document.querySelector('#teacherSearch')) return;

        // Additional check to ensure we're not in a modal
        if (filterContainer.closest('.modal, [id*="modal"], .fixed.inset-0')) return;

        const searchDiv = document.createElement('div');
        searchDiv.className = 'flex-1 min-w-[200px]';
        searchDiv.innerHTML = `
            <label class="block text-gray-700 mb-2 text-sm font-medium">Search Teachers</label>
            <input type="text" id="teacherSearch" placeholder="Search by name, ID, subject..." 
                   class="w-full px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm">
        `;

        filterContainer.appendChild(searchDiv);

        document.getElementById('teacherSearch').addEventListener('input', (e) => {
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

        fetch(`teachers-management.php?${params}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.renderTeachers(result.data);
                    this.renderPagination(result.pagination);
                    this.updateResultsCount(result.pagination);
                } else {
                    this.showError(result.message || 'Failed to load teachers');
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

    renderTeachers(teachers) {
        const tbody = document.querySelector('tbody');
        if (!tbody) return;

        if (teachers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-500">
                        <i class="fas fa-search text-4xl mb-4 block"></i>
                        <p>No teachers found matching your filters</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = teachers.map(teacher => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 border-b border-gray-200">
                    <div class="font-semibold text-nsknavy">${teacher.first_name} ${teacher.last_name}</div>
                    <div class="text-sm text-gray-600">${teacher.teacher_id}</div>
                </td>
                <td class="px-6 py-4 border-b border-gray-200">${teacher.email}</td>
                <td class="px-6 py-4 border-b border-gray-200">${teacher.phone || 'N/A'}</td>
                <td class="px-6 py-4 border-b border-gray-200">${teacher.subject_specialization || 'General'}</td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <span class="px-2 py-1 text-xs rounded-full ${teacher.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${teacher.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <button onclick="viewTeacher(${teacher.id})" class="text-nskblue hover:text-nsknavy mr-2">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editTeacher(${teacher.id})" class="text-green-600 hover:text-green-800 mr-2">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteTeacher(${teacher.id})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    showLoading() {
        this.isLoading = true;
        const container = document.querySelector('.overflow-x-auto');
        if (container) {
            container.style.opacity = '0.6';
            container.style.pointerEvents = 'none';
        }
        this.showLoadingSpinner();
    }

    hideLoading() {
        this.isLoading = false;
        const container = document.querySelector('.overflow-x-auto');
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
        if (spinner) spinner.style.display = 'none';
    }

    renderPagination(pagination) {
        const paginationContainer = document.querySelector('.flex.space-x-2');
        if (!paginationContainer || !pagination) return;

        const { current_page, total_pages, has_prev, has_next } = pagination;
        let paginationHTML = '';
        
        if (has_prev) {
            paginationHTML += `<button onclick="teachersFilter.goToPage(${current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Previous</button>`;
        }
        
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === current_page;
            paginationHTML += `
                <button onclick="teachersFilter.goToPage(${i})" 
                        class="px-3 py-1 border rounded text-sm ${isActive ? 'bg-nskblue text-white' : 'hover:bg-gray-100'}">
                    ${i}
                </button>
            `;
        }
        
        if (has_next) {
            paginationHTML += `<button onclick="teachersFilter.goToPage(${current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Next</button>`;
        }
        
        paginationContainer.innerHTML = paginationHTML;
    }

    updateResultsCount(pagination) {
        if (!pagination) return;
        
        const { total_items, current_page, per_page } = pagination;
        const start = ((current_page - 1) * per_page) + 1;
        const end = Math.min(current_page * per_page, total_items);
        
        let countDisplay = document.getElementById('resultsCount');
        if (!countDisplay) {
            countDisplay = document.createElement('div');
            countDisplay.id = 'resultsCount';
            countDisplay.className = 'text-sm text-gray-600 mb-2';
            
            const filterSection = document.querySelector('.flex.flex-wrap.gap-4');
            const tableContainer = document.querySelector('.overflow-x-auto');
            
            if (filterSection && !filterSection.closest('.modal')) {
                const countWrapper = document.createElement('div');
                countWrapper.className = 'flex items-center ml-auto';
                countWrapper.appendChild(countDisplay);
                filterSection.appendChild(countWrapper);
            } else if (tableContainer) {
                tableContainer.parentNode.insertBefore(countDisplay, tableContainer);
            }
        }
        
        countDisplay.textContent = `Showing ${start}-${end} of ${total_items} teachers`;
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

let teachersFilter;
document.addEventListener('DOMContentLoaded', function() {
    teachersFilter = new TeachersFilter();
});
