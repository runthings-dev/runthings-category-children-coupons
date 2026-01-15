<?php

namespace Runthings\WCCouponsCategoryChildren;

use Exception;
use WC_Coupon;
use WC_Discounts;
use WC_Product;

if (!defined('WPINC')) {
    die;
}

class Validator
{
    public function __construct()
    {
        add_filter('woocommerce_coupon_is_valid', [$this, 'validate_coupon_categories'], 10, 3);
        add_filter('woocommerce_coupon_is_valid_for_product', [$this, 'validate_coupon_for_product'], 10, 4);
    }

    /**
     * Cart-level validation for fixed_cart coupons.
     */
    public function validate_coupon_categories(bool $is_valid, WC_Coupon $coupon, WC_Discounts $discounts): bool
    {
        if (!$is_valid) {
            return $is_valid;
        }

        if ($coupon->is_type(wc_get_product_coupon_types())) {
            return $is_valid;
        }

        $allowed_categories = get_post_meta($coupon->get_id(), Plugin::ALLOWED_CATEGORIES_META_KEY, true);
        $allowed_categories = is_array($allowed_categories) ? $allowed_categories : [];

        $excluded_categories = get_post_meta($coupon->get_id(), Plugin::EXCLUDED_CATEGORIES_META_KEY, true);
        $excluded_categories = is_array($excluded_categories) ? $excluded_categories : [];

        if (empty($allowed_categories) && empty($excluded_categories)) {
            return $is_valid;
        }

        $cart_category_ids = $this->get_cart_category_ids();

        $expanded_allowed = $this->expand_categories_with_children($allowed_categories);
        $expanded_excluded = $this->expand_categories_with_children($excluded_categories);

        if (!empty($expanded_allowed)) {
            $has_allowed = !empty(array_intersect($cart_category_ids, $expanded_allowed));
            if (!$has_allowed) {
                $this->throw_validation_error($coupon, 'allowed', $allowed_categories, $expanded_allowed);
            }
        }

        if (!empty($expanded_excluded)) {
            $has_excluded = !empty(array_intersect($cart_category_ids, $expanded_excluded));
            if ($has_excluded) {
                $this->throw_validation_error($coupon, 'excluded', $excluded_categories, $expanded_excluded);
            }
        }

        return true;
    }

    /**
     * Product-level validation for percent/fixed_product coupons.
     *
     * @param array|WC_Order_Item_Product $values Cart item data or order item.
     */
    public function validate_coupon_for_product(bool $valid, WC_Product $product, WC_Coupon $coupon, $values): bool
    {
        $allowed_categories = get_post_meta($coupon->get_id(), Plugin::ALLOWED_CATEGORIES_META_KEY, true);
        $allowed_categories = is_array($allowed_categories) ? $allowed_categories : [];

        $excluded_categories = get_post_meta($coupon->get_id(), Plugin::EXCLUDED_CATEGORIES_META_KEY, true);
        $excluded_categories = is_array($excluded_categories) ? $excluded_categories : [];

        if (empty($allowed_categories) && empty($excluded_categories)) {
            return $valid;
        }

        $product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
        $product_cats = wc_get_product_cat_ids($product_id);

        $expanded_allowed = $this->expand_categories_with_children($allowed_categories);
        $expanded_excluded = $this->expand_categories_with_children($excluded_categories);

        if (!empty($expanded_allowed)) {
            if (empty(array_intersect($product_cats, $expanded_allowed))) {
                return false;
            }
        }

        if (!empty($expanded_excluded)) {
            if (!empty(array_intersect($product_cats, $expanded_excluded))) {
                return false;
            }
        }

        return $valid;
    }

    private function get_cart_category_ids(): array
    {
        $category_ids = [];

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $product_cats = wc_get_product_cat_ids($product_id);
            $category_ids = array_merge($category_ids, $product_cats);

            $product = wc_get_product($product_id);
            if ($product && $product->get_parent_id()) {
                $parent_cats = wc_get_product_cat_ids($product->get_parent_id());
                $category_ids = array_merge($category_ids, $parent_cats);
            }
        }

        return array_unique($category_ids);
    }

    private function expand_categories_with_children(array $category_ids): array
    {
        $expanded = [];

        foreach ($category_ids as $cat_id) {
            $expanded[] = $cat_id;
            $children = get_term_children($cat_id, 'product_cat');
            if (!is_wp_error($children)) {
                $expanded = array_merge($expanded, $children);
            }
        }

        return array_unique($expanded);
    }

    private function throw_validation_error(WC_Coupon $coupon, string $type, array $configured_categories, array $expanded_categories): void
    {
        $error_context = [
            'coupon' => $coupon,
            'type' => $type,
            'configured_category_ids' => $configured_categories,
            'expanded_category_ids' => $expanded_categories,
        ];

        if ($type === 'allowed') {
            $default_message = __('This coupon is not valid for the product categories in your cart.', 'runthings-wc-coupons-category-children');
        } else {
            $default_message = __('This coupon cannot be used with some product categories in your cart.', 'runthings-wc-coupons-category-children');
        }

        $error_message = apply_filters('runthings_wc_coupons_category_children_error_message', $default_message, $error_context);

        wc_get_logger()->info('Coupon category children validation failed. Coupon: ' . $coupon->get_code() . ', Type: ' . $type, ['source' => 'runthings-wc-coupons-category-children']);

        throw new Exception(esc_html($error_message));
    }
}

