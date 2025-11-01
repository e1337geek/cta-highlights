# PHPCS Fixes Needed

This document tracks the remaining PHPCS violations that need manual fixes.

## Summary

After running `composer run phpcbf`, the following issues remain:

- **Inline comments missing punctuation**: ~100+ instances
- **Missing doc comments**: ~80+ functions/classes/properties
- **Security**: Nonce verification issues
- **I18n**: Missing translator comments
- **Naming**: Reserved keyword parameter names

---

## Quick Fixes

### 1. Inline Comments Missing Punctuation

**Issue**: Inline comments must end with `.`, `!`, or `?`

**Files affected**: Most `includes/` files

**Example**:
```php
// Bad
$foo = 'bar'; // This is a comment

// Good
$foo = 'bar'; // This is a comment.
```

**Bulk fix via regex** (in your IDE):
```regex
Find: //\s*([A-Z][^.!?]*)\n
Replace: // $1.\n
```

---

### 2. Missing Doc Comments

**Issue**: Classes, methods, and properties need PHPDoc comments

**Priority files**:
1. `includes/Core/Plugin.php` - 32 errors
2. `includes/Assets/Manager.php` - 16 errors
3. `includes/AutoInsertion/Manager.php` - 33 inline comment errors
4. `includes/AutoInsertion/Inserter.php` - 30 inline comment errors
5. `includes/Shortcode/Handler.php` - 14 errors
6. `includes/Template/Registry.php` - 13 errors

**Template**:
```php
/**
 * Short description.
 *
 * Longer description if needed.
 *
 * @param type $param Description.
 * @return type Description.
 */
```

---

## Security Fixes

### Nonce Verification

**File**: `includes/Admin/AutoInsertAdmin.php`

**Lines**: 85, 220, 247

**Issue**: Form data processing without nonce verification

**Fix**:
```php
// Add nonce check
if ( ! isset( $_POST['cta_highlights_nonce'] ) ||
     ! wp_verify_nonce( $_POST['cta_highlights_nonce'], 'cta_highlights_action' ) ) {
    wp_die( 'Security check failed' );
}

// Then process $_POST data
```

**Also add nonce to form**:
```php
<?php wp_nonce_field( 'cta_highlights_action', 'cta_highlights_nonce' ); ?>
```

---

## I18n Fixes

### Missing Translator Comments

**File**: `includes/Admin/AutoInsertListTable.php`, `includes/Shortcode/Handler.php`

**Issue**: Translatable strings with placeholders need translator comments

**Example**:
```php
// Bad
$message = sprintf( __( 'Found %s items', 'cta-highlights' ), $count );

// Good
/* translators: %s: number of items found */
$message = sprintf( __( 'Found %s items', 'cta-highlights' ), $count );
```

**Affected lines**:
- `includes/Admin/AutoInsertListTable.php`: 218, 233, 240
- `includes/Shortcode/Handler.php`: 141

---

## Naming Fixes

### Reserved Keyword Parameter Names

**File**: `includes/Template/ViewData.php`

**Line**: 44

**Issue**: Parameter named `$default` (reserved keyword)

**Fix**:
```php
// Before
public function get( $key, $default = '' ) {

// After
public function get( $key, $default_value = '' ) {
```

---

## Low Priority Warnings

### WordPress Alternative Functions

**File**: `includes/AutoInsertion/Matcher.php`

**Lines**: 136, 137

**Issue**: Use `wp_json_encode()` instead of `json_encode()`

**Fix**:
```php
// Before
$encoded = json_encode( $data );

// After
$encoded = wp_json_encode( $data );
```

### DOM Property Naming

**File**: `includes/AutoInsertion/Inserter.php`

**Lines**: 92, 94, 104

**Issue**: DOM properties like `$node->childNodes` use camelCase (JavaScript style)

**Action**: These are **false positives** - DOM properties must use JavaScript naming. Add `// phpcs:ignore` comments:

```php
// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
foreach ( $dom_element->childNodes as $child ) {
```

---

## Recommended Approach

### Phase 1: Quick Wins (30 minutes)
1. ✅ Fix inline comment punctuation (bulk regex find/replace)
2. ✅ Add translator comments (4 locations)
3. ✅ Fix reserved keyword parameter (1 location)
4. ✅ Replace `json_encode` with `wp_json_encode` (2 locations)

### Phase 2: Security (1 hour)
1. ✅ Add nonce verification to admin forms
2. ✅ Test admin functionality still works

### Phase 3: Documentation (2-3 hours)
1. ✅ Add doc comments to `Plugin.php`
2. ✅ Add doc comments to `Manager.php` (Assets)
3. ✅ Add doc comments to `Handler.php` (Shortcode)
4. ✅ Add doc comments to `Registry.php`

### Phase 4: DOM Property Suppressions (15 minutes)
1. ✅ Add `phpcs:ignore` comments for DOM properties

---

## Alternative: Temporarily Relax Rules

If you want to commit now and fix later, update `phpcs.xml.dist`:

```xml
<!-- Relax inline comment rules -->
<rule ref="Squiz.Commenting.InlineComment.InvalidEndChar">
    <severity>3</severity>  <!-- Warning instead of error -->
</rule>

<!-- Relax doc comment rules for pre-release -->
<rule ref="Squiz.Commenting.FunctionComment.Missing">
    <severity>3</severity>
</rule>

<rule ref="Squiz.Commenting.ClassComment.Missing">
    <severity>3</severity>
</rule>
```

This will allow you to commit and address these in a future PR.

---

## Current Status

- ✅ JavaScript linting configured and passing
- ⏳ PHP inline comments need punctuation
- ⏳ PHP missing doc comments
- ⏳ Security: nonce verification needed
- ⏳ I18n: translator comments needed

**Next step**: Choose Phase 1 quick wins, or temporarily relax PHPCS rules to commit v0.1.0.
