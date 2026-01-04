<?php

/**
 * Plugin Name: Coupons Category Children for WooCommerce
 * Plugin URI: https://runthings.dev/wordpress-plugins/wc-coupons-category-children/
 * Description: Restrict coupons by product categories, automatically including all child/descendant categories.
 * Version: 1.0.0
 * Author: runthingsdev
 * Author URI: https://runthings.dev/
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Text Domain: runthings-wc-coupons-category-children
 * Domain Path: /languages
 */

/*
Copyright 2025 Matthew Harris

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

namespace Runthings\WCCouponsCategoryChildren;

use Exception;
use WC_Coupon;
use WC_Discounts;
use WC_Product;

if (!defined('WPINC')) {
    die;
}

class CouponsCategoryChildren
{
    const ALLOWED_CATEGORIES_META_KEY = 'runthings_wc_allowed_categories_with_children';
    const EXCLUDED_CATEGORIES_META_KEY = 'runthings_wc_excluded_categories_with_children';

    public function __construct()
    {
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', [$this, 'admin_notice_wc_inactive']);
            return;
        }

        add_action('woocommerce_coupon_options_usage_restriction', [$this, 'add_category_fields'], 10);
        add_action('woocommerce_coupon_options_save', [$this, 'save_category_fields'], 10, 1);

        // Cart-level validation (for fixed_cart coupons)
        add_filter('woocommerce_coupon_is_valid', [$this, 'validate_coupon_categories'], 10, 3);

        // Product-level validation (for percent/fixed_product coupons - controls which products get discounted)
        add_filter('woocommerce_coupon_is_valid_for_product', [$this, 'validate_coupon_for_product'], 10, 4);
    }

    private function is_woocommerce_active(): bool
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true) ||
            (is_multisite() && array_key_exists('woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins', [])));
    }

    public function admin_notice_wc_inactive(): void
    {
        echo '<div class="error"><p>';
        esc_html_e('Coupons Category Children for WooCommerce requires WooCommerce to be active. Please install and activate WooCommerce.', 'runthings-wc-coupons-category-children');
        echo '</p></div>';
    }

    public function add_category_fields(): void
    {
        global $post;

        $allowed_categories = get_post_meta($post->ID, self::ALLOWED_CATEGORIES_META_KEY, true);
        $allowed_categories = is_array($allowed_categories) ? $allowed_categories : [];

        $excluded_categories = get_post_meta($post->ID, self::EXCLUDED_CATEGORIES_META_KEY, true);
        $excluded_categories = is_array($excluded_categories) ? $excluded_categories : [];

        $categories = get_terms(['taxonomy' => 'product_cat', 'orderby' => 'name', 'hide_empty' => false]);

        echo '<div class="options_group">';
        wp_nonce_field('runthings_save_category_children', 'runthings_category_children_nonce');
        ?>

        <p class="form-field">
            <label for="<?php echo esc_attr(self::ALLOWED_CATEGORIES_META_KEY); ?>"><?php esc_html_e('Product categories (incl. children)', 'runthings-wc-coupons-category-children'); ?></label>
            <select id="<?php echo esc_attr(self::ALLOWED_CATEGORIES_META_KEY); ?>" name="<?php echo esc_attr(self::ALLOWED_CATEGORIES_META_KEY); ?>[]" class="wc-enhanced-select" multiple="multiple" style="width: 50%;" data-placeholder="<?php esc_attr_e('Any category', 'runthings-wc-coupons-category-children'); ?>">
                <?php
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        echo '<option value="' . esc_attr($cat->term_id) . '"' . (in_array($cat->term_id, $allowed_categories) ? ' selected="selected"' : '') . '>' . esc_html($cat->name) . '</option>';
                    }
                }
                ?>
            </select>
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo wc_help_tip(__('Product categories (and their subcategories) that the coupon will be applied to, or that need to be in the cart for cart discounts to be applied.', 'runthings-wc-coupons-category-children'));
            ?>
        </p>

        <p class="form-field">
            <label for="<?php echo esc_attr(self::EXCLUDED_CATEGORIES_META_KEY); ?>"><?php esc_html_e('Exclude categories (incl. children)', 'runthings-wc-coupons-category-children'); ?></label>
            <select id="<?php echo esc_attr(self::EXCLUDED_CATEGORIES_META_KEY); ?>" name="<?php echo esc_attr(self::EXCLUDED_CATEGORIES_META_KEY); ?>[]" class="wc-enhanced-select" multiple="multiple" style="width: 50%;" data-placeholder="<?php esc_attr_e('No categories', 'runthings-wc-coupons-category-children'); ?>">
                <?php
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        echo '<option value="' . esc_attr($cat->term_id) . '"' . (in_array($cat->term_id, $excluded_categories) ? ' selected="selected"' : '') . '>' . esc_html($cat->name) . '</option>';
                    }
                }
                ?>
            </select>
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo wc_help_tip(__('Product categories (and their subcategories) that the coupon will not be applied to, or that cannot be in the cart for cart discounts to be applied.', 'runthings-wc-coupons-category-children'));
            ?>
        </p>

        <?php
        echo '</div>';
    }

    public function save_category_fields(int $post_id): void
    {
        if (!isset($_POST['runthings_category_children_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['runthings_category_children_nonce'])), 'runthings_save_category_children')) {
            return;
        }

        $allowed = isset($_POST[self::ALLOWED_CATEGORIES_META_KEY]) ? array_map('intval', (array) wp_unslash($_POST[self::ALLOWED_CATEGORIES_META_KEY])) : [];
        $excluded = isset($_POST[self::EXCLUDED_CATEGORIES_META_KEY]) ? array_map('intval', (array) wp_unslash($_POST[self::EXCLUDED_CATEGORIES_META_KEY])) : [];

        update_post_meta($post_id, self::ALLOWED_CATEGORIES_META_KEY, $allowed);
        update_post_meta($post_id, self::EXCLUDED_CATEGORIES_META_KEY, $excluded);
    }

    /**
     * Cart-level validation for fixed_cart coupons.
     * For cart coupons, categories control whether the coupon is valid at all.
     */
    public function validate_coupon_categories(bool $is_valid, WC_Coupon $coupon, WC_Discounts $discounts): bool
    {
        if (!$is_valid) {
            return $is_valid;
        }

        // Only apply cart-level validation to cart coupon types (fixed_cart)
        // Product coupons (percent, fixed_product) are handled by validate_coupon_for_product
        if ($coupon->is_type(wc_get_product_coupon_types())) {
            return $is_valid;
        }

        $allowed_categories = get_post_meta($coupon->get_id(), self::ALLOWED_CATEGORIES_META_KEY, true);
        $allowed_categories = is_array($allowed_categories) ? $allowed_categories : [];

        $excluded_categories = get_post_meta($coupon->get_id(), self::EXCLUDED_CATEGORIES_META_KEY, true);
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
     * For product coupons, categories control which products get discounted.
     *
     * @param bool $valid Whether the coupon is valid for this product.
     * @param WC_Product $product The product being checked.
     * @param WC_Coupon $coupon The coupon being validated.
     * @param array|WC_Order_Item_Product $values Cart item data (array) or order item (WC_Order_Item_Product).
     */
    public function validate_coupon_for_product(bool $valid, WC_Product $product, WC_Coupon $coupon, $values): bool
    {
        $allowed_categories = get_post_meta($coupon->get_id(), self::ALLOWED_CATEGORIES_META_KEY, true);
        $allowed_categories = is_array($allowed_categories) ? $allowed_categories : [];

        $excluded_categories = get_post_meta($coupon->get_id(), self::EXCLUDED_CATEGORIES_META_KEY, true);
        $excluded_categories = is_array($excluded_categories) ? $excluded_categories : [];

        // If no categories configured in our fields, don't modify the result
        if (empty($allowed_categories) && empty($excluded_categories)) {
            return $valid;
        }

        // Get product categories (including parent product for variations)
        $product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
        $product_cats = wc_get_product_cat_ids($product_id);

        $expanded_allowed = $this->expand_categories_with_children($allowed_categories);
        $expanded_excluded = $this->expand_categories_with_children($excluded_categories);

        // Check allowed categories - product must be in allowed categories to get discount
        if (!empty($expanded_allowed)) {
            if (empty(array_intersect($product_cats, $expanded_allowed))) {
                return false;
            }
        }

        // Check excluded categories - product in excluded categories doesn't get discount
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

new CouponsCategoryChildren();

