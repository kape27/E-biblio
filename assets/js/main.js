/**
 * Main JavaScript file for E-Lib Digital Library
 * Contains common functionality used across the application
 */

// CSRF Token management
let csrfToken = null;

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeCSRF();
    initializeApp();
});

/**
 * Initialize CSRF token
 */
function initializeCSRF() {
    // Get CSRF token from meta tag
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        csrfToken = metaToken.getAttribute('content');
    }
    
    // If no meta tag, try to get from a hidden input
    const hiddenToken = document.querySelector('input[name="csrf_token"]');
    if (hiddenToken && !csrfToken) {
        csrfToken = hiddenToken.value;
    }
}

/**
 * Get current CSRF token
 */
function getCSRFToken() {
    return csrfToken;
}

/**
 * Refresh CSRF token via AJAX
 */
function refreshCSRFToken() {
    return fetch('/api/csrf_token.php')
        .then(response => response.json())
        .then(data => {
            if (data.token) {
                csrfToken = data.token;
                // Update all CSRF token inputs on the page
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = csrfToken;
                });
                // Update meta tag if exists
                const metaToken = document.querySelector('meta[name="csrf-token"]');
                if (metaToken) {
                    metaToken.setAttribute('content', csrfToken);
                }
            }
            return csrfToken;
        });
}

/**
 * Initialize the application
 */
function initializeApp() {
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize file upload handlers
    initializeFileUploads();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize tooltips and UI enhancements
    initializeUIEnhancements();
}

/**
 * Form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email address');
            isValid = false;
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    errorDiv.setAttribute('data-error-for', field.name);
    
    field.parentNode.appendChild(errorDiv);
    field.classList.add('border-red-500');
}

function clearFieldError(field) {
    const existingError = field.parentNode.querySelector(`[data-error-for="${field.name}"]`);
    if (existingError) {
        existingError.remove();
    }
    field.classList.remove('border-red-500');
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * File upload handling
 */
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            handleFileSelection(this);
        });
    });
}

function handleFileSelection(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Show file info
    const fileInfo = document.getElementById(input.id + '_info');
    if (fileInfo) {
        fileInfo.innerHTML = `
            <div class="text-sm text-gray-600">
                <strong>Selected:</strong> ${file.name}<br>
                <strong>Size:</strong> ${formatFileSize(file.size)}<br>
                <strong>Type:</strong> ${file.type}
            </div>
        `;
    }
    
    // Validate file type for book uploads
    if (input.accept && input.accept.includes('.pdf,.epub')) {
        validateBookFile(file, input);
    }
    
    // Validate file type for image uploads
    if (input.accept && input.accept.includes('image/*')) {
        validateImageFile(file, input);
    }
}

function validateBookFile(file, input) {
    const allowedTypes = ['application/pdf', 'application/epub+zip'];
    const maxSize = 50 * 1024 * 1024; // 50MB
    
    if (!allowedTypes.includes(file.type)) {
        showFileError(input, 'Only PDF and EPUB files are allowed');
        return false;
    }
    
    if (file.size > maxSize) {
        showFileError(input, 'File size must be less than 50MB');
        return false;
    }
    
    clearFileError(input);
    return true;
}

function validateImageFile(file, input) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!allowedTypes.includes(file.type)) {
        showFileError(input, 'Only JPG, PNG, GIF, and WebP images are allowed');
        return false;
    }
    
    if (file.size > maxSize) {
        showFileError(input, 'Image size must be less than 5MB');
        return false;
    }
    
    clearFileError(input);
    return true;
}

function showFileError(input, message) {
    clearFileError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    errorDiv.setAttribute('data-file-error-for', input.id);
    
    input.parentNode.appendChild(errorDiv);
}

function clearFileError(input) {
    const existingError = input.parentNode.querySelector(`[data-file-error-for="${input.id}"]`);
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Search functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 300);
        });
    }
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', performSearch);
    }
}

function performSearch() {
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const resultsContainer = document.getElementById('search-results');
    
    if (!searchInput || !resultsContainer) return;
    
    const query = searchInput.value.trim();
    const category = categoryFilter ? categoryFilter.value : '';
    
    // Show loading state
    resultsContainer.innerHTML = '<div class="text-center py-8"><div class="spinner mx-auto"></div></div>';
    
    // Perform AJAX search with CSRF token
    const formData = new FormData();
    formData.append('query', query);
    formData.append('category', category);
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }
    
    fetch('search.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        resultsContainer.innerHTML = html;
    })
    .catch(error => {
        console.error('Search error:', error);
        resultsContainer.innerHTML = '<div class="text-red-500 text-center py-8">Search failed. Please try again.</div>';
    });
}

/**
 * UI Enhancements
 */
function initializeUIEnhancements() {
    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 5000);
    });
    
    // Confirm dialogs for delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Utility functions
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} fixed top-4 right-4 z-50 max-w-sm`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Export functions for use in other scripts
window.ELib = {
    showNotification,
    formatFileSize,
    validateForm,
    performSearch,
    getCSRFToken,
    refreshCSRFToken
};