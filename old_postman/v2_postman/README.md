# Marketplace Customer API — Postman Collection

This directory documents every route defined in
`routes/api_customer.php` of the marketplace_platform Laravel app.

## Files

- `api_customer.postman_collection.json` — Postman Collection v2.1 with one
  request item per route, grouped into folders (Auth, Cart, Catalog & Browse,
  Checkout, Orders, Account, the country-agnostic `API v1 - *` folders, etc.).
- `api_customer.postman_environment.json` — companion environment with the
  three variables the collection needs.

## Importing

1. Open Postman → **Import** → select both `api_customer.postman_collection.json`
   and `api_customer.postman_environment.json` (or drag-and-drop them).
2. In the top-right environment selector, choose **Marketplace Platform — Customer API**.
3. Start sending requests.

## Variables

| Variable | Purpose |
|---|---|
| `base_url` | API base path, defaults to `http://localhost:8000/api/customer`. Point this at whichever environment you're testing (local, staging, etc). |
| `country` | Country segment used by every `v1/{country}/...` route (e.g. `eg`, `sa`). Country-agnostic routes (`v1/...` without a country segment, such as the Nawy, App Config, and v1-prefixed folders) don't use this variable. |
| `access_token` | Bearer token used by the collection-level auth (`Authorization: Bearer {{access_token}}`) for every `auth:customer`-protected route. Starts empty. |
| `refresh_token` | JWT refresh token, captured alongside `access_token`; used to re-run **Refresh Token**. Collection-level variable, not in the environment file. |
| `guest_cart_token` | Optional `X-Cart-Token` header value for guest (unauthenticated) cart requests. Collection-level variable, not in the environment file. |

## Auto-populating `access_token`

The **Register**, **Login**, and **Refresh Token** requests (in the *Auth*
folder) each have a `test` script attached that reads
`pm.response.json().data.access_token` (and `.data.refresh_token`) from a
successful (200/201) response and calls
`pm.collectionVariables.set('access_token', ...)`. Run any one of those three
requests and every subsequent authenticated request in the collection will
automatically pick up the new token — no manual copy/paste needed.

Public routes (no `auth:customer` middleware) override the collection-level
bearer auth with `"auth": {"type": "noauth"}` at the request level, so they
work correctly even before you've logged in.

## Stub controllers (return HTTP 501)

A handful of routes point at controllers/actions that are not implemented
yet in the codebase and always return `501 Not Implemented`. They are called
out explicitly in the collection (folder names suffixed `(stub)`, plus a
501 example response on each):

- `OtpController` — `POST v1/otp/send`, `POST v1/otp/verify` (whole controller is a stub)
- `Api\Customer\ReviewController` — `POST v1/reviews`, `GET v1/reviews/mine` (whole controller is a stub)
- `Api\Customer\DeviceTokenController@store` — `POST v1/device-tokens`
- `Api\Customer\MiscController@shippingMethods` — `GET v1/shipping-methods`

## Response envelope conventions

Most endpoints follow the app's shared `App\Http\Responses\ApiResponse`
helper conventions, reflected in the example responses:

- Success: `{"success": true, "message": "...", "data": {...}}`
- Validation error (422): `{"success": false, "message": "...", "errors": {"field": ["..."]}}`
- Unauthenticated (401): `{"success": false, "message": "Unauthenticated."}`
- Not found (404): `{"success": false, "message": "..."}`
- Throttled (429): `{"message": "Too Many Attempts."}`
- Not implemented (501): `{"success": false, "message": "This endpoint is not implemented yet."}`

Paginated `index` endpoints return `data.items` plus a `data.meta` object
(`current_page`, `last_page`, `per_page`, `total`), matching
`ApiResponse::paginated()`.
