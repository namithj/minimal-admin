# Minimal Admin

A lightweight WordPress plugin that adds minimal, clean styling overrides to the WordPress admin interface.

## Approach

This plugin **does not replace** WordPress core admin styles. Instead, it adds override styles that enhance the default WordPress admin with:

- **Modern colors** - Updated color palette with better contrast ratios (WCAG AA compliant)
- **Improved focus states** - Clear, visible focus indicators for accessibility
- **Subtle shadows** - Depth and hierarchy through box shadows
- **Refined borders** - Cleaner border colors and border-radius
- **CSS Custom Properties** - Easy theming via CSS variables

## File Structure

```
minimal-admin/
├── minimal-admin.php          # Main plugin file
├── package.json               # Build tool configuration
├── src/
│   └── scss/
│       ├── minimal-admin.scss # Main entry point
│       ├── login.scss         # Login page entry point
│       ├── abstracts/         # Variables, mixins (not compiled)
│       └── components/        # Override stylesheets
│           ├── _variables.scss   # CSS custom properties
│           ├── _colors.scss      # Menu, admin bar, page colors
│           ├── _buttons.scss     # Button overrides
│           ├── _forms.scss       # Form element overrides
│           ├── _tables.scss      # List table overrides
│           ├── _notices.scss     # Admin notice overrides
│           ├── _cards.scss       # Postbox, widget overrides
│           ├── _navigation.scss  # Tabs, pagination overrides
│           └── _login.scss       # Login page overrides
└── dist/
    └── css/                   # Compiled CSS
```

## CSS Custom Properties

The plugin defines CSS custom properties on `:root` for easy customization:

```css
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
    /* ... and more */
}
```

## Build Commands

```bash
# Install dependencies
npx pnpm install

# Build CSS
npm run build

# Watch for changes
npm run dev

# Lint SCSS
npm run lint:scss
```

## Accessibility

All color choices maintain WCAG AA contrast ratios:
- Text colors provide at least 4.5:1 contrast against backgrounds
- Focus states are clearly visible with 2px outline rings
- Interactive elements have distinct hover/focus/active states

## Customization

To customize colors, you can:

1. **Override CSS variables** in your theme or child plugin
2. **Modify the SCSS variables** in `src/scss/abstracts/_variables.scss` and rebuild

## License

GPL-2.0+
