# Seed Prints architecture and audit

The approved architecture is a standalone WordPress block theme with a small companion plugin. WooCommerce remains the product, variation, stock, cart, checkout, order and pricing authority. Square and shipping integrations retain their settings.

A block theme uses the existing SiteGround PHP hosting, native Woo session cookies, checkout and wallet integrations. The owner can edit server-rendered sections in the Site Editor. It avoids a second hosting service and API/cart authentication boundary. Its trade-off is careful compatibility work with WooCommerce's classic hooks and retained plugins.

A headless Next.js alternative could offer separate deployment and fine-grained caching, but would add application hosting, API cart/session synchronization, preview infrastructure and additional work for Square wallets, custom product fields, analytics and checkout compatibility. It was not selected.

## Findings

- XStore child theme only enqueues parent/child styles; no child commerce overrides were found.
- 26 active plugins on the source site; 49 external script tags and 41 stylesheet links in initial home HTML.
- The two-pixel overflow came from Elementor's negative body margin.
- Product HTML was already server rendered; entrance-animation visibility caused the apparent empty grids.
- Cached review counters contradicted the actual review comments. Native WooCommerce recount corrected seven products on staging.
- The sole active Code Snippets entry adds media-library management tools; its identified hooks do not change cart prices, payment or shipping calculations.
- The hosting plan does not include built-in staging. A separate staging database and an IP-restricted staging copy were created without upgrading the plan.

## Replacement and removal plan

Deactivated on staging after replacement: Elementor, Pro Elements, Essential Addons, ET Core and menu-bar-cart. Their files and original page content are retained. The new theme replaces XStore, which remains available for rollback.

HappyForms remains active for the existing newsletter and contact forms until the email platform and replacement subscription delivery are connected. Contact Form 7 assets are not loaded on pages without its shortcode. Do not delete forms or plugins simply because their current templates no longer render.

Retain WowAddons (existing country choice and customization-charge definitions), WooCommerce, Square, shipping, security, SMTP and required operational integrations in production. Marketing/payment/SMTP integrations are intentionally inactive in the protected staging copy. Audit and retire duplicate shipping/payment plugins separately after owner review; do not remove business integrations based solely on page-weight counts.

## Staging safeguards

Staging has a separate database, server IP restrictions, no-index/no-store headers, blocked outgoing WordPress HTTP and email, disabled scheduled jobs/webhooks, and disabled payment/checkout submission. Product prices and payment/shipping option hashes match the source snapshot. Never deploy the staging database over production orders.
