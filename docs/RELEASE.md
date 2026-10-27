# Staging review and release

Preview: https://try.seedprints.com/ (restricted to the owner's current allowed network).

Production has not received the theme, plugin or database changes. The registered development SSH key and scoped staging database access were explicitly approved. No prices, payment/shipping settings or existing content were changed on production.

## Deliverables

- `plugins/seedprints-storefront-fixes/`: ten ordered Phase 1 review commits; original local history and exported patches remain in the handoff package.
- `theme/seedprints/`: standalone block theme, component styles, editor blocks, original-derived responsive AVIF/WebP assets and font licenses.
- `plugins/seedprints-core/`: merchandising taxonomies, saved pieces, local verse data, custom requests/artwork, moderated review photos and ecommerce payloads.
- `reports/`: Lighthouse, axe, responsive checks, validation and settings-hash evidence.
- `tests/`: read-only server validation assertions and guarded staging merchandising setup.

GitHub review repository: https://github.com/jtakyi88/seedprints. Phase 1 changes are proposed as ten ordered draft pull requests, followed by the storefront rebuild. Merging does not deploy to production.

## Remaining launch work

1. Review staging and approve the final design for production release.
2. Confirm the email platform/account connection. Existing forms are preserved; welcome, cart recovery and photo-review request flows are not live. The advertised first-order offer needs a verified fulfillment flow before launch; do not create a discount or change prices without owner approval.
3. Square credit cards, digital wallets and Cash App were already enabled in the source settings. Square's native wallet presentation hook is moved ahead of the checkout form. Wallet visibility still requires eligible devices/domains and a safe gateway test environment. Staging blocks payments, and no payment was attempted. PayPal is not installed; adding it requires an owner decision and merchant connection.
4. Confirm any free-shipping threshold and custom turnaround before adding promotional claims. No new shipping method/rate/threshold was configured.
5. Provide approved customer lifestyle/review photos to replace mockup imagery where desired. Current source product photos and existing published text testimonials are used; no customer provenance is asserted.
6. Custom cart submission, duplicate rejection, existing customization charges and a synthetic review-photo upload/moderation test are verified on staging. Finish the merchant test-mode payment/fulfillment checks with provider-approved test credentials; paid checkout has not been tested.
7. Resolve the PDP mobile LCP gap (2.8 seconds; homepage 2.3 seconds) and validate field INP. Lighthouse TBT is a laboratory proxy, not field INP. Keep staging no-index protection; remove only environment-specific restrictions on an approved production deployment.

## Deployment and rollback

Back up production code and database. Deploy code only first, retain XStore and original plugins, run targeted merchandising/content migrations without copying staging orders/users/settings, run the Phase 1 native review recount (`wp eval-file wp-content/plugins/seedprints-storefront-fixes/migrations/reconcile-review-counts.php`), flush rewrites, and verify real gateways/shipping. Preserve the original content for rollback. Switch back to the prior theme and deactivate the two new plugins to restore the legacy rendering; reactivate the previously used page-builder dependencies. The Phase 1 repair plugin can be evaluated independently on the legacy theme. On the new theme it can remain inactive: its bundled fallback has been verified with the standalone plugin disabled.

Do not copy staging wp-config, MU safety guard, .htaccess restrictions, database dumps, customer data, private uploads or sessions into production. Do not delete historical pages, media, forms or plugins without approval.

## Test fixtures

One clearly labeled synthetic photo review is held in moderation on staging (comment 6, product 657). Its image is private with 0600 permissions and it does not affect the public review count. Synthetic custom-cart sessions are isolated test sessions; no orders or payments were created. These fixtures are not production content.

## Existing customization pricing

WowAddons remains active to preserve the Africa country-choice flow. The custom builder reads its original assigned option definitions and includes the existing mandatory customization charges ($6.50 shirts, $7.50 tumblers). Stored base prices are unchanged. Unsupported future paid option configurations stop direct custom add-to-bag and ask for a request rather than silently omitting charges.

Address fields retain native browser address autofill. An external address-lookup service has not been connected.

Before production, confirm per-product verse references, delivery estimates, custom turnaround, and offer fulfillment with the owner. Native browser address autofill is present; third-party address lookup remains unconnected.
