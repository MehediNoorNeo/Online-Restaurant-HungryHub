// Set current year - will be set in DOMContentLoaded event

// Cart functionality
let cartCount = 0;
window.window.cartItems = {}; // Object to store cart items with quantities (global)
let cartBadge = null;

// Load cart from localStorage on page load
function loadCartFromStorage() {
  const savedCart = localStorage.getItem('hungryHubCart');
  if (savedCart) {
    window.window.cartItems = JSON.parse(savedCart);
    updateCartCount();
    // Update all quantity displays after a short delay to ensure DOM is ready
    setTimeout(updateAllQuantityDisplays, 100);
  }
}

// Function to update all quantity displays on the page
function updateAllQuantityDisplays() {
  Object.keys(window.window.cartItems).forEach(itemName => {
    updateQuantityDisplay(itemName);
  });
}

// Save cart to localStorage
function saveCartToStorage() {
  localStorage.setItem('hungryHubCart', JSON.stringify(window.window.cartItems));
  // Dispatch custom event for cart updates
  window.dispatchEvent(new CustomEvent('cartUpdated'));
}

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', function() {
  // Set current year
  const yearElement = document.getElementById('year');
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }
  
  // Initialize cart badge element
  cartBadge = document.querySelector('.cart-badge');
  
  loadCartFromStorage();
  
  // Initialize cart click handler
  initializeCartClickHandler();
  
  // Initialize Bootstrap dropdowns
  initializeDropdowns();
  
  // Load featured food items on homepage
  if (document.getElementById('featured-food-items')) {
    loadFeaturedFoodItems();
  }
  
});

// Initialize Bootstrap dropdowns
function initializeDropdowns() {
  // Initialize all dropdowns
  const dropdownElements = document.querySelectorAll('.dropdown-toggle');
  dropdownElements.forEach(dropdownElement => {
    new bootstrap.Dropdown(dropdownElement);
  });
}

// Function to update cart count
function updateCartCount() {
  cartCount = Object.values(window.window.cartItems).reduce((total, quantity) => total + quantity, 0);
  if (cartBadge) {
    cartBadge.textContent = cartCount;
    
    // Add animation when count changes
    if (cartCount > 0) {
      cartBadge.classList.add('animate');
      setTimeout(() => {
        cartBadge.classList.remove('animate');
      }, 600);
    }
  }
}

// Function to increase quantity display (without adding to cart)
function increaseQuantity(itemName) {
  const qtyElement = document.getElementById(`qty-${itemName.replace(/\s+/g, '-')}`);
  if (qtyElement) {
    let currentQty = parseInt(qtyElement.textContent) || 0;
    currentQty++;
    qtyElement.textContent = currentQty;
    
    // Update the remove button state
    const removeBtn = document.getElementById(`remove-${itemName.replace(/\s+/g, '-')}`);
    if (removeBtn) {
      removeBtn.disabled = currentQty === 0;
    }
  }
}

// Function to decrease quantity display (without removing from cart)
function decreaseQuantity(itemName) {
  const qtyElement = document.getElementById(`qty-${itemName.replace(/\s+/g, '-')}`);
  if (qtyElement) {
    let currentQty = parseInt(qtyElement.textContent) || 0;
    if (currentQty > 0) {
      currentQty--;
      qtyElement.textContent = currentQty;
      
      // Update the remove button state
      const removeBtn = document.getElementById(`remove-${itemName.replace(/\s+/g, '-')}`);
      if (removeBtn) {
        removeBtn.disabled = currentQty === 0;
      }
    }
  }
}

// Function to get current quantity from display
function getCurrentQuantity(itemName) {
  const qtyElement = document.getElementById(`qty-${itemName.replace(/\s+/g, '-')}`);
  return qtyElement ? parseInt(qtyElement.textContent) || 0 : 0;
}

// Function to add item to cart with current quantity
function addToCart(itemName) {
  const quantity = getCurrentQuantity(itemName);
  
  if (quantity === 0) {
    showToastMessage('Please select a quantity first!', 'warning');
    return;
  }
  
  if (window.cartItems[itemName]) {
    window.cartItems[itemName] += quantity;
  } else {
    window.cartItems[itemName] = quantity;
  }
  updateCartCount();
  updateQuantityDisplay(itemName);
  showAddToCartMessage(itemName, quantity);
  saveCartToStorage(); // Save to localStorage
}

// Function to remove item from cart
function removeFromCart(itemName) {
  if (window.cartItems[itemName] && window.cartItems[itemName] > 0) {
    window.cartItems[itemName]--;
    if (window.cartItems[itemName] === 0) {
      delete window.cartItems[itemName];
    }
    updateCartCount();
    updateQuantityDisplay(itemName);
    showRemoveFromCartMessage(itemName);
    saveCartToStorage(); // Save to localStorage
  }
}

// Function to get item quantity in cart
function getItemQuantity(itemName) {
  return window.cartItems[itemName] || 0;
}

// Function to open food detail page
function openFoodDetail(foodId) {
  window.location.href = `food-detail.php?id=${foodId}`;
}

// Function to clear entire cart
function clearCart() {
  window.cartItems = {};
  updateCartCount();
  updateAllQuantityDisplays();
  saveCartToStorage();
}

// Function to show toast message
function showToastMessage(message, type = 'info') {
  // Create toast container if it doesn't exist
  let toastContainer = document.getElementById('toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
    toastContainer.style.zIndex = '9999';
    document.body.appendChild(toastContainer);
  }
  
  // Create toast element
  const toast = document.createElement('div');
  toast.className = `toast align-items-center text-white bg-${type} border-0`;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', 'assertive');
  toast.setAttribute('aria-atomic', 'true');
  
  const iconClass = type === 'warning' ? 'fa-exclamation-triangle' : 
                   type === 'success' ? 'fa-check-circle' : 
                   type === 'error' ? 'fa-times-circle' : 'fa-info-circle';
  
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        <i class="fas ${iconClass} me-2"></i>
        ${message}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;
  
  toastContainer.appendChild(toast);
  
  // Initialize and show toast
  const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
  bsToast.show();
  
  // Remove toast element after it's hidden
  toast.addEventListener('hidden.bs.toast', () => {
    if (toast.parentNode) {
      toast.parentNode.removeChild(toast);
    }
  });
}

// Function to show add to cart message
function showAddToCartMessage(itemName, quantity = 1) {
  const message = quantity > 1 ? 
    `${quantity}x ${itemName} added to cart!` : 
    `${itemName} added to cart!`;
  showToastMessage(message, 'success');
}

// Function to show remove from cart message
function showRemoveFromCartMessage(itemName) {
  // Create a temporary notification
  const notification = document.createElement('div');
  notification.className = 'remove-from-cart-notification';
  notification.innerHTML = `
    <div class="notification-content">
      <i class="fas fa-minus-circle text-warning me-2"></i>
      <span>${itemName} removed from cart!</span>
    </div>
  `;
  
  document.body.appendChild(notification);
  
  // Remove notification after 3 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 3000);
}

// Cart click handler
function initializeCartClickHandler() {
  const cartLink = document.querySelector('.cart-link');
  if (cartLink) {
    cartLink.addEventListener('click', function(e) {
      e.preventDefault();
      showCartModal();
    });
  }
}

// Show cart modal
window.showCartModal = function showCartModal() {
  const cartModalElement = document.getElementById('cartModal');
  console.log('Cart modal element:', cartModalElement);
  
  let cartModal = bootstrap.Modal.getInstance(cartModalElement);
  console.log('Existing modal instance:', cartModal);
  
  // If no instance exists, create one
  if (!cartModal) {
    console.log('Creating new modal instance');
    cartModal = new bootstrap.Modal(cartModalElement);
  } else {
    console.log('Reusing existing modal instance');
  }
  
  loadCartModalContent();
  cartModal.show();
  console.log('Modal should be visible now');
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

// Load cart modal content
window.loadCartModalContent = async function loadCartModalContent() {
  const cartContent = document.getElementById('cartContent');
  const cartTotal = document.getElementById('cartTotal');
  
  if (Object.keys(window.cartItems).length === 0) {
    cartContent.innerHTML = `
      <div class="cart-empty">
        <i class="fas fa-shopping-cart"></i>
        <h5>Your cart is empty</h5>
        <p>Add some delicious items to get started!</p>
      </div>
    `;
    cartTotal.textContent = '0.00';
    return;
  }

  // Get food items data for cart items
  const foodItemsData = await getFoodItemsData();
  
  let totalPrice = 0;
  let cartHTML = '';

  for (const [itemName, quantity] of Object.entries(window.cartItems)) {
    const foodItem = foodItemsData.find(item => item.name === itemName);
    if (foodItem) {
      const itemTotal = foodItem.price * quantity;
      totalPrice += itemTotal;
      const imagePath = `../${foodItem.imagePath}`;
      
      cartHTML += `
        <div class="cart-item" data-item-name="${itemName}">
          <img src="${imagePath}" alt="${foodItem.name}" class="cart-item-image" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0MFY0MEgyMFYyMFoiIGZpbGw9IiNEREQiLz4KPHBhdGggZD0iTTI1IDI1SDM1VjM1SDI1VjI1WiIgZmlsbD0iI0NDQyIvPgo8L3N2Zz4K'">
          <div class="cart-item-details">
            <div class="cart-item-name">${foodItem.name}</div>
            <div class="cart-item-category">${foodItem.category}</div>
            <div class="cart-item-price">
              <span class="currency-symbol">৳</span>${foodItem.price.toFixed(2)} each
            </div>
          </div>
          <div class="cart-item-controls">
            <div class="quantity-control">
              <button class="quantity-btn" onclick="updateCartItemQuantity('${itemName}', -1)" ${quantity <= 1 ? 'disabled' : ''}>
                <i class="fas fa-minus"></i>
              </button>
              <span class="quantity-display">${quantity}</span>
              <button class="quantity-btn" onclick="updateCartItemQuantity('${itemName}', 1)">
                <i class="fas fa-plus"></i>
              </button>
            </div>
            <button class="remove-item-btn" onclick="removeCartItem('${itemName}')" title="Remove item">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      `;
    }
  }

  cartContent.innerHTML = cartHTML;
  cartTotal.textContent = totalPrice.toFixed(2);
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

// Update cart item quantity
function updateCartItemQuantity(itemName, change) {
  const currentQuantity = window.cartItems[itemName] || 0;
  const newQuantity = currentQuantity + change;
  
  if (newQuantity <= 0) {
    removeCartItem(itemName);
  } else {
    window.cartItems[itemName] = newQuantity;
    updateCartCount();
    updateQuantityDisplay(itemName);
    saveCartToStorage();
    loadCartModalContent(); // Refresh modal content
  }
}

// Remove cart item completely
function removeCartItem(itemName) {
  delete window.cartItems[itemName];
  updateCartCount();
  updateQuantityDisplay(itemName);
  saveCartToStorage();
  loadCartModalContent(); // Refresh modal content
  showRemoveFromCartMessage(itemName);
}

// Proceed to checkout
async function proceedToCheckout() {
  if (Object.keys(window.cartItems).length === 0) {
    showToastMessage('Your cart is empty!', 'warning');
    return;
  }
  
  // Check if user is logged in
  try {
    const response = await fetch('../auth/check-auth.php');
    const data = await response.json();
    
    if (!data.loggedIn) {
      // Close modal
      const cartModal = bootstrap.Modal.getInstance(document.getElementById('cartModal'));
      cartModal.hide();
      
      // Show message and redirect to signin
      showToastMessage('Please sign in to proceed with checkout.', 'warning');
      setTimeout(() => {
        window.location.href = 'signin.php';
      }, 2000);
      return;
    }
  } catch (error) {
    console.error('Error checking authentication:', error);
    showToastMessage('Please sign in to proceed with checkout.', 'warning');
    setTimeout(() => {
      window.location.href = 'signin.php';
    }, 2000);
    return;
  }
  
  // Close modal
  const cartModal = bootstrap.Modal.getInstance(document.getElementById('cartModal'));
  cartModal.hide();
  
  // Redirect to checkout page
  window.location.href = 'checkout.php';
}

// Calculate cart total
async function calculateCartTotal() {
  const foodItemsData = await getFoodItemsData();
  let total = 0;
  
  for (const [itemName, quantity] of Object.entries(window.cartItems)) {
    const foodItem = foodItemsData.find(item => item.name === itemName);
    if (foodItem) {
      total += foodItem.price * quantity;
    }
  }
  
  return total;
}

// Function to load food items for category pages using API
async function loadFoodItems(category) {
  const container = document.getElementById('food-items');
  if (!container) return;

  // Show loading state
  container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

  try {
    // Fetch food items from API
    const response = await fetch(`../api/food-items-random.php?category=${encodeURIComponent(category)}`);
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error || 'Failed to fetch food items');
    }

    const categoryItems = data.data;
    container.innerHTML = '';

    if (categoryItems.length === 0) {
      container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No items found in this category.</p></div>';
      return;
    }

    // Get food IDs for review lookup
    const foodIds = categoryItems.map(item => item.id);
    
    // Fetch review data
    let reviewData = {};
    try {
      const reviewResponse = await fetch(`../api/food-reviews.php?ids=${foodIds.join(',')}`);
      const reviewResult = await reviewResponse.json();
      reviewData = reviewResult;
    } catch (reviewError) {
      console.warn('Could not fetch review data:', reviewError);
    }

    categoryItems.forEach((foodItem, index) => {
      const itemName = foodItem.name;
      const imagePath = `../${foodItem.imagePath}`;
      
      // Get review data for this food item
      const reviews = reviewData[foodItem.id] || { avg_rating: 0, review_count: 0 };
      const rating = reviews.avg_rating;
      const reviewCount = reviews.review_count;
      
      const foodCard = document.createElement('div');
      foodCard.className = 'col-md-6 col-lg-4 col-xl-3 mb-4';
      foodCard.innerHTML = `
        <div class="card food-card h-100 shadow-sm border-0" onclick="openFoodDetail(${foodItem.id})" style="cursor: pointer;">
          <div class="position-relative">
            <img src="${imagePath}" alt="${itemName}" class="card-img-top food-image" style="height: 220px; object-fit: cover;">
            <div class="position-absolute top-0 end-0 m-2">
              <span class="badge bg-warning text-dark">
                <i class="fas fa-star me-1"></i>${rating > 0 ? rating : '0.0'}
              </span>
            </div>
            <div class="position-absolute top-0 start-0 m-2">
              <span class="badge bg-success">Fresh</span>
            </div>
          </div>
          
          <div class="card-body d-flex flex-column">
            <h5 class="card-title fw-bold text-dark mb-2">${itemName}</h5>
            
            <!-- Rating Section -->
            <div class="d-flex align-items-center mb-3">
              <div class="rating-stars me-2">
                ${generateStarRating(rating)}
              </div>
              <small class="text-muted">(${reviewCount} reviews)</small>
            </div>
            
            <!-- Price Section -->
            <div class="mb-3">
              <span class="h5 text-warning fw-bold"><span class="currency-symbol">৳</span>${foodItem.price.toFixed(2)}</span>
              <small class="text-muted ms-2">per serving</small>
            </div>
            
            <!-- Quantity Controls -->
            <div class="mt-auto">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="btn-group" role="group" onclick="event.stopPropagation()">
                  <button class="btn btn-outline-secondary btn-sm" onclick="decreaseQuantity('${itemName}')" id="remove-${itemName.replace(/\s+/g, '-')}">
                    <i class="fas fa-minus"></i>
                  </button>
                  <span class="btn btn-light border" id="qty-${itemName.replace(/\s+/g, '-')}">0</span>
                  <button class="btn btn-outline-primary btn-sm" onclick="increaseQuantity('${itemName}')">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
              
              <button class="btn btn-warning w-100 fw-semibold" onclick="event.stopPropagation(); addToCart('${itemName}')">
                <i class="fas fa-shopping-cart me-2"></i>Add to Cart
              </button>
            </div>
          </div>
        </div>
      `;
      
      container.appendChild(foodCard);
    });
    
    // Update quantity displays after all items are loaded
    setTimeout(updateAllQuantityDisplays, 100);
    
  } catch (error) {
    console.error('Error loading food items:', error);
    container.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error loading food items. Please try again later.</p></div>';
  }
}

// Function to load featured food items for homepage using API
async function loadFeaturedFoodItems() {
  const container = document.getElementById('featured-food-items');
  if (!container) return;

  // Show loading state
  container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

  try {
    // Fetch random 12 items from API
    const response = await fetch('../api/food-items-random.php?random=true&limit=12');
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error || 'Failed to fetch featured food items');
    }

    const selectedItems = data.data;
    container.innerHTML = '';

    if (selectedItems.length === 0) {
      container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No featured items available.</p></div>';
      return;
    }

    // Get food IDs for review lookup
    const foodIds = selectedItems.map(item => item.id);
    
    // Fetch review data
    let reviewData = {};
    try {
      const reviewResponse = await fetch(`../api/food-reviews.php?ids=${foodIds.join(',')}`);
      const reviewResult = await reviewResponse.json();
      reviewData = reviewResult;
    } catch (reviewError) {
      console.warn('Could not fetch review data:', reviewError);
    }

    selectedItems.forEach((foodItem, index) => {
      const itemName = foodItem.name;
      const imagePath = `../${foodItem.imagePath}`;
      
      // Get review data for this food item
      const reviews = reviewData[foodItem.id] || { avg_rating: 0, review_count: 0 };
      const rating = reviews.avg_rating;
      const reviewCount = reviews.review_count;
      
      const foodCard = document.createElement('div');
      foodCard.className = 'col-md-6 col-lg-4 col-xl-3 mb-4';
      foodCard.innerHTML = `
        <div class="card food-card h-100 shadow-sm border-0" onclick="openFoodDetail(${foodItem.id})" style="cursor: pointer;">
          <div class="position-relative">
            <img src="${imagePath}" alt="${itemName}" class="card-img-top food-image" style="height: 220px; object-fit: cover;">
            <div class="position-absolute top-0 end-0 m-2">
              <span class="badge bg-warning text-dark">
                <i class="fas fa-star me-1"></i>${rating > 0 ? rating : '0.0'}
              </span>
            </div>
            <div class="position-absolute top-0 start-0 m-2">
              <span class="badge bg-success">Featured</span>
            </div>
          </div>
          
          <div class="card-body d-flex flex-column">
            <h5 class="card-title fw-bold text-dark mb-2">${itemName}</h5>
            
            <!-- Rating Section -->
            <div class="d-flex align-items-center mb-3">
              <div class="rating-stars me-2">
                ${generateStarRating(rating)}
              </div>
              <small class="text-muted">(${reviewCount} reviews)</small>
            </div>
            
            <!-- Price Section -->
            <div class="mb-3">
              <span class="h5 text-warning fw-bold"><span class="currency-symbol">৳</span>${foodItem.price.toFixed(2)}</span>
              <small class="text-muted ms-2">per serving</small>
            </div>
            
            <!-- Quantity Controls -->
            <div class="mt-auto">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="btn-group" role="group" onclick="event.stopPropagation()">
                  <button class="btn btn-outline-secondary btn-sm" onclick="removeFromCart('${itemName}')" id="remove-${itemName.replace(/\s+/g, '-')}">
                    <i class="fas fa-minus"></i>
                  </button>
                  <span class="btn btn-light border" id="qty-${itemName.replace(/\s+/g, '-')}">0</span>
                  <button class="btn btn-outline-primary btn-sm" onclick="increaseQuantity('${itemName}')">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
              
              <button class="btn btn-warning w-100 fw-semibold" onclick="event.stopPropagation(); addToCart('${itemName}')">
                <i class="fas fa-shopping-cart me-2"></i>Add to Cart
              </button>
            </div>
          </div>
        </div>
      `;
      
      container.appendChild(foodCard);
    });
    
    // Update quantity displays after all items are loaded
    setTimeout(updateAllQuantityDisplays, 100);
    
  } catch (error) {
    console.error('Error loading featured food items:', error);
    container.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error loading featured items. Please try again later.</p></div>';
  }
}

// Function to generate star rating HTML
function generateStarRating(rating) {
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 !== 0;
  const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
  
  let starsHTML = '';
  
  // Full stars
  for (let i = 0; i < fullStars; i++) {
    starsHTML += '<i class="fas fa-star text-warning"></i>';
  }
  
  // Half star
  if (hasHalfStar) {
    starsHTML += '<i class="fas fa-star-half-alt text-warning"></i>';
  }
  
  // Empty stars
  for (let i = 0; i < emptyStars; i++) {
    starsHTML += '<i class="far fa-star text-warning"></i>';
  }
  
  return starsHTML;
}

// Function to update quantity display
function updateQuantityDisplay(itemName) {
  const qtyElement = document.getElementById(`qty-${itemName.replace(/\s+/g, '-')}`);
  const removeButton = document.getElementById(`remove-${itemName.replace(/\s+/g, '-')}`);
  
  if (qtyElement) {
    // Show cart quantity, but don't change the display quantity
    const cartQuantity = getItemQuantity(itemName);
    // Only update if the display is currently 0 (initial state)
    if (parseInt(qtyElement.textContent) === 0) {
      qtyElement.textContent = cartQuantity;
    }
  }
  
  if (removeButton) {
    const currentDisplayQty = getCurrentQuantity(itemName);
    removeButton.disabled = currentDisplayQty === 0;
  }
}

// Function to load food items by category for dynamically created pages
async function loadFoodItemsByCategory(category) {
  const container = document.getElementById('food-items-container');
  if (!container) return;

  // Show loading state
  container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

  try {
    // Fetch food items from API
    const response = await fetch(`../api/food-items-random.php?category=${encodeURIComponent(category)}`);
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error || 'Failed to fetch food items');
    }

    const categoryItems = data.data;
    container.innerHTML = '';

    if (categoryItems.length === 0) {
      container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No items found in this category.</p></div>';
      return;
    }

    categoryItems.forEach((foodItem, index) => {
      const itemName = foodItem.name;
      const imagePath = `../${foodItem.imagePath}`;
      
      // Generate random rating between 4.0 and 5.0
      const rating = (4 + Math.random()).toFixed(1);
      const reviewCount = Math.floor(Math.random() * 50) + 10;
      
      const foodCard = document.createElement('div');
      foodCard.className = 'col-md-6 col-lg-4 col-xl-3 mb-4';
      foodCard.innerHTML = `
        <div class="card food-card h-100 shadow-sm border-0" onclick="openFoodDetail(${foodItem.id})" style="cursor: pointer;">
          <div class="position-relative">
            <img src="${imagePath}" alt="${itemName}" class="card-img-top food-image" style="height: 220px; object-fit: cover;">
            <div class="position-absolute top-0 end-0 m-2">
              <span class="badge bg-warning text-dark">
                <i class="fas fa-star me-1"></i>${rating > 0 ? rating : '0.0'}
              </span>
            </div>
            <div class="position-absolute top-0 start-0 m-2">
              <span class="badge bg-success">Fresh</span>
            </div>
          </div>
          
          <div class="card-body d-flex flex-column">
            <h5 class="card-title fw-bold text-dark mb-2">${itemName}</h5>
            
            <!-- Rating Section -->
            <div class="d-flex align-items-center mb-3">
              <div class="rating-stars me-2">
                ${generateStarRating(rating)}
              </div>
              <small class="text-muted">(${reviewCount} reviews)</small>
            </div>
            
            <!-- Price Section -->
            <div class="mb-3">
              <span class="h5 text-warning fw-bold"><span class="currency-symbol">৳</span>${foodItem.price.toFixed(2)}</span>
              <small class="text-muted ms-2">per serving</small>
            </div>
            
            <!-- Quantity Controls -->
            <div class="mt-auto">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="btn-group" role="group" onclick="event.stopPropagation()">
                  <button class="btn btn-outline-secondary btn-sm" onclick="decreaseQuantity('${itemName}')" id="remove-${itemName.replace(/\s+/g, '-')}">
                    <i class="fas fa-minus"></i>
                  </button>
                  <span class="btn btn-light border" id="qty-${itemName.replace(/\s+/g, '-')}">0</span>
                  <button class="btn btn-outline-primary btn-sm" onclick="increaseQuantity('${itemName}')">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
              
              <button class="btn btn-warning w-100 fw-semibold" onclick="event.stopPropagation(); addToCart('${itemName}')">
                <i class="fas fa-shopping-cart me-2"></i>Add to Cart
              </button>
            </div>
          </div>
        </div>
      `;
      
      container.appendChild(foodCard);
    });
    
    // Update quantity displays after all items are loaded
    setTimeout(updateAllQuantityDisplays, 100);
    
  } catch (error) {
    console.error('Error loading food items:', error);
    container.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error loading food items. Please try again later.</p></div>';
  }
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

