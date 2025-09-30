  <footer class="bg-dark text-light py-5">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-6">
          <div class="d-flex align-items-center mb-3">
            <i class="fas fa-utensils text-warning fs-3 me-3"></i>
            <h5 class="fw-bold mb-0">HungryHub</h5>
          </div>
          <p class="text-light-emphasis">Your cravings, delivered. Fast, fresh, and reliable online restaurant service.</p>
        </div>
        <div class="col-lg-6">
          <div class="row">
            <div class="col-md-4">
              <h6 class="fw-semibold mb-3">Quick Links</h6>
              <ul class="list-unstyled">
                <li><a href="index.php#menu" class="text-light-emphasis text-decoration-none">Menu</a></li>
                <li><a href="index.php#how-it-works" class="text-light-emphasis text-decoration-none">How it works</a></li>
                <li><a href="index.php#why" class="text-light-emphasis text-decoration-none">Why HungryHub</a></li>
                <li><a href="index.php#testimonials" class="text-light-emphasis text-decoration-none">Reviews</a></li>
              </ul>
            </div>
            <div class="col-md-4">
              <h6 class="fw-semibold mb-3">Support</h6>
              <ul class="list-unstyled">
                <li><a href="help.html" class="text-light-emphasis text-decoration-none">Help Center</a></li>
                <li><a href="contact.html" class="text-light-emphasis text-decoration-none">Contact Us</a></li>
                <li><a href="track.html" class="text-light-emphasis text-decoration-none">Track Order</a></li>
              </ul>
            </div>
            <div class="col-md-4">
              <h6 class="fw-semibold mb-3">Legal</h6>
              <ul class="list-unstyled">
                <li><a href="terms.html" class="text-light-emphasis text-decoration-none">Terms of Service</a></li>
                <li><a href="privacy.html" class="text-light-emphasis text-decoration-none">Privacy Policy</a></li>
                <li><a href="cookies.html" class="text-light-emphasis text-decoration-none">Cookie Policy</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <hr class="my-4 border-secondary">
      <div class="row align-items-center">
        <div class="col-md-6">
          <small class="text-light-emphasis">© <span id="year"></span> HungryHub — All rights reserved.</small>
        </div>
        <div class="col-md-6 text-md-end">
          <div class="d-flex gap-3 justify-content-md-end">
            <!-- <a href="#" class="text-warning fs-5"><i class="fab fa-facebook"></i></a>
            <a href="#" class="text-warning fs-5"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-warning fs-5"><i class="fab fa-instagram"></i></a>
            <a href="#" class="text-warning fs-5"><i class="fab fa-youtube"></i></a> -->
          </div>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom JavaScript -->
  <script src="../javaScript/script.js"></script>
  <?php if (isset($include_checkout_js) && $include_checkout_js): ?>
  <script src="../javaScript/checkout.js"></script>
  <?php endif; ?>
</body>
</html>
