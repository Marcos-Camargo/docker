# Docker Development Environment

This repository contains a container setup for the application.

## Configuration

* `multiseller_freight_results` – JSON defining the marketplace shipping method names. The setting is inactive by default.
  Example value:
  ```json
  {"lowest_price": "Normal", "lowest_deadline": "Expressa"}
  ```

## Manual Testing

To manually verify quote lookups and order splitting:
1. Insert a row into the `quotes_ship` table with the expected marketplace, ZIP code, SKUs and cost.
2. Create an order that matches the same parameters.
3. Run the batch `TinyInvoice` process that contracts freight.
4. Confirm the process fetches the stored quote and splits the order accordingly in the marketplace dashboard.
