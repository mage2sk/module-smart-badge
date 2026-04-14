# Panth SmartBadge -- User Guide

This guide walks a Magento store administrator through every screen
and setting of the Panth SmartBadge extension. No coding required.

---

## Table of contents

1. [Installation](#1-installation)
2. [Verifying the extension is active](#2-verifying-the-extension-is-active)
3. [Global configuration](#3-global-configuration)
4. [Creating a badge rule](#4-creating-a-badge-rule)
5. [Badge types](#5-badge-types)
6. [Smart conditions](#6-smart-conditions)
7. [Display settings](#7-display-settings)
8. [Advanced styling](#8-advanced-styling)
9. [Scheduling badges](#9-scheduling-badges)
10. [Badge images](#10-badge-images)
11. [Animations](#11-animations)
12. [Managing badge rules](#12-managing-badge-rules)
13. [Troubleshooting](#13-troubleshooting)

---

## 1. Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-smart-badge
bin/magento module:enable Panth_SmartBadge
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual

1. Extract the archive into `app/code/Panth/SmartBadge/`
2. Run the commands above starting from `bin/magento module:enable`.

---

## 2. Verifying the extension is active

```bash
bin/magento module:status Panth_SmartBadge
```

You should see `Module is enabled`.

---

## 3. Global configuration

Navigate to **Stores > Configuration > Panth Extensions > Smart Badge**.

| Setting | Description |
|---|---|
| Enable Module | Enable or disable all badge rendering globally |

---

## 4. Creating a badge rule

1. Navigate to **Marketing > Panth SmartBadge > Badge Rules**
2. Click **Add New Rule**
3. Fill in the badge builder form:

### Basic Information

- **Rule Name** -- a descriptive name (admin only)
- **Status** -- Enabled / Disabled
- **Priority** -- 0-100, higher values appear first when multiple
  badges match the same product

### Badge Design

- **Badge Type** -- choose from preset types (Sale, New, Hot, etc.)
- **Badge Text** -- custom text (max 20 characters)
- **Badge Color** -- hex colour picker
- **Badge Icon** -- emoji or FontAwesome icon

---

## 5. Badge types

The extension ships with these preset badge types:

| Type | Default Text | Default Colour |
|---|---|---|
| Sale | SALE | Red |
| New | NEW | Blue |
| Hot | HOT | Orange |
| Bestseller | BESTSELLER | Purple |
| Low Stock | LOW STOCK | Yellow |
| Free Shipping | FREE SHIPPING | Green |
| Limited Edition | LIMITED | Gold |
| Trending | TRENDING | Pink |

Each type sets sensible defaults for text, colour, and icon.
You can override any default after selecting the type.

---

## 6. Smart conditions

Smart conditions let badges appear automatically based on product
attributes. Enable one or more conditions:

| Condition | Description |
|---|---|
| Price Range | Min/max price range |
| Stock Level | Less than / greater than / equals a quantity |
| Discount Percentage | Based on special price vs regular price |
| Product Age (Days) | Newer or older than N days |
| Stock Status | In Stock or Out of Stock |
| Customer Reviews | Rating threshold (1-5 stars) |
| Sales Count | Number of orders containing the product |
| Wishlist Count | Number of times wishlisted |
| Product Attribute | Match any product attribute code/value |
| Special Date Range | Active only during specific dates |

Multiple conditions are combined with AND logic -- all enabled
conditions must match for the badge to appear.

---

## 7. Display settings

### Where to Display

Choose where the badge appears:
- All Pages (Category, Product, Sliders)
- Category Pages Only
- Product Detail Pages Only
- Product Sliders Only
- Any combination of the above

### Badge Position

Set the badge position independently per page type, or use the
same position everywhere:
- Top Left
- Top Right
- Bottom Left
- Bottom Right

---

## 8. Advanced styling

Fine-tune every visual aspect:

- **Dimensions** -- width, height (px, %, em, auto)
- **Border & Shape** -- border radius (0-50px), border width,
  style (solid, dashed, dotted, double, groove, ridge), colour
- **Typography** -- font size (px, em, rem), font weight (100-900)
- **Padding** -- top, right, bottom, left (px)
- **Effects** -- opacity (0-100%), box shadow (none, small, medium,
  large, extra large, inner, glow, custom), z-index
- **Style Presets** -- one-click: Minimal, Bold, Rounded, Pill,
  Outlined, Shadow

---

## 9. Scheduling badges

Set **Active From** and **Active To** dates and times to
automatically enable and disable badges for promotions, seasonal
sales, or flash deals.

---

## 10. Badge images

Upload a custom badge image (PNG, JPG, SVG, WebP, max 2 MB).

Options:
- **Image Only** -- hide text, show only the image
- **Width / Height** -- set image dimensions in pixels

---

## 11. Animations

Choose from built-in CSS animations:
- Pulse
- Bounce
- Fade
- Shake
- Glow
- Slide
- And more

The animation plays continuously on the badge to draw attention.

---

## 12. Managing badge rules

### Admin Grid

Navigate to **Marketing > Panth SmartBadge > Badge Rules** to see
all rules in a sortable, filterable grid.

Available actions:
- **Inline Edit** -- click a cell to edit directly in the grid
- **Edit** -- open the full badge builder form
- **Delete** -- remove a single rule
- **Mass Delete** -- select multiple rules and delete
- **Mass Status** -- enable or disable multiple rules at once

---

## 13. Troubleshooting

| Symptom | Solution |
|---|---|
| Badges not showing | Check that the module is enabled in config and the rule status is Enabled |
| Badge appears on wrong page type | Check the Display Settings for the rule |
| Badge position is wrong | Verify per-page-type position settings |
| Smart conditions not matching | Check that all enabled conditions are satisfied by the product |
| Badge image not uploading | Ensure the file is under 2 MB and in a supported format |
| Cache issues | Run `bin/magento cache:flush` after changes |

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
