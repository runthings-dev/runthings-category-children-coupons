<?php

namespace Runthings\WCCouponsCategoryChildren;

if (!defined('WPINC')) {
    die;
}

/**
 * Copies meta from coupon templates to generated coupons
 */
class AutomateWooMetaCopier
{
    public function __construct()
    {
        add_action('automatewoo/coupon_generator/generate_from_template_coupon', [$this, 'copy_meta'], 10, 2);
    }

    public function copy_meta($coupon, $template_coupon)
    {
        $meta_keys = [
            Plugin::ALLOWED_CATEGORIES_META_KEY,
            Plugin::EXCLUDED_CATEGORIES_META_KEY,
        ];

        foreach ($meta_keys as $key) {
            $value = $template_coupon->get_meta($key);
            if ($value !== '' && $value !== null) {
                $coupon->update_meta_data($key, $value);
            }
        }
    }
}

