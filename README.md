# Free Gift with Purchase for Magento 2

Put a free product in the cart automatically when the cart meets your promotion rules.

Magento Open Source cart price rules handle conditions (subtotal, SKUs, categories, quantity), coupons, dates, websites, customer groups and priorities, but they can't put a product in the cart. This module adds one more **Apply** option to those rules, **Add free gift**, so "free water bottle over $50" is set up the same way as any other promotion.

## Features

- **Works inside cart price rules**: no new admin screen to learn. Use every condition, coupon, date range, website and customer-group setting core rules already have.
- **Automatic add and remove**: the gift appears at $0 as soon as the cart qualifies and disappears when it no longer does.
- **One or several gifts per rule**: list one or more SKUs; each is added once.
- **Gift only when the right products are bought**: optionally limit the rule to carts containing matching items.
- **Gifts don't inflate other promotions**: a gift never counts toward any rule's quantity, weight or product conditions, so "10% off 3 items" doesn't fire on 2 paid items plus a gift.
- **Clearly labelled**: gift lines show **Promotion: Free gift** in the cart, minicart and order.
- **Checkout is never blocked**: if a gift goes out of stock or is disabled, it is quietly removed and comes back when available again.
- **Reorders handled**: reordering, or editing an order in the admin, keeps a gift free while the rule still matches and drops it when it doesn't.

## How it works

1. Go to **Marketing › Cart Price Rules › Add New Rule** and set conditions as usual, e.g. *Subtotal equals or greater than 50*.
2. Under Actions, choose **Apply = Add free gift**, **Discount Amount = 0**, and enter **Free Gift SKUs**, e.g. `24-UG06` (comma-separate several).
3. Optional: fill in *Apply the rule only to cart items matching the following conditions*. The gift is added when at least one cart item matches.

Shoppers see the gift in their cart at $0 whenever the rule matches.

## Good to know

- Gifts must be **simple or virtual** products. Configurable products are skipped.
- Quantity is always 1 per gift SKU per rule.
- Shoppers can't choose between gifts or decline one; a deleted gift comes back on the next cart update while the rule still matches.
- An out-of-stock gift is simply not added; the rest of the cart works normally.
- **Multishipping** checkout isn't supported: gifts are removed from multishipping carts.

## Requirements

- Magento Open Source 2.4.8
- PHP 8.3 or 8.4
- Magento modules: Quote, SalesRule, Catalog

## Installation

```sh
composer require tonyapps/module-free-gift
bin/magento module:enable Tonyapps_FreeGift
bin/magento setup:upgrade
```

## Configuration

- **Marketing › Cart Price Rules**: create or edit a rule and choose **Apply = Add free gift**. There is no separate settings page.

## For developers

Architecture, decisions, data model and tests are in [AGENTS.md](AGENTS.md).
