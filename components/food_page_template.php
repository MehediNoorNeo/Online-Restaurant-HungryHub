<?php
// Set page variables before including this template
// Required variables: $page_title, $category_name, $category_icon, $page_description, $food_category
?>
<?php 
$include_food_cards_css = true;
include 'header.php'; 
?>

  <main class="pt-5">
    <?php include 'page_header.php'; ?>
    <?php include 'menu_explorer.php'; ?>
    <?php include 'food_items_section.php'; ?>
  </main>

<?php include 'footer.php'; ?>

<script>
  // Load food items using API
  loadFoodItems('<?php echo $food_category; ?>');
</script>
