# Quick Order

`mage-os/module-quick-order` (`MageOS_QuickOrder`) — a module for Mage-OS / Magento Open Source.

Adds a storefront page, `/quickorder`, to fill the cart from a list of SKUs.

- One product per line, `SKU,quantity` (separator: comma, semicolon or tab), typed in a text area or uploaded as a CSV file with `sku,qty` columns.
- *Preview* shows each line before anything reaches the cart: image, name, price, stock, and the error the cart would raise (unknown SKU, out of stock, quantity not available...). SKU and quantity are fixed in place, then previewed again.
- The table's SKU fields suggest products while you type a SKU or a name.
- A configurable SKU opens a matrix of its variants (last attribute in columns), with one quantity per variant. Variants go to the cart as their own simple SKUs.
- On *Add to Cart*, lines that still fail are reported one by one; the other lines are added.
- At most 200 SKUs per submission and 500 rows per preview (lines plus variants of configurable products), CSV files up to 1 MB.
- The preview checks each line on its own: it does not count what is already in the cart, so a quantity that only fits without the cart's content is refused at *Add to Cart*.

No menu link is added: link to `/quickorder` from a CMS block or your navigation.

## Compatibility

Mage-OS 3.6 and 4.x, and Magento Open Source 2.4.9: all share the Magento 2.4.9 base (`magento/framework` ^103.0.9). PHP 8.3 to 8.5. CI runs the check-extension workflow on the currently supported Mage-OS versions (today Mage-OS 3.6.0 with PHP 8.4; Mage-OS 4.0 joins once the check-extension actions know it); developed on Mage-OS 4.0.0 with PHP 8.5. GraphQL is not supported.

| Target | Status | Verified |
|---|---|---|
| Luma | Supported | Run on Mage-OS 4.0.0, PHP 8.5, Luma 4.0.0: suggestions, preview, variant matrix, add to cart with the mini cart refreshed. |
| Hyvä Theme | Supported | Run on Mage-OS 4.0.0, PHP 8.5, Hyvä default theme 1.5.4: suggestions, preview, variant matrix, add to cart with the mini cart refreshed. |
| Hyvä Checkout | Not applicable | The module stops at the cart and adds nothing to the checkout. |
| Loki Checkout | Not applicable | Same. |
| Elasticsuite | Supported | Run with Elasticsuite 2.12.2 as the search engine, under both themes. Suggestions query the product collection in SQL, not the search engine. |

On Hyvä, the template uses Tailwind classes: rebuild the theme's styles after installing the module (it registers itself for Hyvä's Tailwind compilation).

## Installation

```bash
composer config repositories.mageos-quick-order vcs https://github.com/mage-os-lab/module-quick-order
composer require mage-os/module-quick-order
bin/magento module:enable MageOS_QuickOrder
bin/magento setup:upgrade
```

## Configuration

No configuration. The page is `/quickorder`; link to it from a CMS block or your navigation.

## Getting started

1. Open `/quickorder` on the storefront.
2. Type one product per line, `SKU,quantity`, or upload a CSV or TXT file with `sku,qty` columns.
3. Click *Preview*: check the table, fix the lines in error, fill the variant matrix of configurable products, add lines with the empty rows (they suggest products as you type). To remove a line, clear its SKU or set its quantity to 0. Preview again as often as needed.
4. Click *Add to Cart*: you land on the cart, with a message for each line that could not be added.

![Quick order preview on Hyvä](docs/images/quick-order.png)

## Troubleshooting

- **"Please upload a .csv or .txt file"**: other extensions are refused.
- **A line is reported with an error**: unknown SKU, out of stock, or a product that needs options (bundle, grouped...); add those from the product page. Configurable products are ordered through their variant matrix.
- **No suggestion in Safari when typing a name**: suggestions use the browser's native `<datalist>`, which Safari only matches on the SKU.
- **Suggestions miss a product**: only products visible on the storefront are suggested; variants are reached through their configurable product.
- **"Please preview fewer configurable products at a time"**: a preview holds at most 500 rows, variants included (two form fields per row, within PHP's default `max_input_vars` of 1000). Preview the configurable products in several batches.

## More

- [CHANGELOG](CHANGELOG.md)
- [Security policy](SECURITY.md)
- [License](LICENSE.txt): Open Software License 3.0. This module contains no code from Adobe Commerce.
