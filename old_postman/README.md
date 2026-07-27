# Marketplace — Customer API (Postman Collection)

> **Note:** This folder has accumulated more than one generated collection over time. This README
> documents the current primary collection, **`customer_api_collection.json`** (168 requests / 34
> folders — one request per `Route::` line in `routes/api_customer.php`, confirmed 1:1 by route
> count). An older, differently-organized collection (`Marketplace Customer API.postman_collection.json`,
> 121 requests / 12 folders, built by `generate_collection.cjs`) is also still present — either
> works, but new edits should target `customer_api_collection.json`.

Covers every endpoint in [`routes/api_customer.php`](../routes/api_customer.php) — 168 requests
across 34 folders: Auth, Cart, Profile, Security, Wishlist, Addresses, Payment Methods, Wallet,
Gift Cards, Checkout, Orders, Returns, Disputes, Reviews, Refunds, Warranty Claims, Warranty
Purchases, Support Tickets, Notifications, Account, Catalog/Browse/Search, Nawy Now, Dual-Mode
Categories, App Config, and the country-agnostic "flat" variants (Orders, Return Requests, Wallet,
Gift Cards, Warranty, OTP, Listings, Reviews, Device Tokens, Misc).

Every request's **Description** tab documents the body/query keys — type, required/optional, and
enum values where they exist — plus a saved example response for every status code the endpoint
can realistically return. The page-builder endpoints (`GET /pages/{type}`, `GET /home`,
`GET /browse/{type}/{id}`, `GET /vendors/{vendor_id}`, `GET /brands/{id}`) all reuse one shared,
fully-populated example covering every known block type (`hero_slider`, `flash_sale`,
`product_row`, `full_banner`, `category_pills`, `text_block`, etc.) so you can see real nested
bilingual `{ar, en}` data rather than an empty shell.

## Files

- `customer_api_collection.json` — **the current collection** (168 requests / 34 folders, generated
  from a full read of `routes/api_customer.php` plus every controller and Form Request it calls
  into). Import this one.
- `README.md` — this file.
- `Marketplace Customer API.postman_collection.json`, `Marketplace Customer API.postman_environment.json`,
  `generate_collection.cjs` — an earlier, alternate collection/generator pair, left in place for
  reference. Not required if you're using `customer_api_collection.json`.

## Setup

1. Import `customer_api_collection.json` into Postman (**Import** → drag the file, or File →
   Import). No separate environment file is required — every configurable value lives in the
   collection's own **Variables** tab (Collection → Variables).
2. Set `base_url` to wherever your app is running (default `http://localhost:8000/api/customer`).
3. Set `country` to an active `site_code` in your `countries` table (default `eg`). Country-scoped
   routes 404 if this is wrong.
4. Run **Auth → Register** (or **Login**). Its post-response *Tests* script auto-saves
   `access_token` / `refresh_token` as collection variables. The collection's root-level Auth
   (Bearer `{{access_token}}`) is inherited by every request, so nothing else needs configuring —
   public endpoints (Catalog/Browse/Search, App Config, Nawy Now, the pre-auth routes in the Auth
   folder, and the public gift-card balance check) explicitly override this to "No Auth" at the
   request level.
5. For guest cart flows, copy the `data.guest_cart_token` from a **Cart → Get Cart** / **Add Item
   to Cart** response into the `cart_token` collection variable — it's sent on subsequent cart
   requests via the `X-Cart-Token` header. After logging in, call **Cart → Merge Guest Cart Into
   Account** to fold that guest cart into the authenticated customer's cart.
6. Dual-mode catalog endpoints (Catalog Listings, Dual-Mode Categories, Listings (Flat)) send an
   `X-Listing-Type` header (default `marketplace`) — change it to `nawy_now` to exercise the other
   mode.

## Variable auto-chaining

These requests save their generated tokens into collection variables via a post-response *Tests*
script, so the auth flow works with zero copy-pasting:

| Request | Saves |
|---|---|
| Auth → Register / Login / Refresh Token | `access_token`, `refresh_token` |

The remaining ID variables (`order_number`, `order_item_id`, `address_id`, `vendor_listing_id`,
`shipping_method_id`, `return_number`, `dispute_number`, `claim_number`, `ticket_number`,
`listing_number`, `review_id`, `vendor_id`, `booking_id`) ship with example placeholder values —
replace them with real IDs copied from the corresponding "list"/"create" response as you exercise
dependent requests (e.g. run **Addresses → Create Address**, copy the returned `id` into
`address_id`, then run **Checkout → Prepare Checkout**).

## Response envelope

Endpoints return the app's `App\Http\Responses\ApiResponse` envelope:

```json
{ "success": true,  "message": "...", "data": { } }
{ "success": false, "message": "...", "errors": { } }
```

A couple of raw (non-`ApiResponse`) endpoints — `AppConfigController@config`/`homePage`, and the
501 stub controllers — return their own ad-hoc JSON shape rather than the standard envelope; these
are called out in their request descriptions and saved examples. Paginated list endpoints nest
results as `data: { items: [...], meta: { current_page, last_page, per_page, total } }`.

Framework-level failures (missing/invalid JWT, `findOrFail()` misses that aren't caught by the
controller) may bypass the envelope entirely and return Laravel's own default shape
(`{ "message": "Unauthenticated." }`, etc.) — the saved 401/404 examples reflect this where the
controller doesn't explicitly catch it. Since these examples were built from static route/
controller/Form-Request inspection rather than live requests, always sanity-check against your
running app's actual behavior.

## Regenerating `customer_api_collection.json`

There's no committed generator script for this file — it was assembled directly against the
current `routes/api_customer.php` and controller source. If you add/change routes, the fastest
path is:

1. Re-read the relevant `Route::` block in `routes/api_customer.php` and the controller
   method(s)/Form Request(s) it calls.
2. Hand-edit `customer_api_collection.json` (it's plain Postman v2.1 schema — each folder is an
   `item` array of `{ name, item: [...] }`; each request is `{ name, request: {...}, response:
   [...] }`).
3. Re-import into Postman (Import → same file path updates the existing collection if it's linked
   to a workspace).

If you only need to tweak one example response or fix a typo, hand-editing the JSON directly is
the simplest option.
