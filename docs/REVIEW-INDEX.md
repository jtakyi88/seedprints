# GitHub review order

Repository: https://github.com/jtakyi88/seedprints

The PRs were initially opened as drafts. GitHub records their current merge status. Merging does not deploy to production. The repository was empty, so `main` contains the project introduction and ignore rules.

Review Phase 1 PRs 1–10 in order, then the storefront rebuild. Each PR initially targets the preceding branch to show only its incremental changes. After merging a predecessor into `main`, retarget the next PR to `main` and verify its diff before merging. Do not merge later fixes into an already merged stack branch.

| Order | Review | Initial base |
| --- | --- | --- |
| 1 | [Phase 1 (1/10): stabilize storefront layout and variation space](https://github.com/jtakyi88/seedprints/pull/1) | `main` |
| 2 | [Phase 1 (2/10): shared header and immediate bag feedback](https://github.com/jtakyi88/seedprints/pull/2) | `review/phase1-01` |
| 3 | [Phase 1 (3/10): visible first-render products and accessible legacy controls](https://github.com/jtakyi88/seedprints/pull/3) | `review/phase1-02` |
| 4 | [Phase 1 (4/10): native cart and checkout with express-wallet placement](https://github.com/jtakyi88/seedprints/pull/4) | `review/phase1-03` |
| 5 | [Phase 1 (5/10): confirmed variants and one addition per action](https://github.com/jtakyi88/seedprints/pull/5) | `review/phase1-04` |
| 6 | [Phase 1 (6/10): preserve native product-add-on validation](https://github.com/jtakyi88/seedprints/pull/6) | `review/phase1-05` |
| 7 | [Phase 1 (7/10): stable variation spacing during Woo animation](https://github.com/jtakyi88/seedprints/pull/7) | `review/phase1-06` |
| 8 | [Phase 1 (8/10): restore focus after quick-add opens the bag](https://github.com/jtakyi88/seedprints/pull/8) | `review/phase1-07` |
| 9 | [Phase 1 (9/10): correct asset URLs for bundled theme commerce](https://github.com/jtakyi88/seedprints/pull/9) | `review/phase1-08` |
| 10 | [Phase 1 (10/10): on-demand bag refresh for page restoration](https://github.com/jtakyi88/seedprints/pull/10) | `review/phase1-09` |
| 11 | [Rebuild Seed Prints storefront with a lightweight WordPress block theme](https://github.com/jtakyi88/seedprints/pull/11) | `review/phase1-10` |

Source review checkout: `development/github` in the local Seed Prints project. Original development histories and installable packages remain available in the local handoff.

See [QA evidence](QA.md) and [remaining launch work and rollback](RELEASE.md). No deployment automation was added.
