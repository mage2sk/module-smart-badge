# Panth SmartBadge -- User Guide

This guide describes the admin screens and settings of the Panth
SmartBadge extension for Magento store administrators.

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
13. [Automatic and manual badges](#13-automatic-and-manual-badges)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. Installation

### Composer

```bash
composer require mage2kishan/module-smart-badge
bin/magento module:enable Panth_Core Panth_SmartBadge
bin/magento setup:upgrade
bin/magento cache:flush
```

In production mode also run `bin/magento setup:di:compile` and
`bin/magento setup:static-content:deploy -f`.

### Manual

1. Extract the archive into `app/code/Panth/SmartBadge/`. The
   `Panth_Core` module (`mage2kishan/module-core`) must also be
   installed.
2. Run the commands above starting from `bin/magento module:enable`.

---

## 2. Verifying the extension is active

```bash
bin/magento module:status Panth_SmartBadge
```

You should see `Module is enabled`.

---

## 3. Global configuration

Navigate to **Stores > Configuration > Panth Extensions > Smart
Product Badges**. All settings can be set at default, website and
store view scope.

| Setting | Default | Description |
|---|---|---|
| Enable Smart Badges | Yes | Turns all badge output on or off |
| Maximum Badges Per Product | 3 | Upper limit of badges on one product (1-10) |
| Badge Combination Mode | Priority Mode (Single Source) | Priority Mode shows badges from one source only (manual attribute, then rules, then automatic badges). Collect All Mode merges all sources up to the maximum |
| Show Multiple Rule Badges | No | Collect All Mode only: every matching rule adds a badge instead of only the highest-priority rule |
| Show Auto Badges With Manual/Rules | No | Collect All Mode only: automatic badges are added even when a manual or rule badge exists |
| Multiple Badge Layout | Vertical Stack (Default) | Vertical Stack, Horizontal Row, Grid Layout (2 columns) or Compact (Overlapping) |
| Badge Spacing | gap-2 | Space between badges: `gap-1`, `gap-2`, `gap-3` or `gap-4` |

---

## 4. Creating a badge rule

1. Navigate to **Panth Infotech > Smart Badges > Manage Badges**
2. Click **Create New Badge Rule**
3. Fill in the form sections described below and click **Save**

### Basic Information

- **Rule Name** -- a descriptive name (admin only, required)
- **Enable Rule** -- only enabled rules are evaluated
- **Priority** -- 0-100; rules with a higher priority are evaluated
  and shown first
- **Store Views** -- the store views where the rule applies; All Store
  Views applies it everywhere. Rules created before version 1.1.0 apply
  to all store views
- **Customer Groups** -- the customer groups that see the badge; leave
  empty for all groups (guests are NOT LOGGED IN)
- **Badge Type** -- one of the preset types in section 5

### Design

- **Badge Text** -- text shown on the badge
- **Badge Color** -- background colour in `#RRGGBB` format
- **Badge Icon** -- a FontAwesome icon from the list

### Display Conditions

- **Specific Products** -- comma-separated product IDs
- **Categories** -- comma-separated category IDs; products in child
  categories also match

Leave both empty to apply the rule to all products. When product IDs
are set, the category list is ignored.

---

## 5. Badge types

| Type | Default text | Default colour variable |
|---|---|---|
| New | NEW | `--badge-new` (green) |
| Sale | SALE | `--badge-sale` (red) |
| Hot | HOT | `--badge-hot` (orange) |
| Limited | LIMITED | `--badge-limited` (purple) |
| Bestseller | BESTSELLER | `--badge-sale` |
| Trending | TRENDING | `--badge-hot` |
| Exclusive | EXCLUSIVE | `--badge-hot` |
| Featured | FEATURED | `--badge-new` |

A rule with its own **Badge Color** overrides the default colour.

---

## 6. Smart conditions

Enable one or more conditions in the **Smart Conditions** section:

| Condition | Description |
|---|---|
| Price Range | Final price between Min and Max |
| Stock Level | Quantity less than / greater than / equal to a value |
| Discount Percentage | Difference between regular and final price, in percent |
| Product Age (Days) | Newer or older than N days since the product was created |
| Stock Status | In Stock or Out of Stock |
| Customer Rating | Average approved rating (1-5 stars) |
| Sales Count | Quantity ordered according to the bestsellers report |

All enabled conditions must match for the badge to appear (AND
logic).

---

## 7. Display settings

### Display On

- All Pages
- Product Detail Page Only
- Category Page Only
- Sliders Only

### Badge Position

Use **Use Same Position for All Views** with **Badge Position**, or
set **Category Page Position**, **Product Page Position** and
**Slider Position** separately. Available positions: Top Left, Top
Right, Bottom Left, Bottom Right, Top Center, Bottom Center, Center
Left and Center Right.

---

## 8. Advanced styling

The **Advanced Styling** section offers:

- **Border Radius (px)** -- 0 for square corners, 50 for a pill shape
- **Font Size (px)** -- 8-24
- **Font Weight** -- 400 to 800
- **Padding** -- top, right, bottom, left (px)
- **Opacity (%)** -- 10-100
- **Box Shadow** -- None, Small, Medium, Large
- **Border Width (px)** -- 0-5
- **Border Style** -- None, Solid, Dashed, Dotted
- **Border Color** -- colour picker

---

## 9. Scheduling badges

Set **Start Date** and **End Date** (date and time) in the
**Schedule** section. Outside this period the rule is skipped. Leave
a field empty for no limit on that side.

---

## 10. Badge images

Upload a custom badge image (JPG, JPEG, PNG, GIF or WebP, max 2 MB) in
the **Badge Image** section. The file content must be one of these
image types; SVG files are not accepted. Images are stored in
`pub/media/smartbadge/` and shown inside the badge next to the text.

---

## 11. Animations

Choose an **Animation Type**: None, Pulse, Bounce, Shake, Glow,
Rotate, Flip, Swing, Tada, Jello, Heartbeat, Fade In, Fade Out, Slide
In Left, Slide In Right, Slide In Up, Slide In Down, Zoom In, Zoom
Out, Flash, Rubber Band, Wobble, Head Shake, Roll In or Roll Out. The
animation repeats continuously on the storefront.

---

## 12. Managing badge rules

Navigate to **Panth Infotech > Smart Badges > Manage Badges** to
see all rules in a sortable, filterable grid.

Available actions:
- **Inline Edit** -- edit Status, Priority, Rule Name, Custom Text
  and Custom Color directly in the grid
- **Edit** -- open the full rule form
- **Delete Rule** -- button on the rule form
- **Delete** (mass action) -- delete the selected rules
- **Change Status** (mass action) -- enable or disable the selected
  rules

---

## 13. Automatic and manual badges

Without any rule, the extension shows:
- a discount badge (for example `-20%`) while the final price is at
  least 1% below the regular price (special price, catalog price rule
  or the lowest configurable option price)
- a NEW badge while the product "Set Product as New From/To" dates are
  current, or for products created in the last 30 days when those
  dates are empty
- an "Only N left" badge when an in-stock product has a quantity
  between 1 and 10

On a single product you can set **Product Badge**, **Custom Badge
Text** and **Custom Badge Color** (product edit page, Product Details
group). A value in **Product Badge** takes precedence over rules and
automatic badges.

---

## 14. Troubleshooting

| Symptom | Solution |
|---|---|
| Badges not showing | Check that Enable Smart Badges is Yes for the store view and the rule is enabled |
| Badge appears on wrong page type | Check Display On for the rule |
| Badge position is wrong | Check the position settings of the rule |
| Smart conditions not matching | Check that every enabled condition is met by the product |
| Badge image not uploading | Use a JPG, PNG, GIF or WebP file under 2 MB |
| FontAwesome icons missing | The icons are served from the module static files (`Panth_SmartBadge/fontawesome/`); in production mode run `bin/magento setup:static-content:deploy` after upgrading |
| Settings not applied | Run `bin/magento cache:flush` after changing configuration |

---

## Support

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
