<!-- SEO Meta -->
<!--
  Title: Magento 2 Smart Product Badges and Labels Extension | Hyva + Luma | Panth Infotech
  Description: Panth Smart Badge adds automated, rule-based product badges and labels to Magento 2. Assign Sale, New, Hot, Best Seller, Low Stock, Free Shipping, Limited Edition, and Trending badges automatically using a smart conditions engine. Visual badge builder with colors, FontAwesome icons, animations, and scheduling. Works on Magento 2.4.4 to 2.4.8 and PHP 8.1 to 8.4. Built by Top Rated Plus Magento developer Kishan Savaliya.
  Keywords: magento 2 product badges, magento 2 product labels, sale badge magento 2, new arrival badge, best seller badge, low stock badge, smart badge magento 2, rule-based badges, hyva product badges, luma product labels, magento 2 badge extension, animated product badges, scheduled product badges, fontawesome magento badges
  Author: Kishan Savaliya (Panth Infotech)
  Canonical: https://kishansavaliya.com/magento-2-smart-badge.html
-->

# Magento 2 Smart Product Badges and Labels Extension: Auto-Assign Sale, New, Hot and Custom Badges (Hyva + Luma)

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange?logo=magento&logoColor=white)](https://magento.com)
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue?logo=php&logoColor=white)](https://php.net)
[![Hyva + Luma](https://img.shields.io/badge/Themes-Hyva%20%2B%20Luma-14b8a6)](https://www.hyva.io)
[![Live Demo & Details](https://img.shields.io/badge/Live%20Demo%20%26%20Details-magento--2--smart--badge-0D9488?style=flat)](https://kishansavaliya.com/magento-2-smart-badge.html)
[![Packagist](https://img.shields.io/badge/Packagist-mage2kishan%2Fmodule--smart--badge-orange?logo=packagist&logoColor=white)](https://packagist.org/packages/mage2kishan/module-smart-badge)
[![Upwork Top Rated Plus](https://img.shields.io/badge/Upwork-Top%20Rated%20Plus-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)
[![Website](https://img.shields.io/badge/Website-kishansavaliya.com-0D9488)](https://kishansavaliya.com)

> **Automatically show product badges on your Magento 2 store without touching each product manually.** Panth Smart Badge assigns Sale, New, Hot, Best Seller, Low Stock, Free Shipping, Limited Edition, and Trending badges based on live product data. Design them with a visual builder, set a schedule, and let the rules do the work.

**Product page:** [kishansavaliya.com/magento-2-smart-badge.html](https://kishansavaliya.com/magento-2-smart-badge.html)

---

## Quick Answer

**What is Panth Smart Badge?** It is a Magento 2 product badge and label extension that automatically assigns visual badges to products based on rules you configure, so shoppers can quickly spot sale items, new arrivals, hot deals, and low-stock products.

**What does it add to my store?**

- **8 preset badge types** covering the most common merchandising scenarios, each fully customizable.
- A **visual badge builder** with color pickers, FontAwesome icons, CSS animations, and a live preview.
- A **smart conditions engine** that triggers badges based on price, stock, discount, product age, ratings, and more.
- **Scheduling** so badges appear and disappear automatically on a start and end datetime you set.
- **Per-page-type positioning** so a badge can sit top-left on category pages and top-right on product detail pages.

**Which themes are supported?** Both **Hyva** (Alpine.js, no jQuery) and **Luma**. Theme detection is automatic via `Panth_Core`.

**What does it need?** Magento 2.4.4 to 2.4.8, PHP 8.1 to 8.4, and the free `mage2kishan/module-core` package.

---

## Need Custom Magento 2 Development?

> **Get a free quote for your project in 24 hours** for custom modules, Hyva themes, performance work, M1 to M2 migrations, and Adobe Commerce Cloud.

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/Get%20a%20Free%20Quote%20%E2%86%92-Reply%20within%2024%20hours-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<table>
<tr>
<td width="50%" align="center">

### Kishan Savaliya
**Top Rated Plus on Upwork**

[![Hire on Upwork](https://img.shields.io/badge/Hire%20on%20Upwork-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)

100% Job Success • 10+ Years Magento Experience
Adobe Certified • Hyva Specialist

</td>
<td width="50%" align="center">

### Panth Infotech Agency
**Magento Development Team**

[![Visit Agency](https://img.shields.io/badge/Visit%20Agency-Panth%20Infotech-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)

Custom Modules • Theme Design • Migrations
Performance • SEO • Adobe Commerce Cloud

</td>
</tr>
</table>

**Visit our website:** [kishansavaliya.com](https://kishansavaliya.com) &nbsp;|&nbsp; **Get a quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)

---

## Table of Contents

- [Who Is It For](#who-is-it-for)
- [The 8 Built-In Badge Types](#the-8-built-in-badge-types)
- [Key Features](#key-features)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [How Badge Rules Work](#how-badge-rules-work)
- [FAQ](#faq)
- [Support](#support)
- [About Panth Infotech](#about-panth-infotech)
- [Quick Links](#quick-links)

---

## Who Is It For

- **Stores running promotions** where you want a Sale or Hot Deal badge to appear automatically when a product has a special price, without editing each product one by one.
- **Fashion and lifestyle stores** that launch new collections often and want a New badge to show up for a set number of days after the product is created.
- **Merchants focused on urgency** who want a Low Stock badge to appear when inventory drops below a threshold you choose.
- **Hyva storefronts** that need a badge solution built with Alpine.js and Tailwind, without pulling in jQuery or RequireJS.
- **Stores with seasonal campaigns** where badges need to go live and expire on a schedule without manual work each time.

---

## The 8 Built-In Badge Types

Smart Badge ships with **8 preset badge types** that cover the most common merchandising scenarios. Each can be styled, scheduled, and positioned independently.

| # | Badge Type | Trigger | Typical Use |
|---|---|---|---|
| 1 | **Sale** | Product has an active special price | Flash sales, clearance, seasonal discounts |
| 2 | **New** | Product created within N days (configurable) | Fresh inventory, new collections |
| 3 | **Hot** | Discount percentage exceeds a threshold | High-value deals |
| 4 | **Best Seller** | Top N products by sales count | Social proof, popular items |
| 5 | **Low Stock** | Stock qty falls below a threshold | Urgency, scarcity |
| 6 | **Free Shipping** | Product qualifies for free shipping | Reduce cart abandonment |
| 7 | **Limited Edition** | Rule-based on attribute, category, or SKU list | Exclusive or limited products |
| 8 | **Trending** | Rule-based (e.g. wishlist count, sales velocity) | Highlight trending items |

---

## Key Features

### Rule-Based Automatic Assignment
- **No manual tagging** -- badges are applied automatically based on live product data.
- **Smart conditions engine** -- combine price range, stock level, discount percentage, product age, stock status, customer rating, sales count, wishlist count, product attribute, and date range conditions.
- **AND logic** -- all enabled conditions must match before a badge is shown.
- **Priority control** -- when multiple badges match a product, priority determines which ones display.
- **Product and category targeting** -- assign badges to specific SKUs, product IDs, or category trees.

### Visual Badge Builder
- **Color picker** for background and text colors, with custom CSS class support.
- **FontAwesome 6 icons** -- hundreds of icons including fire, bolt, star, tag, gift, and more. Search and pick from the admin builder.
- **Emoji icons** -- quick-pick emoji grid as an alternative to FontAwesome.
- **Custom badge image** -- upload your own PNG, JPG, SVG, or WebP (up to 2 MB) as a badge.
- **Style presets** -- Minimal, Bold, Rounded, Pill, Outlined, and Shadow shapes out of the box.
- **Full CSS control** -- border radius, border width and color, font size, font weight, padding, opacity, box shadow, z-index, and dimensions with unit selection (px, %, em, auto).
- **Live preview** -- see your badge rendered in real time in the admin builder, in both product view and category view modes.

### Animations
- **Pulse, bounce, fade, shake, glow, and slide** animations built in.
- **Continuous animation** on badge display.
- All animations use CSS only, no JavaScript required.

### Scheduling
- **Start and end datetime** for each badge rule -- it activates and expires automatically.
- **Automatic enable and disable** based on the schedule, via Magento cron.
- **Timezone-aware** -- all times respect the store's configured timezone.

### Page-Specific Positioning
- **Category and listing pages** -- top-left, top-right, bottom-left, or bottom-right.
- **Product detail pages** -- independent position setting.
- **Product sliders and widgets** -- badges shown in featured product blocks.
- **Use same position everywhere** option, or set a different position per page type.

### Admin Experience
- **Manage Badges grid** under Admin > Panth Infotech > Smart Badges with sorting, filtering, pagination, inline edit, mass delete, and mass status change.
- **Priority-based ordering** (0 to 100) controls which badges display when more match than the configured maximum.
- **Badge combination mode** -- Priority Mode shows only the highest-priority badge; Collect All Mode combines badges from multiple sources up to the maximum limit.
- **MEQP compliant** -- passes Adobe's Magento Extension Quality Program code standards.
- **Translation ready** -- all admin labels use Magento's `__()` function.

### Hyva + Luma Ready
- **Native Hyva templates** built with Alpine.js and Tailwind CSS, no jQuery or RequireJS.
- **Native Luma templates** for classic storefronts.
- **Automatic theme detection** via `Panth\Core\Helper\Theme`.

---

## Compatibility

| Requirement | Versions Supported |
|---|---|
| Magento Open Source | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce Cloud | 2.4.4 to 2.4.8 |
| PHP | 8.1.x, 8.2.x, 8.3.x, 8.4.x |
| Hyva Theme | 1.0+ (native Alpine.js support) |
| Luma Theme | Native support |
| Required Dependency | `mage2kishan/module-core` (free) |

---

## Installation

### Composer Installation (Recommended)

```bash
composer require mage2kishan/module-smart-badge
bin/magento module:enable Panth_Core Panth_SmartBadge
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation via ZIP

1. Download the latest release from [Packagist](https://packagist.org/packages/mage2kishan/module-smart-badge) or from the [product page](https://kishansavaliya.com/magento-2-smart-badge.html).
2. Extract it to `app/code/Panth/SmartBadge/` in your Magento install.
3. Make sure `Panth_Core` is installed too (required dependency).
4. Run the commands above starting from `bin/magento module:enable`.

### Verify Installation

```bash
bin/magento module:status Panth_SmartBadge
# Expected: Module is enabled
```

After install, open:
```
Admin > Panth Infotech > Smart Badges > Manage Badges
```

---

## Configuration

Go to **Stores > Configuration > Panth Extensions > Smart Product Badges**.

| Setting | Group | Default | Description |
|---|---|---|---|
| Enable Smart Badges | General | Yes | Master toggle. Turns all badge display on or off. |
| Maximum Badges Per Product | General | 3 | How many badges can show on a single product at once (1-10). |
| Badge Combination Mode | General | -- | Priority Mode shows only the highest-priority badge. Collect All Mode combines badges up to the maximum limit. |
| Show Multiple Rule Badges | General | Yes | Allow more than one rule-based badge on the same product (only active in Collect All mode). |
| Show Auto Badges With Manual/Rules | General | Yes | Show auto-detection badges (New, Sale) alongside manual or rule-based badges (only active in Collect All mode). |
| Multiple Badge Layout | Display | -- | How multiple badges are arranged on a product image: vertical stack, horizontal row, grid, or compact overlap. |
| Badge Spacing | Display | gap-2 | Tailwind CSS gap class controlling space between badges (e.g., gap-1, gap-2, gap-3). |

Badge colors are managed in the theme configuration file (`app/design/frontend/Panth/Infotech/web/tailwind/theme-config.json`). Individual badge rules can override these defaults with custom colors in the badge rule builder.

---

## How Badge Rules Work

Each badge in the admin grid is a **rule** that combines a badge design, conditions, a schedule, and positioning settings.

### Rule Components

1. **Badge type** -- one of the 8 preset types (Sale, New, Hot, Best Seller, Low Stock, Free Shipping, Limited Edition, Trending), which sets the default trigger.
2. **Smart conditions** -- optional additional filters such as price range, stock level, discount percentage, product age, customer rating, sales count, or any product attribute.
3. **Product and category targeting** -- restrict the badge to specific product IDs, category IDs, or leave it open for store-wide matching.
4. **Schedule** -- optional start and end datetime. Leave blank for an always-active badge.
5. **Design** -- badge text, color, icon (FontAwesome, emoji, or uploaded image), animation, and CSS style settings.
6. **Priority** -- integer (0 to 100) that controls display order when multiple badges match the same product.
7. **Positioning** -- choose the same position for all page types, or set a different position for category pages, product detail pages, and product sliders.

### How the Assignment Works

The module checks each active rule against every product. A product receives a badge when its data matches all enabled conditions on the rule. Matched badges are sorted by priority, and only up to the configured maximum are shown per product.

---

## FAQ

### Does Smart Badge work on Hyva themes?

Yes. Panth Smart Badge ships native Alpine.js and Tailwind templates for Hyva, with no jQuery or RequireJS. Theme detection is automatic via `Panth_Core`.

### Will it slow down my category pages?

No. Badge assignments are stored in the `panth_smart_badge` and `panth_smart_badge_rule` tables and looked up efficiently. Badge rendering uses pure CSS animations with no extra JavaScript on the frontend.

### Can I upload a custom badge image instead of using text?

Yes. Each badge rule supports uploading a PNG, JPG, SVG, or WebP image (up to 2 MB) as the badge graphic instead of text and icon.

### Can I show more than one badge on the same product?

Yes. Set Badge Combination Mode to Collect All and configure the Maximum Badges Per Product value. Priority determines which badges display when more match than the limit.

### My theme already loads FontAwesome. Will Smart Badge load it again?

Smart Badge uses FontAwesome icons in the badge rule builder and renders selected icon classes in the badge markup. If your theme already loads FontAwesome, the icons will display without any double load.

### Can I schedule a badge for a specific sale window?

Yes. Each rule has a start datetime and end datetime. The module uses Magento cron to activate and expire badges automatically. Make sure `bin/magento cron:run` is scheduled on your server.

### Does it work with configurable and bundle products?

Yes. Badges apply at the parent product level on listing pages and follow the standard display logic on product detail pages.

### Does Smart Badge support multi-store setups?

Yes. The `smart_badge` configuration section supports default, website, and store view scopes, so you can enable or configure badges differently per store.

### Does Panth Smart Badge need Panth Core?

Yes. `mage2kishan/module-core` is a free, required dependency. Composer installs it automatically.

---

## Support

| Channel | Contact |
|---|---|
| Product Page | [kishansavaliya.com/magento-2-smart-badge.html](https://kishansavaliya.com/magento-2-smart-badge.html) |
| Email | kishansavaliyakb@gmail.com |
| Website | [kishansavaliya.com](https://kishansavaliya.com) |
| WhatsApp | +91 84012 70422 |
| GitHub Issues | [github.com/mage2sk/module-smart-badge/issues](https://github.com/mage2sk/module-smart-badge/issues) |
| Upwork (Top Rated Plus) | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| Upwork Agency | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |

Response time: 1-2 business days.

### Need Custom Magento Development?

Looking for **custom Magento module development**, **Hyva theme work**, **store migrations**, or **performance tuning**? Get a free quote in 24 hours:

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%92%AC%20Get%20a%20Free%20Quote-kishansavaliya.com%2Fget--quote-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<p align="center">
  <a href="https://www.upwork.com/freelancers/~016dd1767321100e21">
    <img src="https://img.shields.io/badge/Hire%20Kishan-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Hire on Upwork" />
  </a>
  &nbsp;&nbsp;
  <a href="https://www.upwork.com/agencies/1881421506131960778/">
    <img src="https://img.shields.io/badge/Visit-Panth%20Infotech%20Agency-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Visit Agency" />
  </a>
  &nbsp;&nbsp;
  <a href="https://kishansavaliya.com/magento-2-smart-badge.html">
    <img src="https://img.shields.io/badge/View%20Product%20Page-magento--2--smart--badge-0D9488?style=for-the-badge" alt="View Product Page" />
  </a>
</p>

---

## About Panth Infotech

Built and maintained by **Kishan Savaliya** ([kishansavaliya.com](https://kishansavaliya.com)), a **Top Rated Plus** Magento developer on Upwork with 10+ years of eCommerce experience.

**Panth Infotech** is a Magento 2 development agency that builds high quality, security focused extensions and themes for both Hyva and Luma storefronts. The extension suite covers SEO, performance, checkout, product presentation, customer engagement, and store management, with each module built to MEQP standards and tested across Magento 2.4.4 to 2.4.8.

Browse the full extension catalog on our [Magento extensions page](https://kishansavaliya.com/magento-extensions.html) or on [Packagist](https://packagist.org/packages/mage2kishan/).

---

## Quick Links

| Resource | Link |
|---|---|
| **Product Page** | [magento-2-smart-badge.html](https://kishansavaliya.com/magento-2-smart-badge.html) |
| **Packagist** | [mage2kishan/module-smart-badge](https://packagist.org/packages/mage2kishan/module-smart-badge) |
| **GitHub** | [mage2sk/module-smart-badge](https://github.com/mage2sk/module-smart-badge) |
| **Website** | [kishansavaliya.com](https://kishansavaliya.com) |
| **Free Quote** | [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote) |
| **Upwork (Top Rated Plus)** | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| **Upwork Agency** | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |
| **Email** | kishansavaliyakb@gmail.com |
| **WhatsApp** | +91 84012 70422 |

---

<p align="center">
  <strong>Ready to highlight your best products automatically?</strong><br/>
  <a href="https://kishansavaliya.com/magento-2-smart-badge.html">
    <img src="https://img.shields.io/badge/%F0%9F%9A%80%20See%20Smart%20Badge%20%E2%86%92-Product%20Page%20%26%20Details-DC2626?style=for-the-badge" alt="See Smart Badge" />
  </a>
</p>

---

**SEO Keywords:** magento 2 product badges, magento 2 product labels, sale badge magento 2, new arrival badge magento 2, best seller badge, low stock badge magento, hot deal badge, free shipping badge magento, smart badge magento 2, rule-based product badges, automated badges magento, magento 2 badge extension, magento 2 label extension, product badge plugin magento, hyva product badges, hyva badge extension, luma product labels, animated product badges, scheduled product badges, fontawesome magento badges, emoji product badges, magento 2 badge builder, custom badge magento 2, magento 2 urgency badges, scarcity badges magento, badge conditions magento, magento 2 trending badge, limited edition badge, magento 2 product label plugin, magento 2.4.8 badges, php 8.4 magento module, mage2kishan smart badge, panth smart badge, panth infotech, kishan savaliya magento, top rated plus magento freelancer, hire magento developer upwork, custom magento module development
