# Email flow implementation handoff

Waiting for the owner's platform choice and authenticated account connection. Do not store API keys in theme code, Git, browser payloads or public files.

Welcome: explicit newsletter subscription → confirmed consent → first-order offer delivery using the owner-approved coupon → weekly verse subscription preferences. Preserve unsubscribe links and the existing list; do not import unconsented addresses.

Abandoned cart: platform's native Woo integration and verified customer consent/session rules → restore the server cart by a validated recovery URL → suppress when purchase is recorded. Use provider-supported integration, not raw customer email in analytics events.

Post-purchase: completed/fulfilled order event → owner-approved delay → request an honest review, with a link to the product's native review form and optional photo upload. Photos require display consent and moderation. Do not offer a reward conditioned on a positive review.

GA4 events are dataLayer payloads for the existing consent-aware tag integration: view_item_list, view_item, add_to_cart, begin_checkout, purchase, custom_order_start and custom_order_submit. No new tracking tag is injected. Production-only emission avoids polluting analytics with staging tests. Purchase is restricted to a paid order with its valid order key and deduplicated in the browser session. Confirm existing tags do not send duplicate ecommerce events before enabling mappings.
