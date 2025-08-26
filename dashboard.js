// Dashboard utilities and helper functions

class DashboardUtils {
    constructor() {
        this.apiBaseUrl = 'api.php';
        this.updateInterval = 30000; // 30 seconds
        this.platforms = ['shopee', 'lazada', 'tiktok'];
    }

    // Format currency
    formatCurrency(amount) {
        return `₿${parseInt(amount).toLocaleString()}`;
    }

    // Format numbers
    formatNumber(num) {
        return parseInt(num).toLocaleString();
    }

    // Format time ago
    timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInMinutes = Math.floor((now - date) / (1000 * 60));

        if (diffInMinutes < 1) return 'เมื่อสักครู่';
        if (diffInMinutes < 60) return `${diffInMinutes} นาทีที่แล้ว`;
        if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)} ชั่วโมงที่แล้ว`;
        return `${Math.floor(diffInMinutes / 1440)} วันที่แล้ว`;
    }

    // API call wrapper
    async apiCall(endpoint, params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            const url = `${this.apiBaseUrl}?${queryString}`;
            
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    }

    // Get platform data
    async getPlatformData(platform) {
        return await this.apiCall('', {
            action: 'summary',
            platform: platform
        });
    }

    // Get all platforms data
    async getAllPlatformsData() {
        const promises = this.platforms.map(platform => 
            this.getPlatformData(platform).catch(error => {
                console.error(`Error fetching ${platform} data:`, error);
                return { sales: 0, orders: 0, top_products: [] };
            })
        );
        
        const results = await Promise.all(promises);
        
        return {
            shopee: results[0],
            lazada: results[1],
            tiktok: results[2]
        };
    }

    // Show notification
    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Hide and remove notification
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }

    // Local storage helpers
    setStorage(key, value) {
        localStorage.setItem(`dashboard_${key}`, JSON.stringify(value));
    }

    getStorage(key) {
        const item = localStorage.getItem(`dashboard_${key}`);
        return item ? JSON.parse(item) : null;
    }

    // Generate random data for demo
    generateRandomSalesData(base = 1000, variation = 500) {
        return base + Math.floor(Math.random() * variation);
    }

    // Calculate growth percentage
    calculateGrowth(current, previous) {
        if (previous === 0) return 0;
        return ((current - previous) / previous * 100).toFixed(1);
    }

    // Validate platform
    isValidPlatform(platform) {
        return this.platforms.includes(platform);
    }

    // Get platform color
    getPlatformColor(platform) {
        const colors = {
            shopee: '#EE4D2D',
            lazada: '#0F156D',
            tiktok: '#FF0050'
        };
        return colors[platform] || '#6B7280';
    }

    // Get platform icon
    getPlatformIcon(platform) {
        const icons = {
            shopee: 'fab fa-shopify',
            lazada: 'fas fa-shopping-bag',
            tiktok: 'fab fa-tiktok'
        };
        return icons[platform] || 'fas fa-store';
    }

    // Initialize real-time updates
    initRealTimeUpdates(callback, interval = this.updateInterval) {
        // Initial update
        callback();
        
        // Set up interval
        return setInterval(callback, interval);
    }

    // Debounce function
    debounce(func, wait) {
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

    // Loading state management
    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = '<div class="loading-spinner mx-auto"></div>';
        }
    }

    hideLoading(elementId, content) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = content;
        }
    }

    // Error handling
    handleError(error, context = 'Unknown') {
        console.error(`Error in ${context}:`, error);
        this.showNotification(`เกิดข้อผิดพลาด: ${error.message}`, 'error');
    }

    // Check connection status
    async checkConnectionStatus() {
        try {
            const response = await fetch('api.php?action=ping');
            return response.ok;
        } catch (error) {
            return false;
        }
    }

    // Export data to CSV
    exportToCSV(data, filename) {
        const csvContent = "data:text/csv;charset=utf-8," 
            + data.map(row => Object.values(row).join(",")).join("\n");
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `${filename}_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Format date for Thai locale
    formatThaiDate(date) {
        return new Date(date).toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // Get greeting based on time
    getTimeGreeting() {
        const hour = new Date().getHours();
        if (hour < 12) return 'สวัสดีตอนเช้า';
        if (hour < 17) return 'สวัสดีตอนบ่าย';
        return 'สวัสดีตอนเย็น';
    }
}

// Initialize dashboard utilities
const dashboardUtils = new DashboardUtils();

// Global functions for backward compatibility
function formatCurrency(amount) {
    return dashboardUtils.formatCurrency(amount);
}

function formatNumber(num) {
    return dashboardUtils.formatNumber(num);
}

function showNotification(message, type = 'success') {
    return dashboardUtils.showNotification(message, type);
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DashboardUtils;
}
