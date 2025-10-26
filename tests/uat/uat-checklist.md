# User Acceptance Testing (UAT) Checklist

**Version:** 1.0.0
**Last Updated:** 2025-01-XX
**Plugin:** CTA Highlights

---

## Overview

This checklist provides comprehensive manual testing scenarios for User Acceptance Testing (UAT) of the CTA Highlights plugin. Use this guide to verify all functionality works correctly from an end-user perspective before releasing updates.

### How to Use This Checklist

1. **Test on Clean Environment**: Use a fresh WordPress installation when possible
2. **Test Multiple Browsers**: Chrome, Firefox, Safari (minimum)
3. **Test Multiple Devices**: Desktop, tablet, mobile
4. **Check All Boxes**: Complete each test scenario
5. **Document Issues**: Note any failures with screenshots and steps to reproduce
6. **Sign Off**: QA team member should sign and date when complete

---

## Pre-Testing Setup

### Environment Preparation

- [ ] Fresh WordPress installation (version 5.8+)
- [ ] PHP 7.4+ confirmed
- [ ] Plugin activated successfully
- [ ] No JavaScript errors in browser console
- [ ] No PHP errors in error log
- [ ] Database tables created correctly
- [ ] Default templates exist in `/templates/` directory

### Test User Accounts

- [ ] Administrator account created
- [ ] Editor account created
- [ ] Author account created
- [ ] Subscriber account created

### Test Content

- [ ] At least 5 test posts created
- [ ] At least 2 test pages created
- [ ] At least 2 categories created with posts assigned
- [ ] Featured images added to posts

---

## 1. Installation & Activation

### Initial Installation

- [ ] **Install via WordPress Admin**
  - Upload plugin ZIP file
  - Click "Activate"
  - No errors displayed
  - Success message shown

- [ ] **Install via FTP**
  - Upload plugin folder to `/wp-content/plugins/`
  - Plugin appears in admin plugins list
  - Activation successful

- [ ] **Database Tables Created**
  - Check database for plugin tables (if any)
  - Verify schema is correct

- [ ] **Default Settings**
  - Plugin settings initialized correctly
  - Default templates available
  - No warnings in admin

### Deactivation & Reactivation

- [ ] **Deactivate Plugin**
  - Settings preserved
  - No errors on deactivation
  - Frontend CTAs disappear

- [ ] **Reactivate Plugin**
  - All settings restored
  - CTAs reappear on frontend
  - No data loss

### Uninstallation (Optional)

- [ ] **Uninstall Plugin**
  - Data cleanup (if configured)
  - Database tables removed (if configured)
  - No orphaned data

---

## 2. Admin Interface - CTA Creation

### Basic CTA Creation

- [ ] **Navigate to CTA Highlights Menu**
  - Menu item visible in WordPress admin sidebar
  - Clicking opens CTA list page
  - "Add New" button visible

- [ ] **Create New CTA**
  - Click "Add New"
  - Title field accepts text
  - Content editor works (both Classic and Block Editor)
  - Template dropdown shows available templates
  - All meta boxes visible

- [ ] **Primary CTA Creation**
  - Select "Primary" CTA type
  - Post type checkboxes work
  - Category checkboxes work (when post type selected)
  - Insertion settings visible
  - Direction dropdown (forward/reverse)
  - Position number input accepts values

- [ ] **Fallback CTA Creation**
  - Select "Fallback" CTA type
  - Fallback dropdown appears
  - Shows list of available primary CTAs
  - Can select parent CTA
  - Settings inherit or can be customized

- [ ] **Publish CTA**
  - Click "Publish" button
  - Success notice displayed
  - CTA appears in list
  - Edit link works

- [ ] **Save as Draft**
  - Click "Save Draft"
  - Draft status shown
  - Can edit again later
  - Not active on frontend

---

## 3. Admin Interface - CTA Management

### Editing CTAs

- [ ] **Edit Existing CTA**
  - Click CTA title or "Edit" link
  - All fields populated with saved data
  - Can modify title
  - Can modify content
  - Can change settings
  - Update button works

- [ ] **Quick Edit**
  - Hover over CTA in list
  - Click "Quick Edit"
  - Can change title, status
  - Save changes
  - Verify changes applied

### Deleting CTAs

- [ ] **Move to Trash**
  - Click "Trash" link
  - CTA moved to trash
  - Can be restored from trash
  - Confirmation message shown

- [ ] **Restore from Trash**
  - View "Trash" tab
  - Click "Restore"
  - CTA moved back to published/draft
  - All data intact

- [ ] **Permanent Delete**
  - From trash, click "Delete Permanently"
  - Confirmation dialog (if applicable)
  - CTA permanently removed
  - Cannot be restored

### Bulk Actions

- [ ] **Bulk Trash**
  - Select multiple CTAs
  - Choose "Move to Trash" from dropdown
  - Click "Apply"
  - All selected CTAs trashed

- [ ] **Bulk Delete**
  - Select multiple trashed CTAs
  - Choose "Delete Permanently"
  - Click "Apply"
  - All selected CTAs deleted

### Search & Filter

- [ ] **Search CTAs**
  - Enter CTA title in search box
  - Click "Search CTAs"
  - Results show matching CTAs only
  - Clear search shows all CTAs

- [ ] **Filter by Status**
  - Click "Published" link
  - Shows only published CTAs
  - Click "Draft" link
  - Shows only draft CTAs

---

## 4. Frontend - Shortcode Display

### Basic Shortcode Rendering

- [ ] **Add Shortcode to Post**
  - Create/edit post
  - Add shortcode: `[cta_highlights title="Test"]Content[/cta_highlights]`
  - Publish post
  - View post on frontend

- [ ] **Verify CTA Renders**
  - CTA wrapper element present
  - Title displays correctly
  - Content displays correctly
  - Styling applied (CSS loaded)
  - No JavaScript errors

- [ ] **Default Template**
  - Use `[cta_highlights]` without template attribute
  - Default template used
  - All template elements present

- [ ] **Custom Template**
  - Use `[cta_highlights template="custom"]`
  - Custom template used (if exists)
  - Falls back to default if custom doesn't exist

### Shortcode Attributes

- [ ] **Title Attribute**
  - `[cta_highlights title="My Title"]Content[/cta_highlights]`
  - Title displays in template

- [ ] **Highlight Attribute**
  - `[cta_highlights highlight="false"]Content[/cta_highlights]`
  - Highlight effect disabled
  - No overlay appears

- [ ] **Multiple Shortcodes**
  - Add 2+ shortcodes to same post
  - All render correctly
  - Each has unique instance
  - No ID conflicts

### Content Handling

- [ ] **HTML in Content**
  - Add HTML tags in shortcode content
  - HTML renders (not escaped)
  - Styling preserved

- [ ] **Empty Content**
  - `[cta_highlights title="Test"][/cta_highlights]`
  - CTA still renders
  - No errors

- [ ] **XSS Prevention**
  - Try `[cta_highlights title="<script>alert('XSS')</script>Test"]`
  - Script tags escaped
  - No alert executes
  - Text only displayed

---

## 5. Frontend - Highlight Effect

### Highlight Activation

- [ ] **Scroll into View**
  - Create post with CTA below fold
  - Load page (CTA not visible)
  - Scroll down to CTA
  - Highlight effect activates
  - Overlay appears
  - CTA elevated with higher z-index

- [ ] **Timing**
  - Highlight activates smoothly
  - Animation is smooth (not jarring)
  - Timing is appropriate (not too fast/slow)

- [ ] **Visual Elements**
  - Dark overlay covers page
  - CTA stands out from overlay
  - Close button visible
  - Close button styled correctly
  - All interactive elements visible

### Dismissal

- [ ] **Close Button Click**
  - Click close button (×)
  - Highlight dismisses
  - Overlay fades out
  - Page returns to normal
  - Smooth animation

- [ ] **Overlay Click**
  - Click dark overlay (not CTA)
  - Highlight dismisses
  - Same behavior as close button

- [ ] **Escape Key**
  - Activate highlight
  - Press Escape key
  - Highlight dismisses
  - Focus returns appropriately

### Cooldown Behavior

- [ ] **Global Cooldown Stored**
  - Dismiss CTA
  - Check localStorage (browser dev tools)
  - `cta_highlights_global_cooldown` key exists
  - Timestamp value correct

- [ ] **Template Cooldown Stored**
  - Dismiss CTA
  - Check localStorage
  - `cta_highlights_template_[template-name]` key exists

- [ ] **Cooldown Respected**
  - Dismiss CTA
  - Refresh page
  - Scroll to CTA
  - Highlight does NOT activate
  - CTA visible but not highlighted

- [ ] **Cookie Fallback**
  - Disable localStorage (browser setting)
  - Dismiss CTA
  - Check cookies (browser dev tools)
  - Cooldown cookie set
  - Cooldown still works on refresh

---

## 6. Frontend - Auto-Insertion

### Basic Auto-Insertion

- [ ] **Create Auto-Insert CTA**
  - Create primary CTA
  - Set post type: "Post"
  - Set direction: "Forward"
  - Set position: 3
  - Publish

- [ ] **Verify Auto-Insertion**
  - Create post with 5+ paragraphs (no shortcode)
  - Publish post
  - View on frontend
  - CTA auto-inserted after 3rd paragraph
  - Correct position in DOM

- [ ] **Forward Direction**
  - CTA appears after paragraph 3 (counting from start)
  - Position correct

- [ ] **Reverse Direction**
  - Change CTA to reverse direction
  - Position 2
  - CTA appears 2nd from end

### Post Type Targeting

- [ ] **Posts Only**
  - CTA set to "Post" type only
  - CTA appears on posts
  - CTA does NOT appear on pages
  - CTA does NOT appear on custom post types

- [ ] **Pages Only**
  - CTA set to "Page" type only
  - CTA appears on pages
  - CTA does NOT appear on posts

- [ ] **Multiple Post Types**
  - CTA set to "Post" and "Page"
  - CTA appears on both posts and pages

### Category Targeting

- [ ] **Specific Category**
  - CTA set to category "News"
  - CTA appears on posts in "News" category
  - CTA does NOT appear on posts in other categories

- [ ] **Multiple Categories**
  - CTA set to "News" and "Updates"
  - CTA appears on posts in either category

### Fallback Chain

- [ ] **Primary CTA Shows First**
  - Create primary CTA
  - Create fallback CTA with primary as parent
  - Visit matching post
  - Primary CTA shows

- [ ] **Fallback Shows After Dismissal**
  - Dismiss primary CTA
  - Refresh page
  - Fallback CTA shows instead
  - Primary on cooldown

- [ ] **Circular Reference Prevention**
  - Create CTA A → fallback B
  - Create CTA B → fallback A
  - Visit post
  - No infinite loop
  - One CTA shows (first in chain)

### Disable Auto-Insert

- [ ] **Meta Box Disable**
  - Create post
  - Check "Disable auto-insert CTAs" meta box
  - Publish post
  - No auto-inserted CTAs appear
  - Shortcode CTAs still work

---

## 7. Template System

### Default Templates

- [ ] **Default Template Works**
  - Use default template
  - All elements render
  - Styling correct
  - Responsive

- [ ] **Browse All Templates**
  - Check `/templates/` directory
  - All `.php` files valid
  - No syntax errors

### Theme Override

- [ ] **Create Theme Override**
  - Copy `/templates/default.php` to theme
  - Place in `/theme/cta-highlights-templates/default.php`
  - Modify theme version
  - Refresh page with CTA

- [ ] **Verify Theme Override Used**
  - Theme version renders (not plugin version)
  - Changes visible
  - View page source confirms theme path

- [ ] **Remove Theme Override**
  - Delete theme template file
  - Refresh page
  - Falls back to plugin template
  - No errors

### Cache Management

- [ ] **Template Cache Works**
  - Load page with CTA (cache template path)
  - Modify template file
  - Refresh page
  - Changes appear (cache cleared on file change)

- [ ] **Theme Switch Clears Cache**
  - Switch to different theme
  - Template cache cleared
  - Correct templates load

---

## 8. Accessibility

### Keyboard Navigation

- [ ] **Tab Through CTA**
  - Load page with CTA
  - Press Tab key repeatedly
  - Can tab to CTA links/buttons
  - Can tab to close button
  - Tab order logical

- [ ] **Shift+Tab Backwards**
  - Tab forward through CTA
  - Press Shift+Tab
  - Can tab backwards
  - Focus moves correctly

- [ ] **Escape to Dismiss**
  - Activate highlight
  - Press Escape
  - Highlight dismisses
  - Focus restored

- [ ] **Enter on Close Button**
  - Tab to close button
  - Press Enter
  - Highlight dismisses

- [ ] **Space on Close Button**
  - Tab to close button
  - Press Space
  - Highlight dismisses

### Focus Management

- [ ] **Focus Trap When Highlighted**
  - Activate highlight
  - Press Tab many times
  - Focus stays within CTA and overlay
  - Cannot tab to page content behind overlay

- [ ] **Auto-Focus Close Button**
  - Activate highlight
  - Close button receives focus automatically
  - Can immediately press Enter to close

- [ ] **Focus Restoration**
  - Focus an element
  - Activate highlight
  - Dismiss highlight
  - Focus returns to original element (or logical place)

### Screen Reader Support

- [ ] **ARIA Attributes Present**
  - Inspect CTA with browser tools
  - `role` attribute present
  - `aria-label` or `aria-labelledby` present
  - `aria-modal="true"` when highlighted

- [ ] **ARIA Live Announcements**
  - Enable screen reader (NVDA, JAWS, VoiceOver)
  - Scroll CTA into view
  - Screen reader announces CTA activation
  - Announcement is clear and helpful

- [ ] **Close Button Label**
  - Inspect close button
  - `aria-label` describes action (e.g., "Close call to action")
  - Screen reader announces button purpose

### Visual Accessibility

- [ ] **Color Contrast**
  - Use browser accessibility tools
  - Check contrast ratio of text
  - Should meet WCAG AA (4.5:1 for normal text)
  - All text readable

- [ ] **Zoom to 200%**
  - Browser zoom to 200%
  - All CTA content visible
  - No horizontal scroll
  - Text doesn't overflow

- [ ] **High Contrast Mode**
  - Enable OS high contrast mode
  - CTA still visible
  - Borders/outlines present
  - Usable interface

---

## 9. Cross-Browser Testing

### Desktop Browsers

- [ ] **Google Chrome (Latest)**
  - All features work
  - Rendering correct
  - No console errors
  - Performance acceptable

- [ ] **Mozilla Firefox (Latest)**
  - All features work
  - Rendering correct
  - No console errors
  - Performance acceptable

- [ ] **Safari (Latest - macOS)**
  - All features work
  - Rendering correct
  - No console errors
  - Performance acceptable

- [ ] **Microsoft Edge (Latest)**
  - All features work
  - Rendering correct
  - No console errors
  - Performance acceptable

### Mobile Browsers

- [ ] **Mobile Safari (iOS)**
  - Rendering correct
  - Touch events work
  - Tap to dismiss works
  - Responsive layout
  - No horizontal scroll

- [ ] **Chrome Mobile (Android)**
  - Rendering correct
  - Touch events work
  - Tap to dismiss works
  - Responsive layout
  - No horizontal scroll

### Responsive Design

- [ ] **Desktop (1920x1080)**
  - Layout correct
  - All elements visible
  - Proper spacing

- [ ] **Laptop (1366x768)**
  - Layout correct
  - All elements visible
  - Proper spacing

- [ ] **Tablet Portrait (768x1024)**
  - Responsive layout
  - Touch targets adequate size
  - Readable text

- [ ] **Mobile Portrait (375x667)**
  - Mobile-optimized layout
  - Touch targets adequate size
  - Readable text
  - No horizontal scroll

- [ ] **Mobile Landscape (667x375)**
  - Layout adapts
  - All content accessible
  - Usable interface

---

## 10. Performance

### Page Load Performance

- [ ] **First Contentful Paint**
  - Open Chrome DevTools (Network tab)
  - Load page with CTA
  - FCP under 1.5 seconds (good)

- [ ] **Largest Contentful Paint**
  - LCP under 2.5 seconds (good)
  - CTA doesn't block main content

- [ ] **Total Blocking Time**
  - TBT under 300ms
  - Page remains interactive

### Asset Loading

- [ ] **CSS Minified**
  - Check loaded CSS file
  - Should be minified in production
  - Size reasonable (< 50KB)

- [ ] **JavaScript Minified**
  - Check loaded JS file
  - Should be minified in production
  - Size reasonable (< 100KB)

- [ ] **No Unnecessary Requests**
  - Only loads assets when CTA present
  - No assets on pages without CTAs (if force_enqueue not set)

### Database Performance

- [ ] **Query Count**
  - Use Query Monitor plugin
  - Load page with auto-insert CTA
  - Additional queries reasonable (< 5)
  - No N+1 query problems

- [ ] **Query Speed**
  - All queries under 100ms
  - No slow queries

---

## 11. User Permissions

### Administrator

- [ ] **Full Access**
  - Can create CTAs
  - Can edit any CTA
  - Can delete any CTA
  - Can access all settings
  - Can manage templates

### Editor

- [ ] **Edit Posts Access**
  - Can create CTAs
  - Can edit any CTA
  - Can delete any CTA
  - Meta boxes visible

### Author

- [ ] **Own Posts Only**
  - Can create CTAs
  - Can edit own CTAs
  - Cannot edit others' CTAs
  - Meta boxes visible on own CTAs

### Contributor

- [ ] **Draft Only**
  - Can create draft CTAs
  - Cannot publish CTAs
  - Can edit own drafts
  - Cannot edit published CTAs

### Subscriber

- [ ] **No Access**
  - Cannot create CTAs
  - Cannot edit CTAs
  - CTA menu not visible
  - Meta boxes not visible

---

## 12. Error Handling & Edge Cases

### Invalid Data

- [ ] **Empty Title**
  - Try to publish CTA without title
  - Auto-generated title or validation error
  - Graceful handling

- [ ] **Missing Template**
  - Use non-existent template in shortcode
  - Falls back to default
  - No fatal error
  - Warning logged (optional)

- [ ] **Invalid Position**
  - Set position to 0 or negative
  - Validation prevents or clamps value
  - Sensible default used

### JavaScript Errors

- [ ] **JS Disabled**
  - Disable JavaScript in browser
  - CTA still renders (no highlight effect)
  - Content accessible
  - No broken layout

- [ ] **Console Errors**
  - Open browser console
  - Load various pages with CTAs
  - No JavaScript errors
  - No warnings (or only expected warnings)

### Theme Conflicts

- [ ] **Test with Popular Themes**
  - Twenty Twenty-Four
  - Astra
  - GeneratePress
  - No layout breaking
  - CTAs integrate well

- [ ] **Page Builder Compatibility**
  - Elementor
  - Beaver Builder
  - Divi
  - Shortcodes work in page builders
  - Auto-insert works (if applicable)

---

## 13. Security

### XSS Prevention

- [ ] **Shortcode Title**
  - `[cta_highlights title="<script>alert('XSS')</script>"]`
  - No script executes
  - Tags escaped in output

- [ ] **Shortcode Content**
  - `[cta_highlights]<script>alert('XSS')</script>[/cta_highlights]`
  - Allowed HTML only
  - Unsafe tags stripped

- [ ] **Admin Fields**
  - Try entering scripts in admin fields
  - Data sanitized on save
  - No XSS on admin or frontend

### SQL Injection

- [ ] **Search Field**
  - Try SQL injection in search: `' OR 1=1 --`
  - No SQL errors
  - Query properly escaped

- [ ] **URL Parameters**
  - Try manipulating URL parameters
  - No SQL injection possible
  - All queries use prepared statements

### CSRF Protection

- [ ] **Nonce Verification**
  - Check form submissions have nonces
  - Try submitting without nonce (use tools)
  - Request rejected

### File Upload (if applicable)

- [ ] **File Type Validation**
  - Only allowed file types accepted
  - Malicious files rejected

---

## 14. Multisite (If Applicable)

### Network Activation

- [ ] **Network Activate**
  - Activate on network
  - Works on all sites
  - No errors

### Per-Site Activation

- [ ] **Individual Site**
  - Activate on single site
  - Only active on that site
  - Other sites unaffected

### Settings

- [ ] **Network-Wide Settings**
  - Network admin settings work
  - Apply to all sites

- [ ] **Per-Site Settings**
  - Each site has own CTAs
  - No data leakage between sites

---

## 15. Upgrade Testing

### Plugin Update

- [ ] **Update from Previous Version**
  - Install previous version
  - Create test data
  - Update to new version
  - All data preserved
  - New features work
  - No errors

### Database Migration

- [ ] **Schema Changes**
  - Old database migrates correctly
  - Migration script runs automatically
  - No data loss
  - No errors in logs

---

## Final Checklist

### Documentation

- [ ] **README.md accurate**
  - Installation instructions correct
  - Usage examples work
  - Screenshots current

- [ ] **Changelog updated**
  - All changes documented
  - Version number correct

### Release Readiness

- [ ] **Version number updated** in plugin header
- [ ] **All tests passed** (unit, integration, E2E)
- [ ] **No console errors** in browser
- [ ] **No PHP errors** in logs
- [ ] **Performance acceptable** (<2.5s LCP)
- [ ] **Accessibility verified** (WCAG AA)
- [ ] **Cross-browser tested** (Chrome, Firefox, Safari)
- [ ] **Mobile tested** (iOS Safari, Chrome Android)
- [ ] **Security reviewed** (XSS, CSRF, SQL injection)

---

## Sign-Off

**Tester Name:** ___________________________

**Tester Role:** ___________________________

**Date Completed:** ___________________________

**Overall Result:** ⬜ PASS  ⬜ FAIL

**Notes/Issues Found:**

```
(Document any issues, bugs, or concerns discovered during testing)
```

---

**End of UAT Checklist**
