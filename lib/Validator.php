<?php

namespace Runthings\CategoryChildrenCoupons;

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
        add_filter('woocommerce_coupon_error', [$this, 'customize_coupon_error'], 10, 3);
    }

    /**
     * Cart-level validation for fixed_cart coupons.
     * Product coupons are handled by validate_coupon_for_product + customize_coupon_error.
     */
    public function validate_coupon_categories(bool $is_valid, WC_Coupon $coupon, WC_Discounts $discounts): bool
    {
        if (!$is_valid || $coupon->is_type(wc_get_product_coupon_types())) {
            return $is_valid;
        }

        $restrictions = $this->get_category_restrictions($coupon);
        if (!$restrictions) {
            return $is_valid;
        }

        $cart_category_ids = $this->get_cart_category_ids();

        // Check incl. children allowed categories
        if (!empty($restrictions['expanded_allowed'])) {
            if (empty(array_intersect($cart_category_ids, $restrictions['expanded_allowed']))) {
                $this->throw_validation_error($coupon, 'allowed', $restrictions);
            }
        }

        // Check incl. children excluded categories
        if (!empty($restrictions['expanded_excluded'])) {
            if (!empty(array_intersect($cart_category_ids, $restrictions['expanded_excluded']))) {
                $this->throw_validation_error($coupon, 'excluded', $restrictions);
            }
        }

        // Check excl. children allowed categories (no expansion)
        if (!empty($restrictions['allowed_excl'])) {
            if (empty(array_intersect($cart_category_ids, $restrictions['allowed_excl']))) {
                $this->throw_validation_error($coupon, 'allowed_excl', $restrictions);
            }
        }

        // Check excl. children excluded categories (no expansion)
        if (!empty($restrictions['excluded_excl'])) {
            if (!empty(array_intersect($cart_category_ids, $restrictions['excluded_excl']))) {
                $this->throw_validation_error($coupon, 'excluded_excl', $restrictions);
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
        $restrictions = $this->get_category_restrictions($coupon);
        if (!$restrictions) {
            return $valid;
        }

        $product_id = $product->get_parent_id() ?: $product->get_id();
        $product_cats = wc_get_product_cat_ids($product_id);

        return $this->categories_pass_restrictions($product_cats, $restrictions);
    }

    /**
     * Customize error message for product coupons that fail due to our category restrictions.
     * Only customizes if we can verify our restrictions actually caused the failure.
     */
    public function customize_coupon_error(string $err, int $err_code, WC_Coupon $coupon): string
    {
        if ($err_code !== \WC_Coupon::E_WC_COUPON_NOT_APPLICABLE) {
            return $err;
        }

        $restrictions = $this->get_category_restrictions($coupon);
        if (!$restrictions) {
            return $err;
        }

        // Verify our restrictions actually caused the failure by re-checking cart products
        if (!$this->did_our_restrictions_fail($restrictions)) {
            return $err;
        }

        // Determine which type of restriction caused the failure
        $type = $this->determine_failed_restriction_type($restrictions);
        return $this->get_error_message($coupon, $type, $restrictions);
    }

    /**
     * Determine which restriction type caused the failure.
     */
    private function determine_failed_restriction_type(array $restrictions): string
    {
        if (!empty($restrictions['allowed'])) {
            return 'allowed';
        }
        if (!empty($restrictions['allowed_excl'])) {
            return 'allowed_excl';
        }
        if (!empty($restrictions['excluded'])) {
            return 'excluded';
        }
        if (!empty($restrictions['excluded_excl'])) {
            return 'excluded_excl';
        }
        return 'allowed';
    }

    /**
     * Check if our category restrictions caused ALL products to fail.
     * Returns true only if NO products pass our restrictions.
     */
    private function did_our_restrictions_fail(array $restrictions): bool
    {
        if (!WC()->cart) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['data']->get_parent_id() ?: $cart_item['data']->get_id();
            $product_cats = wc_get_product_cat_ids($product_id);

            if ($this->categories_pass_restrictions($product_cats, $restrictions)) {
                // At least one product passes - we didn't cause the total failure
                return false;
            }
        }

        // No products passed our restrictions - we caused the failure
        return true;
    }

    /**
     * Get category restrictions for a coupon, with expanded children where applicable.
     * Returns null if no restrictions configured.
     */
    private function get_category_restrictions(WC_Coupon $coupon): ?array
    {
        $allowed = get_post_meta($coupon->get_id(), Plugin::ALLOWED_CATEGORIES_META_KEY, true);
        $allowed = is_array($allowed) ? $allowed : [];

        $excluded = get_post_meta($coupon->get_id(), Plugin::EXCLUDED_CATEGORIES_META_KEY, true);
        $excluded = is_array($excluded) ? $excluded : [];

        $allowed_excl = get_post_meta($coupon->get_id(), Plugin::ALLOWED_CATEGORIES_EXCL_META_KEY, true);
        $allowed_excl = is_array($allowed_excl) ? $allowed_excl : [];

        $excluded_excl = get_post_meta($coupon->get_id(), Plugin::EXCLUDED_CATEGORIES_EXCL_META_KEY, true);
        $excluded_excl = is_array($excluded_excl) ? $excluded_excl : [];

        if (empty($allowed) && empty($excluded) && empty($allowed_excl) && empty($excluded_excl)) {
            return null;
        }

        return [
            'allowed' => $allowed,
            'excluded' => $excluded,
            'allowed_excl' => $allowed_excl,
            'excluded_excl' => $excluded_excl,
            'expanded_allowed' => $this->expand_categories_with_children($allowed),
            'expanded_excluded' => $this->expand_categories_with_children($excluded),
        ];
    }

    /**
     * Check if categories pass the allowed/excluded restrictions.
     */
    private function categories_pass_restrictions(array $category_ids, array $restrictions): bool
    {
        // Check incl. children allowed categories
        if (!empty($restrictions['expanded_allowed'])) {
            if (empty(array_intersect($category_ids, $restrictions['expanded_allowed']))) {
                return false;
            }
        }

        // Check incl. children excluded categories
        if (!empty($restrictions['expanded_excluded'])) {
            if (!empty(array_intersect($category_ids, $restrictions['expanded_excluded']))) {
                return false;
            }
        }

        // Check excl. children allowed categories (no expansion)
        if (!empty($restrictions['allowed_excl'])) {
            if (empty(array_intersect($category_ids, $restrictions['allowed_excl']))) {
                return false;
            }
        }

        // Check excl. children excluded categories (no expansion)
        if (!empty($restrictions['excluded_excl'])) {
            if (!empty(array_intersect($category_ids, $restrictions['excluded_excl']))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get filtered error message for validation failures.
     */
    private function get_error_message(WC_Coupon $coupon, string $type, array $restrictions): string
    {
        $configured_ids = match ($type) {
            'allowed' => $restrictions['allowed'],
            'excluded' => $restrictions['excluded'],
            'allowed_excl' => $restrictions['allowed_excl'],
            'excluded_excl' => $restrictions['excluded_excl'],
            default => [],
        };

        $expanded_ids = match ($type) {
            'allowed' => $restrictions['expanded_allowed'],
            'excluded' => $restrictions['expanded_excluded'],
            'allowed_excl' => $restrictions['allowed_excl'], // No expansion for excl. children
            'excluded_excl' => $restrictions['excluded_excl'], // No expansion for excl. children
            default => [],
        };

        $error_context = [
            'coupon' => $coupon,
            'type' => $type,
            'configured_category_ids' => $configured_ids,
            'expanded_category_ids' => $expanded_ids,
        ];

        $is_allowed_type = in_array($type, ['allowed', 'allowed_excl'], true);
        $default_message = $is_allowed_type
            ? __('This coupon is not valid for the product categories in your cart.', 'runthings-category-children-coupons')
            : __('This coupon cannot be used with some product categories in your cart.', 'runthings-category-children-coupons');

        // Deprecated filter - check if anyone is using it and apply with warning
        $deprecated_hook = 'runthings_wc_coupons_category_children_error_message';
        if (has_filter($deprecated_hook)) {
            _deprecated_hook(
                $deprecated_hook,
                '1.3.0',
                'runthings_category_children_coupons_error_message'
            );
            $default_message = apply_filters($deprecated_hook, $default_message, $error_context);
        }

        return apply_filters('runthings_category_children_coupons_error_message', $default_message, $error_context);
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

    private function throw_validation_error(WC_Coupon $coupon, string $type, array $restrictions): void
    {
        $error_message = $this->get_error_message($coupon, $type, $restrictions);
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- WC constant is int exception code, not output
        throw new Exception(esc_html($error_message), WC_Coupon::E_WC_COUPON_INVALID_FILTERED);
    }
}

