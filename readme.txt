=== Accessibility Guard ===
Contributors: digiminati
Tags: accessibility, wcag, a11y, compliance, scanner
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WCAG 2.2 compliance scanner for WordPress. Scan pages for accessibility issues, auto-fix common problems, and generate accessibility statements.

== Description ==

Accessibility Guard scans your WordPress pages for WCAG 2.2 accessibility issues and helps you fix them. Unlike overlay widgets that mask problems without fixing them, this plugin identifies issues at the source code level.

**20+ WCAG 2.2 Checks Including:**

* Missing image alt text (WCAG 1.1.1)
* Empty links and buttons (WCAG 2.4.4)
* Color contrast ratio (WCAG 1.4.3)
* Heading hierarchy (WCAG 1.3.1)
* Missing form labels (WCAG 1.3.1)
* Missing document language (WCAG 3.1.1)
* Removed focus indicators (WCAG 2.4.7)
* Missing ARIA landmarks (WCAG 1.3.1)
* Empty table headers (WCAG 1.3.1)
* Missing skip navigation (WCAG 2.4.1)
* Duplicate element IDs (WCAG 4.1.1)
* Auto-playing media (WCAG 1.4.2)
* Restrictive viewport meta (WCAG 1.4.4)
* And more...

**Auto-Fix Features:**

* Inject skip navigation link
* Ensure HTML lang attribute
* Remove empty headings
* Add missing form labels (screen-reader friendly)

**Accessibility Statement Generator:**

Generate a complete accessibility statement page with your organization details, conformance target, and known limitations.

**Dashboard:**

* Summary cards showing total issues, pages scanned, and compliance percentage
* Per-page issue details with severity, WCAG reference, and element snippets
* A11y status column in Posts/Pages list tables
* Bulk scan all pages with progress indicator

== Installation ==

1. Upload the `wp-accessibility-guard` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **A11y Guard** in the admin sidebar
4. Click **Scan All Pages** to run your first scan
5. Configure auto-fixes in **A11y Guard > Settings**

== Frequently Asked Questions ==

= Is this an overlay widget? =

No. Accessibility Guard is a scanner that identifies issues in your actual page HTML. It does not inject a toolbar or overlay widget. Auto-fixes modify the source output, not a JavaScript layer.

= Can this guarantee WCAG compliance? =

No automated tool can guarantee full WCAG compliance. Approximately 40% of WCAG criteria can be tested automatically. Accessibility Guard helps you identify and fix the issues that can be detected programmatically.

= Does it work with page builders? =

Yes. The scanner works with any page builder (Elementor, Divi, etc.) because it scans the rendered HTML output, not the builder's internal data.

= Will it slow down my site? =

No. Scanning happens only in the WordPress admin via AJAX. The only front-end impact is from auto-fixes (skip link, lang attribute), which are minimal.

== Changelog ==

= 1.0.0 =
* Initial release
* 20+ WCAG 2.2 accessibility checks
* AJAX-based per-page and bulk scanning
* Auto-fix: skip links, lang attribute, empty headings, form labels
* Accessibility statement page generator
* Admin dashboard with summary cards and issues table
* A11y status column in post/page list tables
* Scan results caching via transients

== Screenshots ==

1. Dashboard with summary cards showing total issues, pages scanned, and compliance percentage.
2. Per-page issue details with severity levels, WCAG references, and element snippets.
3. Settings page to configure auto-fix features and scan post types.
4. Accessibility statement generator with live preview.
5. A11y status column in the Posts/Pages list table.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
