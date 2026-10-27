# Design system and component library

Tokens live in `theme/seedprints/theme.json` and `assets/css/theme.css`: Ink, Parchment, Linen, Seed Red, Olive, Gold and Stone; eight-pixel spacing; twelve-pixel cards; forty-eight-pixel pill buttons. Inter and Fraunces are self-hosted with OFL licenses. Dark muted text replaces Stone where the original palette would not pass text contrast.

| Component | Implementation |
| --- | --- |
| Button | `sp_button()`; primary Ink and outlined secondary |
| ProductCard | `sp_product_card()`; real Woo prices, honest review counts, color links, saved pieces, quick-add |
| Rail | Native horizontal scroll, scroll snap, keyboard-focusable container; server-rendered cards |
| Hero | Editable `seedprints/hero` block, responsive WebP, high-priority image preload |
| Tile | Collection and category blocks with source photos and existing product links |
| Drawer | Native modal dialogs for bag, search, quick-add and size guide; Escape and focus return |
| Accordion | Native details/summary for product story, details, shipping and reviews |
| FilterSheet | Native dialog; GET filters keep shareable URLs and server-rendered results |
| ReviewCard | Approved native photo reviews with actual ratings; existing authentic text fallback; no fabricated customer photos or ratings |

Dynamic Site Editor blocks provide headings/body/verse/image controls. The block templates cover Home, PLP, PDP, Cart, Checkout, Custom, Our Story, Wishlist, Contact, default pages/posts, search and 404. Original pages and URLs are retained.

Custom design uses actual product/variation stock, local KJV verse lookup, editable text, two fonts, print color, optional image, preview, quantity, requested date and required final-design acknowledgment. Uploaded artwork is private and available to authorized store managers. Submission never accepts a client-supplied price. Optional review photos require permission and moderation before public display.

The free-shipping progress bar renders only when the currently matched native shipping zone has an enabled minimum-amount free-shipping method. It does not invent a threshold or change shipping settings.
