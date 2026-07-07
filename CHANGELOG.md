# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.7]

### Changed
- Code cleanup: removed redundant inline comments and docblocks from the PHP source. No functional changes.

## [1.0.6] -- README rewrite

### Changed
- README.md rewritten to match updated documentation template: corrected
  canonical URL to live product page, added Quick Answer section, added
  gold-template section order, rewrote Configuration table from real
  system.xml fields only, removed unverified claims (cart/mini-cart badges,
  customer group targeting, hover effects, recurring weekly schedules).

## [1.0.5] -- Upload extension deny-list (defense-in-depth)

### Added
- `Controller/Adminhtml/Rule/Upload` now calls
  `Panth\Core\Security\UploadExtensionPolicy::assertSafeExtension()` before
  saving — a hard executable deny-list independent of the explicit image
  allowlist. Admin-gated, defense-in-depth. Requires
  `mage2kishan/module-core ^1.0.17`.

## [1.0.0] -- Initial release

### Added -- visual badge builder
- Alpine.js-powered admin form with live preview panel
- Real-time badge rendering as settings change
- Product view and category view preview modes
- Style presets: Minimal, Bold, Rounded, Pill, Outlined, Shadow

### Added -- badge types and design
- 8+ preset badge types: Sale, New, Hot, Bestseller, Low Stock,
  Free Shipping, Limited Edition, Trending
- Custom badge text (max 20 characters), colour picker, icon picker
- FontAwesome icon library integration with search
- Emoji icon support with quick-pick grid
- Custom badge image upload (PNG, JPG, SVG, WebP, max 2 MB)

### Added -- smart conditions engine
- Price range condition (min/max)
- Stock level condition (less than, greater than, equals)
- Discount percentage condition
- Product age condition (newer/older than N days)
- Stock status condition (in stock / out of stock)
- Customer rating condition (1-5 stars)
- Sales count condition
- Wishlist count condition
- Product attribute condition (any attribute code/value)
- Special date range condition
- AND logic -- all enabled conditions must match

### Added -- display and positioning
- Per-page-type display control (category, product, sliders, or all)
- Independent badge position per page type (top-left, top-right,
  bottom-left, bottom-right)
- Option to use the same position everywhere

### Added -- advanced styling
- Full CSS control: border radius, border width/style/colour,
  font size, font weight, padding, opacity, box shadow, z-index
- Custom box shadow CSS input
- Width and height with unit selection (px, %, em, auto)

### Added -- animations
- Pulse, bounce, fade, shake, glow, slide, and more
- Continuous animation on badge display

### Added -- scheduling
- Active From / Active To date-time pickers
- Automatic enable/disable of badges based on schedule

### Added -- product and category assignment
- Product chooser with search, pagination, and bulk select
- Category tree chooser with nested display
- Assign badges to specific products/categories or use smart
  conditions for automatic assignment

### Added -- admin grid
- Full listing grid with sorting, filtering, and pagination
- Inline edit support
- Mass delete and mass status change actions
- Priority-based badge ordering (0-100)

### Quality
- Constructor injection only -- zero `ObjectManager::getInstance()`
- All PHP files pass MEQP Magento2 standard at severity 10
- Composer validate passes

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 -- 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4
- Hyva Theme compatible
- Luma Theme compatible

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
