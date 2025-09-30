// Open coupon modal function
function openCouponModal(action) {
    if (action === 'add') {
        document.getElementById('modalTitle').textContent = 'Add New Coupon';
        document.getElementById('formAction').value = 'create';
        document.getElementById('couponId').value = '';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save Coupon';
        document.getElementById('couponForm').reset();
    }
    new bootstrap.Modal(document.getElementById('couponModal')).show();
}

// Edit coupon function
function editCoupon(coupon) {
    document.getElementById('modalTitle').textContent = 'Edit Coupon';
    document.getElementById('formAction').value = 'update';
    document.getElementById('couponId').value = coupon.id;
    document.getElementById('coupon_code').value = coupon.coupon_code;
    document.getElementById('percentage').value = coupon.percentage;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update Coupon';
    new bootstrap.Modal(document.getElementById('couponModal')).show();
}

// Delete coupon function
function deleteCoupon(id, code) {
    document.getElementById('deleteCouponId').value = id;
    document.getElementById('deleteCouponCode').textContent = code;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Reset form when modal is hidden
document.getElementById('couponModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('couponForm').reset();
    document.getElementById('modalTitle').textContent = 'Add New Coupon';
    document.getElementById('formAction').value = 'create';
    document.getElementById('couponId').value = '';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save Coupon';
});

// Auto-uppercase coupon code
document.getElementById('coupon_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});


