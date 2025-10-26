# Performance Benchmarks

**Version:** 1.0.0
**Last Updated:** 2025-01-XX
**Plugin:** CTA Highlights

---

## Overview

This document defines performance benchmarks and targets for the CTA Highlights plugin. These metrics ensure the plugin delivers a fast, responsive user experience without degrading page performance.

### Why Performance Matters

- **User Experience**: Fast pages keep users engaged
- **SEO Rankings**: Google prioritizes fast-loading pages
- **Conversion Rates**: Every 100ms delay can reduce conversions by 1%
- **Accessibility**: Slow pages hurt users on slow connections/devices

---

## Performance Targets

### Core Web Vitals (Google's User Experience Metrics)

| Metric | Good | Needs Improvement | Poor | Target |
|--------|------|-------------------|------|--------|
| **LCP** (Largest Contentful Paint) | ≤ 2.5s | 2.5s - 4.0s | > 4.0s | **≤ 2.5s** |
| **FID** (First Input Delay) | ≤ 100ms | 100ms - 300ms | > 300ms | **≤ 100ms** |
| **CLS** (Cumulative Layout Shift) | ≤ 0.1 | 0.1 - 0.25 | > 0.25 | **≤ 0.1** |
| **INP** (Interaction to Next Paint) | ≤ 200ms | 200ms - 500ms | > 500ms | **≤ 200ms** |

### Additional Performance Metrics

| Metric | Target | Max Acceptable |
|--------|--------|----------------|
| **First Contentful Paint (FCP)** | ≤ 1.5s | 2.0s |
| **Time to Interactive (TTI)** | ≤ 3.5s | 5.0s |
| **Total Blocking Time (TBT)** | ≤ 200ms | 300ms |
| **Speed Index** | ≤ 3.0s | 4.0s |

---

## Asset Benchmarks

### File Sizes

| Asset | Uncompressed | Compressed (Gzip) | Target |
|-------|--------------|-------------------|--------|
| **CSS (Frontend)** | ≤ 30 KB | ≤ 10 KB | Minimize |
| **CSS (Admin)** | ≤ 40 KB | ≤ 15 KB | Minimize |
| **JavaScript (Frontend)** | ≤ 50 KB | ≤ 15 KB | Minimize |
| **JavaScript (Admin)** | ≤ 60 KB | ≤ 20 KB | Minimize |
| **Total Assets (Frontend)** | ≤ 100 KB | ≤ 30 KB | < 100 KB |

### HTTP Requests

| Page Type | Additional Requests | Target |
|-----------|---------------------|--------|
| **Page with Shortcode CTA** | 2-3 (CSS + JS + possible template CSS) | ≤ 3 |
| **Page with Auto-Insert CTA** | 2-3 | ≤ 3 |
| **Page without CTA** | 0 (conditional loading) | 0 |
| **Admin CTA Edit Page** | 2-4 | ≤ 5 |

---

## Database Performance

### Query Benchmarks

| Operation | Queries | Execution Time | Target |
|-----------|---------|----------------|--------|
| **Get Auto-Insert CTAs** | 1-2 | ≤ 10ms | < 20ms |
| **Get Fallback Chain** | 1-3 | ≤ 15ms | < 30ms |
| **Save CTA Meta** | 1-5 | ≤ 20ms | < 50ms |
| **Shortcode Rendering** | 0-1 | ≤ 5ms | < 10ms |

### Caching

| Cache Type | Hit Rate | Target |
|------------|----------|--------|
| **Template Path Cache** | ≥ 95% | ≥ 90% |
| **Auto-Insert CTA Cache** | ≥ 90% | ≥ 85% |

---

## JavaScript Performance

### Execution Time

| Operation | Time | Target |
|-----------|------|--------|
| **Script Parsing & Compile** | ≤ 50ms | < 100ms |
| **DOMContentLoaded Event** | ≤ 10ms | < 20ms |
| **Highlight Activation** | ≤ 50ms | < 100ms |
| **Auto-Insert DOM Manipulation** | ≤ 100ms | < 150ms |
| **Cooldown Check (localStorage)** | ≤ 5ms | < 10ms |

### Memory Usage

| Metric | Target | Max Acceptable |
|--------|--------|----------------|
| **Heap Size Increase** | ≤ 1 MB | 2 MB |
| **Event Listeners** | ≤ 10 | 20 |
| **DOM Nodes Added** | ≤ 50 | 100 |

---

## Measuring Performance

### 1. Lighthouse (Chrome DevTools)

**Run Lighthouse Audit:**
```bash
# Install Lighthouse CLI
npm install -g lighthouse

# Run audit on page with CTA
lighthouse https://your-site.com/test-post/ \
  --output=html \
  --output-path=./lighthouse-report.html \
  --chrome-flags="--headless"
```

**Expected Scores:**
- Performance: ≥ 90
- Accessibility: ≥ 95
- Best Practices: ≥ 90
- SEO: ≥ 90

### 2. WebPageTest

**URL:** https://webpagetest.org

**Test Settings:**
- Location: Choose closest to target audience
- Browser: Chrome
- Connection: 4G (or Cable for desktop)
- Repeat View: Yes (test caching)

**Metrics to Check:**
- First Byte Time
- Start Render
- Speed Index
- LCP
- TBT

### 3. Chrome DevTools Performance Panel

**Steps:**
1. Open Chrome DevTools (F12)
2. Go to "Performance" tab
3. Click "Record" (⚫)
4. Load page and interact with CTA
5. Click "Stop" (⏹)
6. Analyze timeline

**What to Look For:**
- Long tasks (> 50ms) in yellow/red
- Layout shifts (CLS)
- JavaScript execution time
- Paint/composite times

### 4. Chrome DevTools Coverage

**Steps:**
1. Open DevTools → "..." → More tools → Coverage
2. Click "Record" (⚫)
3. Load page
4. Click "Stop" (⏹)
5. Review unused CSS/JS

**Target:**
- CSS: ≥ 70% used
- JS: ≥ 60% used

### 5. Query Monitor (WordPress Plugin)

**Install Query Monitor plugin:**
```bash
wp plugin install query-monitor --activate
```

**Check:**
- Database queries count
- Query execution time
- Slow queries (> 0.05s)
- Duplicate queries

**Targets:**
- Total queries: < 50
- Plugin queries: ≤ 5
- No slow queries from plugin
- No duplicate queries

---

## Performance Testing Checklist

### Initial Page Load

- [ ] **LCP ≤ 2.5s** - Main content loads quickly
- [ ] **FCP ≤ 1.5s** - First paint happens early
- [ ] **CLS ≤ 0.1** - No layout shifts from CTA loading
- [ ] **TBT ≤ 200ms** - Page remains interactive
- [ ] **Speed Index ≤ 3.0s** - Visual completion is fast

### Asset Loading

- [ ] **CSS minified** - Check file size
- [ ] **JS minified** - Check file size
- [ ] **Gzip/Brotli compression** - Headers confirm compression
- [ ] **No render-blocking resources** - CSS/JS doesn't block rendering
- [ ] **Conditional loading** - Assets only load when CTA present

### JavaScript Execution

- [ ] **No long tasks** (> 50ms) - Check Performance tab
- [ ] **Smooth animations** - 60 FPS target
- [ ] **Fast highlight activation** - < 100ms
- [ ] **Fast auto-insertion** - < 150ms
- [ ] **No memory leaks** - Heap doesn't grow indefinitely

### Database Queries

- [ ] **Minimal queries** - ≤ 5 additional queries
- [ ] **Fast queries** - All < 50ms
- [ ] **No N+1 problems** - Query count doesn't scale with CTAs
- [ ] **Cached effectively** - Repeat views use cache

### Interactions

- [ ] **Fast scrolling** - No jank when scrolling to CTA
- [ ] **Fast close action** - Dismissal is immediate
- [ ] **Fast tab navigation** - Keyboard nav is responsive
- [ ] **Touch response** - Mobile tap/swipe is immediate

---

## Optimization Techniques

### Frontend Optimizations

**1. Critical CSS Inlining**
- Inline critical CTA styles in `<head>`
- Load full stylesheet async

**2. JavaScript Defer/Async**
- Use `defer` attribute on scripts
- Load non-critical JS async

**3. Code Splitting**
- Separate admin and frontend bundles
- Load only what's needed

**4. Image Optimization** (if using images in templates)
- Use modern formats (WebP)
- Implement lazy loading
- Provide responsive images

**5. Minimize DOM Manipulation**
- Batch DOM updates
- Use DocumentFragment for insertions
- Avoid layout thrashing

### Backend Optimizations

**1. Query Optimization**
- Use indexed columns
- Limit result sets
- Cache query results

**2. Object Caching**
- Use WordPress object cache
- Cache expensive operations
- Implement cache warming

**3. Transients**
- Store template paths
- Store auto-insert CTA lists
- Set appropriate expiration

**4. Minimize Plugin Conflicts**
- Hook late when appropriate
- Avoid hooking into every page load
- Conditional admin loading

### Asset Optimizations

**1. Minification**
```bash
# CSS minification
npm run build:css

# JavaScript minification
npm run build:js
```

**2. Compression**
- Enable Gzip on server
- Enable Brotli if supported
- Compress static assets

**3. CDN Usage** (for large sites)
- Serve assets from CDN
- Reduce latency
- Improve caching

---

## Performance Budgets

### Per-Page Budget

| Resource Type | Budget | Max Acceptable |
|---------------|--------|----------------|
| **Total Page Size** | ≤ 1.5 MB | 2.0 MB |
| **JavaScript** | ≤ 200 KB | 300 KB |
| **CSS** | ≤ 50 KB | 100 KB |
| **Images** | ≤ 500 KB | 1 MB |
| **Fonts** | ≤ 100 KB | 150 KB |

### CTA Highlights Plugin Budget

| Metric | Budget |
|--------|--------|
| **Additional JS** | ≤ 50 KB (15 KB compressed) |
| **Additional CSS** | ≤ 30 KB (10 KB compressed) |
| **HTTP Requests** | ≤ 3 |
| **LCP Impact** | ≤ 100ms |
| **TBT Impact** | ≤ 50ms |

---

## Continuous Monitoring

### 1. Real User Monitoring (RUM)

**Tools:**
- Google Analytics (Core Web Vitals report)
- Cloudflare Web Analytics
- New Relic Browser
- Sentry Performance Monitoring

**Track:**
- LCP, FID, CLS for real users
- Geographic performance differences
- Device/browser performance
- Error rates

### 2. Synthetic Monitoring

**Tools:**
- Lighthouse CI (automated audits)
- WebPageTest (scheduled tests)
- SpeedCurve (performance monitoring)

**Frequency:**
- Before each release
- Daily production monitoring
- After major WordPress/theme updates

### 3. Performance Regression Testing

**Add to CI/CD pipeline:**
```yaml
# .github/workflows/performance.yml
name: Performance Tests

on: [push, pull_request]

jobs:
  lighthouse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run Lighthouse
        uses: treosh/lighthouse-ci-action@v9
        with:
          urls: |
            https://staging.example.com/test-post/
          budgetPath: ./lighthouse-budget.json
          uploadArtifacts: true
```

---

## Lighthouse Budget Configuration

Create `lighthouse-budget.json`:

```json
[
  {
    "path": "/*",
    "resourceSizes": [
      {
        "resourceType": "script",
        "budget": 300
      },
      {
        "resourceType": "stylesheet",
        "budget": 100
      },
      {
        "resourceType": "document",
        "budget": 50
      },
      {
        "resourceType": "total",
        "budget": 1500
      }
    ],
    "resourceCounts": [
      {
        "resourceType": "script",
        "budget": 10
      },
      {
        "resourceType": "stylesheet",
        "budget": 5
      },
      {
        "resourceType": "third-party",
        "budget": 10
      }
    ],
    "timings": [
      {
        "metric": "first-contentful-paint",
        "budget": 1500
      },
      {
        "metric": "largest-contentful-paint",
        "budget": 2500
      },
      {
        "metric": "interactive",
        "budget": 3500
      },
      {
        "metric": "max-potential-fid",
        "budget": 100
      },
      {
        "metric": "cumulative-layout-shift",
        "budget": 0.1
      },
      {
        "metric": "total-blocking-time",
        "budget": 200
      }
    ]
  }
]
```

---

## Performance Testing Workflow

### Before Release

1. **Run Lighthouse audit** on test pages
2. **Check Coverage** for unused CSS/JS
3. **Measure Core Web Vitals** with real browser
4. **Run Query Monitor** to check database impact
5. **Test on slow connection** (throttle to 3G)
6. **Test on low-end device** (throttle CPU 4x)

### After Release

1. **Monitor RUM data** for real user metrics
2. **Check error rates** in production
3. **Review Lighthouse CI** reports
4. **Compare to baseline** metrics

### Regular Maintenance

- **Monthly**: Review performance metrics
- **Quarterly**: Full performance audit
- **Yearly**: Comprehensive optimization review

---

## Troubleshooting Slow Performance

### Issue: High LCP (> 2.5s)

**Possible Causes:**
1. **Large images** - Optimize images, use modern formats
2. **Render-blocking resources** - Defer non-critical CSS/JS
3. **Slow server response** - Optimize backend, use caching
4. **Third-party scripts** - Lazy load or remove

### Issue: High FID/INP (> 100ms/200ms)

**Possible Causes:**
1. **Long JavaScript tasks** - Break up long tasks, use web workers
2. **Too much JavaScript** - Code split, remove unused code
3. **Main thread blocking** - Defer non-essential work
4. **Heavy event listeners** - Debounce/throttle, use passive listeners

### Issue: High CLS (> 0.1)

**Possible Causes:**
1. **Images without dimensions** - Set width/height attributes
2. **Dynamic content injection** - Reserve space for CTA
3. **Web fonts loading** - Use font-display: swap
4. **Ads/embeds** - Reserve space, lazy load

### Issue: High TBT (> 200ms)

**Possible Causes:**
1. **Heavy JavaScript execution** - Optimize algorithms, lazy evaluate
2. **Large bundles** - Code split, tree shake
3. **Render-blocking resources** - Async/defer scripts
4. **Too many recalculations** - Batch DOM updates

---

## Performance Testing Tools Summary

| Tool | Purpose | When to Use |
|------|---------|-------------|
| **Lighthouse** | Overall audit | Before releases, CI/CD |
| **WebPageTest** | Detailed analysis | Investigate issues |
| **Chrome DevTools Performance** | Deep debugging | Development |
| **Chrome DevTools Coverage** | Unused code detection | Optimization |
| **Query Monitor** | Database performance | WordPress-specific |
| **Google Analytics** | Real user metrics | Ongoing monitoring |
| **Lighthouse CI** | Automated checks | Every commit/PR |

---

## Summary

Performance is a feature, not an afterthought. By maintaining these benchmarks and continuously monitoring performance metrics, we ensure the CTA Highlights plugin delivers a fast, responsive user experience that doesn't degrade page performance.

**Key Takeaways:**
- ✅ Target LCP ≤ 2.5s, FID ≤ 100ms, CLS ≤ 0.1
- ✅ Keep assets small (< 100 KB total, compressed)
- ✅ Minimize database queries (≤ 5 additional)
- ✅ Conditional asset loading (only when CTA present)
- ✅ Regular monitoring and testing

---

**End of Performance Benchmarks**
