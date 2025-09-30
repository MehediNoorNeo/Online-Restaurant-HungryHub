// Checkout Page JavaScript

// Global variable to store applied coupon
globalAppliedCoupon = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeCheckout();
});

function initializeCheckout() {
    // Initialize form validation
    initializeFormValidation();
    
    
    // Initialize coupon functionality
    initializeCouponSystem();
    
    // Initialize order total calculation
    initializeOrderTotals();
    
    // Initialize phone number formatting
    initializePhoneFormatting();
    
    
    // Initialize form submission
    initializeFormSubmission();
    
    // Initialize progress indicator
    initializeProgressIndicator();
    
    // Listen for cart updates from localStorage
    window.addEventListener('storage', async function(e) {
        if (e.key === 'hungryHubCart') {
            await loadCartData();
            await updateOrderTotals();
        }
    });
    
    // Listen for cart updates from other tabs/windows
    window.addEventListener('cartUpdated', async function() {
        await loadCartData();
        await updateOrderTotals();
    });
}

// Form Validation
function initializeFormValidation() {
    const form = document.getElementById('checkoutForm');
    const inputs = form.querySelectorAll('input[required], select[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Remove existing validation classes
    field.classList.remove('is-valid', 'is-invalid');
    
    // Remove existing feedback
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback, .valid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    // Phone validation (Bangladesh format)
    if (field.type === 'tel' && value) {
        const phoneRegex = /^(\+?88)?01[3-9]\d{8}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid Bangladesh phone number (01XXXXXXXXX)';
        }
    }
    
    
    // Apply validation result
    if (isValid) {
        field.classList.add('is-valid');
        if (value) {
            showFeedback(field, 'Looks good!', 'valid');
        }
    } else {
        field.classList.add('is-invalid');
        showFeedback(field, errorMessage, 'invalid');
    }
    
    return isValid;
}

function showFeedback(field, message, type) {
    const feedback = document.createElement('div');
    feedback.className = `${type}-feedback`;
    feedback.textContent = message;
    field.parentNode.appendChild(feedback);
}


// Coupon System
function initializeCouponSystem() {
    const applyCouponBtn = document.getElementById('applyCoupon');
    const couponCodeInput = document.getElementById('coupon_code');
    const couponMessage = document.getElementById('couponMessage');
    const couponRow = document.getElementById('couponRow');
    const couponDiscount = document.getElementById('couponDiscount');
    // Ensure hidden flag exists to signal server that coupon was applied
    let couponAppliedInput = document.getElementById('coupon_applied');
    const checkoutForm = document.getElementById('checkoutForm');
    if (!couponAppliedInput && checkoutForm) {
        couponAppliedInput = document.createElement('input');
        couponAppliedInput.type = 'hidden';
        couponAppliedInput.name = 'coupon_applied';
        couponAppliedInput.id = 'coupon_applied';
        couponAppliedInput.value = '0';
        checkoutForm.appendChild(couponAppliedInput);
    }
    
    // Store applied coupon data
    let appliedCoupon = null;
    
    applyCouponBtn.addEventListener('click', async function() {
        // clear previous message state
        couponMessage.textContent = '';
        couponMessage.className = 'mt-2';
        const code = couponCodeInput.value.trim().toUpperCase();
        
        if (!code) {
            showCouponMessage('Please enter a coupon code', 'error');
            return;
        }
        
        try {
            // Validate coupon with database
            const response = await fetch('../api/coupons.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ coupon_code: code, user_id: (window.__currentUserId || 0) })
            });
            
            const result = await response.json();
            
            if (result.success) {
                appliedCoupon = result.data;
                globalAppliedCoupon = appliedCoupon; // Store globally
                const subtotal = await calculateCartSubtotal();
                const discountAmount = (subtotal * appliedCoupon.percentage) / 100;
                
                // Apply coupon
                couponRow.style.display = 'flex';
                couponDiscount.textContent = `-৳ ${discountAmount.toFixed(2)}`;
                couponCodeInput.disabled = true;
                applyCouponBtn.disabled = true;
                applyCouponBtn.textContent = 'Applied';
                if (couponAppliedInput) couponAppliedInput.value = '1';
                
                showCouponMessage(`Coupon applied! You saved ৳ ${discountAmount.toFixed(2)} (${appliedCoupon.percentage}% off)`, 'success');
                
                // Update totals
                await updateOrderTotals();
            } else {
                // reset UI if validation fails
                if (couponAppliedInput) couponAppliedInput.value = '0';
                couponRow.style.display = 'none';
                couponDiscount.textContent = '';
                couponCodeInput.disabled = false;
                applyCouponBtn.disabled = false;
                applyCouponBtn.textContent = 'Apply Coupon';
                showCouponMessage(result.message || 'Invalid coupon code', 'error');
            }
        } catch (error) {
            console.error('Error validating coupon:', error);
            // reset UI on error so it does not look applied
            if (couponAppliedInput) couponAppliedInput.value = '0';
            couponRow.style.display = 'none';
            couponDiscount.textContent = '';
            couponCodeInput.disabled = false;
            applyCouponBtn.disabled = false;
            applyCouponBtn.textContent = 'Apply Coupon';
            showCouponMessage('Error validating coupon. Please try again.', 'error');
        }
    });
    
    // Get remove coupon button
    const removeCouponBtn = document.getElementById('removeCouponBtn');
    removeCouponBtn.addEventListener('click', function() {
        removeCoupon();
    });
    
    function removeCoupon() {
        appliedCoupon = null;
        globalAppliedCoupon = null; // Clear global variable
        couponRow.style.display = 'none';
        couponCodeInput.disabled = false;
        couponCodeInput.value = '';
        applyCouponBtn.disabled = false;
        applyCouponBtn.textContent = 'Apply Coupon';
        removeCouponBtn.style.display = 'none';
        couponMessage.textContent = '';
        couponMessage.className = 'mt-2';
        if (couponAppliedInput) couponAppliedInput.value = '0';
        
        // Update totals
        updateOrderTotals();
    }
    
    // Show remove button when coupon is applied
    const originalApplyCoupon = applyCouponBtn.addEventListener;
    
    function showCouponMessage(message, type) {
        couponMessage.textContent = message;
        couponMessage.className = `mt-2 ${type === 'success' ? 'coupon-success' : 'coupon-error'}`;
        
        // Show remove button if coupon is successfully applied
        if (type === 'success') {
            removeCouponBtn.style.display = 'inline-block';
        }
    }
}

// Order Totals Calculation
function initializeOrderTotals() {
    // Load cart data from localStorage
    loadCartData();
    updateOrderTotals();
}

// Load cart data from localStorage
async function loadCartData() {
    const savedCart = localStorage.getItem('hungryHubCart');
    if (savedCart) {
        window.cartItems = JSON.parse(savedCart);
        await loadCartItemsInSummary();
    } else {
        window.cartItems = {};
        showEmptyCartMessage();
    }
}

// Load cart items in order summary
async function loadCartItemsInSummary() {
    const cartItemsContainer = document.getElementById('cartItems');
    
    if (Object.keys(window.cartItems).length === 0) {
        showEmptyCartMessage();
        return;
    }

    // Get food items data for cart items
    const foodItemsData = await getFoodItemsData();
    
    let cartHTML = '';

    for (const [itemName, quantity] of Object.entries(window.cartItems)) {
        const foodItem = foodItemsData.find(item => item.name === itemName);
        if (foodItem) {
            const itemTotal = foodItem.price * quantity;
            const imagePath = `../${foodItem.imagePath}`;
            
            cartHTML += `
                <div class="cart-item" data-item-name="${itemName}">
                    <img src="${imagePath}" alt="${foodItem.name}" class="cart-item-image" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0MFY0MEgyMFYyMFoiIGZpbGw9IiNEREQiLz4KPHBhdGggZD0iTTI1IDI1SDM1VjM1SDI1VjI1WiIgZmlsbD0iI0NDQyIvPgo8L3N2Zz4K'">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${foodItem.name}</div>
                        <div class="cart-item-category">${foodItem.category}</div>
                        <div class="cart-item-price">
                            <span class="currency-symbol">৳</span> ${foodItem.price.toFixed(2)}
                        </div>
                    </div>
                    <div class="cart-item-controls">
                        <div class="quantity-control">
                            <span class="quantity-display">x${quantity}</span>
                        </div>
                        <div class="item-total-price">
                            <span class="total-currency-symbol">৳</span> ${itemTotal.toFixed(2)}
                        </div>
                    </div>
                </div>
            `;
        }
    }

    cartItemsContainer.innerHTML = cartHTML;
}

// Show empty cart message
function showEmptyCartMessage() {
    const cartItemsContainer = document.getElementById('cartItems');
    cartItemsContainer.innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
            <p>Your cart is empty</p>
            <a href="index.php" class="btn btn-outline-primary btn-sm">Continue Shopping</a>
        </div>
    `;
}

// Get food items data for cart
async function getFoodItemsData() {
    try {
        const response = await fetch('../api/food-items.php');
        const data = await response.json();
        return data.success ? data.data : [];
    } catch (error) {
        console.error('Error fetching food items:', error);
        return [];
    }
}

// Get category picture path
function getCategoryPicture(category) {
    const categoryMap = {
        'Burgers': 'burgers_pic.png',
        'Cake': 'cake_pic.png',
        'Desserts': 'desserts_pic.png',
        'Noodles': 'noodles_pic.png',
        'Pasta': 'pasta_pic.png',
        'Pure Veg': 'pureveg_pic.png',
        'Rolls': 'rolls_pic.png',
        'Salad': 'salad_pic.png',
        'Sandwich': 'sandwich_pic.png'
    };
    
    const pictureName = categoryMap[category] || 'default_pic.png';
    return `../assets/${pictureName}`;
}

// Refresh cart data and show modal
async function refreshCartAndShowModal() {
    // Refresh cart data from localStorage
    await loadCartData();
    await updateOrderTotals();
    
    // Show cart modal
    if (typeof showCartModal === 'function') {
        showCartModal();
    } else {
        // Fallback if showCartModal is not available
        window.location.href = 'index.php';
    }
}

async function updateOrderTotals() {
    // Calculate subtotal from actual cart data
    const subtotal = await calculateCartSubtotal();
    const taxRate = 0.05;
    const deliveryFee = 50;
    
    const tax = subtotal * taxRate;
    const couponDiscount = getCouponDiscount();
    const total = subtotal + tax + deliveryFee - couponDiscount;
    
    // Debug logging
    console.log('Order Totals Calculation:');
    console.log('Subtotal:', subtotal);
    console.log('Tax (5%):', tax);
    console.log('Delivery Fee:', deliveryFee);
    console.log('Coupon Discount:', couponDiscount);
    console.log('Total:', total);
    
    // Update display
    document.getElementById('subtotal').textContent = `৳ ${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `৳ ${tax.toFixed(2)}`;
    document.getElementById('deliveryFee').textContent = `৳ ${deliveryFee.toFixed(2)}`;
    document.getElementById('totalAmount').textContent = `৳ ${total.toFixed(2)}`;
    document.getElementById('finalTotal').textContent = `৳ ${total.toFixed(2)}`;
}

// Calculate subtotal from cart items
async function calculateCartSubtotal() {
    if (!window.cartItems || Object.keys(window.cartItems).length === 0) {
        return 0;
    }

    const foodItemsData = await getFoodItemsData();
    let subtotal = 0;

    for (const [itemName, quantity] of Object.entries(window.cartItems)) {
        const foodItem = foodItemsData.find(item => item.name === itemName);
        if (foodItem) {
            subtotal += foodItem.price * quantity;
        }
    }

    return subtotal;
}

function getCouponDiscount() {
    const couponDiscount = document.getElementById('couponDiscount');
    const couponRow = document.getElementById('couponRow');
    
    console.log('getCouponDiscount - couponDiscount:', couponDiscount);
    console.log('getCouponDiscount - couponRow:', couponRow);
    console.log('getCouponDiscount - couponRow.style.display:', couponRow ? couponRow.style.display : 'N/A');
    
    if (couponDiscount && couponRow && couponRow.style.display !== 'none') {
        const discountText = couponDiscount.textContent.replace('-৳', '').replace(',', '');
        const discountValue = parseFloat(discountText) || 0;
        console.log('getCouponDiscount - discountText:', discountText);
        console.log('getCouponDiscount - discountValue:', discountValue);
        return discountValue;
    }
    console.log('getCouponDiscount - returning 0');
    return 0;
}

// Phone Number Formatting
function initializePhoneFormatting() {
    const phoneInput = document.getElementById('phone');
    
    phoneInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        
        // Add +88 prefix if it starts with 01
        if (value.startsWith('01') && !value.startsWith('8801')) {
            value = '88' + value;
        }
        
        // Format as +88 01XXXXXXXXX
        if (value.length > 2) {
            value = value.substring(0, 3) + ' ' + value.substring(3);
        }
        
        this.value = value;
    });
}


// Form Submission
function initializeFormSubmission() {
    const form = document.getElementById('checkoutForm');
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const paymentMethodSelect = document.getElementById('payment_method');
    const cartDataInput = document.getElementById('cart_data');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate all fields
        const inputs = form.querySelectorAll('input[required], select[required]');
        let isFormValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isFormValid = false;
            }
        });
        
        if (!isFormValid) {
            showNotification('Please fix the errors in the form', 'error');
            return;
        }
        
        // Check if cart is empty
        if (!window.cartItems || Object.keys(window.cartItems).length === 0) {
            showNotification('Your cart is empty. Please add items to your cart.', 'error');
            return;
        }
        
        // Store cart data in hidden input
        cartDataInput.value = JSON.stringify(window.cartItems);
        
        // Ensure coupon code handling
        const couponCodeInput = document.getElementById('coupon_code');
        const enteredCoupon = (couponCodeInput.value || '').trim().toUpperCase();
        // If a code is typed but not applied, block submission and prompt user
        if (enteredCoupon && (!globalAppliedCoupon || globalAppliedCoupon.coupon_code !== enteredCoupon)) {
            showNotification('Please click "Apply Coupon" to validate your code before placing the order.', 'error');
            placeOrderBtn.disabled = false;
            placeOrderBtn.innerHTML = 'Place Order';
            return;
        }
        // If applied, ensure the applied code is submitted
        if (globalAppliedCoupon && globalAppliedCoupon.coupon_code) {
            couponCodeInput.value = globalAppliedCoupon.coupon_code;
            couponCodeInput.disabled = false; // Re-enable for form submission
            console.log('Coupon code set for submission:', globalAppliedCoupon.coupon_code);
        } else {
            console.log('No coupon applied for submission');
        }
        
        // Show loading state
        placeOrderBtn.disabled = true;
        placeOrderBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        // Submit form
        form.submit();
    });
}

// Clear cart after successful order
function clearCart() {
    localStorage.removeItem('hungryHubCart');
    window.cartItems = {};
    
    // Dispatch event to notify other components
    window.dispatchEvent(new CustomEvent('cartUpdated'));
}

// Utility Functions
function showNotification(message, type) {
    // Lazy-inject minimal styles once
    if (!document.getElementById('hh-alert-styles')) {
        const styles = document.createElement('style');
        styles.id = 'hh-alert-styles';
        styles.textContent = `
          .hh-alert-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
          .hh-alert{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.12);border-left:5px solid transparent;max-width:640px;min-width:320px;background:#fff;opacity:0;transform:translateY(-8px);transition:all .25s ease}
          .hh-alert.show{opacity:1;transform:translateY(0)}
          .hh-alert .hh-icon{width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center}
          .hh-alert .hh-text{flex:1;color:#334155;font-weight:500}
          .hh-alert .hh-close{background:transparent;border:0;color:#64748b;font-size:18px;line-height:1;cursor:pointer}
          .hh-alert.success{border-left-color:#16a34a;background:#ecfdf5}
          .hh-alert.success .hh-icon{color:#16a34a}
          .hh-alert.error{border-left-color:#dc2626;background:#fef2f2}
          .hh-alert.error .hh-icon{color:#dc2626}
        `;
        document.head.appendChild(styles);
    }

    // Create (or reuse) container
    let container = document.querySelector('.hh-alert-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'hh-alert-container';
        document.body.appendChild(container);
    }

    const isSuccess = type === 'success';
    const icon = isSuccess ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-triangle"></i>';

    const alert = document.createElement('div');
    alert.className = `hh-alert ${isSuccess ? 'success' : 'error'}`;
    alert.innerHTML = `
      <span class="hh-icon">${icon}</span>
      <div class="hh-text">${message}</div>
      <button class="hh-close" aria-label="Close">&times;</button>
    `;

    // Close handlers
    alert.querySelector('.hh-close').addEventListener('click', () => {
        alert.remove();
    });

    container.appendChild(alert);
    // Trigger animation
    requestAnimationFrame(() => alert.classList.add('show'));

    // Auto-remove after 4s
    setTimeout(() => {
        if (alert && alert.parentNode) {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 200);
        }
    }, 4000);
}



// Progress Indicator
function initializeProgressIndicator() {
    const form = document.getElementById('checkoutForm');
    const infoStep = document.getElementById('info-step');
    
    // Define the specific fields that need to be filled for the Information step
    const infoFields = [
        'full_name',
        'email', 
        'phone',
        'address1',
        'area',
        'payment_method'
    ];
    
    // Function to check if all information fields are filled
    function checkAllFieldsFilled() {
        let allFilled = true;
        
        infoFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                if (!field.value.trim()) {
                    allFilled = false;
                }
            }
        });
        
        return allFilled;
    }
    
    // Function to update progress indicator
    function updateProgressIndicator() {
        if (checkAllFieldsFilled()) {
            infoStep.classList.add('active');
        } else {
            infoStep.classList.remove('active');
        }
    }
    
    // Add event listeners to information fields
    infoFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.addEventListener('input', updateProgressIndicator);
            field.addEventListener('change', updateProgressIndicator);
            field.addEventListener('blur', updateProgressIndicator);
        }
    });
    
    // Also listen to payment method changes
    const paymentMethodSelect = document.getElementById('payment_method');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', updateProgressIndicator);
    }
    
    // Initial check
    updateProgressIndicator();
}
