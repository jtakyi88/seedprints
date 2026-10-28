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
- Final tracked runtime code matches production byte-for-byte: 280 files. See `reports/production/deployed-source.json`.

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

Initial release reports: `reports/production/home-stable-lighthouse.report.html` and `reports/production/pdp-lighthouse.report.html`. Earlier live diagnostics are retained locally, not substituted for these results.

## Performance and SEO follow-up

PR 20 skips two late block-only stylesheets only after rendering confirms a page has no native WooCommerce blocks. Early-loaded CSS, native Woo blocks, cart, checkout, account, admin and native wallet styling remain. The live baseline transferred 31,920 bytes for these two stylesheets (93,901 bytes decoded); both requests are absent from Home and PDP after deployment. Live populated checkout retains both styles and its Square/Cash App/wallet markup. Eighteen isolated guard assertions and eight staging responsive checks passed; both staging axe scans had zero violations and the variant button moved 0 px.

The paired homepage laboratory samples are 54 → 55 performance, 3.5 → 3.4 s LCP, 0.005 → 0 CLS and 7,830 → 9,110 ms TBT. Accessibility and SEO remain 100. These single samples vary; the small LCP difference is not proof of a causal speed gain. Ads, analytics and native wallet scripts remain active. Anonymous repeat GETs returned a SiteGround cache hit around 0.4 seconds; cache settings were already enabled and were not changed.

PR 21 adds missing PDP meta descriptions from existing verse meaning or product copy, with a product-name/brand fallback and dedicated-SEO-plugin deferral. Legacy prose containing written prices is excluded from snippets. For example, the placemat copy mentions $12 while its stored price is $15. Original prose and stored prices were preserved; written-price content needs owner review. Home, simple and variable products each return exactly one description. No product content, prices or merchant settings changed.

The final live samples report Home **55 performance / 100 accessibility / 100 SEO**, 3.4 s LCP, 0 CLS and 9,110 ms TBT; PDP **37 / 100 / 100**, 5.1 s LCP, 0 CLS and 13,350 ms TBT. The earlier CSS-only PDP sample was 5.2 s LCP. No scripts or resources were blocked to produce these scores. These are laboratory samples, not field Core Web Vitals, and neither page meets the LCP target.

Current follow-up measurements and retained reports: `reports/performance-followup/` and `reports/seo-followup/`. Earlier release reports above remain historical evidence. The live LCP target remains unmet and field INP remains unverified.

## Mobile navigation follow-up — October 28, 2026

PR 23 completes the photo-led mobile category drawer using six existing source photos and the shared native modal controls. The header's original menu remains the fallback without JavaScript or native-dialog support; desktop navigation remains above 1100 px. Native focus return, Escape, backdrop closing, background scroll locking and desktop-resize closing were verified. The minimal checkout header does not include the mobile drawer.

Protected staging and live checks passed at 375, 768, 1280 and 1440 px with zero document or drawer overflow. Each run covered Home plus Shop, PDP, Custom and Our Story; category links, close controls, keyboard focus, resize behavior and the JavaScript-disabled menu passed. Both open-drawer axe scans per environment reported zero violations for the selected WCAG A/AA rule sets. Photos are lazy-loaded and no new image assets, plugins or runtime dependencies were added. Visual evidence and reproducible test instructions are in `reports/mobile-navigation/` and `tests/MOBILE-NAVIGATION.md`.

Live populated checkout retains Square, Cash App and wallet markup, with no order/payment/email submitted. All 386 price rows and the commerce-settings hash still match the baseline. Original content, payment/shipping settings, ads and email integrations remain unchanged. All 280 tracked runtime files match production.

Latest live homepage laboratory sample: **61 performance / 100 accessibility / 100 SEO**, **3.0 s LCP**, **0 CLS**, **8,580 ms TBT**. Existing ads, analytics and payment integrations were retained; no resources were blocked. This is a single sample, not proof that the drawer caused a speed improvement. The LCP target below 2.5 seconds remains unmet, and field INP remains unverified. The latest homepage report is `reports/mobile-navigation/home-lighthouse.report.html`; the most recent PDP measurement remains the October 27 follow-up above.

## Remaining work

1. Decide whether storefront display ads should be suppressed. The proposed exclusion keeps Analytics, Google Ads conversion tracking and blog display ads. It is **not installed**; existing monetization remains unchanged.
2. Choose/connect the lifecycle email platform. Existing HappyForms confirmation is configured to deliver the existing 10% coupon through SMTP; actual email delivery was not tested. Abandoned-cart and photo-review-request automations are not live.
3. Validate paid checkout and eligible Apple Pay/Google Pay devices with the merchant. The regular-browser GPay button was observed; no payment was attempted. PayPal is not installed and needs the owner's merchant connection/decision.
4. Verify account-side GA4 receipt and paid-purchase delivery with the merchant. Destination mapping and duplicate-handler suppression are verified in deployed markup; consent configuration was preserved.
5. Complete performance work and field measurements. External address lookup is not connected; native browser autofill remains. Confirm optional shipping-threshold/custom-turnaround claims and supply approved customer lifestyle/photos and remaining product verse copy.

## Rollback

Use the retained XStore theme and original frontend plugins to restore the previous presentation. **Keep Seed Prints Core active** once new custom carts/orders may exist: it preserves their customization charges, metadata and artwork access. New custom-order entry may need temporary assistance until roll-forward.

The private prepared rollback script restores the original theme/plugin activation set while retaining Core and verified HTTPS addresses. It does not import a database snapshot. Never overwrite newer production orders/users/settings with staging or the pre-release database backup. Back up production private artwork separately as real uploads arrive. Retain all original content and files until the owner approves cleanup.
