# Changelog

All notable changes to this module are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versions follow [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Preview table before adding to the cart: image, name, price, stock and, per line, the error the cart would raise, with SKU and quantity editable in place.
- SKU / product name suggestions in the table's SKU fields (`/quickorder/index/suggest`).
- Variant matrix for configurable SKUs: one quantity per variant, added to the cart as simple SKUs. A preview holds at most 500 rows, variants included.

### Changed

- The form's first step is now *Preview*; *Add to Cart* appears once lines are previewed. Posting `lines` / `csv` straight to `/quickorder/index/add` still works.
- A malformed `lines` parameter (an array) is ignored instead of failing.
- New dependencies: `magento/module-configurable-product`, `magento/module-quote`, `magento/module-store`.

## [1.0.0] - 2026-11-17

### Added

- Storefront page `/quickorder` that fills the cart from `SKU,quantity` lines, typed or uploaded as a `.csv` / `.txt` file (at most 200 SKUs, 1 MB).
- Unknown SKUs and products that need options are reported one by one; the other lines are added.
- Storefront: Hyvä default theme and Luma; the module registers itself for Hyvä's Tailwind compilation.
- Admin and storefront texts translated into French, German, Spanish and Italian.
- Supports Mage-OS 3.6 and 4.x / Magento Open Source 2.4.9 (`magento/framework` ^103.0.9), PHP 8.3 to 8.5.
