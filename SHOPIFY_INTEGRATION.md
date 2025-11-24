# Shopify Integration Pointers

The Shopify synchronization endpoints already live in the Laravel backend under `app/Http/Controllers/ShopifyController.php` with routes registered in `routes/api.php` under the authenticated group.

To add a user-facing integration flow in the frontend:

1. Create settings inputs (store domain and API token) in a Vue view (for example, a new tab in the Profile/Settings area) and POST them to `/shopify/settings`.
2. Trigger product import via `POST /shopify/sync-products` and order import via `POST /shopify/sync-orders` (requires an `account_id`).
3. Surface connection status by reading `/shopify/settings` and allow disconnect with `DELETE /shopify/disconnect`.

These endpoints expect the authenticated user session (JWT) and will create/update Product, Stock Movement, and POS Order records on successful syncs.
