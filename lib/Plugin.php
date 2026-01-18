<?php

namespace Runthings\CategoryChildrenCoupons;

if (!defined('WPINC')) {
    die;
}

class Plugin
{
    public const ALLOWED_CATEGORIES_META_KEY = 'runthings_ccc_allowed_categories_with_children';
    public const EXCLUDED_CATEGORIES_META_KEY = 'runthings_ccc_excluded_categories_with_children';
    public const ALLOWED_CATEGORIES_EXCL_META_KEY = 'runthings_ccc_allowed_categories_excl_children';
    public const EXCLUDED_CATEGORIES_EXCL_META_KEY = 'runthings_ccc_excluded_categories_excl_children';

    public function __construct()
    {
        new Admin();
        new Validator();
        new AutomateWooMetaCopier();
        new Migrator();
    }
}

