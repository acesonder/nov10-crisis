/**
 * Crisis Management System - Main JavaScript
 * AJAX, Notifications, Real-time Updates
 */

// Application state
const App = {
    notificationInterval: null,
    updateInterval: 30000, // 30 seconds
    currentTheme: localStorage.getItem('theme') || 'light',
    currentColorScheme: localStorage.getItem('colorScheme') || 'blue'
};

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initNavigation();
    initNotifications();
    initModals();
    initForms();
    initTooltips();
    
    // Start real-time updates if user is logged in
    if (document.querySelector('[data-user-id]')) {
        startRealTimeUpdates();
    }
});

// Theme Management
function initTheme() {
    applyTheme(App.currentTheme);
    applyColorScheme(App.currentColorScheme);
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    App.currentTheme = theme;
    localStorage.setItem('theme', theme);
}

function applyColorScheme(scheme) {
    document.documentElement.setAttribute('data-color-scheme', scheme);
    App.currentColorScheme = scheme;
    localStorage.setItem('colorScheme', scheme);
}

function toggleTheme() {
    const newTheme = App.currentTheme === 'light' ? 'dark' : 'light';
    applyTheme(newTheme);
    
    // Save to server if user is logged in
    const userId = document.querySelector('[data-user-id]')?.dataset.userId;
    if (userId) {
        updateUserPreference('theme', newTheme);
    }
}

// Navigation
function initNavigation() {
    const toggle = document.querySelector('.navbar-toggle');
    const menu = document.querySelector('.navbar-menu');
    
    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            menu.classList.toggle('active');
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (menu && !menu.contains(e.target) && !toggle.contains(e.target)) {
            menu.classList.remove('active');
        }
    });
}

// Notifications
function initNotifications() {
    // Check for notifications every 30 seconds
    if (document.querySelector('[data-user-id]')) {
        checkNotifications();
        App.notificationInterval = setInterval(checkNotifications, App.updateInterval);
    }
}

function checkNotifications() {
    const userId = document.querySelector('[data-user-id]')?.dataset.userId;
    if (!userId) return;
    
    fetch('/nov10-crisis/includes/ajax/get_notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationBadge(data.unread_count);
            if (data.new_notifications && data.new_notifications.length > 0) {
                showNewNotifications(data.new_notifications);
            }
        }
    })
    .catch(error => console.error('Error checking notifications:', error));
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.setAttribute('data-count', count > 99 ? '99+' : count);
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function showNewNotifications(notifications) {
    notifications.forEach(notification => {
        showToast(notification.title, notification.message, notification.priority);
    });
}

function showToast(title, message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} toast-notification`;
    toast.innerHTML = `
        <strong>${title}</strong><br>
        ${message}
    `;
    
    // Add to page
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 350px;';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Real-time Updates
function startRealTimeUpdates() {
    // Check for messages
    setInterval(checkMessages, App.updateInterval);
    
    // Check for task updates
    setInterval(checkTaskUpdates, App.updateInterval);
}

function checkMessages() {
    fetch('/nov10-crisis/includes/ajax/get_messages.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.unread_count > 0) {
                updateMessageBadge(data.unread_count);
            }
        })
        .catch(error => console.error('Error checking messages:', error));
}

function updateMessageBadge(count) {
    const badge = document.querySelector('.message-badge');
    if (badge) {
        badge.setAttribute('data-count', count > 99 ? '99+' : count);
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

function checkTaskUpdates() {
    // Implementation for checking task updates
    fetch('/nov10-crisis/includes/ajax/get_task_updates.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.updates) {
                // Handle task updates
                console.log('Task updates:', data.updates);
            }
        })
        .catch(error => console.error('Error checking tasks:', error));
}

// Modal Management
function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modalTarget;
            openModal(modalId);
        });
    });
    
    const modalCloses = document.querySelectorAll('.modal-close, [data-modal-close]');
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) closeModal(modal.id);
        });
    });
    
    // Close modal on background click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Form Handling
function initForms() {
    // AJAX form submission
    const ajaxForms = document.querySelectorAll('[data-ajax-form]');
    
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitFormAjax(this);
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function submitFormAjax(form) {
    const formData = new FormData(form);
    const action = form.action || form.dataset.action;
    const method = form.method || 'POST';
    
    // Show loading state
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner spinner-sm"></span> Processing...';
    }
    
    fetch(action, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Success', data.message || 'Operation completed successfully', 'success');
            
            // Redirect if specified
            if (data.redirect) {
                setTimeout(() => window.location.href = data.redirect, 1500);
            }
            
            // Reset form if specified
            if (data.reset) {
                form.reset();
            }
            
            // Reload if specified
            if (data.reload) {
                setTimeout(() => window.location.reload(), 1500);
            }
        } else {
            showToast('Error', data.message || 'An error occurred', 'danger');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showToast('Error', 'Network error occurred', 'danger');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
            showFieldError(input, 'This field is required');
        } else {
            input.classList.remove('is-invalid');
            removeFieldError(input);
        }
        
        // Email validation
        if (input.type === 'email' && input.value) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(input.value)) {
                isValid = false;
                input.classList.add('is-invalid');
                showFieldError(input, 'Please enter a valid email address');
            }
        }
    });
    
    return isValid;
}

function showFieldError(input, message) {
    let errorDiv = input.nextElementSibling;
    if (!errorDiv || !errorDiv.classList.contains('field-error')) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-danger';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }
    errorDiv.textContent = message;
}

function removeFieldError(input) {
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('field-error')) {
        errorDiv.remove();
    }
}

// Tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            showTooltip(this, this.dataset.tooltip);
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

function showTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip-popup';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        font-size: 0.875rem;
        z-index: 10000;
        pointer-events: none;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.top = `${rect.top - tooltip.offsetHeight - 10}px`;
    tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip-popup');
    if (tooltip) tooltip.remove();
}

// User Preferences
function updateUserPreference(key, value) {
    fetch('/nov10-crisis/includes/ajax/update_preferences.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ key, value })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to update preference:', data.message);
        }
    })
    .catch(error => console.error('Error updating preference:', error));
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true
    });
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
        }
    }
    
    return 'just now';
}

// Export functions for use in other scripts
window.App = App;
window.toggleTheme = toggleTheme;
window.openModal = openModal;
window.closeModal = closeModal;
window.showToast = showToast;
window.formatDate = formatDate;
window.formatTime = formatTime;
window.timeAgo = timeAgo;
