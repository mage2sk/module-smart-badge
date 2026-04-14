# Panth SmartBadge for Magento 2

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![Hyva Compatible](https://img.shields.io/badge/Hyva-Compatible-green)]()
[![Luma Compatible](https://img.shields.io/badge/Luma-Compatible-green)]()

**Smart Product Badge & Label System** for Magento 2 -- automatically
displays beautiful, rule-based badges on products to increase urgency,
highlight promotions, and drive conversions.

Create unlimited badge rules with a visual builder, smart conditions,
custom styling, animations, and per-page-type positioning.

---

## Key Features

- **Visual Badge Builder** -- admin UI with live preview, drag-free
  Alpine.js-powered form with real-time badge rendering
- **Smart Conditions** -- auto-apply badges based on price range,
  stock level, discount percentage, product age, customer rating,
  sales count, wishlist count, product attributes, and date ranges
- **Multiple Badge Types** -- Sale, New, Hot, Bestseller, Low Stock,
  Free Shipping, Limited Edition, Trending, and more
- **Custom Images** -- upload PNG/JPG/SVG/WebP badge images with
  configurable dimensions
- **Animations** -- pulse, bounce, fade, shake, glow, slide, and more
- **Per-Page Positioning** -- set different badge positions for
  category pages, product detail pages, and product sliders
- **Advanced Styling** -- full control over border radius, font size,
  font weight, padding, opacity, box shadow, border, and z-index
- **Style Presets** -- one-click minimal, bold, rounded, pill,
  outlined, and shadow presets
- **Schedule Support** -- set active-from and active-to dates for
  time-limited badge campaigns
- **Product & Category Assignment** -- assign badges to specific
  products or categories, or let smart conditions decide
- **FontAwesome Icon Picker** -- choose from hundreds of icons, or
  use emoji icons
- **Admin Grid** -- full listing with inline edit, mass delete, and
  mass status change
- **Priority System** -- control which badges appear first when
  multiple rules match (0-100)

---

## Requirements

| Requirement | Version |
|---|---|
| Magento Open Source / Commerce | 2.4.4 -- 2.4.8 |
| PHP | 8.1, 8.2, 8.3, 8.4 |
| Panth Core | ^1.0 |

---

## Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-smart-badge
bin/magento module:enable Panth_SmartBadge
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual

1. Extract the archive into `app/code/Panth/SmartBadge/`
2. Run:
```bash
bin/magento module:enable Panth_SmartBadge
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Configuration

Navigate to **Stores > Configuration > Panth Extensions > Smart Badge**
to enable the module and configure global settings.

See the [User Guide](USER_GUIDE.md) for detailed configuration
instructions and screenshots.

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
