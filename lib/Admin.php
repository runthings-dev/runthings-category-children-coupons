<?php

namespace Runthings\WCCouponsCategoryChildren;

if (!defined('WPINC')) {
    die;
}

class Admin
{
    public function __construct()
    {
        add_action('woocommerce_coupon_options_usage_restriction', [$this, 'add_category_fields'], 10);
        add_action('woocommerce_coupon_options_save', [$this, 'save_category_fields'], 10, 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts(string $hook): void
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'shop_coupon') {
            return;
        }

        wp_enqueue_script(
            'runthings-wc-ccc-admin-conflict-notice',
            RUNTHINGS_WC_CCC_URL . 'assets/js/admin-conflict-notice.js',
            ['jquery'],
            RUNTHINGS_WC_CCC_VERSION,
            true
        );

        wp_enqueue_style(
            'runthings-wc-ccc-admin-conflict-notice',
            RUNTHINGS_WC_CCC_URL . 'assets/css/admin-conflict-notice.css',
            [],
            RUNTHINGS_WC_CCC_VERSION
        );
    }

    public function add_category_fields(): void
    {
        global $post;

        $allowed_categories = get_post_meta($post->ID, Plugin::ALLOWED_CATEGORIES_META_KEY, true);
        $allowed_categories = is_array($allowed_categories) ? $allowed_categories : [];

        $excluded_categories = get_post_meta($post->ID, Plugin::EXCLUDED_CATEGORIES_META_KEY, true);
        $excluded_categories = is_array($excluded_categories) ? $excluded_categories : [];

        $categories = get_terms(['taxonomy' => 'product_cat', 'orderby' => 'name', 'hide_empty' => false]);

        echo '<div class="options_group">';
        echo '<div class="hr-section hr-section-coupon_restrictions">' . esc_html__('And', 'runthings-wc-coupons-category-children') . '</div>';
        wp_nonce_field('runthings_save_category_children', 'runthings_category_children_nonce');
        ?>

        <p class="form-field">
            <label for="<?php echo esc_attr(Plugin::ALLOWED_CATEGORIES_META_KEY); ?>"><?php esc_html_e('Product categories (incl. children)', 'runthings-wc-coupons-category-children'); ?></label>
            <select id="<?php echo esc_attr(Plugin::ALLOWED_CATEGORIES_META_KEY); ?>" name="<?php echo esc_attr(Plugin::ALLOWED_CATEGORIES_META_KEY); ?>[]" class="wc-enhanced-select" multiple="multiple" style="width: 50%;" data-placeholder="<?php esc_attr_e('Any category', 'runthings-wc-coupons-category-children'); ?>">
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
            <label for="<?php echo esc_attr(Plugin::EXCLUDED_CATEGORIES_META_KEY); ?>"><?php esc_html_e('Exclude categories (incl. children)', 'runthings-wc-coupons-category-children'); ?></label>
            <select id="<?php echo esc_attr(Plugin::EXCLUDED_CATEGORIES_META_KEY); ?>" name="<?php echo esc_attr(Plugin::EXCLUDED_CATEGORIES_META_KEY); ?>[]" class="wc-enhanced-select" multiple="multiple" style="width: 50%;" data-placeholder="<?php esc_attr_e('No categories', 'runthings-wc-coupons-category-children'); ?>">
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


            <div class="runthings-category-conflict-notice notice notice-warning inline" style="display: none;">
                <p>
                    <strong><?php esc_html_e('Conflicting category settings detected.', 'runthings-wc-coupons-category-children'); ?></strong>
                    <?php esc_html_e('You have both WooCommerce\'s built-in category fields and "incl. children" fields configured. These operate as AND logic - the coupon must pass both checks, which may cause unexpected results. We recommend using one or the other.', 'runthings-wc-coupons-category-children'); ?>
                    <a href="https://github.com/runthings-dev/runthings-wc-coupons-category-children#can-i-use-both-this-plugins-fields-and-woocommerces-built-in-category-fields" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn more', 'runthings-wc-coupons-category-children'); ?></a>
                </p>
            </div>


        <?php
        echo '</div>';
    }

    public function save_category_fields(int $post_id): void
    {
        if (!isset($_POST['runthings_category_children_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['runthings_category_children_nonce'])), 'runthings_save_category_children')) {
            return;
        }

        $allowed = isset($_POST[Plugin::ALLOWED_CATEGORIES_META_KEY]) ? array_map('intval', (array) wp_unslash($_POST[Plugin::ALLOWED_CATEGORIES_META_KEY])) : [];
        $excluded = isset($_POST[Plugin::EXCLUDED_CATEGORIES_META_KEY]) ? array_map('intval', (array) wp_unslash($_POST[Plugin::EXCLUDED_CATEGORIES_META_KEY])) : [];

        update_post_meta($post_id, Plugin::ALLOWED_CATEGORIES_META_KEY, $allowed);
        update_post_meta($post_id, Plugin::EXCLUDED_CATEGORIES_META_KEY, $excluded);
    }
}

