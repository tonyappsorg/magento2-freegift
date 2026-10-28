# Tonyapps_FreeGift

Free gift with purchase, built as one more **Apply** option on core cart price rules.

Core cart price rules already handle conditions (subtotal, SKUs, categories, qty), coupons, dates,
websites, customer groups and priorities. They can't put a product in the cart. This module adds only that.

## Use

**Marketing > Cart Price Rules > Add New Rule**

1. Set conditions as usual, e.g. *Subtotal equals or greater than 50*.
2. Actions: **Apply = Add free gift**, **Discount Amount = 0**, **Free Gift SKUs = `24-UG06`** (comma-separate several).
3. Optional: restrict *Apply the rule only to cart items matching the following conditions*. The gift is added
   when at least one cart item matches.

## How it works

| Piece | What it does |
|---|---|
| `salesrule.tonyapps_gift_skus` | Extra column on the core rule table; the core form saves and loads it with no extra code. |
| `Model\Rule\Action\Discount\FreeGift` | Registered with core's discount `CalculatorFactory`. Discounts nothing; its only job is to make core mark matching items with the rule id. |
| `Observer\SyncGifts` | On `sales_quote_collect_totals_before`, hides gift lines (flagged deleted) so a gift never counts toward any rule's qty, weight or product conditions. On `..._after`, restores them, adds the gifts of matched rules at price 0 (`custom_price`), removes gifts whose rule no longer matches, resets gift qty to 1, then re-collects once. |
| `Model\GiftPlanner` | Pure add/remove diff, unit-tested. |

Gift lines carry the rule id in a `tonyapps_free_gift` item option and in the buy request, and show **Promotion: Free gift**
in the cart, minicart and order. Reorder and admin "edit order" copy only the buy request, so a reordered gift is
recognised, kept free, and dropped if the rule no longer matches.

## Limits (v1)

* Gifts must be **simple or virtual** products. Configurables are skipped with a log warning.
* An out-of-stock gift is skipped silently (logged at info). The cart still works.
* The shopper can't choose between gifts or decline one. If they delete it, it comes back on the next cart update while the rule still matches.
* Qty is always 1 per SKU per rule.
* **Multishipping** isn't supported: address items don't carry `custom_price`, so gifts are removed from multishipping carts.

## Install

```
bin/magento module:enable Tonyapps_FreeGift
bin/magento setup:upgrade
```

## Tests

```
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Tonyapps/FreeGift/Test/Unit
```
