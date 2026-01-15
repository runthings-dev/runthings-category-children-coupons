# Coupons Category Children for WooCommerce

Restrict WooCommerce coupons by product categories, automatically including all child/descendant categories.

## Description

Coupons Category Children for WooCommerce extends the built-in WooCommerce coupon category restrictions in two ways:

**Easier category selection:** Select a parent category and all its subcategories are automatically included. With WooCommerce's default restrictions, selecting "Clothing" only matches products directly in that category - not products in "T-Shirts" or "Trousers" subcategories. This plugin includes the entire category tree.

**Future-proof coupons:** With default WooCommerce, if you add or reorganize subcategories, you must manually update every active coupon to include the new categories. This plugin stores the parent category selection and dynamically expands it at validation time - new subcategories are automatically included without editing existing coupons.

## Features

* Restrict coupons to specific category trees (parent + all descendants)
* Exclude entire category trees from coupon eligibility
* Automatic subcategory inclusion - no need to manually select every child category
* Works alongside WooCommerce's non-category coupon restrictions
* Customizable error messages via filter
* AutomateWoo compatibility - category restrictions are copied when generating coupons from templates

## How It Works

When you select a category in the "Product categories (incl. children)" field, the plugin automatically includes all subcategories during validation. If your category structure changes over time, the coupon restrictions update automatically.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/runthings-wc-coupons-category-children` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Marketing > Coupons and edit or create a coupon.
4. In the "Usage restriction" tab, you will see the new category fields with "(incl. children)" labels.

## Frequently Asked Questions

### How does this differ from WooCommerce's built-in category restrictions?

WooCommerce's built-in "Product categories" field only matches products directly assigned to the selected categories. This plugin's fields automatically include all subcategories of your selection.

### Can I use both this plugin's fields and WooCommerce's built-in category fields?

Not really. The built-in category check runs before custom plugin checks. They operate as separate restrictions (AND logic). If you use both, a coupon must first pass the built-in category rules, which isn't likely to contain the child categories.

For simplicity, we recommend using one or the other on a given coupon.

If you find that this is a blocking issue, please open an issue on the [GitHub repo](https://github.com/runthings-dev/runthings-wc-coupons-category-children/issues). Allowing per-category selection of "include children" or "only top-level" is on the potential features roadmap.

### What happens if I add new subcategories later?

They are automatically included! The plugin checks category relationships at validation time, so new subcategories are picked up immediately.

### Does this work with AutomateWoo?

Yes! When AutomateWoo generates coupons from a template coupon, the category restrictions are automatically copied to the generated coupon.

## Screenshots

### Category restriction fields
![The category restriction fields in the coupon Usage restriction tab](https://raw.githubusercontent.com/runthings-dev/runthings-wc-coupons-category-children/master/.wordpress-org/screenshot-1.jpg)

### Valid coupon applied
![A percentage coupon correctly applied only to products in the allowed category](https://raw.githubusercontent.com/runthings-dev/runthings-wc-coupons-category-children/master/.wordpress-org/screenshot-2.jpg)

### Excluded category
![A product from an excluded category shows no discount applied](https://raw.githubusercontent.com/runthings-dev/runthings-wc-coupons-category-children/master/.wordpress-org/screenshot-3.jpg)

## Filters

### runthings_wc_coupons_category_children_error_message

Customize the error message shown when a coupon fails category validation.

#### Parameters

* `$message` (string) - The default error message.
* `$context` (array) - Contains:
  * `coupon` (WC_Coupon) - The coupon object being validated.
  * `type` (string) - Either 'allowed' or 'excluded' indicating which validation failed.
  * `configured_category_ids` (array) - Term IDs selected in the coupon admin.
  * `expanded_category_ids` (array) - All term IDs including children.

#### Example

```php
add_filter(
    'runthings_wc_coupons_category_children_error_message',
    function ($message, $context) {
        if ($context['type'] === 'allowed') {
            $names = array_map(fn($id) => get_term($id)->name, $context['configured_category_ids']);
            return 'This coupon requires products from: ' . implode(', ', $names);
        }
        return 'Sorry, this coupon cannot be used with some items in your cart.';
    },
    10,
    2
);
```

## Changelog

### 1.1.0 - 12th January 2026

- Add compatibility with AutomateWoo coupon generation to clone custom meta fields

### 1.0.2 - 6th January 2026

- Fixed missing "And" separator in the coupon usage restriction panel to match WooCommerce core styling.

### 1.0.1 - 4th January 2026

- Fixed fatal error when validating coupons in order context (WC_Order_Item_Product vs array type).

### 1.0.0 - 19th December 2025

* Initial release.
* Allowed categories with automatic child inclusion.
* Excluded categories with automatic child inclusion.
* Filter `runthings_wc_coupons_category_children_error_message` for custom error messages.

## License

This plugin is licensed under the GPLv3 or later.

## Additional Notes

Built by Matthew Harris of runthings.dev, copyright 2025.

Visit [runthings.dev](https://runthings.dev/) for more WordPress plugins and resources.

Contribute or report issues at the [GitHub repository](https://github.com/runthings-dev/runthings-wc-coupons-category-children).
