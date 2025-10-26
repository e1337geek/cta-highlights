# CTA Highlights Plugin

A flexible WordPress plugin for displaying inline call-to-action elements using shortcodes, with an optional "highlight" effect that draws user focus by darkening the background and elevating the CTA element.

## Table of Contents

- [Overview](#overview)
- [Core Features](#core-features)
- [Basic Usage](#basic-usage)
- [Integration for Theme Developers](#integration-for-theme-developers)
  - [Creating Custom Templates](#creating-custom-templates)
  - [Template Variables](#template-variables)
  - [Template Locations](#template-locations)
  - [Child Theme Support](#child-theme-support)
- [Shortcode Attributes](#shortcode-attributes)
- [Highlight Cooldowns](#highlight-cooldowns)
  - [Global Cooldown](#global-cooldown)
  - [Template-Specific Cooldown](#template-specific-cooldown)
  - [Cookie and Local Storage](#cookie-and-local-storage)
  - [Overriding Timeout Values](#overriding-timeout-values)
- [Filters Reference](#filters-reference)
- [Actions Reference](#actions-reference)
- [Style Customization](#style-customization)
  - [CSS Custom Properties](#css-custom-properties)
  - [Template-Specific Styles](#template-specific-styles)
  - [Wrapper Classes](#wrapper-classes)
- [Accessibility](#accessibility)
- [Helper Functions](#helper-functions)
- [Requirements](#requirements)

---

## Overview

CTA Highlights allows you to insert calls-to-action (CTAs) anywhere in your content using a simple shortcode. CTAs remain in their natural inline position within the page flow. When the optional "highlight" feature is enabled, the plugin adds an overlay effect that darkens the rest of the page while elevating the CTA, drawing user attention without disrupting the document structure.

The plugin includes a smart cooldown system that prevents highlight effects from overwhelming users, with separate timers for global highlights and individual templates.

---

## Core Features

- **Inline CTAs**: Insert call-to-action elements anywhere using `[cta_highlights]` shortcode
- **Optional Highlight Effect**: Draw focus with an overlay and elevated z-index while keeping the CTA in position
- **Customizable Templates**: Override templates in your theme for complete control
- **Smart Cooldowns**: Configurable global and template-specific cooldown periods using localStorage
- **Accessibility First**: ARIA attributes, keyboard navigation, focus trapping, screen reader announcements
- **Performance Optimized**: Conditional asset loading, template caching, intersection observers
- **Modern Architecture**: Namespaced PHP, PSR-4 autoloading, modern JavaScript (ES6+)

---

## Basic Usage

### Simple CTA (No Highlight)

```
[cta_highlights template="default" cta_title="Subscribe to Our Newsletter" cta_button_text="Sign Up" cta_button_url="/subscribe"]
Get weekly updates delivered to your inbox!
[/cta_highlights]
```

### CTA with Highlight Effect

```
[cta_highlights
    template="default"
    cta_title="Limited Time Offer!"
    cta_button_text="Claim Your Discount"
    cta_button_url="/special-offer"
    highlight="true"
    highlight_duration="10"
]
Save 50% on all premium plans this week only!
[/cta_highlights]
```

---

## Integration for Theme Developers

### Creating Custom Templates

Templates are PHP files that define how your CTA will be rendered. The plugin provides a default template, but you can create your own for complete control.

#### Template Structure

```php
<?php
/**
 * Custom CTA Template: pricing-box
 *
 * Available variables:
 * @var CTAHighlights\Template\ViewData $view
 * @var callable $get_att - Helper function for safe attribute access
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get attributes with defaults
$title = $get_att( 'cta_title', 'Default Title' );
$price = $get_att( 'price', '$99' );
$features = $get_att( 'features', 'Feature list here' );
$button_text = $get_att( 'cta_button_text', 'Buy Now' );
$button_url = $get_att( 'cta_button_url', '#' );
?>

<div class="pricing-box">
    <h3 class="pricing-title"><?php echo esc_html( $title ); ?></h3>
    <div class="pricing-amount"><?php echo esc_html( $price ); ?></div>
    <div class="pricing-features"><?php echo wp_kses_post( $content ); ?></div>
    <a href="<?php echo esc_url( $button_url ); ?>" class="pricing-button">
        <?php echo esc_html( $button_text ); ?>
    </a>
</div>
```

### Template Variables

All templates receive these variables:

| Variable | Type | Description | Default |
|----------|------|-------------|---------|
| `$view` | `ViewData` | Object with all shortcode attributes | - |
| `$get_att` | `callable` | Helper function: `$get_att('key', 'default')` | - |
| `$template` | `string` | Template name being used | `'default'` |
| `$cta_title` | `string` | CTA title from shortcode | `''` |
| `$cta_content` | `string` | Processed CTA content | `''` |
| `$cta_button_text` | `string` | Button text | `'Learn More'` |
| `$cta_button_url` | `string` | Button URL | `'#'` |
| `$content` | `string` | Shortcode content (same as `$cta_content`) | `''` |
| `$custom_class` | `string` | Custom CSS classes | `''` |

**Additional attributes** passed via the shortcode are also available through `$get_att()`.

#### Three Ways to Access Variables

```php
// Method 1: Direct variable (extracted for convenience)
<?php echo esc_html( $cta_title ); ?>

// Method 2: ViewData object
<?php echo esc_html( $view->cta_title ); ?>

// Method 3: Helper function with default (recommended for custom attributes)
<?php echo esc_html( $get_att('custom_field', 'fallback value') ); ?>
```

### Template Locations

Templates are loaded in this order (first found wins):

1. **Child Theme**: `wp-content/themes/your-child-theme/cta-highlights-templates/{template-name}.php`
2. **Parent Theme**: `wp-content/themes/your-theme/cta-highlights-templates/{template-name}.php`
3. **Plugin**: `wp-content/plugins/cta-highlights/templates/{template-name}.php`

**Directory Structure Example:**

```
your-theme/
└── cta-highlights-templates/
    ├── pricing-box.php
    ├── pricing-box.css
    ├── newsletter-signup.php
    └── newsletter-signup.css
```

### Child Theme Support

Child themes automatically override parent theme templates. Place your templates in:

```
your-child-theme/cta-highlights-templates/
```

This follows WordPress's standard template hierarchy and will take precedence over both parent theme and plugin templates.

---

## Shortcode Attributes

### Standard Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `template` | `string` | `'default'` | Template name to use |
| `cta_title` | `string` | `''` | CTA headline/title |
| `cta_text` | `string` | `''` | Alias for `cta_title` |
| `cta_button` | `string` | `''` | Alias for `cta_button_text` |
| `cta_link` | `string` | `'#'` | Alias for `cta_button_url` |
| `cta_button_text` | `string` | `'Learn More'` | Button text |
| `cta_button_url` | `string` | `'#'` | Button URL |
| `background` | `string` | `''` | Custom background color (theme dependent) |
| `text_color` | `string` | `''` | Custom text color (theme dependent) |
| `alignment` | `string` | `'center'` | Content alignment (theme dependent) |
| `custom_class` | `string` | `''` | Additional CSS classes for wrapper |

### Highlight Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `highlight` | `string` | `'false'` | Enable highlight effect (`'true'` or `'false'`) |
| `highlight_duration` | `int` | `5` | Duration in seconds before auto-dismiss |

### Custom Attributes

You can pass **any custom attribute** to your templates:

```
[cta_highlights template="custom" price="$99" features="5 users, 10GB storage"]
```

Access in template:

```php
$price = $get_att( 'price', '$0' );
$features = $get_att( 'features', 'No features' );
```

---

## Highlight Cooldowns

The plugin uses a smart cooldown system to prevent highlight effects from being shown too frequently, which could annoy users.

### Global Cooldown

Once **any** CTA with `highlight="true"` is displayed, no other highlighted CTAs will show for the global cooldown period.

**Default**: 3600 seconds (1 hour)

### Template-Specific Cooldown

Each template has its own cooldown timer. Once a specific template's highlight is shown, that template won't highlight again until its cooldown expires.

**Default**: 86400 seconds (24 hours)

### Cookie and Local Storage

Cooldowns are tracked using the browser's **localStorage** API:

- **Key Format**:
  - Global: `cta_highlights_global`
  - Template: `cta_highlights_template_{template-name}`
- **Storage Structure**:
  ```javascript
  {
    "timestamp": 1234567890,
    "expiryTime": 1234571490
  }
  ```
- **Automatic Cleanup**: Expired entries are automatically removed when checked

**Note**: If localStorage is unavailable (private browsing, old browsers), cooldowns are skipped gracefully - highlights will still work but won't be throttled.

### Overriding Timeout Values

#### Via Filters (Recommended)

```php
// Change global cooldown to 30 minutes
add_filter( 'cta_highlights_global_cooldown', function( $seconds ) {
    return 1800; // 30 minutes
} );

// Change template cooldown to 1 week
add_filter( 'cta_highlights_template_cooldown', function( $seconds ) {
    return 604800; // 7 days
} );
```

#### Via JavaScript (Advanced)

Modify the config before initialization:

```javascript
// In your theme's JavaScript file (enqueued after cta-highlights-base)
document.addEventListener('DOMContentLoaded', function() {
    if (window.ctaHighlightsConfig) {
        window.ctaHighlightsConfig.globalCooldown = 1800;   // 30 minutes
        window.ctaHighlightsConfig.templateCooldown = 604800; // 7 days
    }
});
```

**Warning**: JavaScript modifications must run before the plugin initializes.

---

## Filters Reference

### Template and Rendering Filters

#### `cta_highlights_shortcode_atts`

Modify shortcode attributes before processing.

```php
add_filter( 'cta_highlights_shortcode_atts', function( $atts ) {
    // Force all CTAs to use 'premium' template
    $atts['template'] = 'premium';
    return $atts;
} );
```

**Parameters:**
- `$atts` (array): Shortcode attributes

**Returns:** (array) Modified attributes

---

#### `cta_highlights_template_args`

Modify template variables before rendering.

```php
add_filter( 'cta_highlights_template_args', function( $atts, $template_name, $template_path ) {
    // Add site name to all CTA titles
    if ( ! empty( $atts['cta_title'] ) ) {
        $atts['cta_title'] = $atts['cta_title'] . ' - ' . get_bloginfo( 'name' );
    }
    return $atts;
}, 10, 3 );
```

**Parameters:**
- `$atts` (array): Template variables
- `$template_name` (string): Template name
- `$template_path` (string): Full path to template file

**Returns:** (array) Modified template variables

---

#### `cta_highlights_template_output`

Modify final HTML output.

```php
add_filter( 'cta_highlights_template_output', function( $output, $template_name, $atts ) {
    // Add schema.org markup
    return '<div itemscope itemtype="https://schema.org/Offer">' . $output . '</div>';
}, 10, 3 );
```

**Parameters:**
- `$output` (string): Rendered HTML
- `$template_name` (string): Template name
- `$atts` (array): Template attributes

**Returns:** (string) Modified HTML

---

### Asset and Configuration Filters

#### `cta_highlights_force_enqueue`

Force asset loading even when no shortcode detected.

```php
add_filter( 'cta_highlights_force_enqueue', function( $force ) {
    // Always load on homepage
    return is_front_page() ? true : $force;
} );
```

**Parameters:**
- `$force` (bool): Whether to force enqueue

**Returns:** (bool)

---

#### `cta_highlights_global_cooldown`

Set global highlight cooldown in seconds.

```php
add_filter( 'cta_highlights_global_cooldown', function( $seconds ) {
    return 7200; // 2 hours
} );
```

**Parameters:**
- `$seconds` (int): Cooldown duration

**Returns:** (int) Cooldown in seconds

---

#### `cta_highlights_template_cooldown`

Set template-specific cooldown in seconds.

```php
add_filter( 'cta_highlights_template_cooldown', function( $seconds ) {
    return 172800; // 2 days
} );
```

**Parameters:**
- `$seconds` (int): Cooldown duration

**Returns:** (int) Cooldown in seconds

---

#### `cta_highlights_overlay_color`

Set overlay background color.

```php
add_filter( 'cta_highlights_overlay_color', function( $color ) {
    return 'rgba(0, 0, 0, 0.85)'; // Darker overlay
} );
```

**Parameters:**
- `$color` (string): CSS color value

**Returns:** (string) Color value

---

#### `cta_highlights_debug`

Enable debug logging in browser console.

```php
add_filter( 'cta_highlights_debug', function( $debug ) {
    return current_user_can( 'manage_options' ); // Only for admins
} );
```

**Parameters:**
- `$debug` (bool): Debug mode

**Returns:** (bool)

---

#### `cta_highlights_add_preconnect`

Control resource hint generation.

```php
add_filter( 'cta_highlights_add_preconnect', '__return_false' );
```

**Parameters:**
- `$add` (bool): Whether to add preconnect hint

**Returns:** (bool)

---

## Actions Reference

### Template Lifecycle Actions

#### `cta_highlights_before_template_include`

Fires immediately before template file is included.

```php
add_action( 'cta_highlights_before_template_include', function( $template_path, $view ) {
    // Log template usage
    error_log( "Loading CTA template: {$template_path}" );
}, 10, 2 );
```

**Parameters:**
- `$template_path` (string): Full path to template file
- `$view` (ViewData): Template data object

---

#### `cta_highlights_after_template_include`

Fires immediately after template file is included.

```php
add_action( 'cta_highlights_after_template_include', function( $template_path, $view ) {
    // Track template impressions
    do_action( 'analytics_track_event', 'cta_view', $view->template );
}, 10, 2 );
```

**Parameters:**
- `$template_path` (string): Full path to template file
- `$view` (ViewData): Template data object

---

### Lifecycle and Cache Actions

#### `cta_highlights_activated`

Fires when plugin is activated.

```php
add_action( 'cta_highlights_activated', function() {
    // Set default options on activation
    update_option( 'cta_highlights_default_template', 'custom' );
} );
```

---

#### `cta_highlights_deactivated`

Fires when plugin is deactivated.

```php
add_action( 'cta_highlights_deactivated', function() {
    // Clean up temporary data
    delete_transient( 'cta_highlights_stats' );
} );
```

---

#### `cta_highlights_template_cache_cleared`

Fires when template cache is cleared.

```php
add_action( 'cta_highlights_template_cache_cleared', function() {
    // Rebuild template index
    update_option( 'cta_highlights_templates_index', cta_highlights_get_templates() );
} );
```

---

### Security Actions

#### `cta_highlights_security_event`

Fires when a security-related event is detected.

```php
add_action( 'cta_highlights_security_event', function( $message ) {
    // Log to security monitoring system
    my_security_logger( 'CTA Highlights', $message );
} );
```

**Parameters:**
- `$message` (string): Security event description

---

## Style Customization

### CSS Custom Properties

The plugin uses CSS custom properties that you can override:

```css
/* In your theme's stylesheet */
:root {
    /* Overlay appearance */
    --cta-highlights-overlay-color: rgba(0, 0, 0, 0.85);

    /* CTA background (auto-detected by default) */
    --cta-highlights-cta-background: #ffffff;

    /* Close button sizing */
    --cta-highlights-close-button-size: 48px;

    /* Animation timing */
    --cta-highlights-transition-duration: 0.5s;

    /* Z-index layers */
    --cta-highlights-z-overlay: 2;
    --cta-highlights-z-highlight: 3;
    --cta-highlights-z-controls: 4;
}
```

### Template-Specific Styles

Create a CSS file matching your template name in the same directory:

**Template**: `cta-highlights-templates/pricing-box.php`
**Styles**: `cta-highlights-templates/pricing-box.css`

The plugin automatically loads template-specific CSS when the template is used.

**Example** (`pricing-box.css`):

```css
.cta-highlights-template-pricing-box .pricing-box {
    border: 2px solid #0073aa;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
}

.cta-highlights-template-pricing-box .pricing-amount {
    font-size: 3rem;
    font-weight: bold;
    color: #0073aa;
    margin: 1rem 0;
}

.cta-highlights-template-pricing-box .pricing-button {
    display: inline-block;
    background: #0073aa;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
}
```

### Wrapper Classes

Every CTA is wrapped with these classes:

- `.cta-highlights-wrapper` - Always present
- `.cta-highlights-template-{template-name}` - Template-specific class
- `.cta-highlights-enabled` - Present when `highlight="true"`
- `.cta-highlights-active` - Present when highlight is currently active
- Custom classes from `custom_class` attribute

**Example selectors:**

```css
/* Target all CTAs */
.cta-highlights-wrapper {
    margin: 2rem 0;
}

/* Target specific template */
.cta-highlights-template-default {
    background: #f5f5f5;
}

/* Target highlighted CTAs */
.cta-highlights-wrapper.cta-highlights-enabled {
    transition: transform 0.3s ease;
}

/* Active highlight state */
.cta-highlights-wrapper.cta-highlights-active {
    transform: scale(1.02);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}
```

---

## Accessibility

The plugin follows WCAG 2.1 AA standards:

### Keyboard Navigation
- **Tab**: Navigate through focusable elements within highlighted CTA
- **Shift+Tab**: Navigate backwards
- **Escape**: Dismiss active highlight
- **Focus Trap**: Keyboard focus stays within highlighted CTA

### Screen Reader Support
- Proper ARIA attributes (`role="dialog"`, `aria-modal`, `aria-labelledby`)
- Live region announcements when highlight activates
- Screen reader-only dismiss instructions
- Semantic HTML structure

### Visual Accessibility
- High contrast mode support
- Respects `prefers-reduced-motion`
- Forced colors mode support (Windows High Contrast)
- Visible focus indicators

---

## Helper Functions

### `cta_highlights()`

Get the main plugin instance.

```php
$plugin = cta_highlights();
```

**Returns:** `CTAHighlights\Core\Plugin`

---

### `cta_highlights_render_template( $template_name, $args, $echo )`

Programmatically render a CTA template.

```php
cta_highlights_render_template( 'pricing-box', array(
    'cta_title' => 'Pro Plan',
    'price' => '$99/month',
    'cta_button_text' => 'Subscribe',
    'cta_button_url' => '/checkout',
    'content' => '<ul><li>Feature 1</li><li>Feature 2</li></ul>'
), true );
```

**Parameters:**
- `$template_name` (string): Template to use
- `$args` (array): Template variables
- `$echo` (bool): Echo output or return (default: `true`)

**Returns:** (string|void) HTML output if `$echo = false`

---

### `cta_highlights_has_shortcode( $content )`

Check if content contains CTA shortcode.

```php
if ( cta_highlights_has_shortcode() ) {
    // Current post has CTA
}

if ( cta_highlights_has_shortcode( $custom_content ) ) {
    // Custom content has CTA
}
```

**Parameters:**
- `$content` (string|null): Content to check (default: current post)

**Returns:** (bool)

---

### `cta_highlights_get_templates()`

Get all available templates.

```php
$templates = cta_highlights_get_templates();
// Returns: array of template info with name, path, and location (theme/plugin)
```

**Returns:** (array)

---

### `cta_highlights_clear_cache()`

Clear template path cache.

```php
cta_highlights_clear_cache();
```

**Returns:** (void)

---

### `cta_highlights_get_template_loader()`

Get template loader instance.

```php
$loader = cta_highlights_get_template_loader();
$path = $loader->locate_template( 'custom' );
```

**Returns:** `CTAHighlights\Template\Loader`

---

### `cta_highlights_get_asset_manager()`

Get asset manager instance.

```php
$assets = cta_highlights_get_asset_manager();
```

**Returns:** `CTAHighlights\Assets\Manager`

---

## Requirements

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **Browser Support** (for highlight feature):
  - Chrome/Edge 90+
  - Firefox 88+
  - Safari 14+
  - Modern mobile browsers

**Optional Browser Features:**
- IntersectionObserver API (graceful degradation)
- localStorage API (cooldowns skip gracefully if unavailable)
- CSS Container Queries (fallback to media queries)

---

## License

GPL v2 or later
