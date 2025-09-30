// Card Payment Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeCardPayment();
});

function initializeCardPayment() {
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize card formatting
    initializeCardFormatting();
    
    // Initialize card type detection
    initializeCardTypeDetection();
    
    // Initialize billing address toggle
    initializeBillingAddressToggle();
    
    // Initialize form submission
    initializeFormSubmission();
    
    // Load order total
    loadOrderTotal();
}

// Form Validation
function initializeFormValidation() {
    const form = document.getElementById('cardPaymentForm');
    const inputs = form.querySelectorAll('input[required]');
    
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
    
    // Card number validation
    if (field.name === 'card_number' && value) {
        const cardRegex = /^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$/;
        if (!cardRegex.test(value.replace(/\s/g, ''))) {
            isValid = false;
            errorMessage = 'Please enter a valid 16-digit card number';
        } 
        // else {
        //     // Luhn algorithm validation
        //     if (!validateLuhn(value.replace(/\s/g, ''))) {
        //         isValid = false;
        //         errorMessage = 'Invalid card number';
        //     }
        // }
    }
    
    // Expiry date validation
    if (field.name === 'expiry_date' && value) {
        const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
        if (!expiryRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter expiry date in MM/YY format';
        } else {
            // Check if card is expired
            const [month, year] = value.split('/');
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear() % 100;
            const currentMonth = currentDate.getMonth() + 1;
            
            if (parseInt(year) < currentYear || 
                (parseInt(year) === currentYear && parseInt(month) < currentMonth)) {
                isValid = false;
                errorMessage = 'Card has expired';
            }
        }
    }
    
    // CVV validation
    if (field.name === 'cvv' && value) {
        const cvvRegex = /^\d{3,4}$/;
        if (!cvvRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid CVV (3-4 digits)';
        }
    }
    
    // Cardholder name validation
    if (field.name === 'cardholder_name' && value) {
        const nameRegex = /^[a-zA-Z\s]{2,50}$/;
        if (!nameRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid cardholder name (letters and spaces only)';
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

// Luhn Algorithm for card validation
function validateLuhn(cardNumber) {
    let sum = 0;
    let isEven = false;
    
    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber[i]);
        
        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        
        sum += digit;
        isEven = !isEven;
    }
    
    return sum % 10 === 0;
}

// Card Formatting
function initializeCardFormatting() {
    const cardNumberInput = document.getElementById('card_number');
    const expiryInput = document.getElementById('expiry_date');
    const cvvInput = document.getElementById('cvv');
    
    // Card number formatting
    cardNumberInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        this.value = value;
    });
    
    // Expiry date formatting
    expiryInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        this.value = value;
    });
    
    // CVV formatting
    cvvInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
}

// Card Type Detection
function initializeCardTypeDetection() {
    const cardNumberInput = document.getElementById('card_number');
    const cardTypeIcon = document.getElementById('cardTypeIcon');
    
    cardNumberInput.addEventListener('input', function() {
        const cardNumber = this.value.replace(/\s/g, '');
        const cardType = detectCardType(cardNumber);
        updateCardTypeIcon(cardType);
    });
}

function detectCardType(cardNumber) {
    if (cardNumber.startsWith('4')) {
        return 'visa';
    } else if (cardNumber.startsWith('5') || cardNumber.startsWith('2')) {
        return 'mastercard';
    } else if (cardNumber.startsWith('3')) {
        return 'amex';
    } else if (cardNumber.startsWith('6')) {
        return 'discover';
    }
    return 'generic';
}

function updateCardTypeIcon(cardType) {
    const cardTypeIcon = document.getElementById('cardTypeIcon');
    const icons = {
        visa: 'fab fa-cc-visa',
        mastercard: 'fab fa-cc-mastercard',
        amex: 'fab fa-cc-amex',
        discover: 'fab fa-cc-discover',
        generic: 'fas fa-credit-card'
    };
    
    cardTypeIcon.className = icons[cardType] || icons.generic;
    cardTypeIcon.classList.add(`card-type-${cardType}`);
}

// Billing Address Toggle
function initializeBillingAddressToggle() {
    const sameAsDeliveryCheckbox = document.getElementById('sameAsDelivery');
    const billingAddressFields = document.getElementById('billingAddressFields');
    const billingInputs = billingAddressFields.querySelectorAll('input');
    
    sameAsDeliveryCheckbox.addEventListener('change', function() {
        if (this.checked) {
            billingAddressFields.style.display = 'none';
            billingInputs.forEach(input => {
                input.removeAttribute('required');
                input.value = '';
            });
        } else {
            billingAddressFields.style.display = 'block';
            billingInputs.forEach(input => {
                input.setAttribute('required', 'required');
            });
        }
    });
}

// Form Submission
function initializeFormSubmission() {
    const form = document.getElementById('cardPaymentForm');
    const processPaymentBtn = document.getElementById('processPaymentBtn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate all fields
        const inputs = form.querySelectorAll('input[required]');
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
        
        // Show loading state
        processPaymentBtn.disabled = true;
        processPaymentBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing Payment...';
        
        // Submit form
        form.submit();
    });
}

// Load Order Total
async function loadOrderTotal() {
    try {
        const savedCart = localStorage.getItem('hungryHubCart');
        if (savedCart) {
            const cartItems = JSON.parse(savedCart);
            const subtotal = await calculateCartSubtotal(cartItems);
            const taxRate = 0.05;
            const deliveryFee = 50;
            const tax = subtotal * taxRate;
            const total = subtotal + tax + deliveryFee;
            
            document.getElementById('paymentTotal').textContent = `৳ ${total.toFixed(2)}`;
        }
    } catch (error) {
        console.error('Error loading order total:', error);
    }
}

// Calculate cart subtotal
async function calculateCartSubtotal(cartItems) {
    if (!cartItems || Object.keys(cartItems).length === 0) {
        return 0;
    }

    try {
        const response = await fetch('../api/food-items.php');
        const data = await response.json();
        const foodItemsData = data.success ? data.data : [];
        
        let subtotal = 0;
        for (const [itemName, quantity] of Object.entries(cartItems)) {
            const foodItem = foodItemsData.find(item => item.name === itemName);
            if (foodItem) {
                subtotal += foodItem.price * quantity;
            }
        }
        
        return subtotal;
    } catch (error) {
        console.error('Error calculating subtotal:', error);
        return 0;
    }
}

// Go back to checkout
function goBackToCheckout() {
    window.location.href = 'checkout.php';
}

// Utility Functions
function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
