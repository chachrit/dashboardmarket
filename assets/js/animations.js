// Modern Dashboard Animations and Effects
class DashboardAnimations {
    constructor() {
        this.audioContext = null;
        this.notifications = [];
        this.init();
    }

    init() {
        this.initAudioContext();
        this.createNotificationContainer();
        this.setupVisibilityChange();
        this.initRealTimeFeatures();
    }

    // Check if Web Audio API is supported and initialize
    initAudioContext() {
        try {
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
            if (window.AudioContext) {
                // Only create context when user interacts (to comply with browser policies)
                document.addEventListener('click', () => {
                    if (!this.audioContext) {
                        this.audioContext = new AudioContext();
                    }
                }, { once: true });
            }
        } catch (e) {
            console.log('Web Audio API not supported:', e);
        }
    }

    // Enhanced sound generation with different tones
    generateTone(frequency, duration, type = 'sine') {
        if (!this.audioContext) return;
        
        try {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.type = type;
            oscillator.frequency.setValueAtTime(frequency, this.audioContext.currentTime);
            
            // Create envelope
            gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.3, this.audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration);
            
            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + duration);
            
            return oscillator;
        } catch (e) {
            console.log('Error generating tone:', e);
        }
    }

    // Play success sound sequence
    playSuccessSound() {
        if (!this.audioContext) return;
        
        const notes = [523.25, 659.25, 783.99]; // C5, E5, G5
        notes.forEach((freq, index) => {
            setTimeout(() => {
                this.generateTone(freq, 0.2, 'triangle');
            }, index * 100);
        });
    }

    // Play error sound
    playErrorSound() {
        if (!this.audioContext) return;
        
        this.generateTone(220, 0.3, 'sawtooth');
        setTimeout(() => {
            this.generateTone(196, 0.3, 'sawtooth');
        }, 200);
    }

    // Play notification sound for new orders
    playNewOrderSound() {
        if (!this.audioContext) return;
        
        // Cheerful ascending melody
        const melody = [440, 554.37, 659.25, 880]; // A4, C#5, E5, A5
        melody.forEach((freq, index) => {
            setTimeout(() => {
                this.generateTone(freq, 0.15, 'triangle');
            }, index * 120);
        });
    }

    // Update the original playNotificationSound to use new methods
    playNotificationSound(type = 'success') {
        switch(type) {
            case 'success':
                this.playSuccessSound();
                break;
            case 'error':
                this.playErrorSound();
                break;
            case 'newOrder':
                this.playNewOrderSound();
                break;
            default:
                this.playSuccessSound();
        }
    }

    // Enhanced number animation with easing
    animateNumber(element, targetValue, prefix = '', duration = 1000) {
        if (!element) return;
        
        const startValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
        const difference = targetValue - startValue;
        const startTime = performance.now();
        
        // Add loading animation
        element.classList.add('loading');
        
        const updateNumber = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function (ease-out cubic)
            const eased = 1 - Math.pow(1 - progress, 3);
            
            const currentValue = Math.round(startValue + (difference * eased));
            element.textContent = prefix + currentValue.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            } else {
                element.classList.remove('loading');
                // Add success animation
                element.classList.add('success-bounce');
                setTimeout(() => element.classList.remove('success-bounce'), 600);
            }
        };
        
        requestAnimationFrame(updateNumber);
    }

    // Easing function
    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    // Show notification toast
    showNotification(message, type = 'info', duration = 4000) {
        const container = this.getNotificationContainer();
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="${icons[type] || icons.info} text-xl mr-3"></i>
                <span class="font-medium">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-white/80 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        container.appendChild(notification);
        
        // Auto remove notification
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, duration);
        
        return notification;
    }

    getNotificationContainer() {
        let container = document.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    }

    getNotificationClasses(type) {
        const classes = {
            success: 'bg-green-100 border border-green-400 text-green-700',
            warning: 'bg-yellow-100 border border-yellow-400 text-yellow-700',
            error: 'bg-red-100 border border-red-400 text-red-700',
            info: 'bg-blue-100 border border-blue-400 text-blue-700',
            newOrder: 'bg-purple-100 border border-purple-400 text-purple-700'
        };
        return classes[type] || classes.info;
    }

    getNotificationIcon(type) {
        const icons = {
            success: '<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            warning: '<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            error: '<svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            newOrder: '<svg class="h-5 w-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>'
        };
        return icons[type] || icons.info;
    }

    createNotificationContainer() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
        }
    }

    // Animate elements on scroll
    setupScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fadeIn');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
    }

    // Handle visibility change for audio context
    setupVisibilityChange() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (this.audioContext && this.audioContext.state === 'running') {
                    this.audioContext.suspend();
                }
            } else {
                if (this.audioContext && this.audioContext.state === 'suspended') {
                    this.audioContext.resume();
                }
            }
        });
    }

    // Add shake animation to element
    shakeElement(element) {
        if (!element) return;
        
        element.classList.add('animate-shake');
        setTimeout(() => {
            element.classList.remove('animate-shake');
        }, 1000);
    }

    // Highlight element with glow effect
    highlightElement(element, duration = 2000) {
        if (!element) return;
        
        element.classList.add('animate-glow');
        setTimeout(() => {
            element.classList.remove('animate-glow');
        }, duration);
    }

    // Particle effect for celebrations
    createParticles(element, count = 20) {
        const rect = element.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;

        for (let i = 0; i < count; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.cssText = `
                position: fixed;
                width: 4px;
                height: 4px;
                background: ${this.getRandomColor()};
                border-radius: 50%;
                pointer-events: none;
                z-index: 9999;
                left: ${centerX}px;
                top: ${centerY}px;
            `;

            document.body.appendChild(particle);

            const angle = (Math.PI * 2 * i) / count;
            const velocity = 100 + Math.random() * 50;
            const gravity = 200;
            const life = 1 + Math.random();

            let x = 0, y = 0;
            let vx = Math.cos(angle) * velocity;
            let vy = Math.sin(angle) * velocity;
            
            const startTime = Date.now();
            
            const animate = () => {
                const elapsed = (Date.now() - startTime) / 1000;
                
                if (elapsed > life) {
                    particle.remove();
                    return;
                }
                
                x = vx * elapsed;
                y = vy * elapsed + 0.5 * gravity * elapsed * elapsed;
                
                particle.style.transform = `translate(${x}px, ${y}px)`;
                particle.style.opacity = Math.max(0, 1 - elapsed / life);
                
                requestAnimationFrame(animate);
            };
            
            requestAnimationFrame(animate);
        }
    }

    // Create mouse follow particles
    createMouseParticle(x, y) {
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        const color = colors[Math.floor(Math.random() * colors.length)];
        
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = x + 'px';
        particle.style.top = y + 'px';
        particle.style.width = '4px';
        particle.style.height = '4px';
        particle.style.backgroundColor = color;
        particle.style.boxShadow = `0 0 10px ${color}`;
        
        document.body.appendChild(particle);
        
        // Remove particle after animation
        setTimeout(() => {
            if (particle.parentNode) {
                particle.parentNode.removeChild(particle);
            }
        }, 3000);
    }

    // Create background particles
    createParticleBackground() {
        const particleCount = 50;
        const container = document.createElement('div');
        container.style.position = 'fixed';
        container.style.top = '0';
        container.style.left = '0';
        container.style.width = '100%';
        container.style.height = '100%';
        container.style.pointerEvents = 'none';
        container.style.zIndex = '1';
        container.style.opacity = '0.3';
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.style.position = 'absolute';
            particle.style.width = Math.random() * 3 + 1 + 'px';
            particle.style.height = particle.style.width;
            particle.style.backgroundColor = '#3B82F6';
            particle.style.borderRadius = '50%';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            
            // Animate particle
            particle.style.animation = `float ${Math.random() * 20 + 10}s linear infinite`;
            particle.style.animationDelay = Math.random() * 20 + 's';
            
            container.appendChild(particle);
        }
        
        document.body.appendChild(container);
    }

    getRandomColor() {
        const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#f0932b', '#eb4d4b', '#6c5ce7', '#a29bfe'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    // Loading state management
    showLoading(element) {
        if (element) {
            element.classList.add('loading');
        }
    }

    hideLoading(element) {
        if (element) {
            element.classList.remove('loading');
        }
    }

    // Error state animation
    showError(element) {
        if (element) {
            element.classList.add('error-shake');
            setTimeout(() => {
                element.classList.remove('error-shake');
            }, 600);
        }
    }

    // Success state animation
    showSuccess(element) {
        if (element) {
            element.classList.add('success-bounce');
            setTimeout(() => {
                element.classList.remove('success-bounce');
            }, 600);
        }
    }

    // Animate list items with stagger
    animateListItems(container, itemSelector = '.animate-item') {
        const items = container.querySelectorAll(itemSelector);
        items.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('animate-slideInRight');
        });
    }

    // Typewriter effect
    typeWriter(element, text, speed = 50) {
        element.innerHTML = '';
        let i = 0;
        
        const type = () => {
            if (i < text.length) {
                element.innerHTML += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        };
        
        type();
    }

    // Stagger animations for lists
    staggerAnimation(elements, delay = 100) {
        elements.forEach((element, index) => {
            setTimeout(() => {
                element.classList.add('animate-fadeIn');
            }, index * delay);
        });
    }

    // Real-time dashboard updates with smart refresh
    startRealTimeUpdates(interval = 15000) {
        let updateCounter = 0;
        
        const updateData = () => {
            updateCounter++;
            
            // Every 5th update, force reload from API
            if (updateCounter % 5 === 0) {
                window.loadAllPlatformsData && window.loadAllPlatformsData();
            } else {
                // Simulate minor updates for more dynamic feel
                this.simulateMinorUpdates();
            }
        };
        
        // Initial update
        setTimeout(updateData, 2000);
        
        // Set up periodic updates
        return setInterval(updateData, interval);
    }

    // Simulate realistic minor updates
    simulateMinorUpdates() {
        if (!window.salesData) return;
        
        const platforms = ['shopee', 'lazada', 'tiktok'];
        let hasUpdates = false;
        
        platforms.forEach(platform => {
            // 25% chance of update per platform
            if (Math.random() > 0.75) {
                const salesIncrease = Math.floor(Math.random() * 2000) + 300;
                const ordersIncrease = Math.floor(Math.random() * 4) + 1;
                
                // Store previous values for comparison
                const prevSales = window.salesData[platform].sales;
                const prevOrders = window.salesData[platform].orders;
                
                window.salesData[platform].sales += salesIncrease;
                window.salesData[platform].orders += ordersIncrease;
                
                // Trigger notifications and animations
                if (window.animations) {
                    window.animations.playNotificationSound('newOrder');
                    window.animations.showNotification(
                        `🎉 ออเดอร์ใหม่จาก ${platform.toUpperCase()}! +₿${salesIncrease.toLocaleString()}`,
                        'success',
                        5000
                    );
                    
                    // Animate the platform card
                    const platformCard = document.querySelector(`#${platform}Sales`)?.closest('.platform-card');
                    if (platformCard) {
                        window.animations.shakeElement(platformCard);
                        window.animations.highlightElement(platformCard);
                    }
                }
                
                hasUpdates = true;
            }
        });
        
        // Update dashboard if there were changes
        if (hasUpdates && window.updateDashboardWithAnimations) {
            window.updateDashboardWithAnimations();
            this.updateRecentActivity();
        }
    }

    // Update recent activity with new items
    updateRecentActivity() {
        const activityContainer = document.getElementById('recentActivity');
        if (!activityContainer || !window.salesData) return;
        
        const platforms = ['shopee', 'lazada', 'tiktok'];
        const platformData = {
            shopee: { name: 'Shopee', icon: 'fab fa-shopify', color: 'shopee' },
            lazada: { name: 'Lazada', icon: 'fas fa-shopping-bag', color: 'lazada' },
            tiktok: { name: 'TikTok', icon: 'fab fa-tiktok', color: 'tiktok' }
        };
        
        const randomPlatform = platforms[Math.floor(Math.random() * platforms.length)];
        const platform = platformData[randomPlatform];
        const amount = Math.floor(Math.random() * 1500) + 200;
        const products = ['เสื้อยืด Basic', 'กางเกงยีนส์', 'รองเท้าผ้าใบ', 'เสื้อเชิ้ต', 'กระเป๋าสะพาย', 'หูฟัง Wireless', 'นาฬิกาข้อมือ'];
        const randomProduct = products[Math.floor(Math.random() * products.length)];
        
        const newActivity = document.createElement('div');
        newActivity.className = 'activity-item flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl border border-yellow-200 animate-bounceIn';
        newActivity.innerHTML = `
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-br from-${platform.color} to-${platform.color}-600 rounded-full flex items-center justify-center animate-float">
                    <i class="${platform.icon} text-white text-lg"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">ออเดอร์ใหม่จาก ${platform.name}</p>
                    <p class="text-gray-600 text-sm">สินค้า: ${randomProduct} • รหัส: #${randomPlatform.toUpperCase()}${String(Math.floor(Math.random() * 9999)).padStart(4, '0')}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="font-bold text-${platform.color} text-lg">₿${amount.toLocaleString()}</p>
                <p class="text-gray-500 text-sm">เมื่อสักครู่</p>
            </div>
        `;
        
        // Insert at the top
        activityContainer.insertBefore(newActivity, activityContainer.firstChild);
        
        // Keep only the latest 5 activities
        const activities = activityContainer.querySelectorAll('.activity-item');
        if (activities.length > 5) {
            for (let i = 5; i < activities.length; i++) {
                activities[i].remove();
            }
        }
    }

    // Enhanced visibility change handling
    handleVisibilityChange() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Page is hidden, reduce update frequency
                this.isPageHidden = true;
            } else {
                // Page is visible again, resume normal updates
                this.isPageHidden = false;
                // Immediately refresh data when user returns
                if (window.loadAllPlatformsData) {
                    setTimeout(() => window.loadAllPlatformsData(), 500);
                }
            }
        });
    }

    // Initialize all real-time features
    initRealTimeFeatures() {
        this.handleVisibilityChange();
        this.intervalId = this.startRealTimeUpdates();
        
        // Add connection status monitoring
        this.monitorConnection();
        
        // Add performance monitoring
        this.monitorPerformance();
    }

    // Monitor connection status
    monitorConnection() {
        window.addEventListener('online', () => {
            this.showNotification('🌐 การเชื่อมต่ออินเทอร์เน็ตกลับมาแล้ว', 'success');
            // Refresh data when coming back online
            if (window.loadAllPlatformsData) {
                setTimeout(() => window.loadAllPlatformsData(), 1000);
            }
        });
        
        window.addEventListener('offline', () => {
            this.showNotification('⚠️ การเชื่อมต่ออินเทอร์เน็ตหลุด - ใช้ข้อมูลแคช', 'warning', 0);
        });
    }

    // Basic performance monitoring
    monitorPerformance() {
        // Monitor for slow frames
        let lastTime = performance.now();
        const checkPerformance = (currentTime) => {
            const delta = currentTime - lastTime;
            if (delta > 100) { // Frame took longer than 100ms
                console.warn('Slow frame detected:', delta + 'ms');
            }
            lastTime = currentTime;
            requestAnimationFrame(checkPerformance);
        };
        requestAnimationFrame(checkPerformance);
    }

    // Cleanup method
    destroy() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        
        // Remove event listeners
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        window.removeEventListener('online', this.monitorConnection);
        window.removeEventListener('offline', this.monitorConnection);
        
        // Clear notifications
        const container = document.querySelector('.notification-container');
        if (container) {
            container.remove();
        }
    }
}

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardAnimations = new DashboardAnimations();
    
    // Setup scroll animations
    window.dashboardAnimations.setupScrollAnimations();
    
    // Add hover effects to cards
    document.querySelectorAll('.card-hover').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-8px)';
            card.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '';
        });
    });
});
