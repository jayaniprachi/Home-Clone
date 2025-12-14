// Products Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Filter Toggle
    const filterToggle = document.getElementById('filter-toggle');
    const filterSidebar = document.getElementById('filter-sidebar');
    
    if (filterToggle && filterSidebar) {
        filterToggle.addEventListener('click', function() {
            filterSidebar.classList.toggle('active');
        });
    }
    
    // Price Range Filter
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    const priceDisplay = document.getElementById('price-display');
    
    if (minPriceInput && maxPriceInput && priceDisplay) {
        function updatePriceDisplay() {
            const min = minPriceInput.value || minPriceInput.min;
            const max = maxPriceInput.value || maxPriceInput.max;
            priceDisplay.textContent = `$${min} - $${max}`;
        }
        
        minPriceInput.addEventListener('input', updatePriceDisplay);
        maxPriceInput.addEventListener('input', updatePriceDisplay);
        updatePriceDisplay();
    }
    
    // Category Filter
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Uncheck other categories if this one is checked (for single selection)
            if (this.checked) {
                categoryCheckboxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });
    
    // Sort Select
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            document.getElementById('products-form').submit();
        });
    }
    
    // Apply Filters Button
    const applyFiltersBtn = document.getElementById('apply-filters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            document.getElementById('products-form').submit();
        });
    }
    
    // Clear Filters
    const clearFiltersBtn = document.getElementById('clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            // Reset all form inputs
            const form = document.getElementById('products-form');
            form.reset();
            form.submit();
        });
    }
    
    // Product Grid/List View Toggle
    const gridViewBtn = document.getElementById('grid-view');
    const listViewBtn = document.getElementById('list-view');
    const productsGrid = document.getElementById('products-grid');
    
    if (gridViewBtn && listViewBtn && productsGrid) {
        gridViewBtn.addEventListener('click', function() {
            productsGrid.classList.remove('list-view');
            gridViewBtn.classList.add('active');
            listViewBtn.classList.remove('active');
        });
        
        listViewBtn.addEventListener('click', function() {
            productsGrid.classList.add('list-view');
            listViewBtn.classList.add('active');
            gridViewBtn.classList.remove('active');
        });
    }
    
    // Wishlist Toggle
    const wishlistButtons = document.querySelectorAll('.btn-wishlist');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const isActive = this.classList.contains('active');
            
            makeAjaxRequest('ajax/toggle_wishlist.php', 'POST', {
                product_id: productId,
                action: isActive ? 'remove' : 'add'
            }, function(response) {
                if (response.success) {
                    this.classList.toggle('active');
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.className = this.classList.contains('active') ? 
                            'fas fa-heart' : 'far fa-heart';
                    }
                    
                    // Update wishlist count in header
                    const wishlistCount = document.getElementById('wishlist-count');
                    if (wishlistCount) {
                        wishlistCount.textContent = response.wishlist_count;
                    }
                    
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message, 'error');
                }
            }.bind(this));
        });
    });
    
    // Add to Cart
    const addToCartButtons = document.querySelectorAll('.btn-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            
            makeAjaxRequest('ajax/add_to_cart.php', 'POST', {
                product_id: productId,
                quantity: 1
            }, function(response) {
                if (response.success) {
                    // Update cart count in header
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = response.cart_count;
                    }
                    
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message, 'error');
                }
            });
        });
    });
    
    // Quick View Modal
    const quickViewButtons = document.querySelectorAll('.btn-quick-view');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            // Implement quick view functionality
            console.log('Quick view for product:', productId);
        });
    });
});

// Infinite Scroll (if implemented)
let isLoading = false;
let page = 1;
let hasMore = true;

function initInfiniteScroll() {
    window.addEventListener('scroll', function() {
        if (isLoading || !hasMore) return;
        
        const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
        
        if (scrollTop + clientHeight >= scrollHeight - 100) {
            loadMoreProducts();
        }
    });
}

function loadMoreProducts() {
    isLoading = true;
    
    // Show loading indicator
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading-indicator';
    loadingIndicator.textContent = 'Loading more products...';
    document.getElementById('products-grid').appendChild(loadingIndicator);
    
    // Load next page of products
    page++;
    const formData = new FormData(document.getElementById('products-form'));
    formData.append('page', page);
    
    makeAjaxRequest('products.php', 'POST', formData, function(response) {
        isLoading = false;
        loadingIndicator.remove();
        
        if (response.success && response.html) {
            document.getElementById('products-grid').innerHTML += response.html;
            hasMore = response.has_more;
        } else {
            hasMore = false;
        }
    });
}