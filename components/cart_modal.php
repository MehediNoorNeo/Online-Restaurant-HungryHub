<?php
// Cart Modal Component
// This file contains the HTML structure for the cart modal
// CSS is included separately in cssFiles/cart_modal.css
?>

<!-- Cart Modal -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cartModalLabel">
          <i class="fas fa-shopping-cart me-2"></i>
          Your Cart
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="cartContent">
          <!-- Cart items will be loaded here -->
        </div>
      </div>
      <div class="modal-footer">
        <div class="d-flex justify-content-between align-items-center w-100">
          <div class="cart-total">
            <strong>Total: <span class="currency-symbol">৳</span><span id="cartTotal">0.00</span></strong>
          </div>
          <div class="cart-actions">
            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
              <i class="fas fa-arrow-left me-1"></i>Continue Shopping
            </button>
            <button type="button" class="btn btn-primary" id="checkoutBtn" onclick="proceedToCheckout()">
              <i class="fas fa-credit-card me-1"></i>Checkout
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
