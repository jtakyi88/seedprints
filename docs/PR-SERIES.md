# Phase 1 review series

The ten patches are ordered and independently loadable. They target the repair plugin root; when applying to a full WordPress repository, map them under `wp-content/plugins/seedprints-storefront-fixes/`. GitHub repository: https://github.com/jtakyi88/seedprints. Each fix has its own branch and draft PR, targeting the previous step. Review and merge in order.

| Patch | Reviewer-facing change | Verification |
| --- | --- | --- |
| 1 | Correct negative body margins, duplicate headings, footer year, and reserve variation space. | Legacy staging geometry/headings across four widths. |
| 2 | Provide one header with search/account/bag and immediate bag feedback. | Search, native cart fragments, keyboard dialog controls. |
| 3 | Make existing server-rendered products visible immediately and repair legacy gallery/tab semantics. | First-render inspection and axe findings. |
| 4 | Render native Woo cart/checkout with minimal header and move the native Square wallet hook first. | Native forms, guest checkout layout; no gateway settings changed. |
| 5 | Validate confirmed variation IDs and remove the extra classic add trigger from AJAX requests. | Correct Black/M cart item and one addition per action. |
| 6 | Preserve native product-add-on submission rather than bypassing its required fields. | Africa country selector and original price in cart. |
| 7 | Prevent Woo margin animation from moving the reserved variation region. | Identical Add to bag coordinates before/after selection at all four widths. |
| 8 | Restore focus to the originating product card after quick-add opens the bag. | Focus returns to product 615 after closing the bag. |
| 9 | Allow the block theme's bundled integration to supply its own asset URL. | Standalone plugin disabled; all four theme-bundled assets return HTTP 200. |

| 10 | Expose on-demand bag refresh for lightweight page restoration. | Homepage omits automatic cart fragments; bag open and add responses refresh contents. |

Staging preview and full reports are included with this delivery. The block-theme rebuild is a separate review branch. Production remains unchanged, and wallet/provider setup is still gated on merchant verification and the owner's payment-settings boundary.
