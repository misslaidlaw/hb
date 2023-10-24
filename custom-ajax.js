jQuery(document).ready(function ($) {
    function load_cart_contents() {
        $.ajax({
            type: "POST",
            url: frontendajax.ajaxurl,
            data: {
                action: "fetch_cart_contents",
            },
            success: function (response) {
                $("#ajax-cart-contents").html(response);
                return false;
            },
        });
    }

    // Load cart contents on page load
    load_cart_contents();

    // Load cart contents when an item is added to the cart
    $(document.body).on("added_to_cart", load_cart_contents);

    // Remove cart item when "x" is clicked
    $(document).on("click", ".remove-item", function () {
        var item_key = $(this).data("item-key");
        $.ajax({
            type: "POST",
            url: frontendajax.ajaxurl,
            data: {
                action: "ajax_remove_cart_item",
                cart_item_key: item_key,
            },
            success: function (response) {
                if (response === "success") {
                    load_cart_contents();
                }
            },
        });
    });
});
/////////// adds the cart message and hides and shows buttons

jQuery(document).ready(function ($) {
    function load_cart_message() {
        $.ajax({
            type: "POST",
            url: frontendajax.ajaxurl,
            data: {
                action: "check_cart_quantity",
            },
            success: function (response) {
                var data = JSON.parse(response);
                $("#cart-message").html(data.message);

                if (data.limitReached) {
                    $(".hideMeBtn").hide();
                    $(".showMeBtn").show(); // Hide all buttons with the class 'hideMeBtn'
                } else {
                    $(".hideMeBtn").show();
                    $(".showMeBtn").hide(); // Show all buttons with the class 'hideMeBtn'
                }
            },
        });
    }

    // Load cart message on page load
    load_cart_message();

    // Update cart message when an item is added to or removed from the cart
    $(document.body).on("added_to_cart removed_from_cart", load_cart_message);

    // Update cart message when "x" is clicked to remove an item
    $(document).on("click", ".remove-item", function () {
        setTimeout(load_cart_message, 500); // Delay to ensure the cart is updated before fetching the message
    });
});

// adds functionality to the coupon form and allows for the shaking animation to happen.
jQuery(document).ready(function ($) {
    function checkCartItemCount() {
        $.ajax({
            url: frontendajax.ajaxurl,
            type: "POST",
            data: {
                action: "check_cart_item_count",
            },
            success: function (response) {
                if (response.show_coupon_form) {
                    $("#coupon-form").show();
                } else {
                    $("#coupon-form").hide();
                }
            },
        });
    }

    $("#coupon-form").on("submit", function (e) {
        e.preventDefault();
        // ... existing coupon form submit code ...
    });

    $(document.body).on("added_to_cart", function () {
        $("#ajax-cart-contents").addClass("shake");
        setTimeout(function () {
            $("#ajax-cart-contents").removeClass("shake");
        }, 1000);
        checkCartItemCount();
    });

    checkCartItemCount(); // Initial check on page load
});
