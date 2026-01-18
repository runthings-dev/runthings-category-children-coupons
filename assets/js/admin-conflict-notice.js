/**
 * Conflict notice for category field combinations.
 *
 * Shows a warning when both WooCommerce built-in category fields
 * and the plugin's "incl. children" fields are used together.
 */
(function ($) {
    'use strict';

    const SELECTORS = {
        // WooCommerce built-in fields
        wcAllowed: '#product_categories',
        wcExcluded: '#exclude_product_categories',
        // Plugin fields (incl. children)
        pluginAllowed: '#runthings_ccc_allowed_categories_with_children',
        pluginExcluded: '#runthings_ccc_excluded_categories_with_children',
        // Plugin fields (excl. children)
        pluginAllowedExcl: '#runthings_ccc_allowed_categories_excl_children',
        pluginExcludedExcl: '#runthings_ccc_excluded_categories_excl_children',
        // Notice container class
        notice: '.runthings-category-conflict-notice'
    };

    /**
     * Check if a select field has any values selected.
     */
    function hasSelection(selector) {
        const el = document.querySelector(selector);
        if (!el) return false;

        // For select2/wc-enhanced-select, check selected options
        const selected = el.querySelectorAll('option:checked');
        return selected.length > 0;
    }

    /**
     * Determine if there's a conflict between built-in and plugin fields.
     */
    function hasConflict() {
        const wcHasValues = hasSelection(SELECTORS.wcAllowed) || hasSelection(SELECTORS.wcExcluded);
        const pluginHasValues = hasSelection(SELECTORS.pluginAllowed) || hasSelection(SELECTORS.pluginExcluded) ||
            hasSelection(SELECTORS.pluginAllowedExcl) || hasSelection(SELECTORS.pluginExcludedExcl);

        return wcHasValues && pluginHasValues;
    }

    /**
     * Update notice visibility based on current field states.
     */
    function updateNotice() {
        const $notices = $(SELECTORS.notice);
        if (!$notices.length) return;

        if (hasConflict()) {
            $notices.show();
        } else {
            $notices.hide();
        }
    }

    /**
     * Clone notice to WooCommerce built-in category fields section.
     */
    function cloneNoticeToBuiltInSection() {
        const $notice = $(SELECTORS.notice).first();
        if (!$notice.length) return;

        // Find the built-in exclude_product_categories field wrapper
        const $builtInField = $(SELECTORS.wcExcluded).closest('p.form-field');
        if (!$builtInField.length) return;

        // Clone and insert after the built-in field
        const $clone = $notice.clone();
        $builtInField.after($clone);
    }

    /**
     * Initialize listeners on all category fields.
     */
    function init() {
        const $notice = $(SELECTORS.notice);
        if (!$notice.length) return;

        // Clone notice to built-in category section
        cloneNoticeToBuiltInSection();

        // All fields to watch
        const fieldSelectors = [
            SELECTORS.wcAllowed,
            SELECTORS.wcExcluded,
            SELECTORS.pluginAllowed,
            SELECTORS.pluginExcluded,
            SELECTORS.pluginAllowedExcl,
            SELECTORS.pluginExcludedExcl
        ];

        fieldSelectors.forEach(function (selector) {
            const el = document.querySelector(selector);
            if (!el) return;

            // Listen for native change events
            el.addEventListener('change', updateNotice);

            // Select2 fires change event on the original select, but also
            // listen for select2 specific events just in case
            $(el).on('select2:select select2:unselect select2:clear', updateNotice);
        });

        // Initial check
        updateNotice();
    }

    // Initialize when DOM is ready
    $(init);
})(jQuery);

