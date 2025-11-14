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
    initKeyboardShortcuts();
    initGlobalSearch();
    initBulkActions();
    initFavorites();
    
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

// Keyboard Shortcuts System
function initKeyboardShortcuts() {
    const shortcuts = {
        'Alt+D': () => window.location.href = getDashboardUrl(),
        'Alt+M': () => window.location.href = '/nov10-crisis/pages/common/messages.php',
        'Alt+N': () => window.location.href = '/nov10-crisis/pages/common/notifications.php',
        'Alt+P': () => window.location.href = '/nov10-crisis/pages/common/profile.php',
        'Alt+S': () => document.getElementById('global-search-input')?.focus(),
        'Alt+H': () => showShortcutHelp(),
        'Escape': () => closeAllModals(),
        'Ctrl+K': (e) => { e.preventDefault(); toggleGlobalSearch(); }
    };

    document.addEventListener('keydown', function(e) {
        const key = [];
        if (e.ctrlKey) key.push('Ctrl');
        if (e.altKey) key.push('Alt');
        if (e.shiftKey) key.push('Shift');
        key.push(e.key);
        
        const combo = key.join('+');
        
        if (shortcuts[combo]) {
            if (combo !== 'Escape') e.preventDefault();
            shortcuts[combo](e);
        }
    });
    
    // Add keyboard shortcut indicator
    addShortcutIndicator();
}

function getDashboardUrl() {
    const role = document.body.dataset.userRole || 'client';
    return `/nov10-crisis/pages/${role}/dashboard.php`;
}

function showShortcutHelp() {
    const helpHTML = `
        <div class="shortcuts-help">
            <h3>Keyboard Shortcuts</h3>
            <div class="shortcut-list">
                <div><kbd>Alt+D</kbd> <span>Dashboard</span></div>
                <div><kbd>Alt+M</kbd> <span>Messages</span></div>
                <div><kbd>Alt+N</kbd> <span>Notifications</span></div>
                <div><kbd>Alt+P</kbd> <span>Profile</span></div>
                <div><kbd>Alt+S</kbd> <span>Focus Search</span></div>
                <div><kbd>Ctrl+K</kbd> <span>Toggle Search</span></div>
                <div><kbd>Alt+H</kbd> <span>Show this help</span></div>
                <div><kbd>Esc</kbd> <span>Close modals</span></div>
            </div>
        </div>
    `;
    showToast(helpHTML, 'info', 8000);
}

function addShortcutIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'shortcut-indicator';
    indicator.innerHTML = '<span>Press <kbd>Alt+H</kbd> for shortcuts</span>';
    indicator.onclick = showShortcutHelp;
    document.body.appendChild(indicator);
}

// Global Search System
function initGlobalSearch() {
    const searchBar = createGlobalSearchBar();
    document.body.appendChild(searchBar);
}

function createGlobalSearchBar() {
    const searchContainer = document.createElement('div');
    searchContainer.id = 'global-search-container';
    searchContainer.className = 'global-search-container hidden';
    searchContainer.innerHTML = `
        <div class="global-search-overlay" onclick="toggleGlobalSearch()"></div>
        <div class="global-search-box">
            <input type="text" id="global-search-input" placeholder="Search everywhere... (Ctrl+K)" autocomplete="off">
            <div id="global-search-results"></div>
        </div>
    `;
    
    setTimeout(() => {
        const input = searchContainer.querySelector('#global-search-input');
        if (input) {
            input.addEventListener('input', debounce(performGlobalSearch, 300));
            input.addEventListener('keydown', handleSearchNavigation);
        }
    }, 100);
    
    return searchContainer;
}

function toggleGlobalSearch() {
    const container = document.getElementById('global-search-container');
    if (container) {
        container.classList.toggle('hidden');
        if (!container.classList.contains('hidden')) {
            document.getElementById('global-search-input')?.focus();
        }
    }
}

function performGlobalSearch() {
    const query = document.getElementById('global-search-input')?.value;
    const resultsDiv = document.getElementById('global-search-results');
    
    if (!query || query.length < 2) {
        if (resultsDiv) resultsDiv.innerHTML = '';
        return;
    }
    
    if (resultsDiv) {
        resultsDiv.innerHTML = '<div class="search-loading">Searching...</div>';
    }
    
    fetch(`/nov10-crisis/includes/ajax/global_search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('Search error:', error);
            if (resultsDiv) {
                resultsDiv.innerHTML = '<div class="search-error">Search failed. Please try again.</div>';
            }
        });
}

function displaySearchResults(results) {
    const resultsDiv = document.getElementById('global-search-results');
    if (!resultsDiv) return;
    
    if (!results || results.length === 0) {
        resultsDiv.innerHTML = '<div class="search-no-results">No results found</div>';
        return;
    }
    
    let html = '<div class="search-results-list">';
    results.forEach((result, index) => {
        html += `
            <div class="search-result-item" data-index="${index}" onclick="window.location.href='${result.url}'">
                <div class="result-icon">${result.icon || '📄'}</div>
                <div class="result-content">
                    <div class="result-title">${result.title}</div>
                    <div class="result-type">${result.type}</div>
                    ${result.description ? `<div class="result-description">${result.description}</div>` : ''}
                </div>
            </div>
        `;
    });
    html += '</div>';
    resultsDiv.innerHTML = html;
}

function handleSearchNavigation(e) {
    const results = document.querySelectorAll('.search-result-item');
    if (results.length === 0) return;
    
    let currentIndex = -1;
    results.forEach((item, index) => {
        if (item.classList.contains('active')) {
            currentIndex = index;
        }
    });
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        currentIndex = (currentIndex + 1) % results.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        currentIndex = currentIndex <= 0 ? results.length - 1 : currentIndex - 1;
    } else if (e.key === 'Enter' && currentIndex >= 0) {
        e.preventDefault();
        results[currentIndex].click();
        return;
    } else {
        return;
    }
    
    results.forEach(item => item.classList.remove('active'));
    results[currentIndex]?.classList.add('active');
}

// Bulk Actions System
function initBulkActions() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('bulk-select-all')) {
            toggleSelectAll(e.target);
        } else if (e.target.classList.contains('bulk-action-btn')) {
            executeBulkAction(e.target);
        }
    });
}

function toggleSelectAll(checkbox) {
    const container = checkbox.closest('table') || checkbox.closest('.list-container');
    const checkboxes = container?.querySelectorAll('.bulk-item-checkbox');
    checkboxes?.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActionBar();
}

function updateBulkActionBar() {
    const checked = document.querySelectorAll('.bulk-item-checkbox:checked').length;
    const bar = document.querySelector('.bulk-action-bar');
    
    if (checked > 0) {
        if (!bar) {
            createBulkActionBar(checked);
        } else {
            bar.querySelector('.bulk-count').textContent = `${checked} selected`;
            bar.style.display = 'flex';
        }
    } else {
        bar?.style.display = 'none';
    }
}

function createBulkActionBar(count) {
    const bar = document.createElement('div');
    bar.className = 'bulk-action-bar';
    bar.innerHTML = `
        <div class="bulk-info">
            <span class="bulk-count">${count} selected</span>
        </div>
        <div class="bulk-actions">
            <button class="btn btn-sm btn-primary bulk-action-btn" data-action="export">Export</button>
            <button class="btn btn-sm btn-danger bulk-action-btn" data-action="delete">Delete</button>
            <button class="btn btn-sm btn-light" onclick="clearBulkSelection()">Clear</button>
        </div>
    `;
    document.querySelector('.main-content')?.prepend(bar);
}

function executeBulkAction(btn) {
    const action = btn.dataset.action;
    const selected = Array.from(document.querySelectorAll('.bulk-item-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selected.length === 0) return;
    
    if (action === 'export') {
        exportSelectedItems(selected);
    } else if (action === 'delete') {
        if (confirm(`Delete ${selected.length} items?`)) {
            deleteSelectedItems(selected);
        }
    }
}

function exportSelectedItems(ids) {
    const url = `/nov10-crisis/includes/ajax/export_items.php?ids=${ids.join(',')}`;
    window.open(url, '_blank');
}

function clearBulkSelection() {
    document.querySelectorAll('.bulk-item-checkbox').forEach(cb => cb.checked = false);
    document.querySelector('.bulk-select-all')?.checked = false;
    updateBulkActionBar();
}

// Favorites/Bookmarks System
function initFavorites() {
    loadFavorites();
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('favorite-toggle') || e.target.closest('.favorite-toggle')) {
            toggleFavorite(e.target.closest('.favorite-toggle'));
        }
    });
}

function loadFavorites() {
    const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    App.favorites = favorites;
    updateFavoriteButtons();
}

function toggleFavorite(btn) {
    const url = btn.dataset.url || window.location.pathname;
    const title = btn.dataset.title || document.title;
    const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    
    const index = favorites.findIndex(f => f.url === url);
    
    if (index >= 0) {
        favorites.splice(index, 1);
        btn.classList.remove('active');
        showToast('Removed from favorites', 'success');
    } else {
        favorites.push({ url, title, timestamp: Date.now() });
        btn.classList.add('active');
        showToast('Added to favorites', 'success');
    }
    
    localStorage.setItem('favorites', JSON.stringify(favorites));
    App.favorites = favorites;
}

function updateFavoriteButtons() {
    const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    const currentUrl = window.location.pathname;
    
    document.querySelectorAll('.favorite-toggle').forEach(btn => {
        const url = btn.dataset.url || currentUrl;
        if (favorites.some(f => f.url === url)) {
            btn.classList.add('active');
        }
    });
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
    toggleGlobalSearch(); // Close search if open
}

// Export functions for use in other scripts
window.App = App;
window.toggleTheme = toggleTheme;
window.openModal = openModal;
window.closeModal = closeModal;
window.toggleGlobalSearch = toggleGlobalSearch;
window.clearBulkSelection = clearBulkSelection;
window.updateBulkActionBar = updateBulkActionBar;
window.showToast = showToast;
window.formatDate = formatDate;
window.formatTime = formatTime;
window.timeAgo = timeAgo;
