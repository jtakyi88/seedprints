# Mobile navigation verification

Requires Google Chrome and the QA-only Node packages selenium-webdriver, chromedriver and axe-core. Runtime storefront code has no new dependencies.

```sh
NODE_PATH=/path/to/qa/node_modules node tests/mobile-navigation.cjs
```

The default target is protected https://try.seedprints.com. SEEDPRINTS_QA_BASE may select https://seedprints.com for the final deployed check. Only these two hosts are accepted. SEEDPRINTS_QA_OUT overrides the default /tmp/seedprints-mobile-navigation report folder.

Checks cover 375/768/1280/1440 px, overflow, global header routes, native modal focus, Escape and focus return, backdrop and close button, desktop resize, category navigation, lazy photos, WCAG A/AA axe rules and the JavaScript-disabled menu. Tests browse public pages and follow category links; they submit no forms, add no cart items, send no emails and attempt no payments.

A scrollable native dialog may itself receive keyboard focus. The test permits that valid in-dialog stop rather than requiring every backward Tab to land on the last link.
