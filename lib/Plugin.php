<?php

namespace Runthings\WCCouponsCategoryChildren;

if (!defined('WPINC')) {
    die;
}

class Plugin
{
    public const ALLOWED_CATEGORIES_META_KEY = 'runthings_wc_allowed_categories_with_children';
    public const EXCLUDED_CATEGORIES_META_KEY = 'runthings_wc_excluded_categories_with_children';

    public function __construct()
    {
        new Admin();
        new Validator();
        new AutomateWooMetaCopier();
    }
}

