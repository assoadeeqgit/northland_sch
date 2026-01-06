// Professional AJAX Filter for Students Management
class StudentsFilter {
    constructor() {
        this.currentPage = 1;
        this.filters = {
            class_filter: '',
            graduation_filter: '',
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
        // Real-time filtering
        const classSelect = document.querySelector('select[name="class_filter"]');
        const gradSelect = document.querySelector('select[name="graduation_filter"]');

        if (classSelect) {
            classSelect.addEventListener('change', () => {
                this.filters.class_filter = classSelect.value;
                this.debounceFilter();
            });
        }

        if (gradSelect) {
            gradSelect.addEventListener('change', () => {
                this.filters.graduation_filter = gradSelect.value;
                this.debounceFilter();
            });
        }

        // Filter form
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
        if (!filterContainer || document.querySelector('#studentSearch')) return;

        // Additional check to ensure we're not in a modal
        if (filterContainer.closest('.modal, [id*="modal"], .fixed.inset-0')) return;

        const searchDiv = document.createElement('div');
        searchDiv.className = 'flex-1 min-w-[200px]';
        searchDiv.innerHTML = `
            <label class="block text-gray-700 mb-2 text-sm font-medium">Search Students</label>
            <input type="text" id="studentSearch" placeholder="Search by name, ID, email..." 
                   class="w-full px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm">
        `;

        filterContainer.appendChild(searchDiv);

        document.getElementById('studentSearch').addEventListener('input', (e) => {
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

    applyFilters() {
        const formData = new FormData(document.querySelector('form[method="GET"]'));
        this.filters.class_filter = formData.get('class_filter') || '';
        this.filters.graduation_filter = formData.get('graduation_filter') || '';
        this.currentPage = 1;
        this.loadData();
    }

    loadInitialData() {
        const urlParams = new URLSearchParams(window.location.search);
        this.filters.class_filter = urlParams.get('class_filter') || '';
        this.filters.graduation_filter = urlParams.get('graduation_filter') || '';
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

        fetch(`students-management.php?${params}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.renderStudents(result.data);
                    this.renderPagination(result.pagination);
                    this.updateResultsCount(result.pagination);
                } else {
                    this.showError(result.message || 'Failed to load students');
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

    renderStudents(students) {
        const tbody = document.querySelector('tbody');
        if (!tbody) return;

        if (students.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-500">
                        <i class="fas fa-search text-4xl mb-4 block"></i>
                        <p>No students found matching your filters</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = students.map(student => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 border-b border-gray-200">
                    <div class="font-semibold text-nsknavy">${student.first_name} ${student.last_name}</div>
                    <div class="text-sm text-gray-600">${student.student_id}</div>
                </td>
                <td class="px-6 py-4 border-b border-gray-200">${student.class_name || 'Not Assigned'}</td>
                <td class="px-6 py-4 border-b border-gray-200">${student.email || 'N/A'}</td>
                <td class="px-6 py-4 border-b border-gray-200">${student.phone || 'N/A'}</td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <span class="px-2 py-1 text-xs rounded-full ${student.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${student.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 border-b border-gray-200 text-sm text-gray-600">
                    ${new Date(student.created_at).toLocaleDateString()}
                </td>
                <td class="px-6 py-4 border-b border-gray-200">
                    <button onclick="viewStudent(${student.id})" class="text-nskblue hover:text-nsknavy mr-2">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editStudent(${student.id})" class="text-green-600 hover:text-green-800 mr-2">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteStudent(${student.id})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    showLoading() {
        this.isLoading = true;
        // Minimal loading indication - just reduce table opacity
        const table = document.querySelector('.overflow-x-auto table');
        if (table) {
            table.style.opacity = '0.7';
        }
        // Spinner removed per user request
    }

    hideLoading() {
        this.isLoading = false;
        const table = document.querySelector('.overflow-x-auto table');
        if (table) {
            table.style.opacity = '1';
        }
        // Spinner cleanup removed
    }

    renderPagination(pagination) {
        const paginationContainer = document.querySelector('.flex.space-x-2');
        if (!paginationContainer || !pagination) return;

        const { current_page, total_pages, has_prev, has_next } = pagination;
        let paginationHTML = '';

        if (has_prev) {
            paginationHTML += `<button onclick="studentsFilter.goToPage(${current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Previous</button>`;
        }

        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === current_page;
            paginationHTML += `
                <button onclick="studentsFilter.goToPage(${i})" 
                        class="px-3 py-1 border rounded text-sm ${isActive ? 'bg-nskblue text-white' : 'hover:bg-gray-100'}">
                    ${i}
                </button>
            `;
        }

        if (has_next) {
            paginationHTML += `<button onclick="studentsFilter.goToPage(${current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Next</button>`;
        }

        paginationContainer.innerHTML = paginationHTML;
    }

    updateResultsCount(pagination) {
        if (!pagination) return;

        const { total_items, current_page, per_page } = pagination;
        const start = ((current_page - 1) * per_page) + 1;
        const end = Math.min(current_page * per_page, total_items);

        // Show count in existing space or create minimal indicator
        let countDisplay = document.getElementById('resultsCount');
        if (!countDisplay) {
            // Try to find existing pagination area
            const paginationArea = document.querySelector('.flex.space-x-2');
            if (paginationArea) {
                countDisplay = document.createElement('span');
                countDisplay.id = 'resultsCount';
                countDisplay.className = 'text-sm text-gray-600 ml-4';
                paginationArea.appendChild(countDisplay);
            } else {
                // Fallback to minimal floating indicator
                countDisplay = document.createElement('div');
                countDisplay.id = 'resultsCount';
                countDisplay.className = 'fixed bottom-4 right-16 bg-white shadow-sm rounded px-2 py-1 text-xs text-gray-600 border z-40';
                document.body.appendChild(countDisplay);
            }
        }

        countDisplay.textContent = `${start}-${end} of ${total_items}`;
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

let studentsFilter;
// Initialize
function initStudentsFilter() {
    // Prevent multiple initializations
    if (window.studentsFilter) return;
    window.studentsFilter = new StudentsFilter();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStudentsFilter);
} else {
    initStudentsFilter();
}
