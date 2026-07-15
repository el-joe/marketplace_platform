# Marketplace — Customer API (Postman Collection)

Covers every endpoint in [`routes/api_customer.php`](../routes/api_customer.php) — 121 requests
across 12 folders: Auth, Profile, Security, Catalog (home/nav/categories/pages/blocks/browse/
listings/products/vendors/brands/coupons/search), Cart & Wishlist, Checkout & Orders, Addresses,
Payment Methods & Wallet, Gift Cards, Post-Sale (returns/disputes/reviews/refunds/warranty/
support), Notifications, and Account (dashboard/classified-listings/travel-bookings/inquiries).

Every request's **Description** tab documents the body/query keys — type, required/optional, and
enum values where they exist — plus a saved example response for every status code the endpoint
can realistically return. The page-builder endpoints (`GET /pages/{type}`, `GET /home`,
`GET /browse/{type}/{id}`, `GET /vendors/{vendor_id}`, `GET /brands/{id}`) all reuse one shared,
fully-populated example covering every known block type (`hero_slider`, `flash_sale`,
`product_row`, `full_banner`, `category_pills`, `text_block`, etc.) so you can see real nested
bilingual `{ar, en}` data rather than an empty shell.

## Files

- `Marketplace Customer API.postman_collection.json` — the collection itself.
- `Marketplace Customer API.postman_environment.json` — a starter environment (`base_url`,
  `country`, and blank placeholders for every ID variable below).
- `generate_collection.cjs` — the Node (CommonJS) generator that produces the collection JSON.

## Setup

1. Import both JSON files into Postman (**Import** → drag both, or File → Import).
2. Select the **Customer API — Local** environment in the top-right environment picker.
3. Set `base_url` to wherever your app is running (default `http://localhost:8000`).
4. Set `country` to an active `site_code` in your `countries` table (seeded examples: `egy`,
   `ksa`, `uae`, `kwt`, `qat`, `omn`, `bhr`, `jor`). Every route 404s with
   `{"success": false, "message": "Country not found or not active."}` if this is wrong — check
   this first if everything is failing.
5. Run **Auth → Register** (or **Login**). Its post-response *Tests* script auto-saves
   `access_token` / `refresh_token` as collection variables. The collection's root-level Auth
   (Bearer `{{access_token}}`) is inherited by every request, so nothing else needs configuring —
   public endpoints (Catalog, plus the six pre-auth routes in the Auth folder, plus the guest-cart
   endpoints) explicitly override this to "No Auth" at the request level.
6. For guest cart flows, **Cart & Wishlist → Get Cart** / **Add Item to Cart** auto-save the
   server-issued guest token into `cart_token`, sent on subsequent requests via the `X-Cart-Token`
   header. After logging in, call **Cart & Wishlist → Merge Guest Cart into Account** to fold that
   cart into the authenticated customer's cart.

## Variable auto-chaining

These requests save their generated IDs into collection variables via a post-response *Tests*
script, so the natural next request in the flow just works with zero copy-pasting:

| Request | Saves |
|---|---|
| Register / Login / Refresh Token | `access_token`, `refresh_token` |
| Get Cart / Add Item to Cart / Add Items (Bulk) | `cart_token` (guest sessions only) |

The remaining ID variables (`address_id`, `payment_method_id`, `order_number`,
`warranty_claim_id`, `notification_id`, `sub_order_id`, `review_id`, `refund_id`,
`travel_booking_id`, `inquiry_id`, `block_id`, `category_id`, `vendor_id`, `brand_id`,
`device_token_id`, `cart_item_id`, etc.) aren't auto-populated in this pass — copy them from the
response of the corresponding "list"/"create" request into the environment/collection variable
before running dependent requests.

## Response envelope

Endpoints return the app's `App\Http\Responses\ApiResponse` envelope:

```json
{ "success": true,  "message": "...", "data": { } }
{ "success": false, "message": "...", "errors": { } }
```

A few endpoints (`QrCodeController@show`/`regenerate`, and message-add endpoints like
`DisputeController@addMessage`, `WarrantyClaimController@addMessage`,
`SupportTicketController@addMessage`) return a raw JSON object with no envelope — these are
labeled in their saved examples. Paginated list endpoints nest results as
`data: { items: [...], meta: { current_page, last_page, per_page, total } }`.

Framework-level failures (missing/invalid JWT, FormRequest validation misses, `findOrFail()`
misses) may bypass the envelope entirely and return Laravel's own default shape
(`{ "message": "Unauthenticated." }`, etc.) — verify against your running app's actual behavior,
since this collection's examples were built from route/controller/resource inspection rather than
live requests.

## Regenerating

`generate_collection.cjs` builds the collection JSON from a set of composable helpers
(`req(...)`, `folder(...)`, `raw(...)`) — no dependencies beyond Node.js (stdlib `fs` only). To
add or change a request:

1. Find the relevant folder section (they're laid out in the same order as the routes in
   `routes/api_customer.php`: Auth, Profile, Security, Catalog, Cart & Wishlist, Checkout &
   Orders, Addresses, Payment Methods & Wallet, Gift Cards, Post-Sale, Notifications, Account).
2. Copy an existing `req({...})` call as a template — it takes `name`, `method`, `path`, optional
   `query`/`headers`/`body` (via the `raw(...)` helper for JSON or `{mode: 'formdata', formdata:
   [...]}` for multipart), `auth` (defaults to bearer; set `false` for public routes), `desc`
   (markdown), and `responses` (one entry per status code your endpoint can return).
3. Run:
   ```bash
   node generate_collection.cjs
   ```
   This overwrites `Marketplace Customer API.postman_collection.json` in place (pass a path as
   the first CLI arg to write elsewhere instead).
4. Re-import into Postman (or use Postman's "Import" → same file path to update an existing
   collection if you've linked it to a workspace).

If you only need to tweak one example response or fix a typo, it's often faster to hand-edit the
JSON directly (it's plain Postman v2.1 schema) — just make the same edit in the generator too, or
the next regeneration will silently revert it.
