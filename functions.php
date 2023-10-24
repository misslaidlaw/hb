<?php

function enqueue_custom_scripts()
{
    wp_enqueue_script("jquery");
    wp_enqueue_script(
        "custom-ajax",
        get_stylesheet_directory_uri() . "/js/custom-ajax.js",
        ["jquery"],
        null,
        true
    );
    wp_localize_script("custom-ajax", "frontendajax", [
        "ajaxurl" => admin_url("admin-ajax.php"),
    ]);
}
add_action("wp_enqueue_scripts", "enqueue_custom_scripts");

function fetch_cart_contents()
{
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    foreach ($items as $item => $values) {
        $_product = wc_get_product($values["data"]->get_id());
        echo "<div class='cart-item'>";
        echo "<img src='" .
            get_the_post_thumbnail_url($values["product_id"]) .
            "' alt='product-image'>";
        // Add custom class to the title
        echo "<span class='custom-title-class'>" .
            $_product->get_title() .
            "</span>";

        // Add custom class to the quantity
        echo "<span class='custom-quantity-class'> " .
            $values["quantity"] .
            "</span>";

        // Add the "x" sign with a data attribute containing the cart item key
        echo "<span class='remove-item' data-item-key='" . $item . "'>x</span>";
        echo "</div>";
    }
    die();
}

add_action("wp_ajax_fetch_cart_contents", "fetch_cart_contents");
add_action("wp_ajax_nopriv_fetch_cart_contents", "fetch_cart_contents");

function ajax_remove_cart_item()
{
    $cart_item_key = $_POST["cart_item_key"];
    if ($cart_item_key) {
        WC()->cart->remove_cart_item($cart_item_key);
        echo "success";
    } else {
        echo "error";
    }
    die();
}
add_action("wp_ajax_ajax_remove_cart_item", "ajax_remove_cart_item");
add_action("wp_ajax_nopriv_ajax_remove_cart_item", "ajax_remove_cart_item");

////// creating the cart message
function check_cart_quantity()
{
    $cart_total_items = WC()->cart->get_cart_contents_count(); // Get total items in the cart (including quantities)

    if ($cart_total_items >= 25) {
        echo json_encode([
            "message" => "You have reached your limit.",
            "limitReached" => true,
        ]);
    } else {
        echo json_encode([
            "message" =>
                "You have <span class='left-count'>" .
                $cart_total_items .
                "</span> items in the cart out of <span class='left-count'>25</span> add more to your cart to get discounts.",
            "limitReached" => false,
        ]);
    }
    die();
}

add_action("wp_ajax_check_cart_quantity", "check_cart_quantity");
add_action("wp_ajax_nopriv_check_cart_quantity", "check_cart_quantity");

///////////restricting the amount that is in the cart

function check_meals_category_limit($passed, $product_id, $quantity)
{
    // Only run this check on the cart page
    if (!is_cart()) {
        return $passed;
    }

    $meals_category = "meals"; // Slug of the 'meals' category
    $max_limit = 25; // Set your max limit here
    $min_limit = 7; // Set your min limit here
    $current_count = 0;

    // Check if the product being added belongs to the 'meals' category
    if (has_term($meals_category, "product_cat", $product_id)) {
        // Loop through the cart to count the quantity of 'meals' products
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (
                has_term(
                    $meals_category,
                    "product_cat",
                    $cart_item["product_id"]
                )
            ) {
                $current_count += $cart_item["quantity"];
            }
        }

        // Check if the total quantity exceeds the max limit
        if ($current_count + $quantity > $max_limit) {
            wc_add_notice(
                "You can only add a total of " .
                    $max_limit .
                    " meals to your cart. Please adjust your cart to proceed.",
                "error"
            );
            return false;
        }

        // Check if the total quantity is below the min limit
        if ($current_count + $quantity < $min_limit) {
            wc_add_notice(
                "You need to add at least " .
                    $min_limit .
                    " meals to your cart. Please add more meals to proceed.",
                "error"
            );
            return false;
        }
    }

    return $passed;
}
add_filter(
    "woocommerce_add_to_cart_validation",
    "check_meals_category_limit",
    10,
    3
);

function disable_checkout_button_if_meals_exceed()
{
    $meals_category = "meals";
    $max_limit = 25;
    $min_limit = 7;
    $current_count = 0;
    $has_meals = false; // New variable to check if there are meals in the cart

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (
            has_term($meals_category, "product_cat", $cart_item["product_id"])
        ) {
            $current_count += $cart_item["quantity"];
            $has_meals = true; // Set to true if meals are found
        }
    }

    // Only disable the checkout button if there are meals in the cart
    if (
        $has_meals &&
        ($current_count > $max_limit || $current_count < $min_limit)
    ) {
        remove_action(
            "woocommerce_proceed_to_checkout",
            "woocommerce_button_proceed_to_checkout",
            20
        );
        echo '<a href="' .
            get_permalink(get_page_by_title("Honest Box")) .
            '" class="checkout-button button alt wc-forward">Adjust your cart</a>';
        echo '<p style="color: red; margin-top: 10px;">Please ensure you have between ' .
            $min_limit .
            " and " .
            $max_limit .
            " meals in your cart to proceed.</p>";

        if ($current_count > $max_limit) {
            $meals_to_remove = $current_count - $max_limit;
            echo '<p style="color: red;">Get rid of ' .
                $meals_to_remove .
                " more meals to complete your cart.</p>";
        }
    }
}
add_action(
    "woocommerce_proceed_to_checkout",
    "disable_checkout_button_if_meals_exceed",
    1
);
}

function apply_coupon() {
    $coupon_code = $_POST['coupon_code'];
    
    if ( WC()->cart->has_discount( $coupon_code ) ) {
        wp_send_json_success();
    } else {
        if ( WC()->cart->add_discount( $coupon_code ) ) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}
add_action('wp_ajax_apply_coupon', 'apply_coupon');
add_action('wp_ajax_nopriv_apply_coupon', 'apply_coupon');

// check the cart count and show the coupon form

function check_cart_item_count() {
  $cart_total_items = WC()->cart->get_cart_contents_count();
  $show_coupon_form = $cart_total_items >= 7 ? true : false;
  echo json_encode(['show_coupon_form' => $show_coupon_form]);
  die();
}

add_action('wp_ajax_check_cart_item_count', 'check_cart_item_count'); 
add_action('wp_ajax_nopriv_check_cart_item_count', 'check_cart_item_count');
