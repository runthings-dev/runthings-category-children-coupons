# Category Children Coupons for WooCommerce

Restrict WooCommerce coupons by product categories, with options to include or exclude child/descendant categories.

## Description

Category Children Coupons for WooCommerce provides a complete replacement for WooCommerce's built-in coupon category restrictions with additional flexibility:

**Include children mode:** Select a parent category and all its subcategories are automatically included. With WooCommerce's default restrictions, selecting "Clothing" only matches products directly in that category - not products in "T-Shirts" or "Trousers" subcategories. This plugin includes the entire category tree.

**Exclude children mode:** Match only the specific categories you select, without including subcategories. This mirrors WooCommerce's built-in behavior but is managed within this plugin's unified interface.

**Future-proof coupons:** The plugin stores your category selection and dynamically expands child categories at validation time - new subcategories are automatically included (or excluded) without editing existing coupons.

## Features

* Four category restriction fields for complete control:
  * Product categories (incl. children) - allowed categories with all descendants
  * Exclude categories (incl. children) - blocked categories with all descendants
  * Product categories (excl. children) - allowed categories only, no descendants
  * Exclude categories (excl. children) - blocked categories only, no descendants
* Automatic subcategory handling based on your preference
* Works alongside WooCommerce's other (non-category) coupon restrictions
* Customizable error messages via filter
* AutomateWoo compatibility - category restrictions are copied when generating coupons from templates

## How It Works

When you select a category in an "(incl. children)" field, the plugin automatically includes all subcategories during validation. When you use an "(excl. children)" field, only the exact categories you select are matched. Selected categories with children included are validated at usage time, so the current children are automatically used, even if they have changed since the coupon was set up.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/runthings-category-children-coupons` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Marketing > Coupons and edit or create a coupon.
4. In the "Usage restriction" tab, you will see four new category fields - two with "(incl. children)" and two with "(excl. children)" labels.

## Frequently Asked Questions

### How does this differ from WooCommerce's built-in category restrictions?

WooCommerce's built-in "Product categories" field only matches products directly assigned to the selected categories.

This plugin provides both options: "(incl. children)" fields automatically include all subcategories, while "(excl. children)" fields match only the exact categories you select - giving you complete control.

### Can I use both this plugin's fields and WooCommerce's built-in category fields?

Not recommended. The built-in category check runs before custom plugin checks. They operate as separate restrictions (AND logic). If you use both, a coupon must first pass the built-in category rules, which may cause unexpected results.

This plugin provides "(excl. children)" fields that mirror WooCommerce's built-in behavior, so you can use this plugin as a complete replacement without needing WooCommerce's built-in fields.

### What happens if I add new subcategories later?

They are automatically included! The plugin checks category relationships at validation time, so new subcategories are picked up immediately.

### Does this work with AutomateWoo?

Yes! When AutomateWoo generates coupons from a template coupon, the category restrictions are automatically copied to the generated coupon.

## Screenshots

### Category restriction fields
![The category restriction fields in the coupon Usage restriction tab](https://raw.githubusercontent.com/runthings-dev/runthings-category-children-coupons/master/.wordpress-org/screenshot-1.jpg)

### Valid coupon applied
![A percentage coupon correctly applied only to products in the allowed category](https://raw.githubusercontent.com/runthings-dev/runthings-category-children-coupons/master/.wordpress-org/screenshot-2.jpg)

### Excluded category
![A product from an excluded category shows no discount applied](https://raw.githubusercontent.com/runthings-dev/runthings-category-children-coupons/master/.wordpress-org/screenshot-3.jpg)

### Conflict warning
![Warning notice when both WooCommerce's built-in and this plugin's category fields are used together](https://raw.githubusercontent.com/runthings-dev/runthings-category-children-coupons/master/.wordpress-org/screenshot-4.jpg)

## Filters

### runthings_category_children_coupons_error_message

Customize the error message shown when a coupon fails category validation.

#### Parameters

* `$message` (string) - The default error message.
* `$context` (array) - Contains:
  * `coupon` (WC_Coupon) - The coupon object being validated.
  * `type` (string) - One of 'allowed', 'excluded', 'allowed_excl', or 'excluded_excl' indicating which validation failed.
  * `configured_category_ids` (array) - Term IDs selected in the coupon admin.
  * `expanded_category_ids` (array) - All term IDs including children (same as configured for excl. children types).

#### Example

```php
add_filter(
    'runthings_category_children_coupons_error_message',
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

### 1.3.0 - 16th January 2026

- Plugin renamed from runthings-wc-coupons-category-children to runthings-category-children-coupons to comply with WordPress.org trademark guidelines.
- Automatic migration of existing coupon settings to new meta key format on update.
- Changed filter hook from `runthings_wc_coupons_category_children_error_message` to `runthings_category_children_coupons_error_message`.

### 1.2.0 - 15th January 2026

- Added warning notice when conflicting WooCommerce category fields are set alongside plugin restrictions.
- Fixed custom error messages not displaying for percentage and fixed product discount coupons.

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
* Filter `runthings_category_children_coupons_error_message` for custom error messages.

## License

This plugin is licensed under the GPLv3 or later.

## Additional Notes

Built by Matthew Harris of runthings.dev, copyright 2025.

Visit [runthings.dev](https://runthings.dev/) for more WordPress plugins and resources.

Contribute or report issues at the [GitHub repository](https://github.com/runthings-dev/runthings-category-children-coupons).

Icon - Discount by Gregor Cresnar, from Noun Project, [https://thenounproject.com/browse/icons/term/discount/](https://thenounproject.com/browse/icons/term/discount/) (CC BY 3.0)

Icon - Tree view by Paweł Gleń, from Noun Project, [https://thenounproject.com/browse/icons/term/tree-view/](https://thenounproject.com/browse/icons/term/tree-view/) (CC BY 3.0)
