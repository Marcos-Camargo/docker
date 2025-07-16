# Implemented OEP Behaviors

This repository contains partial implementations for several Operational Excellence Proposals (OEP). Below is a brief description of the core behaviors covered by automated tests.

- **OEP-2002** – When the feature flag `OEP-2002-permitir-criar-lojas-para-pessoas-fisicas` is enabled, store creation accepts personal documents (`cpf`/`rg`) instead of corporate (`cnpj`/`ie`).
- **OEP-1957** – API endpoints for add-ons delegate operations to a new `handleRequest` method whenever the flag `OEP-1957-update-delete-publica-addon-occ` is active.
- **OEP-1598** – Creating or updating recipients through the BACEN integration requires a VTEX seller identifier. If it is missing, the operation returns an empty response.
- **OEP-1599** – VTEX simulation may include the seller identifier on `merchantName` when the setting `return_merchant_name_on_simulation_vtex` is active.
- **OEP-1789** – The integration name `mevo` is treated as `vtex` internally, keeping behavior consistent between both channels.
- **OEP-2010** – Partial shipping notifications are skipped when the feature `oep-2009-partial-invoicing` is disabled.
- **OEP-2012** – A financial trigger runs on the first delivery of a multiseller order when the flag `feature-OEP-2012-financial-trigger` is enabled.
