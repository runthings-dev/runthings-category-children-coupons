<?php

/**
 * Plugin Name: Coupons Category Children for WooCommerce
 * Plugin URI: https://runthings.dev/wordpress-plugins/wc-coupons-category-children/
 * Description: Restrict coupons by product categories, automatically including all child/descendant categories.
 * Version: 1.1.0
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

if (!defined('WPINC')) {
    die;
}

define('RUNTHINGS_WC_CCC_VERSION', '1.1.0');
define('RUNTHINGS_WC_CCC_URL', plugin_dir_url(__FILE__));
define('RUNTHINGS_WC_CCC_DIR', plugin_dir_path(__FILE__));

class Bootstrap
{
    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init(): void
    {
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', [$this, 'admin_notice_wc_inactive']);
            return;
        }

        require_once RUNTHINGS_WC_CCC_DIR . 'lib/Plugin.php';
        require_once RUNTHINGS_WC_CCC_DIR . 'lib/Admin.php';
        require_once RUNTHINGS_WC_CCC_DIR . 'lib/Validator.php';
        require_once RUNTHINGS_WC_CCC_DIR . 'lib/AutomateWooMetaCopier.php';

        new Plugin();
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
}

new Bootstrap();
