    <!-- Page Header -->
    <section class="py-2 bg-light">
      <div class="container-fluid">
        <div class="text-center mb-3">
          <h1 class="display-5 fw-bold text-dark mb-2">
            <img src="../<?php echo (strpos($category_icon, 'assets/') === 0) ? $category_icon : 'assets/' . $category_icon; ?>" alt="<?php echo $category_name; ?> Icon" class="me-3" style="width: 60px; height: 60px;"><?php echo $page_title; ?>
          </h1>
          <p class="text-muted"><?php echo $page_description; ?></p>
        </div>
      </div>
    </section>
