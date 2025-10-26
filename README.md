# CTA Highlights Plugin v2.0 - Modernized PHP

## What Changed

### PHP Architecture
- **Namespaced Classes**: All code now uses `CTAHighlights\` namespace
- **PSR-4 Autoloading**: Automatic class loading, no more manual requires
- **Singleton Pattern**: Main plugin class prevents multiple instances
- **Dependency Injection**: Components properly injected where needed
- **No extract()**: Replaced with ViewData class for template variables
- **Template Caching**: File paths cached for better performance
- **Conditional Loading**: Assets only loaded when shortcode is present

### File Structure
```
cta-highlights/
├── cta-highlights.php          # Main plugin file with autoloader
├── uninstall.php                # Cleanup on deletion
├── includes/
│   ├── Core/
│   │   └── Plugin.php          # Main singleton plugin class
│   ├── Template/
│   │   ├── Loader.php          # Template loading with caching
│   │   ├── Registry.php        # Track templates used
│   │   └── ViewData.php        # Safe template variable access
│   ├── Assets/
│   │   └── Manager.php         # Conditional CSS/JS loading
│   └── Shortcode/
│       └── Handler.php         # Shortcode processing
├── assets/
│   ├── css/
│   │   ├── cta-highlights.css
│   │   └── templates/
│   └── js/
│       └── cta-highlights.js
└── templates/
    └── default.php
```

### Backward Compatibility
All old functions still work:
- `cta_highlights_template_shortcode()` - Deprecated but functional
- `$cta_highlights_templates_used` - Still updated
- All filters and actions maintain same names

### New Helper Functions
```php
// Get plugin instance
$plugin = cta_highlights();

// Get specific components  
$loader = cta_highlights_get_template_loader();
$assets = cta_highlights_get_asset_manager();

// Render template programmatically
cta_highlights_render_template('default', array(
    'cta_title' => 'Hello',
    'content' => 'World'
));

// Check for shortcode
if ( cta_highlights_has_shortcode() ) {
    // Do something
}

// Clear cache
cta_highlights_clear_cache();
```

### Performance Improvements
1. **Template Path Caching**: Reduces filesystem calls
2. **Conditional Asset Loading**: Only loads CSS/JS when needed
3. **Object Cache Support**: Works with Redis/Memcached
4. **Optimized Autoloader**: PSR-4 standard, very fast

### Security Enhancements
1. **Enhanced File Validation**: Multiple checks before loading templates
2. **Path Traversal Prevention**: Strict directory validation
3. **Security Event Logging**: Track suspicious activity
4. **Read-only ViewData**: Template data cannot be modified

## Migration Guide

### For Theme Developers
No changes needed! Your existing templates work as-is. However, you can optionally use the new ViewData object:

**Old Way (still works):**
```php
<?php echo esc_html( $cta_title ); ?>
```

**New Way (recommended):**
```php
<?php echo esc_html( $view->cta_title ); ?>
<?php echo esc_html( $view->get('cta_title', 'Default') ); ?>
<?php echo esc_html( $get_att('cta_title', 'Default') ); ?>
```

### For Plugin Developers
If you have code that hooks into CTA Highlights:

**Filters** - No changes, all work the same
**Actions** - All work the same
**Direct Function Calls** - Use new helpers or old deprecated functions

## Testing
All functionality has been preserved. Test your existing shortcodes and templates - they should work identically.

## Requirements
- WordPress 5.8+
- PHP 7.4+
