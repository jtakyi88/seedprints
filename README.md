# Seed Prints storefront

WordPress and WooCommerce storefront redesign. The staging implementation is proposed in draft pull requests; production has not been deployed.

- `plugins/seedprints-storefront-fixes/`: ten ordered Phase 1 review changes.
- `theme/seedprints/` and `plugins/seedprints-core/`: standalone block theme and companion plugin, proposed after Phase 1.
- `docs/` and `reports/`: architecture, verification, and remaining launch work in the rebuild PR.

Protected staging: https://try.seedprints.com/

Review and merge Phase 1 PRs in order, then the storefront PR. `main` starts with documentation only. Deployment requires a separate reviewed release; merging does not deploy.
