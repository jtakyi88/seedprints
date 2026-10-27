# Seed Prints staging verification — October 27, 2026

Preview: https://try.seedprints.com/ (protected staging).

These are historical pre-release staging measurements. For the deployed site with live integrations, see [production verification](PRODUCTION.md) and `reports/production/`.

## Measured Lighthouse results

Mobile emulation, default simulated throttling, isolated Chrome 151 runs on the same workstation. These are laboratory samples, not field Core Web Vitals. Staging deliberately has no page caching and is not indexable.

| Metric | Original live home | Staging home | Staging PDP |
| --- | ---: | ---: | ---: |
| Performance | 31 | 69 | 64 |
| Accessibility | 87 | 100 | 100 |
| SEO | 85 | 69 | 61 |
| LCP | 6.2 s | 2.3 s | 2.8 s |
| CLS | 0.015 | 0 | 0 |
| Total blocking time | 39,780 ms | 1,740 ms | 2,480 ms |

The homepage meets the LCP target below 2.5 seconds in this run; the product page remains above target at 2.8 seconds. Field INP below 200 ms has not been verified; TBT is not INP. Staging's deliberate no-index restriction lowers the SEO score. Do not remove that protection just to improve a test score. Production cache validation and retirement of the remaining form dependencies are follow-up performance work.

Authoritative reports: `before/home-lighthouse.report.html`, `after/home-handoff-lighthouse.report.html`, and `after/pdp-handoff-lighthouse.report.html`. Earlier diagnostic runs are not release results.

## Accessibility and responsive behavior

- Final axe suite: Home, Shop, standard PDP, native country-choice PDP, Custom, Our Story, populated Cart and Checkout. WCAG 2 A/AA, 2.1 A/AA and 2.2 AA rules. See `after/final-axe.json`.
- Seven templates checked at 375, 768, 1280 and 1440 pixels: 28 checks, one H1, one shared header, one main landmark and no document-level horizontal overflow.
- Final PDP recheck at all four widths confirms one gallery, three thumbnails, a full-width Add to bag button, and zero button movement after size selection.
- Native modal bag/search/filter/size drawers, keyboard country selection, thumbnail navigation, variation reset, full-screen image viewing and focus return were exercised manually.
- The standalone theme was also verified with the transitional repair plugin inactive; all four bundled commerce assets return HTTP 200.
- Optional review photos require consent and moderation; an uploaded synthetic test image is private with 0600 permissions, while the public product review count remains zero.

Automated scans and the documented manual checks do not replace a full assistive-technology audit.

## Commerce and functional verification

- Price and payment/shipping option hashes match the original source snapshot; 386 base-price metadata rows are unchanged.
- Native review recount corrected seven stale cached review counters on staging without deleting reviews.
- One action adds one confirmed variant. Missing options prompt the user while keeping the button available.
- Mobile sticky Add to bag adds the selected Black / M tee once at $30 and opens the bag. The native Africa country selector preserves “No” in the cart at its original $30 price.
- Type filtering returns 14 placemats; combining it with an exact $15 price range returns 13. URLs omit empty filters. Product lists are server rendered.
- Wishlist save, restore and remove were exercised.
- 23 read-only custom/verse validation assertions pass, including the original $6.50 shirt and $7.50 tumbler customization charges.
- End-to-end synthetic custom-cart submission retains design metadata, adds the existing $7.50 charge to the $25 base variation (total $32.50), and rejects repeat submission of the same form key.
- No real orders or payment attempts were made. Staging blocks email, outgoing integrations, webhooks and checkout submission.

## Weight and content

Initial home HTML changed from 49 external script tags / 41 stylesheets to 15 / 10. The new home has 30 image elements and no missing alt attributes. Fifty-two source images have responsive WebP copies; original media is retained. The gallery is visible before scripts, thumbnails are server rendered, and closed product-accordion images load lazily.

Home review cards automatically include approved, consented native photo reviews when present and otherwise use existing published text testimonials. Approved customer lifestyle/review photos are still needed to replace source mockups where requested. No customer images or ratings were fabricated.

The mobile hero uses an approximately 25 KB AVIF at the tested resolution, with responsive WebP fallback. Product image viewing loads full-size media on demand. The homepage omits automatic cart-fragment startup; opening the bag and add-to-bag responses still refresh its contents.
