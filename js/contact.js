// Contact Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const contactForm = document.querySelector('form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            const name = document.querySelector('[name="name"]').value.trim();
            const email = document.querySelector('[name="email"]').value.trim();
            const subject = document.querySelector('[name="subject"]').value.trim();
            const message = document.querySelector('[name="message"]').value.trim();
            
            let errors = [];
            
            if (name.length < 2) {
                errors.push('Name must be at least 2 characters long');
            }
            
            if (!isValidEmail(email)) {
                errors.push('Please enter a valid email address');
            }
            
            if (subject.length < 5) {
                errors.push('Subject must be at least 5 characters long');
            }
            
            if (message.length < 10) {
                errors.push('Message must be at least 10 characters long');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                showErrors(errors);
            }
        });
    }
    
    // Email validation function
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Show validation errors
    function showErrors(errors) {
        // Remove existing error alerts
        const existingAlerts = document.querySelectorAll('.alert-error');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create error alert
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-error';
        
        errors.forEach(error => {
            const p = document.createElement('p');
            p.textContent = error;
            errorAlert.appendChild(p);
        });
        
        // Insert before form
        const form = document.querySelector('form');
        form.parentNode.insertBefore(errorAlert, form);
        
        // Scroll to errors
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Character counters
    const messageTextarea = document.querySelector('[name="message"]');
    if (messageTextarea) {
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.textAlign = 'right';
        counter.style.fontSize = '0.8rem';
        counter.style.color = 'var(--text-light)';
        counter.style.marginTop = '0.5rem';
        
        messageTextarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const length = messageTextarea.value.length;
            counter.textContent = `${length} characters (minimum 10)`;
            
            if (length < 10) {
                counter.style.color = '#e74c3c';
            } else if (length < 50) {
                counter.style.color = '#f39c12';
            } else {
                counter.style.color = 'var(--primary-color)';
            }
        }
        
        messageTextarea.addEventListener('input', updateCounter);
        updateCounter(); // Initialize counter
    }
    
    // Auto-resize textarea
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    // Interactive map placeholder (would be replaced with actual map integration)
    const contactInfo = document.querySelector('.contact-info');
    if (contactInfo) {
        const mapPlaceholder = document.createElement('div');
        mapPlaceholder.className = 'map-placeholder';
        mapPlaceholder.innerHTML = `
            <div style="background: var(--bg-light); padding: 2rem; border-radius: var(--radius); text-align: center;">
                <i class="fas fa-map-marker-alt fa-2x" style="color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h4>Our Location</h4>
                <p>123 Furniture Street, City, State 12345</p>
                <button class="btn btn-outline btn-sm" onclick="showMapModal()">View on Map</button>
            </div>
        `;
        contactInfo.appendChild(mapPlaceholder);
    }
});

// Map modal function
function showMapModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '10000';
    
    modal.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: var(--radius); max-width: 500px; width: 90%; text-align: center;">
            <h3>Our Location</h3>
            <div style="background: var(--bg-light); height: 200px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius); margin: 1rem 0;">
                <i class="fas fa-map fa-3x" style="color: var(--text-light);"></i>
            </div>
            <p><strong>HomeClone Furniture Store</strong><br>
            123 Furniture Street<br>
            City, State 12345</p>
            <button class="btn btn-primary" onclick="this.closest('.modal').remove()">Close</button>
        </div>
    `;
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    document.body.appendChild(modal);
}

// Business hours highlighting
function highlightBusinessHours() {
    const now = new Date();
    const day = now.getDay(); // 0 = Sunday, 1 = Monday, etc.
    const hour = now.getHours();
    
    const businessHours = document.querySelector('.contact-item:last-child');
    if (businessHours) {
        const isOpen = (day >= 1 && day <= 5 && hour >= 9 && hour < 18) || // Mon-Fri 9AM-6PM
                      (day === 6 && hour >= 10 && hour < 16); // Sat 10AM-4PM
        
        if (isOpen) {
            businessHours.style.background = '#e8f5e8';
            businessHours.style.borderLeft = '4px solid var(--primary-color)';
            businessHours.style.paddingLeft = 'calc(1rem - 4px)';
            
            const status = document.createElement('div');
            status.innerHTML = '<strong style="color: var(--primary-color);">✓ Currently Open</strong>';
            businessHours.querySelector('div').appendChild(status);
        } else {
            businessHours.style.background = '#fff3cd';
            businessHours.style.borderLeft = '4px solid #f39c12';
            businessHours.style.paddingLeft = 'calc(1rem - 4px)';
            
            const status = document.createElement('div');
            status.innerHTML = '<strong style="color: #856404;">● Currently Closed</strong>';
            businessHours.querySelector('div').appendChild(status);
        }
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', highlightBusinessHours);