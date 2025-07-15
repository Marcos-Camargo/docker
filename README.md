# Docker Development Environment

This repository contains a container setup for the application.

## Configuration

The application reads several settings from the `settings` table. They can be
edited through the administration panel or inserted manually during setup.

* `enable_multiseller_operation` – allows a single order to contain products
  from more than one store. When inactive, mixed orders are canceled.
* `marketplace_multiseller_operation` – comma‑separated list of marketplace
  identifiers (`int_to`) that support sharing the same order between stores.
  This list only has effect when `enable_multiseller_operation` is active.
* `multiseller_freight_results` – JSON with the shipping method names used when
  a quote involves multiple sellers. The setting is inactive by default.
  Example value:
  ```json
  {"lowest_price": "Normal", "lowest_deadline": "Expressa"}
  ```
* Feature flag `oep-2009-partial-invoicing` – enables partial invoicing for
  multiseller orders. When disabled this operation is ignored.
* Feature flag `oep-2010-partial-shipping` – enables shipping notifications for
  multiseller orders.
* Setting `enable_multiple_returns_per_order` – when active the platform allows
  creating more than one return for a single order.
* `POST /api/v1/partial-shipping` – updates shipping information for multiseller
  orders. The route maps to `Api/V1/PartialShipping/index` and calls the batch
  process responsible for handling partial shipments.

When `multiseller_freight_results` is enabled the quote response is modified so
that the cheapest option uses the name from `lowest_price` and the fastest uses
the name from `lowest_deadline`.

## Manual Testing

To manually verify quote lookups and order splitting:
1. Insert a row into the `quotes_ship` table with the expected marketplace, ZIP code, SKUs and cost.
2. Create an order that matches the same parameters.
3. Run the batch `TinyInvoice` process that contracts freight.
4. Confirm the process fetches the stored quote and splits the order accordingly in the marketplace dashboard.
