# Analytics verification

Run the client assertions without dependencies or network requests:

```sh
node tests/analytics.cjs theme/seedprints/assets/js/analytics.js
```

`analytics-staging.php` runs with `wp eval-file` only in a protected staging environment. It uses a fresh CLI cart, adds a synthetic item, checks its queued receipt and clears that cart. Never run it in a customer browser/session.

`analytics-browser.cjs` needs Google Chrome, selenium-webdriver and chromedriver available to Node (or through NODE_PATH). It is fixed to protected try.seedprints.com, intercepts configuration before scripts execute and replaces gtag with a local spy. It checks real simple and variable form submissions and cleans up its isolated cart. Output defaults to /tmp/seedprints-analytics-staging-reports; set SEEDPRINTS_QA_OUT to override. No order, payment or email is submitted and no test event is transmitted to Google.

The purchase client fixture is synthetic. It verifies browser deduplication, not a real payment or Analytics account receipt.
