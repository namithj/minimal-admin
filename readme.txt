=== Minimal Admin ===
Contributors: smartlogix
Donate link: https://smartlogix.co.in
Tags: admin, admin theme, admin styles, dashboard, minimal
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 0.0.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Security: namithj@smartlogix.co.in

Adds minimal, clean styling overrides to WordPress admin with updated colors, borders, and focus states while preserving core layout.

== Description ==

Minimal Admin is a lightweight WordPress plugin that adds minimal, clean styling overrides to the WordPress admin interface.

This plugin **does not replace** WordPress core admin styles. Instead, it adds override styles that enhance the default WordPress admin with:

* **Modern colors** - Updated color palette with better contrast ratios (WCAG AA compliant)
* **Improved focus states** - Clear, visible focus indicators for accessibility
* **Subtle shadows** - Depth and hierarchy through box shadows
* **Refined borders** - Cleaner border colors and border-radius
* **CSS Custom Properties** - Easy theming via CSS variables

= Accessibility =

All color choices maintain WCAG AA contrast ratios:

* Text colors provide at least 4.5:1 contrast against backgrounds
* Focus states are clearly visible with 2px outline rings
* Interactive elements have distinct hover/focus/active states

= CSS Custom Properties =

The plugin defines CSS custom properties on `:root` for easy customization:

`
:root {
    --mac-primary: #6366f1;
    --mac-primary-hover: #4f46e5;
    --mac-success: #10b981;
    --mac-warning: #f59e0b;
    --mac-danger: #ef4444;
    --mac-info: #3b82f6;
    --mac-text-primary: #1e293b;
    --mac-text-secondary: #475569;
    --mac-bg-primary: #ffffff;
    --mac-bg-secondary: #f8fafc;
    --mac-border-light: #e2e8f0;
    --mac-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --mac-shadow-focus: 0 0 0 2px rgba(99, 102, 241, 0.3);
    --mac-radius-md: 6px;
}
`

= Customization =

To customize colors, you can:

1. **Override CSS variables** in your theme or child plugin
2. **Modify the SCSS variables** in `src/scss/abstracts/_variables.scss` and rebuild

== Installation ==

1. Upload the `minimal-admin` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. That's it! The admin styling enhancements are automatically applied.

== Frequently Asked Questions ==

= Does this plugin replace WordPress admin styles? =

No. This plugin adds override styles on top of WordPress core admin CSS. It enhances the default admin with minimal, clean colors, improved focus states, and subtle visual refinements without removing any core functionality.

= Will this work with other admin plugins? =

Yes. Since this plugin only adds override styles with high specificity, it should work alongside other admin plugins. The styles are loaded with a high priority to ensure they apply after core styles.

= Can I customize the colors? =

Yes! You can override the CSS custom properties (CSS variables) in your theme or create a child plugin. All the main colors and styling values are defined as CSS custom properties on `:root`.

= Is this plugin accessible? =

Yes. All color choices maintain WCAG AA contrast ratios. Focus states are clearly visible with 2px outline rings, and interactive elements have distinct hover/focus/active states.

== Screenshots ==

1. Admin dashboard with minimal styling
2. Edit post screen with clean form elements
3. Settings page with improved button styles
4. Login page with modern styling

== Changelog ==

= 0.0.1 =
* Initial release
* Added modern color palette with WCAG AA compliance
* Added improved focus states for accessibility
* Added subtle shadows and refined borders
* Added CSS custom properties for easy theming
* Added login page styling enhancements

== Upgrade Notice ==

= 0.0.1 =
Initial release of Minimal Admin.
