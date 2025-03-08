// Polyfills pour la compatibilité avec les anciens navigateurs
if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest) {
    Element.prototype.closest = function(s) {
        var el = this;
        do {
            if (el.matches(s)) return el;
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}

// Fonctions utilitaires
const utils = {
    formatNumber: function(number, decimals = 2) {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    },

    formatDate: function(date) {
        return new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(new Date(date));
    },

    formatDateTime: function(date) {
        return new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    }
};

// Gestionnaire de formulaires
const formHandler = {
    init: function() {
        document.addEventListener('submit', this.handleSubmit.bind(this));
        this.setupValidation();
    },

    handleSubmit: function(e) {
        if (e.target.tagName === 'FORM') {
            if (!this.validateForm(e.target)) {
                e.preventDefault();
                return false;
            }
            toggleLoading(true);
        }
    },

    validateForm: function(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (input.hasAttribute('required') && !input.value.trim()) {
                isValid = false;
                this.showError(input, 'Ce champ est requis');
            } else if (input.type === 'email' && input.value && !this.isValidEmail(input.value)) {
                isValid = false;
                this.showError(input, 'Email invalide');
            } else if (input.type === 'tel' && input.value && !this.isValidPhone(input.value)) {
                isValid = false;
                this.showError(input, 'Numéro de téléphone invalide');
            }
        });

        return isValid;
    },

    showError: function(input, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        input.classList.add('is-invalid');
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
            input.parentNode.insertBefore(errorDiv, input.nextSibling);
        }
    },

    isValidEmail: function(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    isValidPhone: function(phone) {
        return /^[\d\s\-\+\(\)]{8,}$/.test(phone);
    },

    setupValidation: function() {
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const feedback = e.target.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.remove();
                }
            }
        });
    }
};

// Gestionnaire de tableaux
const tableHandler = {
    init: function() {
        this.setupSorting();
        this.setupFiltering();
        this.setupPagination();
    },

    setupSorting: function() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => this.sortTable(th));
        });
    },

    setupFiltering: function() {
        document.querySelectorAll('.table-filter').forEach(input => {
            input.addEventListener('input', () => this.filterTable(input));
        });
    },

    setupPagination: function() {
        document.querySelectorAll('.table-paginate').forEach(table => {
            const rowsPerPage = parseInt(table.dataset.rowsPerPage) || 10;
            this.paginateTable(table, rowsPerPage);
        });
    },

    sortTable: function(th) {
        const table = th.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const index = Array.from(th.parentNode.children).indexOf(th);
        const direction = th.classList.contains('sort-asc') ? -1 : 1;

        rows.sort((a, b) => {
            const aValue = a.children[index].textContent.trim();
            const bValue = b.children[index].textContent.trim();
            return direction * (isNaN(aValue) ? aValue.localeCompare(bValue) : aValue - bValue);
        });

        tbody.append(...rows);
        th.classList.toggle('sort-asc');
        th.classList.toggle('sort-desc');
    },

    filterTable: function(input) {
        const table = input.closest('.table-container').querySelector('table');
        const searchText = input.value.toLowerCase();
        
        table.querySelectorAll('tbody tr').forEach(tr => {
            const text = tr.textContent.toLowerCase();
            tr.style.display = text.includes(searchText) ? '' : 'none';
        });
    },

    paginateTable: function(table, rowsPerPage) {
        const rows = table.querySelectorAll('tbody tr');
        const numPages = Math.ceil(rows.length / rowsPerPage);
        let currentPage = 1;

        const updateTable = () => {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            rows.forEach((row, index) => {
                row.style.display = (index >= start && index < end) ? '' : 'none';
            });
        };

        // Créer la pagination
        if (numPages > 1) {
            const pagination = document.createElement('div');
            pagination.className = 'pagination justify-content-center mt-3';
            
            for (let i = 1; i <= numPages; i++) {
                const button = document.createElement('button');
                button.className = 'btn btn-outline-primary mx-1';
                button.textContent = i;
                button.addEventListener('click', () => {
                    currentPage = i;
                    updateTable();
                    pagination.querySelectorAll('button').forEach(btn => {
                        btn.classList.toggle('active', btn === button);
                    });
                });
                pagination.appendChild(button);
            }

            table.parentNode.insertBefore(pagination, table.nextSibling);
        }

        updateTable();
    }
};

// Gestionnaire de modals
const modalHandler = {
    init: function() {
        this.setupModals();
    },

    setupModals: function() {
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-bs-target');
                const modal = document.querySelector(modalId);
                if (modal) {
                    const data = button.dataset;
                    this.populateModal(modal, data);
                }
            });
        });
    },

    populateModal: function(modal, data) {
        Object.keys(data).forEach(key => {
            if (key.startsWith('modal')) {
                const field = key.replace('modal', '').toLowerCase();
                const input = modal.querySelector(`[name="${field}"]`);
                if (input) {
                    input.value = data[key];
                }
            }
        });
    }
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    formHandler.init();
    tableHandler.init();
    modalHandler.init();
}); 