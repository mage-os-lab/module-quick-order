# Changelog

All notable changes to this module are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versions follow [Semantic Versioning](https://semver.org/).

## [1.1.0] - 2026-11-23

Quick order lines can now be previewed, corrected and completed before anything reaches the cart, with product suggestions and a variant matrix for configurable products.

Upgrade: on Hyvä, rebuild the theme's styles, as the form uses new Tailwind classes.

### Added

- Preview: a table shows each line before it reaches the cart, with image, name, price, stock and the error the cart would raise; SKU and quantity are corrected in place, then previewed again.
- Suggestions: the table's SKU fields suggest products while a SKU or a product name is typed.
- Configurable products: a configurable SKU opens a matrix of its variants, with one quantity per variant; variants go to the cart as their own simple SKUs.
- A preview holds at most 500 rows, variants of configurable products included; beyond that, the customer is asked to preview fewer configurable products at a time.

### Changed

- The form's first button is now *Preview*; *Add to Cart* appears once lines are previewed. Before, the form added the lines to the cart directly. Posting `lines` or `csv` straight to `/quickorder/index/add` still works.
- The module now requires `magento/module-configurable-product`, `magento/module-quote` and `magento/module-store`, which are part of every Mage-OS installation.

### Fixed

- A request with a malformed `lines` parameter ended in an error page; it is now ignored.

### Compatibility

- Luma (4.0.0) and Hyvä Theme (1.5.4): suggestions, preview, variant matrix and add to cart were run on Mage-OS 4.0.0 with PHP 8.5.
- Elasticsuite (2.12.2): run as the search engine under both themes; suggestions do not go through the search engine.
- Hyvä Checkout and Loki Checkout: not applicable, the module stops at the cart.
- The 500-row limit of a preview was not run against a catalog large enough to reach it.

## [1.0.0] - 2026-11-17

### Added

- Storefront page `/quickorder` that fills the cart from `SKU,quantity` lines, typed or uploaded as a `.csv` / `.txt` file (at most 200 SKUs, 1 MB).
- Unknown SKUs and products that need options are reported one by one; the other lines are added.
- Storefront: Hyvä default theme and Luma; the module registers itself for Hyvä's Tailwind compilation.
- Admin and storefront texts translated into French, German, Spanish and Italian.
- Supports Mage-OS 3.6 and 4.x / Magento Open Source 2.4.9 (`magento/framework` ^103.0.9), PHP 8.3 to 8.5.
