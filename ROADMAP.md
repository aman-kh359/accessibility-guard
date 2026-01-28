# Accessibility Guard - Product Roadmap

## Overview
WordPress WCAG 2.2 compliance scanner with auto-fixes and accessibility statement generation.

---

## Pricing Tiers

| Tier | Price | Target Audience |
|------|-------|-----------------|
| Free | $0 | Small blogs, personal sites |
| Pro | $49/year | Small businesses, freelancers |
| Agency | $149/year | Agencies, multiple sites |
| Enterprise | $299/year | Large organizations |

---

## Feature Matrix

### Phase 1 - Free Version (v1.0) ✅ COMPLETED

| Feature | Status |
|---------|--------|
| 20+ WCAG 2.2 checks (Level A, AA) | ✅ |
| Admin dashboard with summary cards | ✅ |
| Per-page scan results | ✅ |
| Bulk scan all pages | ✅ |
| A11y status column in post list | ✅ |
| Auto-fix: Skip navigation link | ✅ |
| Auto-fix: HTML lang attribute | ✅ |
| Auto-fix: Empty heading removal | ✅ |
| Auto-fix: Form label generation | ✅ |
| Accessibility statement generator | ✅ |
| Scan result caching (transients) | ✅ |

### Phase 2 - Pro Version (v2.0)

| Feature | Description |
|---------|-------------|
| Color contrast checker | Real-time WCAG AA/AAA contrast analysis |
| PDF accessibility scan | Check uploaded PDFs for accessibility |
| Scheduled scans | Automatic weekly/monthly scans |
| Email reports | Send scan reports to admin email |
| Priority support | 48-hour response time |
| Advanced auto-fixes | Alt text suggestions, ARIA labels |
| Export reports | CSV/PDF export of scan results |
| Custom check rules | Add/disable specific checks |

### Phase 3 - Agency Version (v3.0)

| Feature | Description |
|---------|-------------|
| Multi-site support | Network-wide scanning |
| White-label reports | Custom branding on reports |
| Client dashboard | Separate view for clients |
| Bulk site management | Manage multiple sites |
| API access | REST API for integrations |
| Slack/Teams notifications | Alert integrations |
| Role-based access | Control who can scan/fix |
| Historical trends | Track compliance over time |

### Phase 4 - Enterprise Version (v4.0)

| Feature | Description |
|---------|-------------|
| VPAT generation | Voluntary Product Accessibility Template |
| Legal compliance tracking | ADA, Section 508, EN 301 549 |
| Audit trail | Full logging of changes |
| SSO integration | SAML/OAuth support |
| Dedicated support | 24-hour response, phone support |
| Custom development | Bespoke feature requests |
| On-premise option | Self-hosted deployment |
| SLA guarantee | 99.9% uptime commitment |

---

## Development Timeline

### Q1 2026
- [x] Phase 1: Free version development
- [x] WordPress.org submission
- [ ] Gather user feedback
- [ ] Bug fixes and improvements

### Q2 2026
- [ ] Phase 2: Pro version development
- [ ] Payment integration (EDD/WooCommerce)
- [ ] License management system
- [ ] Pro version launch

### Q3 2026
- [ ] Phase 3: Agency version development
- [ ] Multi-site support
- [ ] API development
- [ ] Agency version launch

### Q4 2026
- [ ] Phase 4: Enterprise features
- [ ] VPAT generator
- [ ] Enterprise partnerships
- [ ] Enterprise version launch

---

## WCAG 2.2 Checks (Current)

### Level A (Critical)
1. Missing image alt text (1.1.1)
2. Empty links (2.4.4)
3. Empty buttons (2.4.4)
4. Missing form labels (1.3.1)
5. Missing document language (3.1.1)
6. Auto-playing media (1.4.2)
7. Missing page title (2.4.2)

### Level AA (Important)
8. Color contrast ratio (1.4.3)
9. Heading hierarchy (1.3.1)
10. Missing skip navigation (2.4.1)
11. Removed focus indicators (2.4.7)
12. Target size minimum (2.5.8)
13. Consistent navigation (3.2.3)
14. Link purpose clarity (2.4.4)

### Level AAA (Enhanced)
15. Duplicate IDs (4.1.1)
16. Missing ARIA landmarks (1.3.1)
17. Empty table headers (1.3.1)
18. Restrictive viewport meta (1.4.4)
19. Images of text (1.4.5)
20. Timing adjustable (2.2.1)

---

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 7.4+ (WordPress Plugin API) |
| Scanner | DOMDocument + DOMXPath |
| Admin UI | Classic PHP (WordPress Settings API) |
| AJAX | WordPress AJAX API |
| Caching | WordPress Transients API |
| Database | WordPress Options + Post Meta |

---

## Competitor Analysis

| Competitor | Price | Weakness |
|------------|-------|----------|
| accessiBe | $490/year | Overlay-based, doesn't fix source |
| UserWay | $490/year | Overlay-based, legal issues |
| WP Accessibility | Free | Limited checks, no auto-fix |
| One Click Accessibility | Free | Basic features only |

**Our Advantage:** Source-level fixes, no overlay, WCAG 2.2 compliant, affordable pricing.

---

## Links

- **GitHub:** https://github.com/aman-kh359/accessibility-guard
- **WordPress.org:** https://wordpress.org/plugins/accessibility-guard (pending approval)

---

## Contact

- **Author:** Digiminati
- **WordPress.org:** aman359
