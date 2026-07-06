# Marketplace — Customer API (Postman Collection)

Covers every endpoint in [`routes/api_customer.php`](../routes/api_customer.php) — 87 requests
across 9 folders: Auth, Profile, Addresses, Wishlist, Cart & Checkout, Orders/Returns/Disputes/
Reviews/Refunds, Support Tickets, Account (classifieds/travel/dashboard), and the public
Catalog/Browse/Search surface.

Every request's **Description** tab has a full parameter table (path/query/body — type, required,
constraints, description) plus a list of every status code the endpoint can actually return, and
each of those has a saved example response. Query/path parameters also carry per-field
descriptions natively in Postman's Params/Path Variables tabs; body parameters are documented in
the markdown description table (Postman's raw-JSON body editor has no per-field metadata, so this
is the closest native equivalent).

## Files

- `Marketplace Customer API.postman_collection.json` — the collection itself.
- `Marketplace Customer API.postman_environment.json` — a starter environment (`base_url`,
  `country`, and blank placeholders for every ID variable below).

## Setup

1. Import both files into Postman (**Import** → drag both, or File → Import).
2. Select the **Customer API — Local** environment in the top-right environment picker.
3. Set `base_url` to wherever your app is running (default `http://localhost:8000`).
4. Set `country` to an active `site_code` in your `countries` table (seeded examples: `egy`,
   `ksa`, `uae`, `kwt`, `qat`, `omn`, `bhr`, `jor`). Every route 404s with
   `{"success": false, "message": "Country not found or not active."}` if this is wrong — check
   this first if everything is failing.
5. Run **Auth → Register** (or **Login**). Its post-response *Tests* script auto-saves
   `access_token` / `refresh_token` as collection variables. The collection's root-level Auth
   (Bearer `{{access_token}}`) is inherited by every request, so nothing else needs configuring —
   public endpoints (Catalog/Browse/Search, plus the six pre-auth routes in the Auth folder)
   explicitly override this to "No Auth" at the request level since they don't need a token.

## Variable auto-chaining

These requests save their generated IDs into collection variables via a post-response *Tests*
script, so the natural next request in the flow just works with zero copy-pasting:

| Request | Saves |
|---|---|
| Register / Login / Refresh Token | `access_token`, `refresh_token` |
| Logout | clears `access_token`, `refresh_token` |
| Create Address | `address_id` |
| Add Cart Item | `cart_item_id` |
| Place Order | `order_number` |
| Request Return | `return_number` |
| Open Dispute | `dispute_number` |
| Submit Review | `review_id` |
| Create Support Ticket | `ticket_number` |
| Create Classified Listing | `listing_number` |
| Create Travel Booking | `travel_booking_id`, `travel_booking_number` |
| Send Classified Inquiry | `inquiry_id` |

A few variables can't be auto-populated because nothing in this API surface returns them (they
come from seed data, another team's endpoint, or a mailbox) — you'll need to fill these in
manually at least once: `city_id`, `country_id`, `vendor_listing_id`, `shipping_method_id`,
`order_item_id`, `sub_order_number`, `refund_id`, `classified_category_id`, `classified_slug`,
`travel_package_slug`, `category_id`, `travel_category_id`, `brand_id`, `vendor_id`,
`verification_token`, `reset_token`. Each has a description in the environment/collection
explaining where to source it (usually: response of a nearby GET/list request, or your local
mailbox/log capture for OTP tokens).

## Why token-saving is a "Tests" script, not a "Pre-request" script

A pre-request script runs *before* the request is sent — it physically cannot read a response
body that doesn't exist yet. Token capture has to happen after the response arrives, so
Register/Login/Refresh Token save `access_token`/`refresh_token` in their **Tests** tab. This is
the standard, correct mechanism in Postman for this; it just means "prepare the token *before your
next* request" rather than "before this one."

The one place a genuine pre-request script is used is **Place Order**'s `idempotency_key` — that
uses Postman's built-in `{{$guid}}` dynamic variable directly in the JSON body (no script needed)
to generate a fresh key per send, since re-sending the same key against a *succeeded* payment
returns a `409` replay response.

## Response envelope — two shapes, on purpose

Most endpoints return the app's `App\Http\Responses\ApiResponse` envelope:

```json
{ "success": true,  "message": "...", "data": { } }
{ "success": false, "message": "...", "errors": { } }
```

But **framework-level failures bypass that envelope** and use Laravel's own default shape instead
— no `success` key:

```json
{ "message": "Unauthenticated." }
{ "message": "The given data was invalid.", "errors": { "field": ["..."] } }
{ "message": "No query results for model [App\\Models\\X]." }
```

This happens for: missing/invalid JWT (401), FormRequest validation failures (422), and
`Model::findOrFail()`/`firstOrFail()` misses (404). Every saved example in the collection is
labeled with which shape it uses — check for the presence/absence of `"success"` in test scripts
rather than assuming one envelope everywhere.

## Known issues baked into the examples (verified against the code, not guesses)

- **`GET /l/{identifier}` and its legacy alias `GET /products/{identifier}` always return HTTP
  500** right now — a variable-scope bug in `ListingDetailController::resolveListing()`
  (references `$request` where it isn't in scope). The collection includes both the actual 500
  and the intended 200/404 responses, clearly labeled.
- `PUT`/`DELETE /addresses/{address}` and `PUT /addresses/{address}/set-default` don't check that
  the address belongs to the caller, despite an unused `AddressPolicy` existing in the codebase.
- `PUT /cart/items/{id}` has dead (commented-out) exception handling — a bad `:id` throws an
  uncaught 404 instead of a clean error, and exceeding stock/`max_order_quantity` surfaces as a
  raw 500, not a 422.

These aren't collection bugs — they're what the deployed code actually does today. Worth a fix on
the backend, but documented as-is so the collection matches reality.

## Regenerating

`generate_collection.py` builds the collection JSON — it's checked in alongside the output so you
can extend the API surface without hand-editing ~1.4MB of JSON. It has no dependencies beyond
Python 3 (stdlib only: `json`, `copy`). To add or change a request:

1. Find the relevant folder section in `generate_collection.py` (they're laid out in the same
   order as the folders in Postman: Auth, Profile, Addresses, Wishlist, Cart & Checkout, Orders/
   Returns/Disputes/Reviews/Refunds, Support Tickets, Account, Catalog).
2. Copy an existing `make_request(...)` call as a template — it takes the HTTP method, path
   segments (`":param"` for true path variables), a markdown `description` (param tables +
   business rules), a `body` (via the `json_body()` or `form_body()` helpers), and an
   `examples_fn` returning a list of `example(...)` calls, one per status code.
2. Run:
   ```bash
   python3 generate_collection.py
   ```
   This overwrites `Marketplace Customer API.postman_collection.json` in place.
3. Re-import into Postman (or use Postman's "Import" → same file path to update an existing
   collection if you've linked it to a workspace).

If you only need to tweak one example response or fix a typo, it's often faster to hand-edit the
JSON directly (it's plain Postman v2.1 schema) — just make the same edit in the generator too, or
the next regeneration will silently revert it.
