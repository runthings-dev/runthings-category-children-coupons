=== Coupons Category Children for WooCommerce ===
Contributors: runthingsdev
Donate link: https://runthings.dev/donate/
Tags: woocommerce, coupons, categories, subcategories, restrictions
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Restrict WooCommerce coupons by product categories, automatically including all child/descendant categories.

== Description ==

Coupons Category Children for WooCommerce extends the built-in WooCommerce coupon category restrictions by automatically including all subcategories when you select a parent category.

With WooCommerce's default category restrictions, if you select "Clothing" as an allowed category, only products directly in "Clothing" will qualify - not products in "T-Shirts" or "Trousers" subcategories. This plugin solves that limitation.

= Features =

* Restrict coupons to specific category trees (parent + all descendants)
* Exclude entire category trees from coupon eligibility
* Automatic subcategory inclusion - no need to manually select every child category
* Works alongside WooCommerce's existing coupon restrictions
* Customizable error messages via filter

= How It Works =

When you select a category in the "Allowed categories (incl. children)" field, the plugin automatically includes all subcategories during validation. If your category structure changes over time, the coupon restrictions update automatically.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/runthings-wc-coupons-category-children` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Marketing > Coupons and edit or create a coupon.
4. In the "Usage restriction" tab, you will see the new category fields with "(incl. children)" labels.

== Frequently Asked Questions ==

= How does this differ from WooCommerce's built-in category restrictions? =

WooCommerce's built-in "Product categories" field only matches products directly assigned to the selected categories. This plugin's fields automatically include all subcategories of your selection.

= Can I use both this plugin's fields and WooCommerce's built-in category fields? =

Yes, but they operate as separate restrictions (AND logic). If you use both, a coupon must pass both sets of rules. For simplicity, we recommend using one or the other on a given coupon.

= What happens if I add new subcategories later? =

They are automatically included! The plugin checks category relationships at validation time, so new subcategories are picked up immediately.

== Screenshots ==

1. The category restriction fields in the coupon Usage restriction tab.

== Changelog ==

= 1.0.0 - 18th December 2025 =
* Initial release.
* Allowed categories with automatic child inclusion.
* Excluded categories with automatic child inclusion.
* Filter `runthings_wc_coupons_category_children_error_message` for custom error messages.

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin. No upgrade steps required.

== Filters ==

= runthings_wc_coupons_category_children_error_message =

Customize the error message shown when a coupon fails category validation.

**Parameters:**

* `$message` (string) - The default error message.
* `$context` (array) - Contains 'coupon' (WC_Coupon object) and 'type' ('allowed' or 'excluded').

**Example:**

See [readme.md on GitHub](https://github.com/runthings-dev/runthings-wc-coupons-category-children#filters) for detailed code examples.

