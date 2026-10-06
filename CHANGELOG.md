# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.9] - 2026-10-06

### Changed
- One badge per product by default: "Maximum Badges Per Product" now defaults to 1 and applies to product cards, sliders, the product page gallery and the quick view window.
- New "Badge Priority Order" setting (default sale, new, stock, custom) decides which badge is shown when a product qualifies for several: the discount badge first, then New, then low stock, then other badges by rule priority. Priority Mode now considers the product badge, all matching rules and the automatic badges before applying this order.
- Badges have one standard look: 22px tall, 4px radius, 12px semibold white text, no shadow, on the sale (#B91C1C), new (#0F766E), low stock (#B45309) or brand colour. Size, radius, font and colours can be changed with the CSS custom properties `--sb-badge-height`, `--sb-badge-radius`, `--sb-badge-font-size`, `--sb-badge-font-weight`, `--sb-badge-padding-x`, `--sb-badge-text`, `--sb-badge-sale-bg`, `--sb-badge-new-bg`, `--sb-badge-stock-bg` and `--sb-badge-custom-bg`.
- Badges are text only by default; the new "Show Badge Icons" setting (default No) brings back the icon saved on a rule or a default icon. Default icons are FontAwesome icons instead of emoji. Icons saved on rules are kept.
- The new "Use Custom Badge Styling" setting (default No) controls whether colours set on rules and products, rule Advanced Styling and rule animations are applied. Saved values are kept either way.
- Default badge texts are shorter and in sentence case ("New", "Sale", "Limited" instead of "NEW", "SALE", "LIMITED").

### Fixed
- Badge text is no longer cut off on tablets: badges keep their full short label, never grow wider than the image and only shorten with an ellipsis as a last resort.
- Badges now also appear in the quick view window on Hyva and Luma and follow the product that is opened.
- A product card that already shows the product slider's own badges no longer gets a second set of badges.
- Removed the default pulse, glow and shake animations of the New, Sale and low stock badges.
