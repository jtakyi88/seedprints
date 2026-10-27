# Email flow implementation handoff

The live site retains its existing HappyForms signup/contact forms and SMTP. The welcome confirmation is enabled and contains the existing 10% coupon; no test email was sent. Advanced flows await the owner's platform choice and authenticated account connection. Do not store API keys in theme code, Git, browser payloads or public files.

Welcome: explicit newsletter subscription → confirmed consent → first-order offer delivery using the owner-approved coupon → weekly verse subscription preferences. Preserve unsubscribe links and the existing list; do not import unconsented addresses.

Abandoned cart: platform's native Woo integration and verified customer consent/session rules → restore the server cart by a validated recovery URL → suppress when purchase is recorded. Use provider-supported integration, not raw customer email in analytics events.

Post-purchase: completed/fulfilled order event → owner-approved delay → request an honest review, with a link to the product's native review form and optional photo upload. Photos require display consent and moderation. Do not offer a reward conditioned on a positive review.

GA4 storefront events are view_item_list, view_item, add_to_cart, begin_checkout, purchase, custom_order_start and custom_order_submit. The bridge reads and verifies the existing Site Kit GA4 destination, then sends gtag events explicitly to that destination and emits compatible dataLayer payloads. No new tag or consent configuration is injected. Only Site Kit’s duplicate WooCommerce provider is suppressed while the bridge is active; its other providers, Google Ads and AdSense remain native. Existing logged-in exclusions are respected, including WordPress’s localized string flags. If destination verification or compatibility checks fail, direct transport and provider suppression are disabled.

Production-only emission avoids staging pollution. Product IDs, selected variation prices, quantities, native full-page cart receipts, existing custom charges and discounted checkout items are covered. Purchase is restricted to a paid order with its valid order key and deduplicated in the browser session. No customer email, address, design text or artwork is included. Isolated browser-spy tests confirm transport and values; account-side GA4 receipt and a real paid-purchase conversion still require merchant verification.
