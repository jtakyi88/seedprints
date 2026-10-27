# Seed Prints storefront

Protected staging preview: https://try.seedprints.com/

Standalone WordPress block theme and WooCommerce companion plugin. Activate WooCommerce and Seed Prints Core before switching to the Seed Prints theme. The theme bundles the standalone Phase 1 repair integration as a fallback; an active repair plugin takes precedence. Keep WowAddons for the existing country-choice flow and its original customization-charge definitions.

See [architecture and audit](docs/ARCHITECTURE.md), [components](docs/COMPONENTS.md), [release limits and rollback](docs/RELEASE.md), and [email integration handoff](docs/EMAIL-FLOWS.md). Source KJV data and font licenses are included.

Tests are for the protected staging environment. HTTP tests create synthetic cart sessions; the review-photo test creates a clearly marked pending review and must not be run against production. No payment test credentials or secrets are included. Production payment and email services are not configured by this repository.
