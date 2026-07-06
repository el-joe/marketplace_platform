#!/usr/bin/env python3
"""Generator for the Customer API Postman collection.

Run with `python3 generate_collection.py` from anywhere — it writes
'Marketplace Customer API.postman_collection.json' next to itself. Re-run after editing this
file to add/change endpoints; the environment file and README are hand-maintained separately."""
import json
import copy

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def path_variable(key, value, description):
    return {"key": key, "value": value, "description": description}


def query_param(key, value, description, disabled=False):
    p = {"key": key, "value": value, "description": description}
    if disabled:
        p["disabled"] = True
    return p


def make_url(segments, path_vars=None, query=None):
    """segments: list of literal path parts, using ':name' for true path params."""
    full_path = ["api", "customer", "v1", "{{country}}"] + segments
    raw = "{{base_url}}/" + "/".join(full_path)
    u = {"raw": raw, "host": ["{{base_url}}"], "path": full_path}
    if query:
        u["query"] = query
        qs = "&".join(f"{q['key']}={q.get('value', '')}" for q in query if not q.get("disabled"))
        if qs:
            u["raw"] += "?" + qs
    if path_vars:
        u["variable"] = path_vars
    return u


def json_body(obj):
    return {
        "mode": "raw",
        "raw": json.dumps(obj, indent=2, ensure_ascii=False),
        "options": {"raw": {"language": "json"}},
    }


def form_body(fields):
    return {"mode": "formdata", "formdata": fields}


def header_list(content_type="application/json"):
    h = [{"key": "Accept", "value": "application/json", "type": "text"}]
    if content_type:
        h.append({"key": "Content-Type", "value": content_type, "type": "text"})
    return h


NOAUTH = {"type": "noauth"}


def example(name, status, code, request_obj, body=None, headers=None, raw_body=None):
    if raw_body is not None:
        body_str = raw_body
    elif body is not None:
        body_str = json.dumps(body, indent=2, ensure_ascii=False)
    else:
        body_str = ""
    return {
        "name": name,
        "originalRequest": request_obj,
        "status": status,
        "code": code,
        "_postman_previewlanguage": "json",
        "header": headers if headers is not None else [{"key": "Content-Type", "value": "application/json"}],
        "body": body_str,
        "cookie": [],
    }


def event(listen, script_lines, ):
    return {
        "listen": listen,
        "script": {
            "type": "text/javascript",
            "packages": {},
            "exec": script_lines,
        },
    }


def make_request(
    name,
    method,
    segments,
    description,
    path_vars=None,
    query=None,
    body=None,
    content_type="application/json",
    auth=None,
    examples_fn=None,
    tests=None,
    prerequest=None,
):
    url = make_url(segments, path_vars, query)
    req = {
        "method": method,
        "header": header_list(content_type if body and content_type else (None if body else "application/json") if content_type != "SKIP" else None),
        "url": url,
        "description": description,
    }
    if content_type == "SKIP":
        req["header"] = [{"key": "Accept", "value": "application/json", "type": "text"}]
    if body is not None:
        req["body"] = body
    if auth is not None:
        req["auth"] = auth

    item = {"name": name, "request": req}
    events = []
    if prerequest:
        events.append(event("prerequest", prerequest))
    if tests:
        events.append(event("test", tests))
    if events:
        item["event"] = events

    if examples_fn:
        item["response"] = examples_fn(copy.deepcopy(req))
    else:
        item["response"] = []
    return item


def folder(name, description, items):
    return {"name": name, "description": description, "item": items}


# ---------------------------------------------------------------------------
# Shared param description snippets / reusable pieces
# ---------------------------------------------------------------------------

COUNTRY_NOTE = (
    "\n\n---\n**Path prefix note:** `{{country}}` is a collection variable resolving to the site "
    "`site_code` (e.g. `egy`, `ksa`, `uae`). If the code doesn't match an active country, "
    "every route in this API (public or authenticated) returns:\n```json\n"
    '{"success": false, "message": "Country not found or not active."}\n```\nwith status **404**, '
    "before auth or validation runs."
)

AUTH_401_NOTE = (
    "\n\n**Auth:** Requires `Authorization: Bearer {{access_token}}` (JWT, `customer` guard). "
    "Missing/invalid/expired token returns **401**:\n```json\n{\"message\": \"Unauthenticated.\"}\n```\n"
    "(Laravel's default shape — no `success` key, since this is thrown by the auth middleware, not the app's `ApiResponse` helper.)"
)

def std_401(req):
    return example(
        "401 Unauthorized — missing/invalid token",
        "Unauthorized",
        401,
        req,
        body={"message": "Unauthenticated."},
    )


def std_404_country(req):
    return example(
        "404 Not Found — invalid country code",
        "Not Found",
        404,
        req,
        body={"success": False, "message": "Country not found or not active."},
    )


def formrequest_422(req, field, message):
    return example(
        f"422 Unprocessable Entity — validation error ({field})",
        "Unprocessable Entity",
        422,
        req,
        body={"message": message, "errors": {field: [message]}},
    )


print("helpers loaded ok")

def std_429(req, limit="10 requests/minute"):
    return example(
        f"429 Too Many Requests — throttled ({limit})",
        "Too Many Requests",
        429,
        req,
        body={"message": "Too Many Attempts."},
        headers=[
            {"key": "Content-Type", "value": "application/json"},
            {"key": "Retry-After", "value": "60"},
            {"key": "X-RateLimit-Limit", "value": "10"},
            {"key": "X-RateLimit-Remaining", "value": "0"},
        ],
    )

# ---------------------------------------------------------------------------
# AUTH folder
# ---------------------------------------------------------------------------

SAVE_TOKENS_TEST = [
    "if (pm.response.code === 200 || pm.response.code === 201) {",
    "    const json = pm.response.json();",
    "    const data = json.data || {};",
    "    if (data.access_token) {",
    "        pm.collectionVariables.set('access_token', data.access_token);",
    "        pm.collectionVariables.set('refresh_token', data.refresh_token);",
    "        console.log('Saved access_token / refresh_token to collection variables.');",
    "    }",
    "    pm.test('Response has success:true', () => pm.expect(json.success).to.eql(true));",
    "} else {",
    "    pm.test('Request failed — see response body for details', () => {});",
    "}",
]

register_req = make_request(
    name="Register",
    method="POST",
    segments=["auth", "register"],
    auth=NOAUTH,
    description=(
        "Create a new customer account for the resolved `{{country}}`. Always issues a fresh "
        "access/refresh token pair on success (auto-login after registration) and dispatches a "
        "verification email job.\n\n"
        "**Rate limit:** `throttle:10,1` (10 requests/minute per IP)."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `name` | string | required | max:255 | Customer display name |\n"
        "| `email` | string | required | email, max:255, unique | Login email, must be unique |\n"
        "| `phone` | string | nullable | max:20, unique | Optional phone, must be unique if provided |\n"
        "| `password` | string | required | min:8, confirmed | Plaintext password (hashed server-side) |\n"
        "| `password_confirmation` | string | required (via `confirmed` rule) | must match `password` | Confirmation field |\n"
        "| `referral_code` | string | nullable | size:8 | Another customer's referral code to credit |\n"
        + COUNTRY_NOTE
    ),
    body=json_body({
        "name": "Jane Doe",
        "email": "jane.doe@example.com",
        "phone": "+201234567890",
        "password": "SecurePass123",
        "password_confirmation": "SecurePass123",
        "referral_code": None,
    }),
    tests=SAVE_TOKENS_TEST,
    examples_fn=lambda req: [
        example("201 Created — registration successful", "Created", 201, req, body={
            "success": True,
            "message": "Registration successful. Please verify your email.",
            "data": {
                "customer": {
                    "id": "9c2b6e2a-1234-4a11-9a9b-abcdef123456",
                    "name": "Jane Doe", "email": "jane.doe@example.com", "phone": "+201234567890",
                    "status": "active", "date_of_birth": None, "total_orders": 0, "total_spent": 0.0,
                    "loyalty_points": 0.0, "referral_code": "JANE1234", "email_verified": False,
                    "phone_verified": False, "member_since": "2026-07-06",
                },
                "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...access",
                "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...refresh",
                "token_type": "bearer", "expires_in": 3600,
            },
        }),
        formrequest_422(req, "email", "The email has already been taken."),
        std_429(req, "10 requests/minute"),
        std_404_country(req),
    ],
)

login_req = make_request(
    name="Login",
    method="POST",
    segments=["auth", "login"],
    auth=NOAUTH,
    description=(
        "Authenticate with email OR phone + password. Auto-detects whether `email_or_phone` is an "
        "email (via `filter_var(...,FILTER_VALIDATE_EMAIL)`) or a phone number.\n\n"
        "**Rate limit:** `throttle:10,1`."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `email_or_phone` | string | required | — | Registered email address or phone number |\n"
        "| `password` | string | required | — | Account password |\n"
        + COUNTRY_NOTE
    ),
    body=json_body({"email_or_phone": "jane.doe@example.com", "password": "SecurePass123"}),
    tests=SAVE_TOKENS_TEST,
    examples_fn=lambda req: [
        example("200 OK — login successful", "OK", 200, req, body={
            "success": True, "message": "Login successful.",
            "data": {
                "customer": {
                    "id": "9c2b6e2a-1234-4a11-9a9b-abcdef123456", "name": "Jane Doe",
                    "email": "jane.doe@example.com", "phone": "+201234567890", "status": "active",
                    "date_of_birth": "1995-04-12", "total_orders": 4, "total_spent": 1234.5,
                    "loyalty_points": 12.0, "referral_code": "JANE1234", "email_verified": True,
                    "phone_verified": False, "member_since": "2024-01-10",
                },
                "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...access",
                "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...refresh",
                "token_type": "bearer", "expires_in": 3600,
            },
        }),
        example("401 Unauthorized — invalid credentials", "Unauthorized", 401, req,
                body={"success": False, "message": "Invalid credentials."}),
        example("403 Forbidden — account suspended/banned/deleted", "Forbidden", 403, req,
                body={"success": False, "message": "Your account has been suspended."}),
        formrequest_422(req, "email_or_phone", "The email or phone field is required."),
        std_429(req, "10 requests/minute"),
        std_404_country(req),
    ],
)

refresh_req = make_request(
    name="Refresh Token",
    method="POST",
    segments=["auth", "refresh-token"],
    auth=NOAUTH,
    description=(
        "Exchange a valid, unexpired refresh token for a brand-new access/refresh token pair. "
        "The submitted refresh token is invalidated immediately after use (single-use).\n\n"
        "**Rate limit:** `throttle:10,1`."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `refresh_token` | string | required | — | Refresh token issued by Register/Login/a prior Refresh call |\n"
        + COUNTRY_NOTE
    ),
    body=json_body({"refresh_token": "{{refresh_token}}"}),
    tests=SAVE_TOKENS_TEST,
    examples_fn=lambda req: [
        example("200 OK — token refreshed", "OK", 200, req, body={
            "success": True, "message": "Token refreshed.",
            "data": {
                "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...newaccess",
                "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...newrefresh",
                "token_type": "bearer", "expires_in": 3600,
            },
        }),
        example("401 Unauthorized — invalid or expired refresh token", "Unauthorized", 401, req,
                body={"success": False, "message": "Invalid or expired refresh token."}),
        example("401 Unauthorized — wrong token type (e.g. access token reused as refresh)", "Unauthorized", 401, req,
                body={"success": False, "message": "Invalid token type."}),
        example("401 Unauthorized — account not found or inactive", "Unauthorized", 401, req,
                body={"success": False, "message": "Account not found or inactive."}),
        formrequest_422(req, "refresh_token", "The refresh token field is required."),
        std_429(req, "10 requests/minute"),
    ],
)

forgot_req = make_request(
    name="Forgot Password",
    method="POST",
    segments=["auth", "forgot-password"],
    auth=NOAUTH,
    description=(
        "Requests a password-reset token. **Always returns 200** regardless of whether the "
        "account exists, to prevent user enumeration — check the actual mailbox/OTP table in a "
        "test environment to obtain the real `token` for the Reset Password request.\n\n"
        "**Rate limit:** `throttle:5,1`."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `email_or_phone` | string | required | — | Registered email or phone |\n"
        + COUNTRY_NOTE
    ),
    body=json_body({"email_or_phone": "jane.doe@example.com"}),
    examples_fn=lambda req: [
        example("200 OK — generic success (sent if account exists)", "OK", 200, req, body={
            "success": True,
            "message": "If an account with those credentials exists, a reset link has been sent.",
        }),
        formrequest_422(req, "email_or_phone", "The email or phone field is required."),
        std_429(req, "5 requests/minute"),
    ],
)

reset_req = make_request(
    name="Reset Password",
    method="POST",
    segments=["auth", "reset-password"],
    auth=NOAUTH,
    description=(
        "Completes a password reset using the OTP `token` obtained via Forgot Password.\n\n"
        "**Rate limit:** `throttle:5,1`."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `token` | string | required | size:64 | OTP token from the password-reset email/SMS |\n"
        "| `password` | string | required | min:8, confirmed | New password |\n"
        "| `password_confirmation` | string | required (via `confirmed`) | must match `password` | Confirmation field |\n"
        + COUNTRY_NOTE
    ),
    body=json_body({
        "token": "{{reset_token}}",
        "password": "NewSecurePass456",
        "password_confirmation": "NewSecurePass456",
    }),
    examples_fn=lambda req: [
        example("200 OK — password reset", "OK", 200, req,
                body={"success": True, "message": "Password reset successfully."}),
        example("422 Unprocessable Entity — invalid/expired/used token", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Invalid or expired reset token."}),
        formrequest_422(req, "token", "The token must be 64 characters."),
        std_429(req, "5 requests/minute"),
    ],
)

verify_email_req = make_request(
    name="Verify Email",
    method="POST",
    segments=["auth", "verify-email"],
    auth=NOAUTH,
    description=(
        "Confirms a customer's email using the OTP token sent at registration (no auth guard "
        "needed — the token itself is the proof of identity).\n\n"
        "### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `token` | string | required | size:64 | OTP token from the verification email |\n"
        + COUNTRY_NOTE
    ),
    body=json_body({"token": "{{verification_token}}"}),
    examples_fn=lambda req: [
        example("200 OK — email verified", "OK", 200, req,
                body={"success": True, "message": "Email verified successfully."}),
        example("422 Unprocessable Entity — invalid/expired/used token", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Invalid or expired verification token."}),
        formrequest_422(req, "token", "The token must be 64 characters."),
    ],
)

logout_req = make_request(
    name="Logout",
    method="POST",
    segments=["auth", "logout"],
    description="Invalidates the current JWT access token server-side." + AUTH_401_NOTE + COUNTRY_NOTE,
    tests=[
        "if (pm.response.code === 200) {",
        "    pm.collectionVariables.set('access_token', '');",
        "    pm.collectionVariables.set('refresh_token', '');",
        "    console.log('Cleared stored tokens after logout.');",
        "}",
    ],
    examples_fn=lambda req: [
        example("200 OK — logged out", "OK", 200, req, body={"success": True, "message": "Logged out successfully."}),
        std_401(req),
        std_404_country(req),
    ],
)

me_req = make_request(
    name="Get Current Customer (Me)",
    method="GET",
    segments=["auth", "me"],
    description="Returns the authenticated customer's profile — identical shape to `GET /profile`." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={
            "success": True, "message": "Profile retrieved.",
            "data": {
                "id": "9c2b6e2a-1234-4a11-9a9b-abcdef123456", "name": "Jane Doe",
                "email": "jane.doe@example.com", "phone": "+201234567890", "status": "active",
                "date_of_birth": "1995-04-12", "total_orders": 4, "total_spent": 1234.5,
                "loyalty_points": 12.0, "referral_code": "JANE1234", "email_verified": True,
                "phone_verified": False, "member_since": "2024-01-10",
            },
        }),
        std_401(req),
        std_404_country(req),
    ],
)

resend_verification_req = make_request(
    name="Resend Verification Email",
    method="POST",
    segments=["auth", "resend-verification"],
    description=(
        "Re-dispatches the email verification job for the authenticated customer.\n\n"
        "**Rate limit:** `throttle:3,1`." + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK — verification email sent", "OK", 200, req,
                body={"success": True, "message": "Verification email sent."}),
        example("422 Unprocessable Entity — already verified", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Email is already verified."}),
        std_401(req),
        std_429(req, "3 requests/minute"),
    ],
)

auth_folder = folder(
    "Auth",
    "Registration, login, token lifecycle, password reset, and email verification. "
    "`Register`, `Login`, and `Refresh Token` all auto-save `access_token`/`refresh_token` into "
    "collection variables via a post-response Test script — no manual copy/paste needed.",
    [register_req, login_req, refresh_req, forgot_req, reset_req, verify_email_req,
     logout_req, me_req, resend_verification_req],
)

print("auth folder built:", len(auth_folder["item"]), "requests")

# ---------------------------------------------------------------------------
# PROFILE folder
# ---------------------------------------------------------------------------

CUSTOMER_SHAPE = {
    "id": "9c2b6e2a-1234-4a11-9a9b-abcdef123456", "name": "Jane Doe",
    "email": "jane.doe@example.com", "phone": "+201234567890", "status": "active",
    "date_of_birth": "1995-04-12", "total_orders": 4, "total_spent": 1234.5,
    "loyalty_points": 12.0, "referral_code": "JANE1234", "email_verified": True,
    "phone_verified": False, "member_since": "2024-01-10",
}

get_profile_req = make_request(
    name="Get Profile",
    method="GET",
    segments=["profile"],
    description="Returns the authenticated customer's profile." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Profile retrieved.", "data": CUSTOMER_SHAPE}),
        std_401(req),
        std_404_country(req),
    ],
)

update_profile_req = make_request(
    name="Update Profile",
    method="PUT",
    segments=["profile"],
    description=(
        "Updates `name`/`date_of_birth`/`phone`. Changing `phone` to a new value resets "
        "`phone_verified` to false (requires re-verification, though no phone-OTP endpoint is "
        "exposed in this API surface yet)."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `name` | string | required | max:255 | Display name |\n"
        "| `date_of_birth` | date | nullable | date, before:today | Must be in the past |\n"
        "| `phone` | string | nullable | max:20, unique (ignoring self) | Changing this un-verifies phone |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"name": "Jane A. Doe", "date_of_birth": "1995-04-12", "phone": "+201234567890"}),
    examples_fn=lambda req: [
        example("200 OK — profile updated", "OK", 200, req, body={"success": True, "message": "Profile updated.", "data": CUSTOMER_SHAPE}),
        formrequest_422(req, "phone", "The phone has already been taken."),
        std_401(req),
        std_404_country(req),
    ],
)

update_password_req = make_request(
    name="Update Password",
    method="PUT",
    segments=["profile", "password"],
    description=(
        "Changes the account password. **Force-logs-out all sessions** afterward "
        "(`auth('customer')->logout(true)` invalidates every issued JWT, including the one used "
        "for this very request) — re-authenticate via Login afterward."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `current_password` | string | required | must match stored hash | Current password for re-auth |\n"
        "| `password` | string | required | min:8, confirmed, different:current_password | New password |\n"
        "| `password_confirmation` | string | required (via `confirmed`) | must match `password` | Confirmation field |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({
        "current_password": "SecurePass123",
        "password": "EvenMoreSecure456",
        "password_confirmation": "EvenMoreSecure456",
    }),
    examples_fn=lambda req: [
        example("200 OK — password updated (all tokens invalidated)", "OK", 200, req,
                body={"success": True, "message": "Password updated. Please log in again."}),
        formrequest_422(req, "current_password", "The current password is incorrect."),
        formrequest_422(req, "password", "The password field must be different from current password."),
        std_401(req),
        std_404_country(req),
    ],
)

delete_account_req = make_request(
    name="Delete Account",
    method="DELETE",
    segments=["profile"],
    description=(
        "Soft-deletes the account (`status` set to `deleted`; the `customers` table has no "
        "`deleted_at` column, so this is a status transition, not a true soft-delete). No "
        "confirmation body is required. Force-invalidates all JWT sessions."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK — account deleted", "OK", 200, req, body={"success": True, "message": "Account deleted."}),
        std_401(req),
        std_404_country(req),
    ],
)

profile_folder = folder(
    "Profile",
    "Authenticated customer's own profile: view, update, change password, delete account.",
    [get_profile_req, update_profile_req, update_password_req, delete_account_req],
)

# ---------------------------------------------------------------------------
# ADDRESSES folder
# ---------------------------------------------------------------------------

ADDRESS_SHAPE = {
    "id": "f47ac10b-58cc-4372-a567-0e02b2c3d479", "label": "Home",
    "recipient_name": "Jane Doe", "recipient_phone": "+201234567890",
    "country_id": "c1a1b1c1-0000-4000-8000-000000000001",
    "city_id": "ci1a1b1c-0000-4000-8000-000000000002",
    "area": "Downtown", "street_address": "123 Main St", "building": "5", "floor": "2",
    "apartment": "12", "postal_code": None, "landmark": "Near mall",
    "latitude": 30.0444, "longitude": 31.2357, "is_default": True, "address_type": "shipping",
    "full_address": "Bldg 5, Floor 2, Apt 12, 123 Main St, Downtown, (Near Near mall)",
}

ADDRESS_BODY_TABLE = (
    "\n\n### Body params\n"
    "| Field | Type | Required | Constraints | Description |\n"
    "|---|---|---|---|---|\n"
    "| `label` | string | nullable | max:100 | e.g. \"Home\", \"Work\" |\n"
    "| `recipient_name` | string | required | max:255 | Delivery recipient's name |\n"
    "| `recipient_phone` | string | required | max:20 | Delivery contact phone |\n"
    "| `city_id` | string(uuid) | required | uuid, exists:cities,id | City reference |\n"
    "| `area` | string | nullable | max:255 | Neighborhood/district |\n"
    "| `street_address` | string | required | max:500 | Street line |\n"
    "| `building` | string | nullable | max:100 | Building number/name |\n"
    "| `floor` | string | nullable | max:20 | Floor |\n"
    "| `apartment` | string | nullable | max:50 | Unit/apartment |\n"
    "| `landmark` | string | nullable | max:255 | Nearby landmark |\n"
    "| `latitude` | number | nullable | between:-90,90 | Geo latitude |\n"
    "| `longitude` | number | nullable | between:-180,180 | Geo longitude |\n"
    "| `address_type` | string | required | in: shipping, billing, both | Address purpose |\n"
    "| `is_default` | boolean | optional | boolean | Make this the default address |\n\n"
    "`country_id` is set server-side from the resolved `{{country}}` (or the customer's own "
    "country) and is never client-settable. `postal_code` appears in responses but has no "
    "corresponding input field on either Create or Update."
)

list_addresses_req = make_request(
    name="List Addresses",
    method="GET",
    segments=["addresses"],
    description=(
        "Lists all addresses belonging to the authenticated customer, most-default-first "
        "(`orderByDesc('is_default')`)." + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Addresses retrieved.", "data": [ADDRESS_SHAPE]}),
        std_401(req),
        std_404_country(req),
    ],
)

create_address_req = make_request(
    name="Create Address",
    method="POST",
    segments=["addresses"],
    description="Creates a new address for the authenticated customer." + ADDRESS_BODY_TABLE + AUTH_401_NOTE + COUNTRY_NOTE,
    body=json_body({
        "label": "Home", "recipient_name": "Jane Doe", "recipient_phone": "+201234567890",
        "city_id": "{{city_id}}", "area": "Downtown", "street_address": "123 Main St",
        "building": "5", "floor": "2", "apartment": "12", "landmark": "Near mall",
        "latitude": 30.0444, "longitude": 31.2357, "address_type": "shipping", "is_default": True,
    }),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('address_id', data.id);",
        "    console.log('Saved address_id =', data.id);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Address created.", "data": ADDRESS_SHAPE}),
        formrequest_422(req, "address_type", "The selected address type is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

update_address_req = make_request(
    name="Update Address",
    method="PUT",
    segments=["addresses", ":address"],
    path_vars=[path_variable("address", "{{address_id}}", "Address primary key (UUID)")],
    description=(
        "Updates an existing address. Same body shape as Create — all fields are re-validated "
        "as if creating fresh (this endpoint does not support partial/`sometimes` updates).\n\n"
        "> ⚠️ **Known gap (verified in code):** this endpoint does **not** check that `:address` "
        "belongs to the authenticated customer, despite an `AddressPolicy` existing in the "
        "codebase — it is never invoked here. Only `GET /addresses` is properly ownership-scoped. "
        "Treat any UUID as updatable until this is fixed server-side."
        + ADDRESS_BODY_TABLE + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({
        "label": "Home", "recipient_name": "Jane Doe", "recipient_phone": "+201234567890",
        "city_id": "{{city_id}}", "area": "Downtown", "street_address": "123 Main St, Apt 4",
        "building": "5", "floor": "2", "apartment": "12", "landmark": "Near mall",
        "latitude": 30.0444, "longitude": 31.2357, "address_type": "shipping", "is_default": True,
    }),
    examples_fn=lambda req: [
        example("200 OK — address updated", "OK", 200, req, body={"success": True, "message": "Address updated.", "data": ADDRESS_SHAPE}),
        example("404 Not Found — address id doesn't exist", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Address] f47ac10b-58cc-4372-a567-0e02b2c3d479"}),
        formrequest_422(req, "street_address", "The street address field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

delete_address_req = make_request(
    name="Delete Address",
    method="DELETE",
    segments=["addresses", ":address"],
    path_vars=[path_variable("address", "{{address_id}}", "Address primary key (UUID)")],
    description=(
        "Soft-deletes an address. Blocked with **409** if the address is referenced as the "
        "billing address of any saved payment method (orders do *not* block deletion — they "
        "store address snapshots, not a foreign key)."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK — address deleted", "OK", 200, req, body={"success": True, "message": "Address deleted."}),
        example("409 Conflict — address in use by a saved payment method", "Conflict", 409, req,
                body={"success": False, "message": "This address is in use and cannot be deleted."}),
        example("404 Not Found — address id doesn't exist", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Address] f47ac10b-58cc-4372-a567-0e02b2c3d479"}),
        std_401(req),
        std_404_country(req),
    ],
)

set_default_address_req = make_request(
    name="Set Default Address",
    method="PUT",
    segments=["addresses", ":address", "set-default"],
    path_vars=[path_variable("address", "{{address_id}}", "Address primary key (UUID)")],
    description="Marks `:address` as the customer's default address (unsets default on all others). No body required." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Default address updated.", "data": {**ADDRESS_SHAPE, "is_default": True}}),
        example("404 Not Found — address id doesn't exist", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Address] f47ac10b-58cc-4372-a567-0e02b2c3d479"}),
        std_401(req),
        std_404_country(req),
    ],
)

addresses_folder = folder(
    "Addresses",
    "Manage the customer's saved shipping/billing addresses.",
    [list_addresses_req, create_address_req, update_address_req, delete_address_req, set_default_address_req],
)

# ---------------------------------------------------------------------------
# WISHLIST folder
# ---------------------------------------------------------------------------

WISHLIST_ITEM_SHAPE = {
    "id": "w1a2b3c4-0000-4000-8000-000000000001", "added_at": "2026-07-01T10:00:00.000000Z",
    "listing_id": "9f3a1b2c-0000-4000-8000-000000000003", "listing_ref": "SKU-ABC123--9f3a1b2c",
    "sku": "SKU-ABC123", "price_cents": 15000, "price_formatted": "150.00", "currency": "EGP",
    "status": "active", "is_admin_listing": False,
    "product": {"id": "p1a2b3c4-0000-4000-8000-000000000004", "name_en": "Wireless Mouse",
                "name_ar": "ماوس لاسلكي", "slug": "wireless-mouse", "thumbnail": "https://cdn.example.com/img.jpg"},
    "vendor": {"id": "v1a2b3c4-0000-4000-8000-000000000005", "store_name": "Tech Store"},
    "shipping_badge": {"label_en": "Fast Shipping", "label_ar": "شحن سريع", "color_hex": "#00FF00",
                        "text_color_hex": "#FFFFFF", "delivery_days_min": 1, "delivery_days_max": 3},
}

list_wishlist_req = make_request(
    name="List Wishlist",
    method="GET",
    segments=["wishlist"],
    query=[query_param("page", "1", "Page number (fixed 20/page server-side; no per_page control)", disabled=True)],
    description="Paginated (20/page) wishlist items, newest-added first." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [WISHLIST_ITEM_SHAPE],
            "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 1},
        }}),
        std_401(req),
        std_404_country(req),
    ],
)

add_wishlist_req = make_request(
    name="Add To Wishlist",
    method="POST",
    segments=["wishlist"],
    description=(
        "Adds a vendor listing to the wishlist."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `vendor_listing_id` | string(uuid) | required | uuid, exists:vendor_listings,id | Listing to wishlist |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"vendor_listing_id": "{{vendor_listing_id}}"}),
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Added to wishlist", "data": WISHLIST_ITEM_SHAPE}),
        example("422 Unprocessable Entity — already in wishlist", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Listing already in wishlist"}),
        formrequest_422(req, "vendor_listing_id", "The selected vendor listing id is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

remove_wishlist_req = make_request(
    name="Remove From Wishlist",
    method="DELETE",
    segments=["wishlist", ":vendor_listing_id"],
    path_vars=[path_variable("vendor_listing_id", "{{vendor_listing_id}}", "Vendor listing UUID to remove from wishlist")],
    description="Removes a listing from the wishlist." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Removed from wishlist"}),
        example("404 Not Found — item not in wishlist", "Not Found", 404, req,
                body={"success": False, "message": "Item not found in wishlist"}),
        std_401(req),
        std_404_country(req),
    ],
)

wishlist_folder = folder(
    "Wishlist",
    "Save/remove vendor listings for later.",
    [list_wishlist_req, add_wishlist_req, remove_wishlist_req],
)

print("profile/address/wishlist folders built:", len(profile_folder["item"]), len(addresses_folder["item"]), len(wishlist_folder["item"]))

# ---------------------------------------------------------------------------
# CART / CHECKOUT folder
# ---------------------------------------------------------------------------

CART_ITEM_SHAPE = {
    "cart_item_id": "c1e2a3b4-0000-4000-8000-000000000001",
    "listing_id": "9f3a1b2c-0000-4000-8000-000000000003", "listing_ref": "SKU-ABC123--9f3a1b2c",
    "sku": "SKU-ABC123", "vendor_sku": "V-100", "name_en": "Wireless Mouse", "name_ar": "فأرة لاسلكية",
    "thumbnail": "https://cdn.example.com/img.jpg", "unit_price_cents": 7500, "quantity": 2,
    "line_total_cents": 15000, "max_order_quantity": 10,
    "vendor": {"id": "v1a2b3c4-0000-4000-8000-000000000005", "store_name": "Acme Store"},
    "is_admin_listing": False,
    "shipping_badge": {"label_en": "Express", "label_ar": "سريع", "color_hex": "#00A651",
                        "text_color_hex": "#FFFFFF", "delivery_days_min": 1, "delivery_days_max": 3},
    "in_stock": True, "price_changed": False,
}

CART_SHAPE = {
    "cart_id": "b2a1c3d4-0000-4000-8000-000000000006", "currency": "EGP",
    "summary": {"subtotal_cents": 15000, "discount_cents": 0, "estimated_shipping_cents": 0,
                "estimated_tax_cents": 2100, "estimated_total_cents": 17100, "item_count": 2},
    "coupon": None, "items": [CART_ITEM_SHAPE], "expires_at": "2026-08-05T12:00:00.000000Z",
}

CART_SHAPE_WITH_COUPON = {**CART_SHAPE, "summary": {**CART_SHAPE["summary"], "discount_cents": 1500, "estimated_total_cents": 15600},
                           "coupon": {"code": "WELCOME10", "type": "percentage", "description": "10% off your order"}}

get_cart_req = make_request(
    name="Get Cart",
    method="GET",
    segments=["cart"],
    description=(
        "Returns (creating if necessary) the customer's cart for `{{country}}`. **Side effect:** "
        "every call recalculates the cart — drops items whose listing became inactive/deleted, "
        "re-syncs prices to the live listing price, and bumps `expires_at` by 30 days. "
        "`estimated_shipping_cents` is always `0` here; real shipping only appears from "
        "Checkout → Shipping Methods onward."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": CART_SHAPE}),
        std_401(req),
        std_404_country(req),
    ],
)

add_cart_item_req = make_request(
    name="Add Cart Item",
    method="POST",
    segments=["cart", "items"],
    description=(
        "Adds a listing to the cart, or increments quantity if it's already present."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `vendor_listing_id` | string(uuid) | required | uuid, exists:vendor_listings,id | Listing to add |\n"
        "| `quantity` | integer | required | min:1, max:999 | Quantity to add (summed with existing qty in cart) |\n\n"
        "Business rules enforced (all surface as 422 via `ApiResponse::error`): stock must cover "
        "the requested/combined quantity, combined quantity can't exceed the listing's "
        "`max_order_quantity`, and the cart can't exceed 50 distinct line items."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"vendor_listing_id": "{{vendor_listing_id}}", "quantity": 2}),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('cart_item_id', data.item.cart_item_id);",
        "    console.log('Saved cart_item_id =', data.item.cart_item_id);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Item added to cart", "data": {
            "cart": CART_SHAPE, "item": CART_ITEM_SHAPE, "listing_ref": "SKU-ABC123--9f3a1b2c"}}),
        example("422 Unprocessable Entity — insufficient stock", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Insufficient stock. Only 3 unit(s) available."}),
        example("422 Unprocessable Entity — exceeds max order quantity", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Exceeds maximum order quantity for this listing."}),
        example("422 Unprocessable Entity — cart item limit reached", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Cart cannot exceed 50 items."}),
        formrequest_422(req, "vendor_listing_id", "The selected vendor listing id is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

update_cart_item_req = make_request(
    name="Update Cart Item Quantity",
    method="PUT",
    segments=["cart", "items", ":id"],
    path_vars=[path_variable("id", "{{cart_item_id}}", "Cart item UUID (from Get Cart or Add Cart Item response)")],
    description=(
        "Sets the absolute quantity for a cart line item."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `quantity` | integer | required | min:1, max:999 | New absolute quantity |\n\n"
        "> ⚠️ **Known bug (verified in code):** the controller's exception handling around this "
        "call is dead/commented-out code. A non-existent `:id` throws an **uncaught** "
        "`ModelNotFoundException` (plain 404, not the clean `ApiResponse::error` shape), and "
        "exceeding stock/`max_order_quantity` throws an **uncaught** `DomainException` that "
        "surfaces as a raw **500**, not a 422. Documented here as the collection reflects actual "
        "deployed behavior, not the intended one."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"quantity": 3}),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Cart item updated", "data": {
            "cart": CART_SHAPE, "item": CART_ITEM_SHAPE, "listing_ref": "SKU-ABC123--9f3a1b2c"}}),
        example("404 Not Found — cart item id doesn't exist (uncaught, default Laravel shape)", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\CartItem] c1e2a3b4-0000-4000-8000-000000000001"}),
        example("500 Internal Server Error — quantity exceeds stock/max (uncaught DomainException — known bug)", "Internal Server Error", 500, req,
                body={"message": "Server Error"}),
        formrequest_422(req, "quantity", "The quantity must be at least 1."),
        std_401(req),
        std_404_country(req),
    ],
)

remove_cart_item_req = make_request(
    name="Remove Cart Item",
    method="DELETE",
    segments=["cart", "items", ":id"],
    path_vars=[path_variable("id", "{{cart_item_id}}", "Cart item UUID")],
    description="Removes a single line item from the cart." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Item removed from cart", "data": CART_SHAPE}),
        example("404 Not Found — cart item not found", "Not Found", 404, req,
                body={"success": False, "message": "Cart item not found."}),
        std_401(req),
        std_404_country(req),
    ],
)

clear_cart_req = make_request(
    name="Clear Cart",
    method="DELETE",
    segments=["cart"],
    description="Removes all items and any applied coupon. Idempotent — succeeds even on an empty cart." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Cart cleared"}),
        std_401(req),
        std_404_country(req),
    ],
)

apply_coupon_req = make_request(
    name="Apply Coupon",
    method="POST",
    segments=["cart", "coupon"],
    description=(
        "Applies a coupon code to the cart."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `code` | string | required | max:50 | Coupon code, e.g. `WELCOME10` |\n\n"
        "Coupon `type` ∈ `percentage \\| fixed_amount \\| free_shipping \\| bogo`. This endpoint "
        "checks active/date-window/usage-limits/minimum-order but does **not** check scope "
        "(vendor/category/product) or currency match — those are only re-validated at "
        "Checkout Prepare / Place Order, so a coupon can \"apply\" here yet still fail at checkout."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"code": "WELCOME10"}),
    examples_fn=lambda req: [
        example("200 OK — coupon applied", "OK", 200, req, body={"success": True, "message": "Coupon \"WELCOME10\" applied", "data": CART_SHAPE_WITH_COUPON}),
        example("404 Not Found — coupon doesn't exist / inactive / outside valid dates", "Not Found", 404, req,
                body={"success": False, "message": "Coupon not found or is invalid."}),
        example("422 Unprocessable Entity — total usage limit reached", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "This coupon has reached its total usage limit."}),
        example("422 Unprocessable Entity — per-customer usage limit reached", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "You have already used this coupon the maximum number of times."}),
        example("422 Unprocessable Entity — below minimum order amount", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "A minimum order of 500.00 EGP is required for this coupon."}),
        formrequest_422(req, "code", "The code field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

remove_coupon_req = make_request(
    name="Remove Coupon",
    method="DELETE",
    segments=["cart", "coupon"],
    description="Removes any applied coupon. Idempotent." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Coupon removed", "data": CART_SHAPE}),
        std_401(req),
        std_404_country(req),
    ],
)

shipping_methods_req = make_request(
    name="Get Shipping Methods",
    method="GET",
    segments=["checkout", "shipping-methods"],
    description=(
        "Lists shipping methods available for the cart. If the customer has a default address "
        "with `address_type` in `shipping`/`both`, fees/COD availability are computed for that "
        "address's shipping zone; otherwise all active methods are returned with zeroed-out fees "
        "and `cod_available:false`."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK — with default address resolved to a zone", "OK", 200, req, body={
            "success": True, "message": "Shipping methods retrieved", "data": {
                "shipping_methods": [{
                    "id": "{{shipping_method_id}}", "code": "express", "name": "Express",
                    "badge_label_en": "Express", "badge_label_ar": "سريع", "badge_color_hex": "#00A651",
                    "badge_text_color_hex": "#FFFFFF", "delivery_days_min": 1, "delivery_days_max": 3,
                    "fee_cents": 3000, "is_free": False, "cod_extra_fee_cents": 500, "cod_available": True,
                }],
                "destination_zone": "Cairo Metro", "cod_available_for_address": True,
            }}),
        example("200 OK — no default shipping address on file", "OK", 200, req, body={
            "success": True, "message": "Shipping methods retrieved", "data": {
                "shipping_methods": [{
                    "id": "{{shipping_method_id}}", "code": "standard", "name": "Standard",
                    "badge_label_en": "Standard", "badge_label_ar": "عادي", "badge_color_hex": "#999999",
                    "badge_text_color_hex": "#FFFFFF", "delivery_days_min": 2, "delivery_days_max": 5,
                    "fee_cents": 0, "is_free": True, "cod_extra_fee_cents": 0, "cod_available": False,
                }],
                "destination_zone": None, "cod_available_for_address": False,
            }}),
        std_401(req),
        std_404_country(req),
    ],
)

CHECKOUT_ITEMS_PREVIEW = [{
    "listing_id": "9f3a1b2c-0000-4000-8000-000000000003", "listing_ref": "SKU-ABC123--9f3a1b2c",
    "sku": "SKU-ABC123", "name_en": "Wireless Mouse", "quantity": 2, "unit_price_cents": 7500,
    "line_total_cents": 15000, "thumbnail": "https://cdn.example.com/img.jpg",
    "vendor_name": "Acme Store", "is_admin_listing": False,
}]

prepare_checkout_req = make_request(
    name="Prepare Checkout (Preview)",
    method="POST",
    segments=["checkout", "prepare"],
    description=(
        "Pure pricing preview for a chosen address/shipping method/payment method/coupon — "
        "**does not** create an order, and does **not** re-check stock or listing status (unlike "
        "Place Order)."
        "\n\n### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `address_id` | integer | required | integer, exists:addresses,id | Shipping address (note: int PK, not UUID) |\n"
        "| `shipping_method_id` | string(uuid) | required | uuid, exists:shipping_methods,id | From Get Shipping Methods |\n"
        "| `payment_method` | string | required | in: card, wallet, cod, bnpl, bank_transfer | Payment method to preview |\n"
        "| `coupon_code` | string | nullable | max:50 | Optional coupon to preview |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({
        "address_id": "{{address_id}}", "shipping_method_id": "{{shipping_method_id}}",
        "payment_method": "cod", "coupon_code": "WELCOME10",
    }),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Checkout preview ready", "data": {
            "order_summary": {"subtotal_cents": 15000, "discount_cents": 1500, "shipping_cents": 3000,
                               "cod_fee_cents": 0, "tax_cents": 1890, "total_cents": 18390, "currency": "EGP"},
            "shipping": {"method_id": "{{shipping_method_id}}", "method_name": "Express", "fee_cents": 3000,
                         "is_free": False, "estimated_delivery_days_min": 1, "estimated_delivery_days_max": 3},
            "address": {"id": 42, "recipient_name": "Jane Doe", "street_address": "123 Nile St.",
                        "city": "Cairo", "country": "Egypt"},
            "payment_method": "cod", "available_payment_methods": ["card", "wallet", "cod"],
            "coupon": {"code": "WELCOME10", "type": "percentage", "discount_cents": 1500},
            "items": CHECKOUT_ITEMS_PREVIEW,
        }}),
        example("422 Unprocessable Entity — cart is empty", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Cart is empty."}),
        example("404 Not Found — address doesn't belong to customer", "Not Found", 404, req,
                body={"success": False, "message": "Address not found."}),
        example("422 Unprocessable Entity — COD unavailable for this location", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Cash on delivery is not available for your location."}),
        example("422 Unprocessable Entity — invalid coupon code", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Invalid coupon code."}),
        example("422 Unprocessable Entity — coupon fails business rules (min order not met, shown as example)", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Order does not meet minimum amount for this coupon"}),
        formrequest_422(req, "payment_method", "The selected payment method is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

place_order_req = make_request(
    name="Place Order",
    method="POST",
    segments=["checkout", "place-order"],
    description=(
        "Creates the order from the current cart. **Rate limit:** `throttle:5,1`.\n\n"
        "### Body params\n"
        "| Field | Type | Required | Constraints | Description |\n"
        "|---|---|---|---|---|\n"
        "| `address_id` | integer | required | integer, exists:addresses,id | Shipping address |\n"
        "| `shipping_method_id` | string(uuid) | required | uuid, exists:shipping_methods,id | Chosen method |\n"
        "| `payment_method` | string | required | in: card, wallet, cod, bnpl, bank_transfer | Payment method |\n"
        "| `coupon_code` | string | nullable | max:50 | Optional coupon |\n"
        "| `customer_notes` | string | nullable | max:500 | e.g. \"Leave at door\" |\n"
        "| `idempotency_key` | string | required | max:100 | Client-generated key; replaying a key tied to a **pending or succeeded** transaction returns 409 with the prior order_number. Use `{{$guid}}` in Postman to generate a fresh one per send. |\n"
        "| `gateway_token` | string | required_if payment_method=card | — | Tokenized card reference from gateway SDK |\n"
        "| `gateway` | string | required_if payment_method=card | in: thawani, stripe, tap | Payment gateway |\n\n"
        "**Note:** a `201` response does not guarantee `payment_status:\"succeeded\"` — for `card` "
        "payments, gateway failure is reflected as `payment_status:\"failed\"` / `status:\"cancelled\"` "
        "inside a still-`201` body, since payment happens after the DB transaction commits. For "
        "`cod`, `payment_status` correctly stays `\"pending\"` here too — cash hasn't changed hands "
        "yet. It only flips to `\"captured\"` once the delivery agent collects payment on delivery "
        "(see `Delivery\\AssignmentController::confirmDelivery`), which also flips the underlying "
        "`PaymentTransaction` from `pending` to `succeeded` in lockstep."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({
        "address_id": "{{address_id}}", "shipping_method_id": "{{shipping_method_id}}",
        "payment_method": "cod", "coupon_code": "WELCOME10", "customer_notes": "Leave at door",
        "idempotency_key": "{{$guid}}", "gateway_token": None, "gateway": None,
    }),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('order_number', data.order_number);",
        "    console.log('Saved order_number =', data.order_number);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created — order placed", "Created", 201, req, body={
            "success": True, "message": "Order placed successfully", "data": {
                "order_number": "NOON-20260706-AB12CD", "status": "placed", "payment_status": "pending",
                "total_cents": 18390, "currency": "EGP", "placed_at": "2026-07-06T14:32:10+00:00",
                "sub_orders": [{
                    "sub_order_number": "NOON-20260706-AB12CD-01", "vendor": "Acme Store", "status": "placed",
                    "fulfillment_model": "fbm", "items": [{
                        "listing_ref": "SKU-ABC123--9f3a1b2c", "sku": "SKU-ABC123", "name_en": "Wireless Mouse",
                        "quantity": 2, "unit_price_cents": 7500, "line_total_cents": 15390,
                    }],
                }],
            }}),
        example("409 Conflict — idempotency replay of a succeeded order", "Conflict", 409, req,
                body={"success": False, "message": "Order already placed.", "errors": {"order_number": "NOON-20260706-AB12CD"}}),
        example("422 Unprocessable Entity — cart is empty", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Cart is empty."}),
        example("422 Unprocessable Entity — a listing is no longer active", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Listing 9f3a1b2c-0000-4000-8000-000000000003 is no longer available."}),
        example("422 Unprocessable Entity — insufficient stock (pre-transaction check)", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Insufficient stock for one or more items. Only 1 unit(s) available."}),
        example("404 Not Found — address doesn't belong to customer", "Not Found", 404, req,
                body={"success": False, "message": "Address not found."}),
        example("422 Unprocessable Entity — COD unavailable for this location", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Cash on delivery is not available for your location."}),
        example("422 Unprocessable Entity — invalid coupon code", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Invalid coupon code."}),
        example("422 Unprocessable Entity — race-condition stock check inside transaction", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Insufficient stock for one or more items. Please update your cart."}),
        formrequest_422(req, "gateway_token", "The gateway token field is required when payment method is card."),
        std_429(req, "5 requests/minute"),
        std_401(req),
        std_404_country(req),
    ],
)

order_confirmation_req = make_request(
    name="Order Confirmation",
    method="GET",
    segments=["checkout", ":order_number", "confirmation"],
    path_vars=[path_variable("order_number", "{{order_number}}", "Order number returned by Place Order, e.g. NOON-20260706-AB12CD")],
    description=(
        "Post-checkout confirmation screen data for a just-placed order. Scoped to the "
        "authenticated customer (another customer's order number 404s rather than 403, so "
        "existence isn't leaked). **Note:** unlike every cents-based endpoint above, amounts "
        "here are decimals in major currency units (e.g. `150.00`, not `15000`)."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": {
            "id": "ord-uuid-0000-4000-8000-000000000007", "order_number": "NOON-20260706-AB12CD",
            "status": "placed", "payment_status": "pending", "payment_method": "cod", "currency": "EGP",
            "subtotal": 150.00, "discount": 15.00, "shipping": 30.00, "tax": 18.90, "cod_fee": 5.00,
            "total": 188.90, "placed_at": "2026-07-06T14:32:10+00:00",
            "shipping_address": {"recipient_name": "Jane Doe", "recipient_phone": "+201234567890",
                                  "country_id": "c1a1b1c1-0000-4000-8000-000000000001", "city_id": "ci1a1b1c-0000-4000-8000-000000000002",
                                  "city": {"en": "Cairo", "ar": "القاهرة"}, "area": "Nasr City",
                                  "street_address": "123 Nile St.", "building": "5", "floor": "2", "apartment": "12",
                                  "postal_code": "11511", "landmark": "Near mall", "latitude": "30.0444", "longitude": "31.2357"},
            "coupon_code_used": "WELCOME10",
            "sub_orders": [{"id": "so-uuid-0000-4000-8000-000000000008", "sub_order_number": "NOON-20260706-AB12CD-01",
                            "status": "placed", "fulfillment_model": "fbm", "vendor_name": "Acme Store",
                            "subtotal": 150.00, "shipping": 30.00, "tax": 18.90, "tracking_number": None,
                            "estimated_delivery_date": None, "sla_ship_deadline": "2026-07-07T14:32:10+00:00",
                            "items": [{"id": "oi-uuid-0000-4000-8000-000000000009", "product": {
                                "listing_id": "9f3a1b2c-0000-4000-8000-000000000003", "listing_ref": "SKU-ABC123--9f3a1b2c",
                                "sku": "SKU-ABC123", "vendor_sku": "V-100", "name_en": "Wireless Mouse", "name_ar": "فأرة لاسلكية",
                                "price_cents": 7500, "currency": "EGP", "condition": "new", "global_system_type": "marketplace",
                                "thumbnail_url": "https://cdn.example.com/img.jpg", "brand_name": "Logitech", "category_name": "Electronics"},
                                "sku": "SKU-ABC123", "listing_ref": "SKU-ABC123--9f3a1b2c", "vendor_sku": "V-100",
                                "quantity": 2, "unit_price": 75.00, "line_total": 153.90,
                                "fulfillment_status": "pending", "return_eligible_until": None}]}],
        }}),
        example("404 Not Found — order doesn't exist or belongs to another customer", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Order]."}),
        std_401(req),
        std_404_country(req),
    ],
)

cart_checkout_folder = folder(
    "Cart & Checkout",
    "Shopping cart management, shipping-method lookup, checkout pricing preview, and order "
    "placement. `Place Order` auto-saves `order_number` for chaining into the Orders folder.",
    [get_cart_req, add_cart_item_req, update_cart_item_req, remove_cart_item_req, clear_cart_req,
     apply_coupon_req, remove_coupon_req, shipping_methods_req, prepare_checkout_req,
     place_order_req, order_confirmation_req],
)

print("cart/checkout folder built:", len(cart_checkout_folder["item"]))

# ---------------------------------------------------------------------------
# ORDERS / RETURNS / DISPUTES / REVIEWS / REFUNDS folder
# ---------------------------------------------------------------------------

ORDER_LIST_ITEM = {
    "id": "ord-uuid-0000-4000-8000-000000000007", "order_number": "NOON-20260706-AB12CD",
    "status": "delivered", "payment_status": "captured", "payment_method": "card", "currency": "EGP",
    "subtotal": 450.00, "discount": 20.00, "shipping": 25.00, "tax": 15.00, "cod_fee": 0.00, "total": 470.00,
    "placed_at": "2026-06-20T10:15:00+00:00",
    "shipping_address": {"recipient_name": "Jane Doe", "street_address": "123 Nile St."},
    "coupon_code_used": "SAVE20",
    "sub_orders": [{"id": "so-uuid-0000-4000-8000-000000000008", "sub_order_number": "NOON-20260706-AB12CD-01",
                    "status": "delivered", "fulfillment_model": "marketplace", "vendor_name": "Acme Store",
                    "subtotal": 450.00, "shipping": 25.00, "tax": 15.00, "tracking_number": "TRK123",
                    "estimated_delivery_date": "2026-06-25", "sla_ship_deadline": "2026-06-22T00:00:00+00:00",
                    "items": [{"id": "oi-uuid-0000-4000-8000-000000000009", "product": {}, "sku": "SKU1",
                               "listing_ref": "SKU1--abc12345", "vendor_sku": "V-SKU1", "quantity": 2,
                               "unit_price": 225.00, "line_total": 450.00, "fulfillment_status": "delivered",
                               "return_eligible_until": "2026-07-09"}]}],
}

list_orders_req = make_request(
    name="List Orders",
    method="GET",
    segments=["orders"],
    query=[
        query_param("status", "delivered", "Filter by status: placed, confirmed, partially_shipped, shipped, partially_delivered, delivered, completed, cancelled, refunded, disputed", disabled=True),
        query_param("date_from", "2026-06-01", "Filter: placed_at >= this date (Y-m-d)", disabled=True),
        query_param("date_to", "2026-07-06", "Filter: placed_at <= this date (Y-m-d), must be >= date_from", disabled=True),
        query_param("page", "1", "Page number (fixed 20/page)", disabled=True),
    ],
    description="Paginated order history for the authenticated customer, newest-placed first." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [ORDER_LIST_ITEM], "meta": {"current_page": 1, "last_page": 3, "per_page": 20, "total": 42}}}),
        formrequest_422(req, "date_to", "The date to must be a date after or equal to date from."),
        std_401(req),
        std_404_country(req),
    ],
)

show_order_req = make_request(
    name="Get Order Detail",
    method="GET",
    segments=["orders", ":order_number"],
    path_vars=[path_variable("order_number", "{{order_number}}", "Order number, e.g. NOON-20260706-AB12CD")],
    description=(
        "Rich order-tracking detail (bilingual status labels, per-item `can_return`/`can_review` "
        "flags — informational only, **not enforced** on the actual Return/Review submission "
        "endpoints)." + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": {
            "order_number": "NOON-20260706-AB12CD", "status": "delivered",
            "status_label_en": "Delivered", "status_label_ar": "تم التسليم",
            "payment_method": "card", "payment_status": "captured",
            "placed_at": "2026-06-20T10:15:00+00:00", "currency": "EGP",
            "summary": {"subtotal_cents": 45000, "discount_cents": 2000, "shipping_cents": 2500,
                        "cod_fee_cents": 0, "tax_cents": 1500, "total_cents": 47000},
            "shipping_address": {"recipient_name": "Jane Doe", "recipient_phone": "+201234567890",
                                  "street_address": "123 Nile St.", "area": "Nasr City", "city": "Cairo", "country": "Egypt"},
            "sub_orders": [{"id": "so-uuid-0000-4000-8000-000000000008", "sub_order_number": "NOON-20260706-AB12CD-01",
                            "status": "delivered", "fulfillment_model": "marketplace",
                            "vendor": {"id": "v1a2b3c4-0000-4000-8000-000000000005", "store_name": "Acme Store"},
                            "tracking": {"tracking_number": "TRK123", "carrier": "Aramex", "estimated_delivery_date": "2026-06-25",
                                         "shipped_at": "2026-06-21T09:00:00+00:00", "delivered_at": "2026-06-25T13:00:00+00:00",
                                         "events": [{"status": "in_transit", "status_label_en": "In Transit", "status_label_ar": "في الطريق",
                                                     "location": "Cairo hub", "description": None, "occurred_at": "2026-06-22T08:00:00+00:00"}]},
                            "delivery_agent": {"name": "John", "status": "delivered", "otp_required": True, "otp_verified": True},
                            "items": [{"id": "oi-uuid-0000-4000-8000-000000000009", "sku": "SKU1", "listing_ref": "SKU1--abc12345",
                                       "name_en": "Wireless Mouse", "name_ar": "فأرة لاسلكية", "thumbnail": "https://cdn.example.com/img.jpg",
                                       "quantity": 2, "unit_price_cents": 22500, "line_total_cents": 45000,
                                       "fulfillment_status": "delivered", "return_eligible_until": "2026-07-09",
                                       "can_return": True, "can_review": True}]}],
            "marketer_ref": None,
        }}),
        example("404 Not Found — order not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Order not found."}),
        std_401(req),
        std_404_country(req),
    ],
)

cancel_order_req = make_request(
    name="Cancel Order",
    method="POST",
    segments=["orders", ":order_number", "cancel"],
    path_vars=[path_variable("order_number", "{{order_number}}", "Order number to cancel")],
    description=(
        "Cancels an order. Only allowed while `status` is `placed` or `confirmed` — sub-orders "
        "already shipped/delivered are left untouched even though the order-level status still "
        "flips to `cancelled`."
        "\n\n### Body params\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `reason` | string | required | max:1000 | Why the customer is cancelling |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"reason": "Changed my mind"}),
    examples_fn=lambda req: [
        example("200 OK — order cancelled", "OK", 200, req, body={"success": True, "message": "Order cancelled successfully."}),
        example("404 Not Found — order not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Order not found."}),
        example("422 Unprocessable Entity — order not in a cancellable state", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "This order cannot be cancelled in its current status."}),
        formrequest_422(req, "reason", "The reason field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

RETURN_SHAPE = {
    "id": "rr-uuid-0000-4000-8000-000000000010", "return_number": "RET-AB12CD34EF",
    "order_number": "NOON-20260706-AB12CD", "reason": "defective",
    "reason_description": "Item stopped working after 2 days", "return_type": "refund",
    "status": "requested", "refund_amount": None, "rejection_reason": None,
    "created_at": "2026-07-06T12:00:00+00:00",
}

create_return_req = make_request(
    name="Request Return",
    method="POST",
    segments=["orders", ":order_number", "returns"],
    path_vars=[path_variable("order_number", "{{order_number}}", "Order the returned items belong to")],
    description=(
        "Submits a return request for one or more items on the order.\n\n"
        "### Body params\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `order_item_ids` | array | required | min:1 | Order item UUIDs to return |\n"
        "| `order_item_ids.*` | string(uuid) | required | uuid, exists:order_items,id | ⚠️ existence check is global, not scoped to this order |\n"
        "| `reason` | string | required | in: changed_mind, wrong_item, defective, damaged, not_as_described, size_issue, quality_issue, arrived_late, other | Return reason |\n"
        "| `return_type` | string | required | in: refund, exchange, store_credit | Desired resolution |\n"
        "| `comments` | string | nullable | max:2000 | Extra details |\n\n"
        "> ⚠️ No backend enforcement of the 14-day return window or delivered-status — those are "
        "informational-only flags on Get Order Detail (`can_return`). Also: if `order_item_ids` "
        "belong to a different order than `:order_number`, the service can hit a null-pointer "
        "fatal error (empty collection) — pass items that truly belong to this order."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({
        "order_item_ids": ["{{order_item_id}}"], "reason": "defective",
        "return_type": "refund", "comments": "Item stopped working after 2 days",
    }),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('return_number', data.return_number);",
        "    console.log('Saved return_number =', data.return_number);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Return request submitted.", "data": RETURN_SHAPE}),
        example("404 Not Found — order not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Order not found."}),
        formrequest_422(req, "reason", "The selected reason is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

DISPUTE_SHAPE = {
    "id": "d-uuid-0000-4000-8000-000000000011", "dispute_number": "DSP-AB12CD34",
    "order_number": "NOON-20260706-AB12CD", "reason": "item_not_received", "description": "Never arrived",
    "status": "open", "resolution": None, "resolution_notes": None, "compensation": None,
    "resolved_at": None, "created_at": "2026-07-06T12:00:00+00:00",
    "messages": [{"id": "msg-uuid-0000-4000-8000-000000000012", "sender_role": "customer",
                  "message": "Never arrived", "created_at": "2026-07-06T12:00:00+00:00", "attachments": []}],
}

create_dispute_req = make_request(
    name="Open Dispute",
    method="POST",
    segments=["orders", ":order_number", "disputes"],
    path_vars=[path_variable("order_number", "{{order_number}}", "Order the dispute concerns")],
    description=(
        "Opens a dispute against the order's **first** sub-order (not item- or sub-order-specific "
        "— if the order has multiple vendor sub-orders, the dispute always attaches to the first "
        "one). No limit on multiple disputes per order.\n\n"
        "### Body params\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `reason` | string | required | in: item_not_received, item_damaged, item_not_as_described, counterfeit, wrong_item, quality_issue, seller_unresponsive, refund_not_received, other | Dispute reason |\n"
        "| `description` | string | required | max:5000 | Details of the issue |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"reason": "item_not_received", "description": "Never arrived"}),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('dispute_number', data.dispute_number);",
        "    console.log('Saved dispute_number =', data.dispute_number);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Dispute opened.", "data": DISPUTE_SHAPE}),
        example("404 Not Found — order not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Order not found."}),
        example("422 Unprocessable Entity — order has no sub-orders (edge case)", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "No sub-orders found for this order."}),
        formrequest_422(req, "description", "The description field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

REVIEW_SHAPE = {
    "id": "rev-uuid-0000-4000-8000-000000000013", "rating": 5, "title": None, "body": "Great product!",
    "is_verified_purchase": True, "helpful_count": 0, "not_helpful_count": 0,
    "reviewer_name": "Jane Doe", "created_at": "2026-07-06T12:00:00+00:00",
}

create_review_req = make_request(
    name="Submit Review",
    method="POST",
    segments=["orders", ":order_number", "reviews"],
    path_vars=[path_variable("order_number", "{{order_number}}", "Order the reviewed item belongs to")],
    content_type=None,
    description=(
        "Reviews a delivered order item (multipart — supports optional image uploads). One "
        "review per `order_item_id` per customer; item must have `fulfillment_status:\"delivered\"`. "
        "New reviews start as `status:\"pending\"` (moderation) and won't appear on "
        "`GET products/{slug}/reviews` until published.\n\n"
        "### Body params (multipart/form-data)\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `order_item_id` | string(uuid) | required | uuid, exists:order_items,id | Item being reviewed |\n"
        "| `rating` | integer | required | min:1, max:5 | Star rating |\n"
        "| `comment` | string | nullable | max:5000 | Review text (there is no separate `title` field) |\n"
        "| `images` | file[] | nullable | max:5 files | Photo evidence |\n"
        "| `images.*` | file | — | image, max:5120 KB | Each image ≤ 5MB |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=form_body([
        {"key": "order_item_id", "value": "{{order_item_id}}", "type": "text", "description": "Order item UUID being reviewed"},
        {"key": "rating", "value": "5", "type": "text", "description": "1-5 star rating"},
        {"key": "comment", "value": "Great product, fast shipping!", "type": "text", "description": "Review text"},
        {"key": "images[]", "type": "file", "src": [], "description": "Optional photo(s), image mime, max 5MB each, up to 5 files"},
    ]),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('review_id', data.id);",
        "    console.log('Saved review_id =', data.id);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Review submitted.", "data": REVIEW_SHAPE}),
        example("404 Not Found — order not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Order not found."}),
        example("404 Not Found — order_item_id belongs to a different order", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\OrderItem] oi-uuid-0000-4000-8000-000000000009"}),
        example("422 Unprocessable Entity — item not yet delivered", "Unprocessable Entity", 422, req,
                body={"message": "The given data was invalid.", "errors": {"order_item_id": ["This item has not been delivered yet."]}}),
        example("422 Unprocessable Entity — already reviewed", "Unprocessable Entity", 422, req,
                body={"message": "The given data was invalid.", "errors": {"order_item_id": ["You have already reviewed this item."]}}),
        formrequest_422(req, "rating", "The rating must be at least 1."),
        std_401(req),
        std_404_country(req),
    ],
)

track_suborder_req = make_request(
    name="Track Sub-Order",
    method="POST",
    segments=["sub-orders", ":id", "track"],
    path_vars=[path_variable("id", "{{sub_order_number}}", "⚠️ Despite the param name `id`, this actually matches `sub_order_number` (e.g. NOON-20260706-AB12CD-01), not the SubOrder's UUID PK")],
    description="Standalone tracking lookup for a single sub-order, scoped to the authenticated customer." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": {
            "sub_order_number": "NOON-20260706-AB12CD-01", "status": "shipped", "fulfillment_model": "marketplace",
            "vendor": {"id": "v1a2b3c4-0000-4000-8000-000000000005", "store_name": "Acme Store"},
            "tracking": {"tracking_number": "TRK123", "carrier": "Aramex", "estimated_delivery_date": "2026-06-25",
                         "shipped_at": "2026-06-21T09:00:00+00:00", "delivered_at": None,
                         "events": [{"status": "in_transit", "status_label_en": "In Transit", "status_label_ar": "في الطريق",
                                     "location": "Cairo hub", "description": None, "occurred_at": "2026-06-22T08:00:00+00:00"}]},
            "delivery_agent": {"name": "John", "status": "picked_up", "otp_required": True, "otp_verified": False},
            "timeline": [{"source": "carrier", "status": "in_transit", "status_label_en": "In Transit", "status_label_ar": "في الطريق",
                          "location": "Cairo hub", "description": None, "occurred_at": "2026-06-22T08:00:00+00:00"}],
        }}),
        example("404 Not Found — sub-order not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Sub-order not found."}),
        std_401(req),
        std_404_country(req),
    ],
)

list_returns_req = make_request(
    name="List Returns",
    method="GET",
    segments=["returns"],
    description="Paginated (20/page) return-request history, newest first. No filters supported." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [{**RETURN_SHAPE, "items": [{"order_item_id": "oi-uuid-0000-4000-8000-000000000009", "quantity": 1, "product_snapshot": {}}]}],
            "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 2}}}),
        std_401(req),
        std_404_country(req),
    ],
)

show_return_req = make_request(
    name="Get Return Detail",
    method="GET",
    segments=["returns", ":return_number"],
    path_vars=[path_variable("return_number", "{{return_number}}", "Return number, e.g. RET-AB12CD34EF")],
    description="Return request detail, scoped to the authenticated customer." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": RETURN_SHAPE}),
        example("404 Not Found — return not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Return request not found."}),
        std_401(req),
        std_404_country(req),
    ],
)

show_dispute_req = make_request(
    name="Get Dispute Detail",
    method="GET",
    segments=["disputes", ":dispute_number"],
    path_vars=[path_variable("dispute_number", "{{dispute_number}}", "Dispute number, e.g. DSP-AB12CD34")],
    description="Dispute detail with the message thread (internal admin notes are always excluded)." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": {**DISPUTE_SHAPE, "status": "under_review"}}),
        example("404 Not Found — dispute not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Dispute not found."}),
        std_401(req),
        std_404_country(req),
    ],
)

add_dispute_message_req = make_request(
    name="Add Dispute Message",
    method="POST",
    segments=["disputes", ":dispute_number", "messages"],
    path_vars=[path_variable("dispute_number", "{{dispute_number}}", "Dispute number")],
    content_type=None,
    description=(
        "Adds a message to the dispute thread (multipart — supports one optional attachment). "
        "No restriction on ticket status — messages can be added even to a closed/resolved dispute.\n\n"
        "### Body params (multipart/form-data)\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `message` | string | required | max:5000 | Message text |\n"
        "| `attachment` | file | nullable | max:10240 KB, mimes: jpg,jpeg,png,pdf,mp4 | Optional evidence file |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=form_body([
        {"key": "message", "value": "Any update on this?", "type": "text", "description": "Message text, max 5000 chars"},
        {"key": "attachment", "type": "file", "src": [], "description": "Optional file: jpg/jpeg/png/pdf/mp4, max 10MB"},
    ]),
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Message sent.", "data": {
            "id": "msg-uuid-0000-4000-8000-000000000014", "message": "Any update on this?",
            "created_at": "2026-07-06T12:05:00+00:00"}}),
        example("404 Not Found — dispute not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Dispute not found."}),
        formrequest_422(req, "message", "The message field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

update_review_req = make_request(
    name="Update Review",
    method="PUT",
    segments=["reviews", ":id"],
    path_vars=[path_variable("id", "{{review_id}}", "Review UUID (must belong to the authenticated customer)")],
    description=(
        "Edits a review within the 7-day edit window (from `created_at`); after that, returns "
        "422. Omitted fields keep their existing values.\n\n"
        "### Body params\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `rating` | integer | optional (`sometimes`) | min:1, max:5 | New star rating |\n"
        "| `comment` | string | nullable | max:5000 | New review text |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"rating": 4, "comment": "Updating my review after a week of use."}),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Review updated.", "data": {**REVIEW_SHAPE, "rating": 4}}),
        example("404 Not Found — review not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Review not found."}),
        example("422 Unprocessable Entity — edit window expired (>7 days)", "Unprocessable Entity", 422, req,
                body={"message": "The given data was invalid.", "errors": {"review": ["The edit window for this review has expired."]}}),
        formrequest_422(req, "rating", "The rating must be between 1 and 5."),
        std_401(req),
        std_404_country(req),
    ],
)

mark_review_helpful_req = make_request(
    name="Mark Review Helpful",
    method="POST",
    segments=["reviews", ":id", "helpful"],
    path_vars=[path_variable("id", "{{review_id}}", "Any review UUID — voting on other customers' reviews is allowed")],
    description="Casts one \"helpful\" vote per (review, customer) pair. No \"not helpful\" counterpart endpoint exists." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Marked as helpful."}),
        example("404 Not Found — review not found", "Not Found", 404, req,
                body={"success": False, "message": "Review not found."}),
        example("422 Unprocessable Entity — already voted", "Unprocessable Entity", 422, req,
                body={"message": "The given data was invalid.", "errors": {"review": ["You have already voted on this review."]}}),
        std_401(req),
        std_404_country(req),
    ],
)

REFUND_SHAPE = {
    "id": "rf-uuid-0000-4000-8000-000000000015", "order_number": "NOON-20260706-AB12CD",
    "amount": 150.00, "currency": "EGP", "reason": "damaged", "refund_type": "partial",
    "status": "completed", "created_at": "2026-06-28T10:00:00+00:00",
}

list_refunds_req = make_request(
    name="List Refunds",
    method="GET",
    segments=["refunds"],
    description=(
        "Paginated (20/page) refund history, newest first. `reason` ∈ customer_request, "
        "out_of_stock, damaged, wrong_item, not_as_described, late_delivery, duplicate_order, "
        "other. `status` ∈ pending, approved, processing, completed, failed, rejected. "
        "`refund_type` ∈ full, partial, shipping_only."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [REFUND_SHAPE], "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 1}}}),
        std_401(req),
        std_404_country(req),
    ],
)

show_refund_req = make_request(
    name="Get Refund Detail",
    method="GET",
    segments=["refunds", ":id"],
    path_vars=[path_variable("id", "{{refund_id}}", "Refund UUID")],
    description="Refund detail, scoped to the authenticated customer." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": REFUND_SHAPE}),
        example("404 Not Found — refund not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Refund not found."}),
        std_401(req),
        std_404_country(req),
    ],
)

product_reviews_public_req = make_request(
    name="List Product Reviews (Public)",
    method="GET",
    segments=["products", ":slug", "reviews"],
    path_vars=[path_variable("slug", "wireless-mouse", "Product slug")],
    auth=NOAUTH,
    description=(
        "**Public** — no auth required. Paginated (20/page) published reviews for a product, "
        "ordered by `helpful_count` desc, filtered to the resolved `{{country}}`. Reviews with "
        "`status:\"pending\"` (unmoderated) never appear here."
        + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [{**REVIEW_SHAPE, "helpful_count": 12, "not_helpful_count": 1,
                       "vendor_reply": {"body": "Thanks for the feedback!", "created_at": "2026-07-02T09:00:00+00:00"}}],
            "meta": {"current_page": 1, "last_page": 2, "per_page": 20, "total": 25}}}),
        example("404 Not Found — product slug doesn't exist", "Not Found", 404, req,
                body={"success": False, "message": "Product not found."}),
        std_404_country(req),
    ],
)

orders_folder = folder(
    "Orders, Returns, Disputes, Reviews & Refunds",
    "Post-purchase lifecycle: order history/tracking/cancellation, returns, disputes, product "
    "reviews, and refund history. `Request Return` / `Open Dispute` / `Submit Review` auto-save "
    "their generated identifiers.",
    [list_orders_req, show_order_req, cancel_order_req, create_return_req, create_dispute_req,
     create_review_req, track_suborder_req, list_returns_req, show_return_req, show_dispute_req,
     add_dispute_message_req, update_review_req, mark_review_helpful_req, list_refunds_req,
     show_refund_req, product_reviews_public_req],
)

print("orders folder built:", len(orders_folder["item"]))

# ---------------------------------------------------------------------------
# SUPPORT TICKETS folder
# ---------------------------------------------------------------------------

TICKET_SHAPE = {
    "id": 42, "ticket_number": "TKT-9F2KQZLP", "category": "order_issue", "priority": "normal",
    "status": "open", "subject": "Item missing from my order", "created_at": "2026-07-06T09:15:00+00:00",
    "resolved_at": None, "satisfaction_rating": None, "satisfaction_comment": None,
}

list_tickets_req = make_request(
    name="List Support Tickets",
    method="GET",
    segments=["support", "tickets"],
    description="Paginated (20/page) ticket history for the authenticated customer, newest first. No filters supported." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [TICKET_SHAPE], "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 1}}}),
        std_401(req),
        std_404_country(req),
    ],
)

create_ticket_req = make_request(
    name="Create Support Ticket",
    method="POST",
    segments=["support", "tickets"],
    content_type=None,
    description=(
        "Opens a new support ticket (multipart — supports one optional attachment). The first "
        "message body doubles as the ticket's `description`.\n\n"
        "### Body params (multipart/form-data)\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `category` | string | required | in: order_issue, payment_issue, account, technical, product_inquiry, policy, payout, catalog, other | Ticket category |\n"
        "| `subject` | string | required | max:255 | Short summary |\n"
        "| `message` | string | required | max:10000 | Full description / first message |\n"
        "| `priority` | string | nullable | in: low, normal, high, urgent | Defaults to `normal` |\n"
        "| `order_number` | string | nullable | max:30 | Related order (must belong to this customer, else silently ignored — no error) |\n"
        "| `attachment` | file | nullable | max:10240 KB, mimes: jpg,jpeg,png,pdf,doc,docx | Optional single file |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=form_body([
        {"key": "category", "value": "order_issue", "type": "text", "description": "order_issue|payment_issue|account|technical|product_inquiry|policy|payout|catalog|other"},
        {"key": "subject", "value": "Item missing from my order", "type": "text", "description": "Short summary, max 255 chars"},
        {"key": "message", "value": "One item from my order is missing, please help.", "type": "text", "description": "Full description, max 10000 chars"},
        {"key": "priority", "value": "normal", "type": "text", "description": "low|normal|high|urgent (defaults to normal)"},
        {"key": "order_number", "value": "{{order_number}}", "type": "text", "description": "Related order number, must belong to this customer"},
        {"key": "attachment", "type": "file", "src": [], "description": "Optional file: jpg/jpeg/png/pdf/doc/docx, max 10MB"},
    ]),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('ticket_number', data.ticket_number);",
        "    console.log('Saved ticket_number =', data.ticket_number);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Ticket created.", "data": TICKET_SHAPE}),
        formrequest_422(req, "category", "The selected category is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

show_ticket_req = make_request(
    name="Get Support Ticket Detail",
    method="GET",
    segments=["support", "tickets", ":ticket_number"],
    path_vars=[path_variable("ticket_number", "{{ticket_number}}", "Ticket number, e.g. TKT-9F2KQZLP")],
    description="Ticket detail with its full (non-internal) message thread." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {**TICKET_SHAPE, "status": "resolved",
            "resolved_at": "2026-07-03T14:00:00+00:00", "messages": [
                {"id": 101, "sender_role": "customer", "message": "One item from my order is missing, please help.",
                 "created_at": "2026-07-01T10:22:31+00:00", "attachments": [
                     {"url": "http://localhost:8000/storage/support-tickets/abc123.jpg", "name": "photo.jpg"}]},
                {"id": 108, "sender_role": "support", "message": "We've issued a replacement, sorry for the inconvenience.",
                 "created_at": "2026-07-02T09:00:00+00:00", "attachments": []},
            ]}}),
        example("404 Not Found — ticket not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Ticket not found."}),
        std_401(req),
        std_404_country(req),
    ],
)

add_ticket_message_req = make_request(
    name="Add Ticket Message",
    method="POST",
    segments=["support", "tickets", ":ticket_number", "messages"],
    path_vars=[path_variable("ticket_number", "{{ticket_number}}", "Ticket number")],
    content_type=None,
    description=(
        "Adds a message to the ticket thread. No restriction on ticket status — works even on "
        "resolved/closed tickets.\n\n"
        "### Body params (multipart/form-data)\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `message` | string | required | max:10000 | Message text |\n"
        "| `attachment` | file | nullable | max:10240 KB, mimes: jpg,jpeg,png,pdf,doc,docx | Optional file |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=form_body([
        {"key": "message", "value": "Any update on this?", "type": "text", "description": "Message text, max 10000 chars"},
        {"key": "attachment", "type": "file", "src": [], "description": "Optional file: jpg/jpeg/png/pdf/doc/docx, max 10MB"},
    ]),
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Message sent.", "data": {
            "id": 109, "message": "Any update on this?", "created_at": "2026-07-06T09:20:00+00:00"}}),
        example("404 Not Found — ticket not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Ticket not found."}),
        formrequest_422(req, "message", "The message field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

rate_ticket_req = make_request(
    name="Rate Support Ticket",
    method="PUT",
    segments=["support", "tickets", ":ticket_number", "rate"],
    path_vars=[path_variable("ticket_number", "{{ticket_number}}", "Ticket number")],
    description=(
        "Rates a resolved/closed ticket. Only allowed once the ticket status is `resolved` or "
        "`closed`; re-rating an already-rated ticket is permitted and simply overwrites the "
        "previous rating.\n\n"
        "### Body params\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `satisfaction_rating` | integer | required | min:1, max:5 | Star rating |\n"
        "| `satisfaction_comment` | string | nullable | max:2000 | Optional feedback text |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"satisfaction_rating": 4, "satisfaction_comment": "Resolved quickly, thanks!"}),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Thank you for your feedback.", "data": {
            **TICKET_SHAPE, "status": "resolved", "resolved_at": "2026-07-03T14:00:00+00:00",
            "satisfaction_rating": 4, "satisfaction_comment": "Resolved quickly, thanks!"}}),
        example("404 Not Found — ticket not found / not owned", "Not Found", 404, req,
                body={"success": False, "message": "Ticket not found."}),
        example("422 Unprocessable Entity — ticket not resolved/closed yet", "Unprocessable Entity", 422, req,
                body={"message": "Only resolved or closed tickets can be rated.", "errors": {"ticket": ["Only resolved or closed tickets can be rated."]}}),
        formrequest_422(req, "satisfaction_rating", "The satisfaction rating must be between 1 and 5."),
        std_401(req),
        std_404_country(req),
    ],
)

support_folder = folder(
    "Support Tickets",
    "General customer-support ticketing, separate from order-specific disputes.",
    [list_tickets_req, create_ticket_req, show_ticket_req, add_ticket_message_req, rate_ticket_req],
)

print("support folder built:", len(support_folder["item"]))

# ---------------------------------------------------------------------------
# ACCOUNT (dashboard, classified-listings, travel-bookings, inquiries) folder
# ---------------------------------------------------------------------------

dashboard_req = make_request(
    name="Account Dashboard",
    method="GET",
    segments=["account", "dashboard"],
    description="Aggregated dashboard: profile summary, last-3 orders/listings/inquiries/bookings, loyalty points, wishlist & notification counts." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": {
            "profile": {"id": "9c2b6e2a-1234-4a11-9a9b-abcdef123456", "name": "Jane Doe", "email": "jane.doe@example.com",
                        "phone": "+201234567890", "referral_code": "JANE1234"},
            "recent_orders": [{"order_number": "NOON-20260706-AB12CD", "status": "delivered", "total": 470.00,
                               "currency": "EGP", "created_at": "2026-06-20T10:15:00+00:00"}],
            "recent_classified_listings": [{"listing_number": "CL-7F3K9QX2", "title": "Toyota Corolla 2020",
                                             "status": "active", "primary_image": "https://cdn.example.com/car.jpg",
                                             "created_at": "2026-06-30T09:00:00+00:00"}],
            "recent_classified_inquiries": [{"id": "inq-uuid-0000-4000-8000-000000000016", "listing_number": "CL-7F3K9QX2",
                                              "listing_title": "Toyota Corolla 2020", "status": "new",
                                              "created_at": "2026-06-29T08:00:00+00:00"}],
            "recent_travel_bookings": [{"booking_number": "TRV-9K2M7XPQ", "package_title": "Cairo to Sharm 3 Nights",
                                        "status": "pending_documents", "total": 3000.00, "currency": "EGP",
                                        "created_at": "2026-06-28T07:00:00+00:00"}],
            "loyalty_points": 120.5, "wishlist_count": 4, "unread_notifications_count": 2,
        }}),
        std_401(req),
        std_404_country(req),
    ],
)

CLASSIFIED_LISTING_LIST_SHAPE = {
    "id": "b1a2c3d4-0000-4000-8000-000000000017", "listing_number": "CL-7F3K9QX2",
    "title_en": "Toyota Corolla 2020", "title_ar": "تويوتا كورولا 2020", "price_cents": 1200000,
    "currency": "EGP", "status": "active", "views_count": 57,
    "primary_image": "https://cdn.example.com/car.jpg", "created_at": "2026-06-30T09:00:00+00:00",
}

CLASSIFIED_LISTING_DETAIL_SHAPE = {
    "id": "b1a2c3d4-0000-4000-8000-000000000017", "listing_number": "CL-7F3K9QX2",
    "title_en": "Toyota Corolla 2020", "title_ar": "تويوتا كورولا 2020",
    "description_en": "Well maintained, single owner.", "description_ar": None, "listing_purpose": "sale",
    "price_cents": 1200000, "currency": "EGP", "price_negotiable": False,
    "attributes": {"mileage_km": 45000, "year": 2020}, "latitude": None, "longitude": None,
    "status": "pending_review", "rejection_reason": None, "views_count": 0, "expires_at": None,
    "created_at": "2026-07-06T12:00:00+00:00", "country_id": "c1a1b1c1-0000-4000-8000-000000000001",
    "city_id": None, "category": {"id": "cat-uuid-0000-4000-8000-000000000018", "name_en": "Cars", "name_ar": "سيارات", "slug": "cars"},
    "images": [{"id": "img-uuid-0000-4000-8000-000000000019", "url": "https://cdn.example.com/car1.jpg", "position": 0, "is_primary": True}],
    "sketch_file_url": None, "attachments": [], "contract": {"accepted_at": None, "has_signature": False},
    "inquiries_count": None,
}

list_classified_listings_req = make_request(
    name="List My Classified Listings",
    method="GET",
    segments=["account", "classified-listings"],
    query=[
        query_param("status", "active", "Filter: draft, pending_contract, pending_review, active, paused, sold, expired, rejected", disabled=True),
        query_param("page", "1", "Page number (20/page)", disabled=True),
    ],
    description="Paginated (20/page) listing of the customer's own classified (seller) listings, newest first." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [CLASSIFIED_LISTING_LIST_SHAPE], "meta": {"current_page": 1, "last_page": 3, "per_page": 20, "total": 42}}}),
        formrequest_422(req, "status", "The selected status is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

create_classified_listing_req = make_request(
    name="Create Classified Listing",
    method="POST",
    segments=["account", "classified-listings"],
    content_type=None,
    description=(
        "Creates a classified listing as a seller (multipart). If the resolved category has a "
        "`contract_template_id`, the listing starts as `pending_contract`; otherwise "
        "`pending_review`. Category-specific requirements (`requires_location_map`, "
        "`requires_sketch_upload`, `required_attachment_types`) are enforced with a 422 on "
        "create (but only loosely re-checked, not enforced, on update).\n\n"
        "### Body params (multipart/form-data)\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `classified_category_id` | string(uuid) | required | exists:classified_categories,id | Category |\n"
        "| `country_id` | string(uuid) | required | exists:countries,id | Listing country |\n"
        "| `city_id` | string(uuid) | nullable | exists:cities,id | Listing city |\n"
        "| `listing_purpose` | string | required | in: sale, rent | Sale or rental listing |\n"
        "| `title_en` | string | required | max:255 | English title |\n"
        "| `title_ar` | string | required | max:255 | Arabic title |\n"
        "| `description_en` | string | nullable | — | English description |\n"
        "| `description_ar` | string | nullable | — | Arabic description |\n"
        "| `price_cents` | integer | required | min:0 | Price in minor units (cents) |\n"
        "| `currency` | string | required | size:3 | ISO 4217 code, e.g. EGP |\n"
        "| `price_negotiable` | boolean | optional | boolean | Defaults to false |\n"
        "| `attributes[key]` | any | nullable | category-specific | Free-form attributes, e.g. `attributes[mileage_km]=45000` |\n"
        "| `latitude` | number | nullable | between:-90,90 | Required if category.requires_location_map |\n"
        "| `longitude` | number | nullable | between:-180,180 | Required if category.requires_location_map |\n"
        "| `images[]` | file[] | required | min:1, image, max:10240 KB each | First image (index 0) becomes primary |\n"
        "| `sketch_file` | file | nullable | max:20480 KB | Required if category.requires_sketch_upload |\n"
        "| `attachments[type]` | file | nullable | max:20480 KB each | Keyed by attachment type, e.g. `attachments[national_id]` |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=form_body([
        {"key": "classified_category_id", "value": "{{classified_category_id}}", "type": "text", "description": "Classified category UUID"},
        {"key": "country_id", "value": "{{country_id}}", "type": "text", "description": "Country UUID"},
        {"key": "city_id", "value": "{{city_id}}", "type": "text", "description": "City UUID (optional)"},
        {"key": "listing_purpose", "value": "sale", "type": "text", "description": "sale|rent"},
        {"key": "title_en", "value": "Toyota Corolla 2020", "type": "text", "description": "English title, max 255"},
        {"key": "title_ar", "value": "تويوتا كورولا 2020", "type": "text", "description": "Arabic title, max 255"},
        {"key": "description_en", "value": "Well maintained, single owner.", "type": "text", "description": "English description"},
        {"key": "description_ar", "value": "", "type": "text", "description": "Arabic description"},
        {"key": "price_cents", "value": "1200000", "type": "text", "description": "Price in cents"},
        {"key": "currency", "value": "EGP", "type": "text", "description": "ISO 4217 currency code"},
        {"key": "price_negotiable", "value": "false", "type": "text", "description": "Boolean, defaults false"},
        {"key": "attributes[mileage_km]", "value": "45000", "type": "text", "description": "Free-form category attribute"},
        {"key": "attributes[year]", "value": "2020", "type": "text", "description": "Free-form category attribute"},
        {"key": "images[]", "type": "file", "src": [], "description": "Required, at least 1 image, max 10MB each"},
        {"key": "sketch_file", "type": "file", "src": [], "description": "Required only if category.requires_sketch_upload"},
    ]),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('listing_number', data.listing_number);",
        "    console.log('Saved listing_number =', data.listing_number);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Listing created.", "data": CLASSIFIED_LISTING_DETAIL_SHAPE}),
        example("422 Unprocessable Entity — category requires a sketch file", "Unprocessable Entity", 422, req,
                body={"message": "The given data was invalid.", "errors": {"sketch_file": ["A sketch file is required for this category."]}}),
        formrequest_422(req, "images", "The images field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

show_classified_listing_req = make_request(
    name="Get My Classified Listing",
    method="GET",
    segments=["account", "classified-listings", ":listing_number"],
    path_vars=[path_variable("listing_number", "{{listing_number}}", "Listing number, e.g. CL-7F3K9QX2")],
    description="Listing detail (own listings only). `inquiries_count` and the `contract` block are populated here (unlike Create's response)." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": {**CLASSIFIED_LISTING_DETAIL_SHAPE, "status": "active", "inquiries_count": 3}}),
        example("404 Not Found — listing not found / not owned", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\ClassifiedListing]."}),
        std_401(req),
        std_404_country(req),
    ],
)

update_classified_listing_req = make_request(
    name="Update My Classified Listing",
    method="PUT",
    segments=["account", "classified-listings", ":listing_number"],
    path_vars=[path_variable("listing_number", "{{listing_number}}", "Listing number")],
    content_type=None,
    description=(
        "Updates a classified listing. Only allowed while `status` is `draft` or `rejected` — "
        "otherwise **422**. Sending `images[]` **replaces all existing images**; `attachments[]` "
        "is additive instead. Category-requirement checks (sketch/location/attachments) are "
        "**not** enforced on update (only on create).\n\n"
        "Postman note: multipart bodies can't natively use `PUT` — this request uses method-spoofing "
        "(`POST` + `_method=PUT` field) so file uploads work.\n\n"
        "Body fields are the same as Create Classified Listing but all optional/`sometimes`."
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=form_body([
        {"key": "_method", "value": "PUT", "type": "text", "description": "Laravel method-spoofing for multipart PUT"},
        {"key": "title_en", "value": "Toyota Corolla 2020 (Price Reduced)", "type": "text", "description": "English title, max 255"},
        {"key": "price_cents", "value": "1100000", "type": "text", "description": "Price in cents"},
        {"key": "price_negotiable", "value": "true", "type": "text", "description": "Boolean"},
    ]),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Listing updated.", "data": {**CLASSIFIED_LISTING_DETAIL_SHAPE, "status": "draft", "price_cents": 1100000, "price_negotiable": True}}),
        example("404 Not Found — listing not found / not owned", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\ClassifiedListing]."}),
        example("422 Unprocessable Entity — listing not editable in its current status", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Only draft or rejected listings can be edited."}),
        formrequest_422(req, "currency", "The currency must be 3 characters."),
        std_401(req),
        std_404_country(req),
    ],
)

delete_classified_listing_req = make_request(
    name="Delete My Classified Listing",
    method="DELETE",
    segments=["account", "classified-listings", ":listing_number"],
    path_vars=[path_variable("listing_number", "{{listing_number}}", "Listing number")],
    description="Soft-deletes a listing. Only allowed while `status` is `draft`, `rejected`, or `expired`." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Listing deleted."}),
        example("404 Not Found — listing not found / not owned", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\ClassifiedListing]."}),
        example("422 Unprocessable Entity — listing not deletable in its current status", "Unprocessable Entity", 422, req,
                body={"success": False, "message": "Only draft, rejected, or expired listings can be deleted."}),
        std_401(req),
        std_404_country(req),
    ],
)

listing_inquiries_req = make_request(
    name="List Inquiries On My Listing",
    method="GET",
    segments=["account", "classified-listings", ":listing_number", "inquiries"],
    path_vars=[path_variable("listing_number", "{{listing_number}}", "Listing number")],
    description="Paginated (20/page) buyer inquiries received on this listing (seller view — buyer's name is masked, e.g. \"Ahmed M***** K***\"). No status filter available." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [{"id": "inq-uuid-0000-4000-8000-000000000016", "buyer_name": "Ahmed M***** K***",
                       "message": "Is this still available? Can we negotiate price?", "contact_phone": "+201123456789",
                       "status": "new", "created_at": "2026-07-05T14:22:00+00:00"}],
            "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 2}}}),
        example("404 Not Found — listing not found / not owned", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\ClassifiedListing]."}),
        std_401(req),
        std_404_country(req),
    ],
)

TRAVEL_BOOKING_SHAPE = {
    "id": "book-uuid-0000-4000-8000-000000000020", "booking_number": "TRV-9K2M7XPQ",
    "status": "pending_documents", "travelers_count": 2, "total_price_cents": 300000,
    "passport_uploaded": True, "contract_signed_at": None, "created_at": "2026-06-28T07:00:00+00:00",
    "package": {"id": "pkg-uuid-0000-4000-8000-000000000021", "title": "Cairo to Sharm 3 Nights",
                "price_cents": 150000, "currency": "EGP"},
}

list_travel_bookings_req = make_request(
    name="List My Travel Bookings",
    method="GET",
    segments=["account", "travel-bookings"],
    query=[
        query_param("status", "confirmed", "Filter: pending_documents, confirmed, cancelled, completed", disabled=True),
        query_param("page", "1", "Page number (15/page — different page size than other list endpoints)", disabled=True),
    ],
    description="Paginated (15/page) travel booking history, newest first." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [TRAVEL_BOOKING_SHAPE], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 5}}}),
        formrequest_422(req, "status", "The selected status is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

show_travel_booking_req = make_request(
    name="Get My Travel Booking",
    method="GET",
    segments=["account", "travel-bookings", ":id"],
    path_vars=[path_variable("id", "{{travel_booking_id}}", "Booking UUID (primary key — NOT the booking_number)")],
    description="Booking detail, with `package.agency` and `package.cover_image` populated (absent on the list endpoint)." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": {
            **TRAVEL_BOOKING_SHAPE, "status": "confirmed", "contract_signed_at": "2026-07-01T10:15:00+00:00",
            "package": {**TRAVEL_BOOKING_SHAPE["package"],
                        "agency": {"id": "agency-uuid-0000-4000-8000-000000000022", "name": "Blue Sky Travel"},
                        "cover_image": "https://cdn.example.com/travel-cover.jpg"}}}),
        example("404 Not Found — booking not found / not owned", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\TravelBooking]."}),
        std_401(req),
        std_404_country(req),
    ],
)

cancel_travel_booking_req = make_request(
    name="Cancel My Travel Booking",
    method="POST",
    segments=["account", "travel-bookings", ":id", "cancel"],
    path_vars=[path_variable("id", "{{travel_booking_id}}", "Booking UUID")],
    description=(
        "Cancels a booking. Only allowed while `status` is `pending_documents` or `confirmed`. "
        "Note: `reason` is validated but **not persisted** anywhere (no `cancellation_reason` "
        "column exists) — it's used only to notify the agency.\n\n"
        "### Body params\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `reason` | string | required | max:500 | Cancellation reason (not stored, notification-only) |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"reason": "Change of travel plans"}),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True,
            "message": "Booking cancelled. Refund eligibility is subject to agency review.",
            "data": {"status": "cancelled"}}),
        example("404 Not Found — booking not found / not owned", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\TravelBooking]."}),
        example("422 Unprocessable Entity — booking not cancellable in its current state", "Unprocessable Entity", 422, req,
                body={"message": "The given data was invalid.", "errors": {"status": ["This booking cannot be cancelled in its current state."]}}),
        formrequest_422(req, "reason", "The reason field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

INQUIRY_AS_BUYER_SHAPE = {
    "id": "inq-uuid-0000-4000-8000-000000000023", "message": "Is this still available?",
    "contact_phone": "+201123456789", "status": "new", "created_at": "2026-07-05T14:22:00+00:00",
    "listing": {"id": "listing-uuid-0000-4000-8000-000000000024", "listing_number": "CL-9Z1X4RTY",
                "title_en": "iPhone 14 Pro Max", "title_ar": "آيفون 14 برو ماكس", "status": "active",
                "primary_image": "https://cdn.example.com/phone.jpg"},
}

list_my_inquiries_req = make_request(
    name="List My Inquiries (As Buyer)",
    method="GET",
    segments=["account", "inquiries"],
    query=[
        query_param("status", "new", "Filter: new, replied, closed", disabled=True),
        query_param("page", "1", "Page number (20/page)", disabled=True),
    ],
    description="Paginated (20/page) list of classified-listing inquiries the customer sent as a buyer." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [INQUIRY_AS_BUYER_SHAPE], "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 1}}}),
        formrequest_422(req, "status", "The selected status is invalid."),
        std_401(req),
        std_404_country(req),
    ],
)

show_my_inquiry_req = make_request(
    name="Get My Inquiry (As Buyer)",
    method="GET",
    segments=["account", "inquiries", ":id"],
    path_vars=[path_variable("id", "{{inquiry_id}}", "Inquiry UUID")],
    description="Inquiry detail (as the buyer who sent it)." + AUTH_401_NOTE + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Success", "data": INQUIRY_AS_BUYER_SHAPE}),
        example("404 Not Found — inquiry not found / not owned", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\ClassifiedInquiry]."}),
        std_401(req),
        std_404_country(req),
    ],
)

send_classified_inquiry_req = make_request(
    name="Send Classified Inquiry (As Buyer)",
    method="POST",
    segments=["listings", "classified", ":slug", "inquiries"],
    path_vars=[path_variable("slug", "{{classified_slug}}", "The classified listing's listing_number (e.g. CL-9Z1X4RTY) — must be active in the resolved country")],
    description=(
        "Sends a buyer inquiry about any active classified listing (own or someone else's — no "
        "self-inquiry guard exists in code).\n\n"
        "### Body params\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `message` | string | required | max:2000 | Inquiry text |\n"
        "| `contact_phone` | string | nullable | max:30 | Falls back to the customer's profile phone if omitted |\n"
        "| `marketer_id` | string(uuid) | nullable | uuid, exists:marketers,id | Attribution for marketer-driven traffic |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"message": "Is this still available? Can we negotiate price?", "contact_phone": "+201123456789", "marketer_id": None}),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('inquiry_id', data.id);",
        "    console.log('Saved inquiry_id =', data.id);",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Inquiry submitted.", "data": {
            "id": "inq-uuid-0000-4000-8000-000000000023", "listing_slug": "CL-9Z1X4RTY", "status": "new",
            "created_at": "2026-07-06T12:30:00+00:00"}}),
        example("404 Not Found — listing not found or no longer active", "Not Found", 404, req,
                body={"message": "Listing not found or no longer active."}),
        formrequest_422(req, "message", "The message field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

create_travel_booking_req = make_request(
    name="Create Travel Booking",
    method="POST",
    segments=["listings", "travel", ":slug", "bookings"],
    path_vars=[path_variable("slug", "{{travel_package_slug}}", "The travel package's UUID — must be active/not expired")],
    content_type=None,
    description=(
        "Books a travel package (multipart — optional passport upload). Booking always starts as "
        "`pending_documents`; `total_price_cents = package.price_cents * travelers_count`.\n\n"
        "### Body params (multipart/form-data)\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `travelers_count` | integer | required | min:1, max:50 | Number of travelers |\n"
        "| `passport_file` | file | nullable | mimes: pdf,jpg,jpeg,png, max:10240 KB | Stored on a **private** disk |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=form_body([
        {"key": "travelers_count", "value": "2", "type": "text", "description": "1-50 travelers"},
        {"key": "passport_file", "type": "file", "src": [], "description": "Optional: pdf/jpg/jpeg/png, max 10MB"},
    ]),
    tests=[
        "if (pm.response.code === 201) {",
        "    const data = pm.response.json().data;",
        "    pm.collectionVariables.set('travel_booking_id', data.id);",
        "    pm.collectionVariables.set('travel_booking_number', data.booking_number);",
        "    console.log('Saved travel_booking_id / travel_booking_number');",
        "}",
    ],
    examples_fn=lambda req: [
        example("201 Created", "Created", 201, req, body={"success": True, "message": "Booking submitted.", "data": {
            "id": "book-uuid-0000-4000-8000-000000000020", "booking_number": "TRV-3F8K2LMN", "status": "pending_documents",
            "travelers_count": 2, "total_price_cents": 300000, "currency": "EGP", "created_at": "2026-07-06T12:35:00+00:00",
            "message": "Your booking is pending document review by the agency before confirmation."}}),
        example("404 Not Found — package not found, expired, or inactive", "Not Found", 404, req,
                body={"message": "Travel package not found, expired, or no longer active."}),
        formrequest_422(req, "travelers_count", "The travelers count must be at least 1."),
        std_401(req),
        std_404_country(req),
    ],
)

sign_contract_req = make_request(
    name="Sign Travel Booking Contract",
    method="POST",
    segments=["listings", "travel", ":slug", "bookings", ":booking_number", "contract"],
    path_vars=[
        path_variable("slug", "{{travel_package_slug}}", "Travel package UUID/slug (existence-only check; expired packages are intentionally allowed since signing can happen post-departure)"),
        path_variable("booking_number", "{{travel_booking_number}}", "Booking number, e.g. TRV-3F8K2LMN (not the UUID id)"),
    ],
    description=(
        "Signs the booking's contract. Only allowed while `status` is `pending_documents` or "
        "`confirmed`. Signing does **not** change the booking's `status`.\n\n"
        "### Body params\n| Field | Type | Required | Constraints | Description |\n|---|---|---|---|---|\n"
        "| `signature_data` | string | required | — | Signature payload (e.g. base64-encoded image data) |\n"
        + AUTH_401_NOTE + COUNTRY_NOTE
    ),
    body=json_body({"signature_data": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB..."}),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": "Contract signed successfully.", "data": {
            "id": "book-uuid-0000-4000-8000-000000000020", "booking_number": "TRV-3F8K2LMN",
            "contract_signed_at": "2026-07-06T12:40:00+00:00", "status": "pending_documents"}}),
        example("404 Not Found — travel package not found", "Not Found", 404, req,
                body={"message": "Travel package not found."}),
        example("404 Not Found — booking not found / not owned", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\TravelBooking]."}),
        example("422 Unprocessable Entity — booking not in a signable state", "Unprocessable Entity", 422, req,
                body={"message": "The given data was invalid.", "errors": {"booking_number": ["Contract cannot be signed in the current booking state."]}}),
        formrequest_422(req, "signature_data", "The signature data field is required."),
        std_401(req),
        std_404_country(req),
    ],
)

account_folder = folder(
    "Account (Classifieds, Travel & Dashboard)",
    "Dashboard summary, the customer's own classified listings (as seller), travel bookings, and "
    "buyer-side inquiries/bookings on other sellers' listings. Create requests auto-save "
    "`listing_number` / `travel_booking_id` / `travel_booking_number` / `inquiry_id`.",
    [dashboard_req, list_classified_listings_req, create_classified_listing_req,
     show_classified_listing_req, update_classified_listing_req, delete_classified_listing_req,
     listing_inquiries_req, list_travel_bookings_req, show_travel_booking_req,
     cancel_travel_booking_req, list_my_inquiries_req, show_my_inquiry_req,
     send_classified_inquiry_req, create_travel_booking_req, sign_contract_req],
)

print("account folder built:", len(account_folder["item"]))

# ---------------------------------------------------------------------------
# CATALOG / BROWSE / SEARCH / PUBLIC folder
# ---------------------------------------------------------------------------

CARD_SHAPE = {
    "listing_id": "9f3a1b2c-0000-4000-8000-000000000003", "listing_ref": "SKU-ABC123--9f3a1b2c",
    "sku": "SKU-ABC123", "vendor_sku": "V-100", "product_id": "p1a2b3c4-0000-4000-8000-000000000004",
    "product_slug": "wireless-mouse", "slug": "wireless-mouse", "name_en": "Wireless Mouse",
    "name_ar": "فأرة لاسلكية", "thumbnail": "https://cdn.example.com/img.jpg", "price_cents": 4999,
    "price_formatted": "49.99", "currency": "EGP", "condition": "new", "is_admin_listing": False,
    "is_express_fbn": False, "fulfillment_model": "fbm",
    "vendor": {"id": "v1a2b3c4-0000-4000-8000-000000000005", "store_name": "TechStore", "rating": 4.5},
    "shipping_badge": {"label_en": "Fast", "label_ar": "سريع", "color_hex": "#000000",
                        "text_color_hex": "#FFFFFF", "delivery_days_min": 1, "delivery_days_max": 2},
    "rating_avg": 4.2, "rating_count": 18, "total_sold": 230, "is_wishlisted": False, "is_sponsored": False,
}

home_req = make_request(
    name="Home",
    method="GET",
    segments=["home"],
    auth=NOAUTH,
    description=(
        "Composite home-page payload: nav, page-builder blocks, and content sections (banner "
        "carousel, flash sale if one is active, featured categories, product carousels). If an "
        "`Authorization` bearer is supplied it's read opportunistically (flips `meta` audience) "
        "but is never required."
        + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "nav": [], "page_builder": {},
            "sections": [
                {"section_type": "banner_carousel", "items": []},
                {"section_type": "flash_sale", "title": "Flash Deals", "ends_at": "2026-07-07T00:00:00+00:00", "items": [CARD_SHAPE]},
                {"section_type": "featured_categories", "title": "Shop by Category", "items": [
                    {"id": "cat-uuid-0000-4000-8000-000000000018", "name_en": "Electronics", "name_ar": "إلكترونيات",
                     "slug": "electronics", "icon": None, "image_url": None}]},
                {"section_type": "product_carousel", "title": "Top Picks for You", "items": [CARD_SHAPE]},
            ],
            "meta": {"country_code": "eg", "currency": "EGP", "locale": "en"},
        }}),
        std_404_country(req),
    ],
)

nav_req = make_request(
    name="Navigation Tree",
    method="GET",
    segments=["nav"],
    auth=NOAUTH,
    description="Unified navigation tree merging product categories, classified categories, and travel categories (cached 10 min per country)." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {"nav": [
            {"section": "products", "label_en": "Shop", "label_ar": "تسوق", "nodes": [
                {"id": "cat-uuid-0000-4000-8000-000000000018", "source_type": "product", "name_en": "Electronics",
                 "name_ar": "إلكترونيات", "slug": "electronics", "icon": None, "is_featured": True, "depth": 0,
                 "sort_order": 1, "link": "/browse/product/cat-uuid-0000-4000-8000-000000000018", "children": []}]},
            {"section": "classifieds", "label_en": "Classifieds", "label_ar": "إعلانات مبوبة", "nodes": [
                {"id": "clscat-uuid-0000-4000-8000-000000000025", "source_type": "classified", "name_en": "Cars",
                 "name_ar": "سيارات", "slug": "cars", "icon": "car-icon", "link": "/browse/classified/clscat-uuid-0000-4000-8000-000000000025", "children": []}]},
            {"section": "travel", "label_en": "Travel", "label_ar": "سفر", "link": "/travel", "nodes": [
                {"travel_category_slug": "beach", "name_en": "Beach", "name_ar": "شاطئ", "link": "/travel"}]},
        ]}}),
        std_404_country(req),
    ],
)

list_products_req = make_request(
    name="List Products",
    method="GET",
    segments=["products"],
    auth=NOAUTH,
    query=[
        query_param("category", "{{category_id}}", "Product category UUID", disabled=True),
        query_param("brand", "{{brand_id}}", "Brand UUID", disabled=True),
        query_param("price_min", "10", "Minimum price, major currency units (e.g. EGP, not cents)", disabled=True),
        query_param("price_max", "500", "Maximum price, major currency units", disabled=True),
        query_param("rating_min", "4", "Minimum average rating, between 1 and 5", disabled=True),
        query_param("condition", "new", "new|like_new|good|acceptable|refurbished", disabled=True),
        query_param("fulfillment_model", "fbm", "fbm|fbn|cross_dock", disabled=True),
        query_param("sort", "relevance", "relevance(default)|price_asc|price_desc|rating|newest|best_selling", disabled=True),
        query_param("include_oos", "false", "Include out-of-stock listings (default false)", disabled=True),
        query_param("page", "1", "Page number, default 1", disabled=True),
        query_param("per_page", "20", "Items per page, 1-100, default 20", disabled=True),
    ],
    description="Filterable/sortable product listing grid, scoped to active listings/products/vendors in the resolved country." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "items": [CARD_SHAPE], "facets": {"price_range": {"min": 9.99, "max": 499.99}},
            "meta": {"current_page": 1, "last_page": 5, "per_page": 20, "total": 92}}}),
        formrequest_422(req, "condition", "The selected condition is invalid."),
        std_404_country(req),
    ],
)

listing_detail_req = make_request(
    name="Get Listing Detail",
    method="GET",
    segments=["l", ":identifier"],
    path_vars=[path_variable("identifier", "wireless-mouse", "Listing UUID, variant SKU, vendor SKU, product slug, or listing_ref (sku--shortId)")],
    query=[query_param("source", "direct", "View-attribution tag, e.g. 'search', 'home'; logged not user-facing", disabled=True)],
    auth=NOAUTH,
    description=(
        "> 🐞 **Known bug, verified against the current code (`ListingDetailController::resolveListing`, "
        "app/Http/Controllers/Customer/ListingDetailController.php:79-81): this helper references "
        "`$request` without it being in scope, causing an uncaught `Error` — this endpoint currently "
        "always returns HTTP 500 regardless of input.** Documented as-is (actual deployed behavior); "
        "the 200/404 examples below show the *intended* response once the bug is fixed.\n\n"
        "Identifier auto-detection: UUID pattern → listing id; long hyphenated slug-like string → "
        "product slug; else checked against variant SKU, then vendor SKU; strings containing `--` "
        "are parsed as a `listing_ref` (`{sku}--{shortListingIdPrefix}`)."
        + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("500 Internal Server Error — actual current behavior (known bug)", "Internal Server Error", 500, req,
                body={"message": "Server Error"}),
        example("200 OK — intended behavior once fixed", "OK", 200, req, body={"success": True, "data": {
            "listing": {"listing_id": "9f3a1b2c-0000-4000-8000-000000000003", "listing_ref": "SKU-ABC123--9f3a1b2c",
                        "vendor_sku": "V-100", "sku": "SKU-ABC123", "price_cents": 4999, "price_formatted": "49.99",
                        "currency": "EGP", "condition": "new", "condition_notes": None, "is_admin_listing": False,
                        "is_express_fbn": False, "fulfillment_model": "fbm", "global_system_type": "marketplace",
                        "status": "active", "max_order_quantity": 5, "total_sold": 230, "rating_avg": 4.2,
                        "rating_count": 18, "is_global_shipping": True, "is_wishlisted": False},
            "seller": {"id": "v1a2b3c4-0000-4000-8000-000000000005", "store_name": "TechStore", "rating_avg": 4.5,
                       "rating_count": 120, "is_admin_listing": False},
            "delivery_options": [{"method_code": "standard", "method_name": "Standard", "delivery_days_min": 3, "delivery_days_max": 7}],
            "product": {"id": "p1a2b3c4-0000-4000-8000-000000000004", "slug": "wireless-mouse", "name_en": "Wireless Mouse",
                        "name_ar": "فأرة لاسلكية", "brand": {"id": "brand-uuid", "name_en": "Logitech", "slug": "logitech", "is_verified": True},
                        "category": {"id": "cat-uuid-0000-4000-8000-000000000018", "name_en": "Electronics", "slug": "electronics"},
                        "images": [{"url": "https://cdn.example.com/img.jpg", "is_primary": True}]},
            "variant": {"id": "var-uuid", "sku": "SKU-ABC123", "variant_name": "Black", "is_default": True,
                        "attributes": [{"attribute_name": "Color", "value": "Black"}]},
            "other_sellers": [], "other_variants": [],
            "reviews": {"rating_avg": 4.2, "rating_count": 18, "items": [REVIEW_SHAPE]},
        }}),
        example("404 Not Found — intended behavior once fixed", "Not Found", 404, req,
                body={"success": False, "message": "Listing not found or not available in this country."}),
        std_404_country(req),
    ],
)

legacy_product_detail_req = make_request(
    name="Get Product Detail (legacy alias)",
    method="GET",
    segments=["products", ":identifier"],
    path_vars=[path_variable("identifier", "wireless-mouse", "Same identifier rules as Get Listing Detail — this is a legacy URL alias to the same controller action")],
    auth=NOAUTH,
    description=(
        "Legacy alias — routes to the exact same `ListingDetailController::show` action as "
        "`GET /l/{identifier}`, so it currently shares the **same 500 bug** described there. Kept "
        "in the collection only for backward-compatibility testing of old links."
        + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("500 Internal Server Error — actual current behavior (known bug, shared with GET /l/{identifier})", "Internal Server Error", 500, req,
                body={"message": "Server Error"}),
        std_404_country(req),
    ],
)

browse_product_req = make_request(
    name="Browse — Product Category",
    method="GET",
    segments=["browse", "product", ":id"],
    path_vars=[path_variable("id", "{{category_id}}", "Product category UUID")],
    query=[
        query_param("per_page", "20", "Items per page, default 20", disabled=True),
        query_param("price_min", "10", "Minimum price, major units", disabled=True),
        query_param("price_max", "500", "Maximum price, major units", disabled=True),
        query_param("brand", "{{brand_id}}", "Brand UUID", disabled=True),
        query_param("rating_min", "4", "Minimum rating 1-5", disabled=True),
        query_param("condition", "new", "new|like_new|good|acceptable|refurbished", disabled=True),
        query_param("fulfillment_model", "fbm", "fbm|fbn|cross_dock", disabled=True),
        query_param("include_oos", "false", "Include out-of-stock (default false)", disabled=True),
        query_param("sort", "relevance", "relevance|price_asc|price_desc|rating|newest|best_selling", disabled=True),
        query_param("attributes", "", "Attribute filters, e.g. attributes[color][]=red (not FormRequest-validated — bad values simply match nothing, not a 422)", disabled=True),
    ],
    auth=NOAUTH,
    description="Category-scoped product browse grid, with category breadcrumb/children and an optional page-builder block." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "category": {"id": "{{category_id}}", "name_en": "Electronics", "name_ar": "إلكترونيات", "slug": "electronics",
                        "description_en": "", "description_ar": "", "parent": None,
                        "children": [{"id": "subcat-uuid", "name_en": "Phones", "name_ar": "هواتف", "slug": "phones", "icon": "phone-icon"}]},
            "category_node": {"id": "{{category_id}}", "source_type": "product", "name_en": "Electronics", "link": "/browse/product/{{category_id}}"},
            "page_builder": {},
            "listings": {"items": [CARD_SHAPE], "meta": {"current_page": 1, "last_page": 3, "per_page": 20, "total": 55},
                         "facets": {"price_range": {"min": 9.99, "max": 999.99}}},
        }}),
        example("404 Not Found — category doesn't exist / inactive", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Category]."}),
        std_404_country(req),
    ],
)

browse_classified_req = make_request(
    name="Browse — Classified Category",
    method="GET",
    segments=["browse", "classified", ":id"],
    path_vars=[path_variable("id", "{{classified_category_id}}", "Classified category UUID")],
    query=[query_param("per_page", "20", "Items per page, default 20", disabled=True)],
    auth=NOAUTH,
    description="Category-scoped classified listing browse grid." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "category": {"id": "{{classified_category_id}}", "source_type": "classified", "name_en": "Cars",
                        "name_ar": "سيارات", "slug": "cars", "icon": "car-icon", "link": "/browse/classified/{{classified_category_id}}"},
            "page_builder": {},
            "listings": {"items": [{"listing_id": "b1a2c3d4-0000-4000-8000-000000000017", "listing_number": "CL-1001",
                                    "source_type": "classified", "title_en": "2019 Toyota Corolla", "title_ar": "تويوتا كورولا 2019",
                                    "slug": "CL-1001", "thumbnail": "https://cdn.example.com/car.jpg", "price_cents": 1500000,
                                    "price_formatted": "15000.00", "currency": "EGP", "price_negotiable": True,
                                    "listing_purpose": "sale", "location": "Cairo", "seller_type": "customer",
                                    "created_at": "2026-06-01T00:00:00+00:00"}],
                        "meta": {"current_page": 1, "last_page": 2, "per_page": 20, "total": 30}},
        }}),
        example("404 Not Found — category doesn't exist / inactive", "Not Found", 404, req,
                body={"success": False, "message": "Category not found."}),
        std_404_country(req),
    ],
)

TRAVEL_CARD_SHAPE = {
    "package_id": "pkg-uuid-0000-4000-8000-000000000021", "source_type": "travel", "title_en": "Hurghada Escape",
    "title_ar": "هروب الغردقة", "slug": "pkg-uuid-0000-4000-8000-000000000021",
    "thumbnail": "https://cdn.example.com/hurghada.jpg", "destination_country": "Egypt", "destination_city": "Hurghada",
    "departure_date": "2026-08-01", "return_date": "2026-08-07", "duration_days": 6, "duration_nights": 5,
    "price_cents": 899900, "price_formatted": "8999.00", "currency": "EGP", "available_seats": 40,
    "seats_remaining": 12, "agency_name": "Sunshine Travel", "categories": [{"name_en": "Beach", "slug": "beach"}],
    "link": "/travel",
}

browse_travel_req = make_request(
    name="Browse — Travel Category",
    method="GET",
    segments=["browse", "travel", ":id"],
    path_vars=[path_variable("id", "{{travel_category_id}}", "Travel category UUID, or the literal string 'all' for every active package")],
    query=[query_param("per_page", "20", "Items per page, default 20", disabled=True)],
    auth=NOAUTH,
    description=(
        "Category-scoped travel package browse grid.\n\n"
        "> ⚠️ Note: `listings.items[].slug` here is actually the package's **UUID `id`**, not its "
        "real `slug` column — do not feed this value into `GET listings/travel/{slug}` (see that "
        "request's notes)."
        + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "category": {"id": "{{travel_category_id}}", "source_type": "travel", "name_en": "Beach", "name_ar": "شاطئ",
                        "slug": "beach", "link": "/travel", "travel_category_slug": "beach"},
            "available_categories": [{"id": "{{travel_category_id}}", "name_en": "Beach", "name_ar": "شاطئ",
                                      "slug": "beach", "icon": "beach-icon", "package_count": 12}],
            "page_builder": None,
            "listings": {"items": [TRAVEL_CARD_SHAPE], "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 5}},
        }}),
        example("404 Not Found — travel category doesn't exist / inactive", "Not Found", 404, req,
                body={"success": False, "message": "Category not found."}),
        std_404_country(req),
    ],
)

travel_index_req = make_request(
    name="Travel — All Packages",
    method="GET",
    segments=["travel"],
    query=[query_param("per_page", "20", "Items per page, default 20", disabled=True)],
    auth=NOAUTH,
    description="Equivalent to `GET browse/travel/all` — every active travel package, unfiltered by category. Always 200 (never 404) unless the country itself is invalid." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "category": {"id": None, "source_type": "travel", "name_en": "All Travel", "name_ar": None, "travel_category_slug": None},
            "available_categories": [{"id": "{{travel_category_id}}", "name_en": "Beach", "name_ar": "شاطئ", "slug": "beach", "icon": "beach-icon", "package_count": 12}],
            "page_builder": None,
            "listings": {"items": [TRAVEL_CARD_SHAPE], "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 5}},
        }}),
        std_404_country(req),
    ],
)

listings_product_req = make_request(
    name="Get Listing — Product Detail",
    method="GET",
    segments=["listings", "product", ":slug"],
    path_vars=[path_variable("slug", "wireless-mouse", "Product slug")],
    query=[query_param("source", "direct", "View-attribution tag, logged not user-facing", disabled=True)],
    auth=NOAUTH,
    description="Unified listing-detail endpoint for a product (all sellers/variants/reviews for that product)." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": None, "data": {
            "id": "p1a2b3c4-0000-4000-8000-000000000004", "slug": "wireless-mouse", "name": "Wireless Mouse",
            "description": "A great wireless mouse.", "short_description": "Great mouse", "model_number": "WM-100",
            "gtin": "012345678905", "is_age_restricted": False, "min_age": None, "is_hazardous": False,
            "has_variants": True, "rating_avg": 4.2, "rating_count": 18, "total_sold": 230,
            "brand": {"id": "brand-uuid", "name": "Logitech", "slug": "logitech"},
            "category": {"id": "cat-uuid-0000-4000-8000-000000000018", "name": "Electronics", "slug": "electronics"},
            "images": [{"id": "img-uuid", "url": "https://cdn.example.com/img.jpg", "alt": "Wireless Mouse",
                        "is_primary": True, "position": 0, "variant_id": None}],
            "variants": [{"id": "var-uuid", "sku": "SKU-ABC123", "variant_name": "Black", "is_default": True,
                         "position": 0, "attributes": [{"attribute_id": "attr-uuid", "attribute_code": "color",
                         "attribute_name": "Color", "value_id": "val-uuid", "value": "Black", "color_hex": "#000000"}]}],
            "sellers": [{"id": "v1a2b3c4-0000-4000-8000-000000000005", "seller_name": "TechStore", "seller_slug": "techstore",
                        "price": 49.99, "currency": "EGP", "condition": "new", "condition_notes": None,
                        "fulfillment_model": "fbm", "delivery_estimate": "3-7 days", "is_in_stock": True,
                        "stock_level": "high", "max_order_quantity": 5, "is_buy_box_winner": True}],
            "reviews": [REVIEW_SHAPE], "related": [CARD_SHAPE], "is_wishlisted": False,
            "seo": {"title": "Wireless Mouse | TechStore", "description": "Buy Wireless Mouse online."},
        }}),
        example("404 Not Found — product slug not found/inactive/unavailable in country", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Product]."}),
        std_404_country(req),
    ],
)

listings_classified_req = make_request(
    name="Get Listing — Classified Detail",
    method="GET",
    segments=["listings", "classified", ":slug"],
    path_vars=[path_variable("slug", "cl-1001-toyota-corolla", "The classified listing's `slug` column (commonly, but not always, same as listing_number)")],
    auth=NOAUTH,
    description="Unified listing-detail endpoint for a single classified (buyer-facing, non-owner) listing." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "listing_number": "CL-1001", "slug": "cl-1001-toyota-corolla", "title_en": "2019 Toyota Corolla",
            "title_ar": "تويوتا كورولا 2019", "description_en": "Well maintained.", "description_ar": None,
            "listing_purpose": "sale", "price_cents": 1500000, "currency": "EGP", "price_negotiable": True,
            "attributes": {"mileage_km": 45000, "year": 2019}, "location": {"city": "Cairo"},
            "category": {"id": "clscat-uuid-0000-4000-8000-000000000025", "name_en": "Cars", "name_ar": "سيارات"},
            "images": [{"id": "img-uuid", "url": "https://cdn.example.com/car1.jpg", "position": 0}],
            "seller": {"type": "individual", "display_name": "John D.", "store_url": None},
            "views_count": 124, "expires_at": "2026-08-10", "created_at": "2026-06-01T00:00:00+00:00",
        }}),
        example("404 Not Found — not found / no longer active", "Not Found", 404, req,
                body={"message": "Listing not found or no longer active."}),
        std_404_country(req),
    ],
)

listings_travel_req = make_request(
    name="Get Listing — Travel Package Detail",
    method="GET",
    segments=["listings", "travel", ":slug"],
    path_vars=[path_variable("slug", "hurghada-escape-2026", "The travel package's actual `slug` column (NOT its UUID, despite the route's inline code comment) — must have departure_date in the future")],
    auth=NOAUTH,
    description="Unified listing-detail endpoint for a travel package." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "id": "pkg-uuid-0000-4000-8000-000000000021", "slug": "hurghada-escape-2026", "title_en": "Hurghada Escape",
            "title_ar": "هروب الغردقة", "description_en": "6 days, 5 nights.", "description_ar": None,
            "destination_country": "Egypt", "destination_city": "Hurghada", "price_cents": 899900, "currency": "EGP",
            "duration_days": 6, "duration_nights": 5, "departure_date": "2026-08-01", "return_date": "2026-08-07",
            "inclusions": ["Flights", "Hotel"], "images": [{"id": "img-uuid", "url": "https://cdn.example.com/hurghada.jpg", "position": 0}],
            "categories": [{"id": "travcat-uuid", "name": "Beach", "slug": "beach"}],
            "agency": {"id": "agency-uuid-0000-4000-8000-000000000022", "name": "Sunshine Travel",
                      "logo_url": "https://cdn.example.com/logo.png", "license_number": "TA-1234"},
            "status": "active",
        }}),
        example("404 Not Found — not found / expired / inactive", "Not Found", 404, req,
                body={"message": "Travel package not found, expired, or no longer active."}),
        std_404_country(req),
    ],
)

categories_legacy_redirect_req = make_request(
    name="Category Legacy Redirect",
    method="GET",
    segments=["categories", ":slug"],
    path_vars=[path_variable("slug", "electronics", "Product category slug")],
    auth=NOAUTH,
    description=(
        "Legacy URL — 301-redirects to `GET browse/product/{category_id}`. Not a controller "
        "action, just an inline route closure. In Postman, disable \"Automatically follow "
        "redirects\" on this request if you want to assert the 301 itself rather than the final page."
        + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("301 Moved Permanently — redirects to /browse/product/{id}", "Moved Permanently", 301, req,
                body=None, raw_body="", headers=[{"key": "Location", "value": "{{base_url}}/api/customer/v1/{{country}}/browse/product/cat-uuid-0000-4000-8000-000000000018"}]),
        example("404 Not Found — category slug not found/inactive", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Category]."}),
        std_404_country(req),
    ],
)

categories_index_req = make_request(
    name="Category Tree",
    method="GET",
    segments=["categories"],
    auth=NOAUTH,
    description="Merged flat tree of product/classified/travel root categories with children one level deep (cached 10 min per country)." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": [
            {"id": "cat-uuid-0000-4000-8000-000000000018", "type": "product", "name": "Electronics", "slug": "electronics",
             "parent_id": None, "image_url": None, "product_count": 120,
             "children": [{"id": "subcat-uuid", "type": "product", "name": "Phones", "slug": "phones", "parent_id": "cat-uuid-0000-4000-8000-000000000018", "image_url": None, "product_count": 40, "children": []}]},
            {"id": "clscat-uuid-0000-4000-8000-000000000025", "type": "classified", "name": "Cars", "slug": "cars", "parent_id": None, "icon": "car-icon", "children": []},
            {"id": "travcat-uuid", "type": "travel", "name": "Beach", "slug": "beach", "parent_id": None, "icon": "beach-icon", "children": []},
        ]}),
        std_404_country(req),
    ],
)

pages_req = make_request(
    name="Get Page Builder Content",
    method="GET",
    segments=["pages", ":type"],
    path_vars=[path_variable("type", "home", "Page type, matched against pages.page_type, e.g. home, about-us, category, faq")],
    query=[query_param("slug", "faq", "Optional: further filters to a specific page.slug within this type; if omitted, matches the type's default page", disabled=True)],
    auth=NOAUTH,
    description=(
        "Renders a CMS page-builder page (sections of blocks: `hero_slider`, `ad_grid`, "
        "`product_rail`, `seller_rail`, `category_tiles`, `paid_banner`). Cached 5 min per "
        "(country, type, slug, A/B-variant) combination. Reads `X-Session-Id` header (or a "
        "`session_id` cookie) for A/B test bucketing."
        + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "page": {"type": "faq", "slug": "faq", "version": 3},
            "sections": [{"id": "sec-uuid", "position": 0, "blocks": [{"id": "blk-uuid", "type": "hero_slider", "position": 0,
                "data": {"config": {}, "slides": [{"id": "slide-uuid", "position": 0, "desktop_url": "https://cdn.example.com/hero.jpg",
                    "mobile_url": "https://cdn.example.com/hero-m.jpg", "title_en": "Big Sale", "title_ar": "تخفيضات كبرى",
                    "subtitle_en": "Up to 50% off", "subtitle_ar": "خصم يصل الى 50%", "cta_label_en": "Shop Now",
                    "cta_label_ar": "تسوق الآن", "cta_url": "/browse/product/cat-uuid", "cta_open_new_tab": False,
                    "text_color": "#ffffff", "text_position": "center", "overlay_opacity": 0.3,
                    "link_type": "category", "link_reference_id": "cat-uuid-0000-4000-8000-000000000018"}]}}]}],
        }}),
        example("404 Not Found — no published page for this type/slug/country/window", "Not Found", 404, req,
                body={"success": False, "message": "Page not found."}),
        std_404_country(req),
    ],
)

vendor_page_req = make_request(
    name="Vendor Storefront Page",
    method="GET",
    segments=["vendors", ":vendor_id"],
    path_vars=[path_variable("vendor_id", "{{vendor_id}}", "Vendor UUID")],
    query=[query_param("per_page", "20", "Items per page, default 20", disabled=True)],
    auth=NOAUTH,
    description="Vendor storefront: profile summary plus that vendor's active listings in the resolved country, ordered by fulfillment priority then price." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "vendor": {"id": "{{vendor_id}}", "store_name": "TechStore", "store_rating_avg": 4.5, "store_rating_count": 300, "country": "Egypt"},
            "page_builder": {},
            "listings": {"items": [CARD_SHAPE], "meta": {"current_page": 1, "last_page": 4, "per_page": 20, "total": 75}},
        }}),
        example("404 Not Found — vendor not found / not active", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Vendor]."}),
        std_404_country(req),
    ],
)

brand_page_req = make_request(
    name="Brand Page",
    method="GET",
    segments=["brands", ":id"],
    path_vars=[path_variable("id", "{{brand_id}}", "Brand UUID")],
    query=[query_param("per_page", "20", "Items per page, default 20", disabled=True)],
    auth=NOAUTH,
    description="Brand landing page: brand profile plus its active products' listings in the resolved country." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "data": {
            "brand": {"id": "{{brand_id}}", "name_en": "Logitech", "name_ar": "لوجيتك", "slug": "logitech",
                     "logo_url": "https://cdn.example.com/logo.png", "description_en": "Peripherals maker.",
                     "description_ar": None, "is_verified": True},
            "page_builder": {},
            "listings": {"items": [CARD_SHAPE], "meta": {"current_page": 1, "last_page": 2, "per_page": 20, "total": 33}},
        }}),
        example("404 Not Found — brand not found / not active", "Not Found", 404, req,
                body={"message": "No query results for model [App\\Models\\Brand]."}),
        std_404_country(req),
    ],
)

search_req = make_request(
    name="Search",
    method="GET",
    segments=["search"],
    auth=NOAUTH,
    query=[
        query_param("q", "wireless mouse", "Required. Search text, 1-255 chars"),
        query_param("source_type", "product", "product(default)|classified|travel|all", disabled=True),
        query_param("category", "{{category_id}}", "Category UUID filter", disabled=True),
        query_param("brand", "{{brand_id}}", "Brand UUID filter", disabled=True),
        query_param("price_min", "10", "Minimum price, major units", disabled=True),
        query_param("price_max", "500", "Maximum price, major units", disabled=True),
        query_param("rating_min", "4", "Minimum rating 1-5", disabled=True),
        query_param("condition", "new", "new|like_new|good|acceptable|refurbished", disabled=True),
        query_param("fulfillment_model", "fbm", "fbm|fbn|cross_dock", disabled=True),
        query_param("sort", "relevance", "relevance|price_asc|price_desc|rating|newest|best_selling", disabled=True),
        query_param("include_oos", "false", "Include out-of-stock (default false)", disabled=True),
        query_param("page", "1", "Page number, default 1", disabled=True),
        query_param("per_page", "20", "Items per page, 1-100, default 20", disabled=True),
    ],
    description=(
        "Full-text search across products (default), or classifieds/travel/all via `source_type`. "
        "Fires an async, non-blocking search-log job. Omitting `source_type` preserves legacy "
        "product-only behavior; `all` fans out to all 3 domains (max 4 items each) plus a "
        "combined `total_results`."
        + COUNTRY_NOTE
    ),
    examples_fn=lambda req: [
        example("200 OK — source_type=product (default)", "OK", 200, req, body={"success": True, "data": {
            "items": [CARD_SHAPE], "facets": {"price_range": {"min": 9.99, "max": 499.99}},
            "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 1}}}),
        example("200 OK — source_type=all", "OK", 200, req, body={"success": True, "data": {
            "source_type": "all",
            "products": {"items": [CARD_SHAPE], "total": 18},
            "classifieds": {"items": [], "total": 3},
            "travel": {"items": [], "total": 2},
            "meta": {"query": "wireless mouse", "total_results": 23}}}),
        formrequest_422(req, "q", "The q field is required."),
        std_404_country(req),
    ],
)

search_suggestions_req = make_request(
    name="Search Suggestions",
    method="GET",
    segments=["search", "suggestions"],
    auth=NOAUTH,
    query=[query_param("q", "wirel", "Required. Partial search text, min 1 char, max 255")],
    description="Typeahead suggestions: matching query strings, up to 10 raw product matches, up to 7 categories (3 product + 2 classified + 2 travel), up to 3 vendors." + COUNTRY_NOTE,
    examples_fn=lambda req: [
        example("200 OK", "OK", 200, req, body={"success": True, "message": None, "data": {
            "queries": ["Wireless Mouse", "Wireless Keyboard"],
            "products": [{"id": "9f3a1b2c-0000-4000-8000-000000000003", "product_id": "p1a2b3c4-0000-4000-8000-000000000004",
                         "slug": "wireless-mouse", "name": "Wireless Mouse", "vendor": "TechStore", "type": "product"}],
            "categories": [{"id": "cat-uuid-0000-4000-8000-000000000018", "source_type": "product", "name_en": "Electronics",
                           "name_ar": "إلكترونيات", "slug": "electronics", "link": "/browse/product/cat-uuid-0000-4000-8000-000000000018"}],
            "vendors": [{"id": "v1a2b3c4-0000-4000-8000-000000000005", "store_name": "TechStore", "slug": "techstore", "rating": 4.5}],
        }}),
        formrequest_422(req, "q", "The q field is required."),
        std_404_country(req),
    ],
)

catalog_folder = folder(
    "Catalog, Browse & Search (Public)",
    "Public, unauthenticated discovery surface: home, navigation, product/classified/travel "
    "listing grids, category/vendor/brand pages, CMS page-builder content, and search. None of "
    "these require `Authorization`, though a token is read opportunistically on Home to "
    "personalize the audience flag.",
    [home_req, nav_req, list_products_req, listing_detail_req, legacy_product_detail_req,
     browse_product_req, browse_classified_req, browse_travel_req, travel_index_req,
     listings_product_req, listings_classified_req, listings_travel_req,
     categories_legacy_redirect_req, categories_index_req, pages_req, vendor_page_req,
     brand_page_req, search_req, search_suggestions_req],
)

print("catalog folder built:", len(catalog_folder["item"]))

# ---------------------------------------------------------------------------
# FINAL ASSEMBLY
# ---------------------------------------------------------------------------

collection = {
    "info": {
        "name": "Marketplace — Customer API",
        "_postman_id": "b8e2f1a0-4c3d-4e5f-9a1b-2c3d4e5f6a7b",
        "description": (
            "# Marketplace Customer API\n\n"
            "Covers every endpoint under `routes/api_customer.php` — auth, profile, addresses, "
            "wishlist, cart & checkout, orders/returns/disputes/reviews/refunds, support tickets, "
            "the account area (classified listings, travel bookings, inquiries), and the public "
            "catalog/browse/search surface.\n\n"
            "## Getting started\n"
            "1. Set `base_url` (collection variable) to your local/staging API root, e.g. `http://localhost:8000`.\n"
            "2. Set `country` to a valid, active `site_code` in your database (e.g. `egy`).\n"
            "3. Run **Auth → Register** (or **Login** if you already have an account). A post-response "
            "   *Test* script automatically saves `access_token` / `refresh_token` into collection "
            "   variables — every other request inherits `Authorization: Bearer {{access_token}}` "
            "   from the collection-level auth, so you never have to paste tokens by hand.\n"
            "4. Explore folders top-to-bottom — several \"create\" requests (Add Cart Item, Create "
            "   Address, Place Order, Request Return, Open Dispute, Submit Review, Create Support "
            "   Ticket, Create Classified Listing, Create Travel Booking, Send Classified Inquiry) "
            "   auto-save the IDs they generate into collection variables so downstream requests "
            "   (Update/Get/Cancel/etc.) work with zero manual copy-pasting.\n\n"
            "## Why tokens are saved in a `Test` script, not a `Pre-request` script\n"
            "Pre-request scripts run *before* the request is sent, so they cannot read a response "
            "body that doesn't exist yet. Token capture necessarily happens in the **Tests** tab "
            "(Postman's post-response script), on Register/Login/Refresh Token. This is the "
            "standard, correct place to do it — despite colloquially being called a \"pre-request "
            "step\" in the auth flow sense (i.e., it prepares the token *before your next* request).\n\n"
            "## Response envelope\n"
            "Most endpoints return `App\\Http\\Responses\\ApiResponse`'s envelope: "
            "`{\"success\": true, \"message\": \"...\", \"data\": {...}}` on success, or "
            "`{\"success\": false, \"message\": \"...\"}` (+ `errors` if present) on business-rule "
            "failures. **Laravel's own validation/model-not-found/auth exceptions bypass this "
            "envelope** and return their default shape instead (`{\"message\": \"...\", \"errors\": "
            "{...}}` with no `success` key, or plain `{\"message\": \"Unauthenticated.\"}`). Each "
            "request's saved examples call out which shape applies.\n\n"
            "## Known issues documented in this collection (not opinions — verified against the code)\n"
            "- `GET /l/{identifier}` and its legacy alias `GET /products/{identifier}` currently "
            "  **always return HTTP 500** (a variable-scope bug in `ListingDetailController::resolveListing`).\n"
            "- `PUT/DELETE addresses/{address}` and `PUT addresses/{address}/set-default` do **not** "
            "  enforce address ownership, despite an unused `AddressPolicy` existing.\n"
            "- `PUT cart/items/{id}` has dead exception handling — expect an uncaught 404/500 rather "
            "  than the clean error shape the (commented-out) code implies.\n\n"
            "See each request's description for the full parameter tables, status-code list, and "
            "business-logic notes — everything was verified by reading the actual controllers, "
            "form requests, and API resources, not inferred from routes alone."
        ),
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
    },
    "auth": {
        "type": "bearer",
        "bearer": [{"key": "token", "value": "{{access_token}}", "type": "string"}],
    },
    "event": [
        event("prerequest", [
            "// Collection-level pre-request script.",
            "// Nothing global needed today — public (noauth) requests override auth at the request",
            "// level, and dynamic values (e.g. Place Order's idempotency_key) use Postman's built-in",
            "// {{$guid}} variable directly in the body instead of a script.",
        ]),
    ],
    "variable": [
        {"key": "base_url", "value": "http://localhost:8000", "description": "API root, no trailing slash. Change per environment (local/staging/prod)."},
        {"key": "country", "value": "egy", "description": "Active Country site_code used in every path, e.g. egy, ksa, uae."},
        {"key": "access_token", "value": "", "description": "JWT access token — auto-populated by Register/Login/Refresh Token Test scripts."},
        {"key": "refresh_token", "value": "", "description": "JWT refresh token — auto-populated alongside access_token."},
        {"key": "verification_token", "value": "", "description": "Email verification OTP — copy from the test mailbox/log after Register."},
        {"key": "reset_token", "value": "", "description": "Password reset OTP — copy from the test mailbox/log after Forgot Password."},
        {"key": "address_id", "value": "", "description": "Address PK (integer) — auto-populated by Create Address."},
        {"key": "city_id", "value": "", "description": "City UUID — seed/look up from your database."},
        {"key": "country_id", "value": "", "description": "Country UUID (distinct from the `country` site_code) — seed/look up from your database."},
        {"key": "vendor_listing_id", "value": "", "description": "Vendor listing UUID to add to cart/wishlist — look up from List Products."},
        {"key": "cart_item_id", "value": "", "description": "Cart item UUID — auto-populated by Add Cart Item."},
        {"key": "shipping_method_id", "value": "", "description": "Shipping method UUID — copy from Get Shipping Methods response."},
        {"key": "order_number", "value": "", "description": "Order number — auto-populated by Place Order."},
        {"key": "order_item_id", "value": "", "description": "Order item UUID — copy from Get Order Detail response, needed for Returns/Reviews."},
        {"key": "sub_order_number", "value": "", "description": "Sub-order number (e.g. NOON-...-01) — copy from Get Order Detail response."},
        {"key": "return_number", "value": "", "description": "Return number — auto-populated by Request Return."},
        {"key": "dispute_number", "value": "", "description": "Dispute number — auto-populated by Open Dispute."},
        {"key": "review_id", "value": "", "description": "Review UUID — auto-populated by Submit Review."},
        {"key": "refund_id", "value": "", "description": "Refund UUID — copy from List Refunds response."},
        {"key": "ticket_number", "value": "", "description": "Support ticket number — auto-populated by Create Support Ticket."},
        {"key": "classified_category_id", "value": "", "description": "Classified category UUID — seed/look up from your database."},
        {"key": "listing_number", "value": "", "description": "Own classified listing number — auto-populated by Create Classified Listing."},
        {"key": "classified_slug", "value": "", "description": "Another seller's classified listing_number to inquire about — look up via Browse/Search."},
        {"key": "travel_package_slug", "value": "", "description": "Travel package UUID/slug used when creating a booking or signing a contract — look up via Travel browse/search."},
        {"key": "travel_booking_id", "value": "", "description": "Travel booking UUID (primary key) — auto-populated by Create Travel Booking."},
        {"key": "travel_booking_number", "value": "", "description": "Travel booking number (e.g. TRV-...) — auto-populated by Create Travel Booking."},
        {"key": "inquiry_id", "value": "", "description": "Classified inquiry UUID (as buyer) — auto-populated by Send Classified Inquiry."},
        {"key": "category_id", "value": "", "description": "Product category UUID — look up from Category Tree."},
        {"key": "travel_category_id", "value": "", "description": "Travel category UUID — look up from Category Tree / Navigation Tree."},
        {"key": "brand_id", "value": "", "description": "Brand UUID — look up from search/product responses."},
        {"key": "vendor_id", "value": "", "description": "Vendor UUID — look up from search/product responses."},
    ],
    "item": [
        auth_folder, profile_folder, addresses_folder, wishlist_folder, cart_checkout_folder,
        orders_folder, support_folder, account_folder, catalog_folder,
    ],
}

import os
OUT_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "Marketplace Customer API.postman_collection.json")
with open(OUT_PATH, "w", encoding="utf-8") as f:
    json.dump(collection, f, indent=2, ensure_ascii=False)

total_requests = sum(len(f["item"]) for f in collection["item"])
print(f"Wrote collection to {OUT_PATH}")
print(f"Total folders: {len(collection['item'])}, total requests: {total_requests}")
