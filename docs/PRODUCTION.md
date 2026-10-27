# Production release — October 27, 2026

Live storefront: https://seedprints.com/
Protected staging: https://try.seedprints.com/

The standalone block theme and Seed Prints Core are deployed. The original XStore themes, frontend plugins, pages and media remain available. Merging a PR does not automatically deploy; this release was deployed over the approved SSH connection.

## Release scope and safety

- Private production database and code backups passed integrity checks before activation. Original uploads were not replaced.
- Deployment copied theme/plugin code only. No staging database, customer records, sessions, safety guards, configuration or private artwork was imported.
- Additive merchandising created 12 terms, appended 87 product taxonomy assignments, created one page and added two verified verse fields for product 657. Original content/categories were retained.
- Native Woo review recount corrected seven stale counters without deleting reviews. The native variation photo's filename-only alt was replaced with a descriptive label.
- All 386 stored price rows and the commerce-settings hash match the original snapshot. Existing customization charges remain $6.50 for shirts and $7.50 for tumblers.
- WordPress home/site addresses were corrected from HTTP to HTTPS. This enabled Square's existing digital-wallet initialization; merchant settings were not changed.
- An existing checkout HTTP 500 cleared after the supported SiteGround cache purge. Flexible Shipping passed official checksums; no shipping-plugin files or settings were changed.
- Elementor, Essential Addons, Pro Elements, XStore Core and menu-bar-cart were deactivated after replacement; files remain. HappyForms, WooCommerce, product add-ons, payment/shipping, security, SMTP and existing marketing integrations remain.

## Verified behavior

- 28 live responsive checks at 375, 768, 1280 and 1440 px: one H1/header/main and no document-level horizontal overflow.
- Eight live axe scans: Home, Shop, two PDPs, Custom, Our Story, populated Cart and Checkout; zero violations for the selected WCAG A/AA rule sets. The scans were split across fresh browser sessions after an audit navigation timeout.
- One click adds one item and opens the bag. Confirmed variant selection preserves the purchase button's position. A final regular-browser check confirms the missing-size prompt, a visible native GPay button, and 0 px movement after size selection.
- Native populated checkout contains Square cards, Cash App and the digital-wallet area. No order or payment was submitted.
- Ground Shipping quotes $6.50 for CT, DE, MA, MD, ME, NH, NJ, PA, RI and VT with the synthetic test item.
- Custom product selection and John 3:16 lookup update the live preview. The custom form sends no-store/private cache headers.
- Final tracked runtime code matches production byte-for-byte: 277 files. See `reports/production/deployed-source.json`.

## Analytics follow-up

PRs 17 and 18 correct product IDs, variation prices and quantities, native full-page add receipts, tax-exclusive custom prices and discounted checkout values. The seven storefront events target the existing verified Site Kit GA4 destination. Only its duplicate WooCommerce event-provider script is suppressed; the Google tag, Google Ads and AdSense remain. Existing logged-in-user exclusions were verified, including WordPress’s localized string flags. No tag, consent or integration settings changed.

Validation: 14 isolated client assertions, nine staging PHP assertions, and actual simple/variable staging forms each producing one accurate event to an isolated browser spy. The product page passes all four responsive widths, zero axe violations and zero button movement. Live markup confirms the destination, first-party analytics asset, product metadata and absence of the duplicate provider. Native populated checkout still renders Square, Cash App and its wallet area. No test analytics requests, orders, payments or emails were sent. Reports: `reports/analytics/`.

GA4 account-side receipt and paid-purchase delivery remain unverified. The isolated purchase fixture tests browser deduplication; it does not demonstrate a real payment or account-side conversion.

## Performance evidence and limits

These are cold-mobile Lighthouse laboratory samples on the same workstation, not field Core Web Vitals. Live merchant/marketing integrations were retained; resources were not blocked to improve the score.

| Metric | Original home | Released home | Released PDP |
| --- | ---: | ---: | ---: |
| Performance | 31 | 50 | 36 |
| Accessibility | 87 | 100 | 100 |
| SEO | 85 | 100 | 92 |
| LCP | 6.2 s | 3.8 s | 4.8 s |
| CLS | 0.015 | 0 | 0 |
| Total blocking time | 39,780 ms | 9,060 ms | 32,640 ms |

The live LCP target below 2.5 seconds is **not met**. Field INP below 200 ms is unverified; TBT is not INP. Traces show substantial existing Square/Google Pay, Google-tag and AdSense work. The homepage's initial layout shift was corrected by rendering hero copy before its photo. An additional dependency-order optimization was reverted after its live wallet/resize regression check stalled; native ordering remains.

Authoritative live reports: `reports/production/home-stable-lighthouse.report.html` and `reports/production/pdp-lighthouse.report.html`. Earlier live diagnostics are retained locally, not substituted for these results.

## Remaining work

1. Decide whether storefront display ads should be suppressed. The proposed exclusion keeps Analytics, Google Ads conversion tracking and blog display ads. It is **not installed**; existing monetization remains unchanged.
2. Choose/connect the lifecycle email platform. Existing HappyForms confirmation is configured to deliver the existing 10% coupon through SMTP; actual email delivery was not tested. Abandoned-cart and photo-review-request automations are not live.
3. Validate paid checkout and eligible Apple Pay/Google Pay devices with the merchant. The regular-browser GPay button was observed; no payment was attempted. PayPal is not installed and needs the owner's merchant connection/decision.
4. Verify account-side GA4 receipt and paid-purchase delivery with the merchant. Destination mapping and duplicate-handler suppression are verified in deployed markup; consent configuration was preserved.
5. Complete performance work and field measurements. External address lookup is not connected; native browser autofill remains. Confirm optional shipping-threshold/custom-turnaround claims and supply approved customer lifestyle/photos and remaining product verse copy.

## Rollback

Use the retained XStore theme and original frontend plugins to restore the previous presentation. **Keep Seed Prints Core active** once new custom carts/orders may exist: it preserves their customization charges, metadata and artwork access. New custom-order entry may need temporary assistance until roll-forward.

The private prepared rollback script restores the original theme/plugin activation set while retaining Core and verified HTTPS addresses. It does not import a database snapshot. Never overwrite newer production orders/users/settings with staging or the pre-release database backup. Back up production private artwork separately as real uploads arrive. Retain all original content and files until the owner approves cleanup.
