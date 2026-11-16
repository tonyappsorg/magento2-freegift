# Tonyapps_FreeGift — notes for AI agents and developers

Read this before changing the module. Workspace-wide rules are in ../AGENTS.md.

## Status

- Built 2026-10-23; package `tonyapps/module-free-gift` 1.0.0 (v1 limits below).
- 2026-10-30 review fixes done (see Change history).
- Scope rule: core cart price rules already handle conditions (subtotal, SKUs, categories, qty), coupons, dates, websites, customer groups and priorities. They can't put a product in the cart. This module adds only that, as one more **Apply** option.
- WORKPLAN item "Discount on the cheapest item" (2026-11-09, not scoped) may extend or rename this module into `SpecialPromotions`; a rename touches the composer package, module name, namespaces, config paths and the `salesrule.tonyapps_gift_skus` column. See ../WORKPLAN.md.

## Architecture

| Piece | What it does |
|---|---|
| `salesrule.tonyapps_gift_skus` | Extra column on the core rule table; the core form saves and loads it with no extra code. Field "Free Gift SKUs" added in `view/adminhtml/ui_component/sales_rule_form.xml`. |
| `Plugin\AddFreeGiftAction` | After-plugin on `SimpleActionOptionsProvider::toOptionArray()`: adds "Add free gift" to the Apply dropdown. |
| `Model\Rule\Action\Discount\FreeGift` | Registered with core's discount `CalculatorFactory` (`di.xml`, key `tonyapps_free_gift`). Discounts nothing; its only job is to make core mark matching items with the rule id. |
| `Observer\SyncGifts` | On `sales_quote_collect_totals_before`, hides gift lines (flagged deleted) so a gift never counts toward any rule's qty, weight or product conditions; also clears the addresses' cached item list. On `..._after`, restores them, adds the gifts of matched rules at price 0 (`custom_price`), removes gifts whose rule no longer matches, resets gift qty to 1, then re-collects once. Removes gift lines that fail the stock check and clears the quote's stale out-of-stock error. |
| `Plugin\SalesRule\HideGiftsFromRuleConditions` | Wraps `Magento\SalesRule\Model\Utility::canProcessRule()`, which every rule condition check calls, so gift lines don't count in the final re-collect either (`total_qty`, weight, product found/subselect). |
| `Model\GiftPlanner` | Pure add/remove diff, unit-tested. |

- Gift lines carry the rule id in a `tonyapps_free_gift` item option and in the buy request, and show **Promotion: Free gift** (additional option) in the cart, minicart and order.
- Reorder and admin "edit order" copy only the buy request, so a reordered gift is recognised, kept free, and dropped if the rule no longer matches.
- Other modules read the `tonyapps_free_gift` item option: AbandonedCart (email shows *Free gift*), B2bQuotes (lets free-gift rules through on quoted carts, owner decision 2026-11-09).

## Data model

- `salesrule.tonyapps_gift_skus` varchar(255), nullable: comma-separated gift SKUs. Lives on the rule itself so the core rule save/load/form round-trips it.

## Configuration and ACL

- No system config, ACL or menu of its own. Configured per rule under **Marketing > Cart Price Rules**: Apply = Add free gift, Discount Amount = 0, Free Gift SKUs (comma-separated). Optional item conditions: the gift is added when at least one cart item matches.

## Decisions and constraints

- Built as a cart price rule action, not a separate rule engine, to reuse all core conditions.
- Gifts never count toward any rule's conditions (owner/review decision; example: "-10% from 3 items" must not apply to 2 paid items plus a gift).
- Gift price is set through `custom_price` = 0.

## Known limitations and open points (v1)

- Gifts must be **simple or virtual** products. Configurables are skipped with a log warning (missing SKU also logged as warning).
- An out-of-stock gift is skipped silently (logged at info). The cart still works.
- The shopper can't choose between gifts or decline one. If they delete it, it comes back on the next cart update while the rule still matches.
- Qty is always 1 per SKU per rule.
- **Multishipping** isn't supported: address items don't carry `custom_price`, so gifts are removed from multishipping carts.
- SKU list is limited by the 255-char column.

## Tests

```sh
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Tonyapps/FreeGift/Test/Unit
```

Unit tests: GiftPlanner, SyncGifts, AddFreeGiftAction, HideGiftsFromRuleConditions. Playwright: `../e2e/tests/free-gift.spec.js`.

## Operations

```sh
bin/magento module:enable Tonyapps_FreeGift
bin/magento setup:upgrade
```

## Change history

- **2026-11-16** — Released as free software under GPL-3.0-or-later (owner decision): `LICENSE` added,
  `composer.json` license changed from `proprietary`, License section in the README.
- **2026-10-30 review fixes**:
  - Gift lines no longer count toward cart price rule conditions in the final re-collect (`total_qty`, weight, product found/subselect): `Plugin\SalesRule\HideGiftsFromRuleConditions` wraps `Utility::canProcessRule()`. Example: "-10% from 3 items" no longer applies to 2 paid items plus a gift.
  - Hiding gifts now clears the addresses' cached item list, so a second totals run in the same request also hides them.
  - A gift line that fails the stock check (out of stock or disabled after it was added) is removed, and the quote's stale "Some of the products are out of stock" error is cleared, so the gift no longer blocks checkout. It is added back once it is available again.
