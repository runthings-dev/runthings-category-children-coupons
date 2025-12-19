# Coupons Category Children for WooCommerce

Restrict WooCommerce coupons by product categories, automatically including all child/descendant categories.

## Description

Coupons Category Children for WooCommerce extends the built-in WooCommerce coupon category restrictions by automatically including all subcategories when you select a parent category.

With WooCommerce's default category restrictions, if you select "Clothing" as an allowed category, only products directly in "Clothing" will qualify - not products in "T-Shirts" or "Trousers" subcategories. This plugin solves that limitation.

## Features

* Restrict coupons to specific category trees (parent + all descendants)
* Exclude entire category trees from coupon eligibility
* Automatic subcategory inclusion - no need to manually select every child category
* Works alongside WooCommerce's existing coupon restrictions
* Customizable error messages via filter

## How It Works

When you select a category in the "Allowed categories (incl. children)" field, the plugin automatically includes all subcategories during validation. If your category structure changes over time, the coupon restrictions update automatically.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/runthings-wc-coupons-category-children` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Marketing > Coupons and edit or create a coupon.
4. In the "Usage restriction" tab, you will see the new category fields with "(incl. children)" labels.

## Frequently Asked Questions

### How does this differ from WooCommerce's built-in category restrictions?

WooCommerce's built-in "Product categories" field only matches products directly assigned to the selected categories. This plugin's fields automatically include all subcategories of your selection.

### Can I use both this plugin's fields and WooCommerce's built-in category fields?

Yes, but they operate as separate restrictions (AND logic). If you use both, a coupon must pass both sets of rules. For simplicity, we recommend using one or the other on a given coupon.

### What happens if I add new subcategories later?

They are automatically included! The plugin checks category relationships at validation time, so new subcategories are picked up immediately.

## Filters

### runthings_wc_coupons_category_children_error_message

Customize the error message shown when a coupon fails category validation.

#### Parameters

* `$message` (string) - The default error message.
* `$context` (array) - Contains:
  * `coupon` (WC_Coupon) - The coupon object being validated.
  * `type` (string) - Either 'allowed' or 'excluded' indicating which validation failed.

#### Example

```php
add_filter(
    'runthings_wc_coupons_category_children_error_message',
    function ($message, $context) {
        if ($context['type'] === 'allowed') {
            return 'Sorry, this coupon only works with products from specific categories.';
        }
        return 'Sorry, this coupon cannot be used with some items in your cart.';
    },
    10,
    2
);
```

## Changelog

### 1.0.0 - 18th December 2025

* Initial release.
* Allowed categories with automatic child inclusion.
* Excluded categories with automatic child inclusion.
* Filter `runthings_wc_coupons_category_children_error_message` for custom error messages.

## License

This plugin is licensed under the GPLv3 or later.

## Author

[runthingsdev](https://runthings.dev/)

