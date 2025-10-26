# Phase 2 Complete: Test Factories & Traits

✅ **Status**: COMPLETE
📅 **Completed**: 2025-01-XX
🎯 **Coverage**: 100% of Phase 2 objectives

---

## Summary

Phase 2 has been successfully completed! This phase focused on creating reusable test utilities that make writing tests faster, more consistent, and easier to maintain. All test factories and traits are now in place and ready to use.

---

## What Was Created

### Test Factories (4 files)

Test factories provide convenient methods for creating test data. Instead of manually building arrays and calling WordPress functions, tests can now use simple factory methods.

#### 1. CTAFactory.php
**Location**: `tests/_support/Factories/CTAFactory.php`

**Purpose**: Create test CTA data for the auto-insertion system.

**Key Methods**:
- `create($overrides)` - Create a CTA in database
- `create_many($count, $overrides)` - Create multiple CTAs
- `create_with_conditions($conditions)` - Create CTA with storage conditions
- `create_fallback_chain($depth)` - Create linked fallback CTAs
- `create_circular_chain()` - Create circular reference (for error testing)
- `create_for_post_types($post_types)` - Create CTA for specific post types
- `create_for_categories($category_ids, $mode)` - Create CTA for categories
- `make($overrides)` - Create data array without saving

**Example Usage**:
```php
use CTAHighlights\Tests\Factories\CTAFactory;

// Create simple CTA
$cta_id = CTAFactory::create();

// Create CTA with overrides
$cta_id = CTAFactory::create([
    'name' => 'Newsletter Signup',
    'status' => 'active',
    'post_types' => ['post', 'page']
]);

// Create fallback chain (3 CTAs)
$chain = CTAFactory::create_fallback_chain(3);
```

#### 2. PostFactory.php
**Location**: `tests/_support/Factories/PostFactory.php`

**Purpose**: Create test WordPress posts.

**Key Methods**:
- `create($overrides)` - Create a post
- `create_many($count, $overrides)` - Create multiple posts
- `create_with_shortcode($atts, $content)` - Create post with CTA shortcode
- `create_with_paragraphs($count)` - Create post with N paragraphs
- `create_in_category($category_ids)` - Create post in category
- `create_with_auto_insert_disabled()` - Create post with auto-insert disabled
- `make($overrides)` - Create data array without saving

**Example Usage**:
```php
use CTAHighlights\Tests\Factories\PostFactory;

// Create simple post
$post_id = PostFactory::create();

// Create post with shortcode
$post_id = PostFactory::create_with_shortcode([
    'template' => 'default',
    'cta_title' => 'Subscribe'
], 'Join our newsletter!');

// Create post with 10 paragraphs
$post_id = PostFactory::create_with_paragraphs(10);
```

#### 3. UserFactory.php
**Location**: `tests/_support/Factories/UserFactory.php`

**Purpose**: Create test users with different roles for permission testing.

**Key Methods**:
- `create($overrides)` - Create a user
- `create_many($count, $overrides)` - Create multiple users
- `create_admin()` - Create administrator
- `create_editor()` - Create editor
- `create_author()` - Create author
- `create_contributor()` - Create contributor
- `create_subscriber()` - Create subscriber
- `create_and_login($overrides)` - Create and set as current user
- `create_and_login_admin()` - Create admin and login
- `create_with_capabilities($caps)` - Create with custom capabilities
- `create_with_meta($meta)` - Create with meta data
- `create_all_roles()` - Create one user of each role
- `make($overrides)` - Create data array without saving

**Example Usage**:
```php
use CTAHighlights\Tests\Factories\UserFactory;

// Create and login as admin
$admin_id = UserFactory::create_and_login_admin();

// Create all role types
$users = UserFactory::create_all_roles();
// Returns: ['administrator' => 1, 'editor' => 2, ...]

// Create user with custom capabilities
$user_id = UserFactory::create_with_capabilities([
    'edit_posts',
    'publish_posts'
]);
```

#### 4. TemplateFactory.php
**Location**: `tests/_support/Factories/TemplateFactory.php`

**Purpose**: Create temporary template files for testing template loading and overrides.

**Key Methods**:
- `create($name, $content, $location)` - Create template file
- `create_with_css($name, $template, $css)` - Create template with CSS
- `create_minimal($name)` - Create minimal valid template
- `create_invalid($name, $type)` - Create invalid template (for error testing)
- `create_with_custom_attributes($name, $atts)` - Create with custom attrs
- `create_in_multiple_locations($name, $locations)` - Create in multiple locations
- `cleanup()` - Delete all created templates
- `exists($name, $location)` - Check if template exists
- `get_directory($location)` - Get template directory path

**Example Usage**:
```php
use CTAHighlights\Tests\Factories\TemplateFactory;

// Create template in theme
$path = TemplateFactory::create('custom', $content, 'theme');

// Create minimal template
$path = TemplateFactory::create_minimal('test-template');

// Create in multiple locations (for override testing)
$paths = TemplateFactory::create_in_multiple_locations('custom', [
    'theme', 'parent', 'plugin'
]);

// Clean up all created templates
TemplateFactory::cleanup();
```

---

### Test Traits (4 files)

Test traits provide reusable functionality that can be added to any test class. They include helper methods and custom assertions.

#### 1. CreatesDatabase
**Location**: `tests/_support/Traits/CreatesDatabase.php`

**Purpose**: Database setup, teardown, and testing helpers.

**Key Methods**:
- `setupDatabase()` - Initialize database for testing
- `teardownDatabase()` - Clean up after tests
- `clearDatabaseTable()` - Truncate CTA table
- `dropDatabaseTable()` - Drop CTA table
- `recreateDatabaseTable()` - Drop and recreate table
- `getDatabaseRowCount()` - Get row count
- `getAllDatabaseRows()` - Get all rows
- `assertDatabaseRowCount($expected)` - Assert row count
- `assertDatabaseHasCTA($id)` - Assert CTA exists
- `assertDatabaseMissingCTA($id)` - Assert CTA doesn't exist
- `assertDatabaseTableExists()` - Assert table exists
- `insertRawDatabaseRow($data)` - Insert bypassing Database class
- `updateRawDatabaseRow($id, $data)` - Update bypassing Database class
- `getRawDatabaseRow($id)` - Get row bypassing Database class
- `assertNoDatabaseError()` - Assert no SQL errors

**Example Usage**:
```php
use CTAHighlights\Tests\Traits\CreatesDatabase;

class MyTest extends WP_UnitTestCase {
    use CreatesDatabase;

    public function setUp(): void {
        parent::setUp();
        $this->setupDatabase();
    }

    public function tearDown(): void {
        $this->teardownDatabase();
        parent::tearDown();
    }

    public function test_creates_cta() {
        $id = CTAFactory::create();

        $this->assertDatabaseHasCTA($id);
        $this->assertDatabaseRowCount(1);
    }
}
```

#### 2. CreatesTemplates
**Location**: `tests/_support/Traits/CreatesTemplates.php`

**Purpose**: Template file creation and testing helpers.

**Key Methods**:
- `setupTemplates()` - Initialize template testing
- `teardownTemplates()` - Clean up templates
- `createTemplate($name, $content, $location)` - Create template
- `createMinimalTemplate($name)` - Create minimal template
- `createTemplateWithCSS($name, $template, $css)` - Create with CSS
- `createTemplateInMultipleLocations($name, $locations)` - Create in multiple
- `createInvalidTemplate($name, $type)` - Create invalid template
- `cleanupCreatedTemplates()` - Delete all created templates
- `assertTemplateExists($name, $location)` - Assert exists
- `assertTemplateDoesNotExist($name, $location)` - Assert doesn't exist
- `assertTemplateLoadedFrom($output, $location)` - Assert loaded from location
- `renderTemplate($template, $args)` - Render and return output
- `assertTemplateRendersWithoutErrors($template)` - Assert no errors
- `assertTemplateContains($expected, $template, $args)` - Assert contains HTML

**Example Usage**:
```php
use CTAHighlights\Tests\Traits\CreatesTemplates;

class TemplateTest extends WP_UnitTestCase {
    use CreatesTemplates;

    public function setUp(): void {
        parent::setUp();
        $this->setupTemplates();
    }

    public function tearDown(): void {
        $this->teardownTemplates();
        parent::tearDown();
    }

    public function test_template_override() {
        $this->createTemplateInMultipleLocations('test', [
            'theme', 'plugin'
        ]);

        $output = $this->renderTemplate('test', [
            'cta_title' => 'Test'
        ]);

        // Theme should override plugin
        $this->assertTemplateLoadedFrom($output, 'theme');
    }
}
```

#### 3. CreatesShortcodes
**Location**: `tests/_support/Traits/CreatesShortcodes.php`

**Purpose**: Shortcode building, rendering, and testing helpers.

**Key Methods**:
- `buildShortcode($atts, $content)` - Build shortcode string
- `renderShortcode($atts, $content)` - Build and render shortcode
- `buildSelfClosingShortcode($atts)` - Build self-closing shortcode
- `buildHighlightShortcode($atts, $content)` - Build with highlight
- `buildShortcodeWithTemplate($template, $atts)` - Build with template
- `buildNestedShortcode($outer, $inner)` - Build nested shortcode
- `assertShortcodeRenders($atts, $content)` - Assert renders
- `assertShortcodeContains($expected, $atts)` - Assert contains HTML
- `assertShortcodeDoesNotContain($unexpected, $atts)` - Assert doesn't contain
- `assertShortcodeHasWrapperClasses($classes, $atts)` - Assert has classes
- `assertShortcodeUsesTemplate($template, $atts)` - Assert uses template
- `assertShortcodeHasHighlight($atts)` - Assert has highlight
- `assertShortcodeSanitizesXSS($xss_string)` - Assert XSS sanitized
- `createPostWithShortcode($atts, $content)` - Create post with shortcode

**Example Usage**:
```php
use CTAHighlights\Tests\Traits\CreatesShortcodes;

class ShortcodeTest extends WP_UnitTestCase {
    use CreatesShortcodes;

    public function test_shortcode_renders() {
        $this->assertShortcodeRenders([
            'template' => 'default',
            'cta_title' => 'Test'
        ], 'Content here');
    }

    public function test_xss_prevention() {
        $this->assertShortcodeSanitizesXSS(
            '<script>alert("XSS")</script>',
            'cta_title'
        );
    }
}
```

#### 4. AssertsHTML
**Location**: `tests/_support/Traits/AssertsHTML.php`

**Purpose**: Custom HTML assertions for testing rendered output.

**Key Methods**:
- `assertHTMLHasElement($html, $tag)` - Assert has element
- `assertHTMLHasElementWithAttributes($html, $tag, $atts)` - Assert has element with attrs
- `assertHTMLElementHasClass($html, $tag, $class)` - Assert has class
- `assertHTMLHasLink($html, $url)` - Assert has link
- `assertHTMLIsEscaped($html)` - Assert no XSS
- `assertHTMLHasARIA($html, $tag, $aria_atts)` - Assert ARIA attributes
- `assertHTMLHasProperHeadingHierarchy($html)` - Assert heading order
- `assertHTMLImagesHaveAltText($html)` - Assert images have alt
- `assertHTMLIsValid($html)` - Assert valid HTML
- `assertHTMLHasSemanticElements($html, $elements)` - Assert semantic tags
- `assertHTMLHasDataAttributes($html, $tag, $data)` - Assert data attributes
- `assertHTMLDoesNotHaveElement($html, $tag)` - Assert doesn't have element
- `getHTMLElementCount($html, $tag)` - Count elements
- `assertHTMLElementCount($expected, $html, $tag)` - Assert count
- `extractTextFromHTML($html)` - Extract text content
- `assertHTMLTextContains($expected, $html)` - Assert text contains

**Example Usage**:
```php
use CTAHighlights\Tests\Traits\AssertsHTML;

class RenderTest extends WP_UnitTestCase {
    use AssertsHTML;

    public function test_output_accessibility() {
        $html = do_shortcode('[cta_highlights]Test[/cta_highlights]');

        $this->assertHTMLIsEscaped($html);
        $this->assertHTMLImagesHaveAltText($html);
        $this->assertHTMLHasProperHeadingHierarchy($html);
    }

    public function test_has_wrapper_class() {
        $html = do_shortcode('[cta_highlights]Test[/cta_highlights]');

        $this->assertHTMLElementHasClass(
            $html,
            'div',
            'cta-highlights-wrapper'
        );
    }
}
```

---

## Integration with Bootstrap

All factories and traits are automatically loaded in `tests/_support/bootstrap.php`, so they're available in all tests without manual requires.

---

## Benefits

### 1. Faster Test Writing
Instead of:
```php
$data = array(
    'name' => 'Test CTA',
    'content' => '[cta_highlights]Content[/cta_highlights]',
    'status' => 'active',
    'cta_type' => 'primary',
    // ... 10 more fields
);
$database = new Database();
$id = $database->insert($data);
```

You can write:
```php
$id = CTAFactory::create(['name' => 'Test CTA']);
```

### 2. Consistency
All tests use the same methods to create data, ensuring consistent test data across the entire test suite.

### 3. Maintainability
If the data structure changes, you only need to update the factory, not every test file.

### 4. Better Assertions
Custom assertions make tests more readable:
```php
// Instead of:
$output = renderSomething();
$dom = new DOMDocument();
$dom->loadHTML($output);
// ... complex DOM parsing

// You can write:
$this->assertHTMLHasElementWithAttributes($output, 'a', [
    'href' => 'https://example.com'
]);
```

### 5. Error Testing
Factories include methods for creating invalid data and circular references, making it easy to test error handling.

---

## Usage Examples

### Complete Test Example Using All Utilities

```php
<?php
namespace CTAHighlights\Tests\Unit;

use CTAHighlights\Tests\Factories\CTAFactory;
use CTAHighlights\Tests\Factories\PostFactory;
use CTAHighlights\Tests\Factories\UserFactory;
use CTAHighlights\Tests\Traits\CreatesDatabase;
use CTAHighlights\Tests\Traits\CreatesShortcodes;
use CTAHighlights\Tests\Traits\AssertsHTML;
use WP_UnitTestCase;

class ExampleTest extends WP_UnitTestCase {
    use CreatesDatabase;
    use CreatesShortcodes;
    use AssertsHTML;

    public function setUp(): void {
        parent::setUp();
        $this->setupDatabase();
    }

    public function tearDown(): void {
        $this->teardownDatabase();
        parent::tearDown();
    }

    public function test_complete_workflow() {
        // Create admin user and login
        $admin_id = UserFactory::create_and_login_admin();

        // Create CTA
        $cta_id = CTAFactory::create([
            'name' => 'Newsletter',
            'post_types' => ['post']
        ]);

        // Assert it's in database
        $this->assertDatabaseHasCTA($cta_id);

        // Create post with shortcode
        $post_id = PostFactory::create_with_shortcode([
            'template' => 'default',
            'cta_title' => 'Subscribe'
        ], 'Join our newsletter!');

        // Render and test output
        $post = get_post($post_id);
        $output = do_shortcode($post->post_content);

        // Assert HTML quality
        $this->assertHTMLIsEscaped($output);
        $this->assertHTMLHasElement($output, 'div');
        $this->assertHTMLElementHasClass(
            $output,
            'div',
            'cta-highlights-wrapper'
        );

        // Assert content
        $this->assertHTMLTextContains('Subscribe', $output);
    }
}
```

---

## Next Steps

With Phase 2 complete, the testing infrastructure is now ready for writing actual tests. The next phase (Phase 3) will focus on high-priority security tests:

1. **DatabaseTest.php** - SQL injection, data sanitization (✅ Already created as example)
2. **LoaderTest.php** - Path traversal, file security
3. **HandlerTest.php** - XSS prevention, input sanitization
4. **AutoInsertAdminTest.php** - Nonce verification, capability checks
5. **PostMetaBoxTest.php** - Meta save security

---

## Files Created

**Factories** (4 files):
- `tests/_support/Factories/CTAFactory.php`
- `tests/_support/Factories/PostFactory.php`
- `tests/_support/Factories/UserFactory.php`
- `tests/_support/Factories/TemplateFactory.php`

**Traits** (4 files):
- `tests/_support/Traits/CreatesDatabase.php`
- `tests/_support/Traits/CreatesTemplates.php`
- `tests/_support/Traits/CreatesShortcodes.php`
- `tests/_support/Traits/AssertsHTML.php`

**Updated**:
- `tests/_support/bootstrap.php` - Added factory and trait loading
- `tests/IMPLEMENTATION-STATUS.md` - Updated progress tracking

---

## For LLM Agents

When writing new tests, simply:

1. Use the appropriate trait(s) for your test
2. Use factories to create test data
3. Use custom assertions for HTML validation
4. Follow the example in `DatabaseTest.php`

All utilities are automatically available - no imports needed beyond the `use` statements in your test class.

---

**Phase 2: Complete ✅**
**Ready for Phase 3: Security Tests**
