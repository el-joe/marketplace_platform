const fs = require('fs');

let idc = 1;
function id() { return 'id-' + (idc++); }

function req({name, method, path, query = [], headers = [], body = null, auth = true, desc = '', responses = [], tests = null}) {
  const url = {
    raw: `{{base_url}}/api/customer/v1/{{country}}${path}${query.length ? '?' + query.map(q => `${q.key}=${q.value ?? ''}`).join('&') : ''}`,
    host: ['{{base_url}}'],
    path: ['api', 'customer', 'v1', `{{country}}`, ...path.replace(/^\//, '').split('/')].filter(Boolean),
    query: query.map(q => ({key: q.key, value: q.value ?? '', description: q.description || '', disabled: !!q.disabled}))
  };
  const hdrs = [...headers];
  if (body && body.mode === 'raw') hdrs.push({key: 'Content-Type', value: 'application/json'});
  const item = {
    name,
    request: {
      method,
      header: hdrs,
      url,
      description: desc,
      ...(auth ? {auth: {type: 'bearer', bearer: [{key: 'token', value: '{{access_token}}', type: 'string'}]}} : {auth: {type: 'noauth'}}),
      ...(body ? {body} : {})
    },
    response: responses.map(r => ({
      name: r.name,
      originalRequest: {method, header: hdrs, url, body: body || undefined},
      status: r.status,
      code: r.code,
      _postman_previewlanguage: 'json',
      header: [{key: 'Content-Type', value: 'application/json'}],
      body: JSON.stringify(r.body, null, 2)
    }))
  };
  if (tests) {
    item.event = [{listen: 'test', script: {type: 'text/javascript', exec: tests.split('\n')}}];
  }
  return item;
}

function raw(obj) {
  return {mode: 'raw', raw: JSON.stringify(obj, null, 2), options: {raw: {language: 'json'}}};
}

function folder(name, items, desc = '') {
  return {name, description: desc, item: items};
}

// ─────────────────────────────────────────────────────────────────────────
// Shared example fragments
// ─────────────────────────────────────────────────────────────────────────
const bilingual = (en, ar) => ({en, ar});

const customerResource = {
  id: 'a1b2c3d4-0000-4000-8000-000000000001',
  name: 'Youssef Magdy',
  email: 'dev.youssefmagdy@gmail.com',
  phone: '+201001234567',
  status: 'active',
  date_of_birth: '1996-04-12',
  total_orders: 14,
  total_spent: 3420.50,
  loyalty_points: 860,
  referral_code: 'YM4X9K2P',
  email_verified: true,
  phone_verified: true,
  member_since: '2023-02-11T10:00:00+00:00'
};

const tokenPair = {
  access_token: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJhMWIyYzNkNCJ9.SIGNATURE',
  refresh_token: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.SIGNATURE',
  token_type: 'bearer',
  expires_in: 3600
};

const validationErr = (fields) => ({
  success: false,
  message: 'The given data was invalid.',
  errors: fields
});

const unauth = {success: false, message: 'Unauthenticated.'};
const forbidden = (msg = 'This action is unauthorized.') => ({success: false, message: msg});
const notFound = (msg) => ({success: false, message: msg});

const authTokenScript = `
if (pm.response.code === 200 || pm.response.code === 201) {
    const json = pm.response.json();
    const data = json.data || json;
    if (data.access_token) {
        pm.collectionVariables.set('access_token', data.access_token);
    }
    if (data.refresh_token) {
        pm.collectionVariables.set('refresh_token', data.refresh_token);
    }
    pm.test('Token captured', function () {
        pm.expect(data.access_token || pm.collectionVariables.get('access_token')).to.be.a('string');
    });
}
`;

const cartTokenScript = `
if (pm.response.code < 300) {
    const json = pm.response.json();
    const data = json.data || json;
    if (data.guest_cart_token) {
        pm.collectionVariables.set('cart_token', data.guest_cart_token);
    }
}
`;

// Page-builder full example (reused across home/pages/browse/vendor/brand)
const pageBuilderExample = {
  page: {type: 'home', slug: 'eg-home-default', version: 3},
  sections: [
    {
      id: '8f2b1e2a-1111-4000-8000-000000000010',
      position: 0,
      blocks: [
        {
          id: 'b1a2c3d4-1111-4000-8000-000000000011',
          type: 'hero_slider',
          position: 0,
          device_target: 'all',
          audience: null,
          cache_ttl_seconds: 300,
          data: {
            config: {height_desktop: 480, autoplay_seconds: 5, show_dots: true, show_arrows: true, loop: true, transition: 'slide'},
            slides: [
              {
                desktop_image_url: 'https://cdn.example.com/slides/ramadan-desktop.jpg',
                mobile_image_url: 'https://cdn.example.com/slides/ramadan-mobile.jpg',
                title: bilingual('Ramadan Deals', 'عروض رمضان'),
                subtitle: bilingual('Up to 50% off', 'خصومات حتى 50%'),
                cta_label: bilingual('Shop Now', 'تسوق الآن'),
                cta_url: '/browse/product/electronics',
                cta_open_new_tab: false,
                text_color: '#FFFFFF',
                text_position: 'left',
                overlay_opacity: 0.35,
                link_type: 'category',
                link_reference_id: 'cat-electronics-uuid'
              }
            ]
          }
        },
        {
          id: 'c2b3d4e5-1111-4000-8000-000000000012',
          type: 'flash_sale',
          position: 1,
          device_target: 'all',
          audience: null,
          cache_ttl_seconds: 60,
          data: {
            flash_sale: {name: bilingual('Friday Flash Sale', 'فلاش سيل الجمعة'), ends_at: '2026-07-15T20:00:00+00:00', seconds_left: 16200},
            show_countdown: true,
            show_stock_bar: true,
            background_color: '#FFEBEE',
            badge_label: '-40%',
            items: [
              {product_id: 'prod-uuid-1', name: bilingual('Bluetooth Headphones', 'سماعة بلوتوث'), flash_price: 29900, quantity_remaining: 12, seconds_left: 16200, is_sold_out: false}
            ]
          }
        },
        {
          id: 'd3c4e5f6-1111-4000-8000-000000000013',
          type: 'product_row',
          position: 2,
          device_target: 'all',
          audience: null,
          cache_ttl_seconds: 300,
          data: {
            title: bilingual('Best Sellers', 'الأكثر مبيعاً'),
            source: 'best_sellers',
            items_per_row: 4,
            scrollable_row: true,
            show_view_all: true,
            show_ratings: true,
            show_discount_badge: true,
            products: [
              {id: 'prod-uuid-2', name: bilingual('Laptop Pro 15', 'لابتوب'), slug: 'laptop-pro-15', image: 'https://cdn.example.com/products/laptop.jpg', min_price: 899900, max_price: 949900, active_seller_count: 3, total_stock: 1, rating_avg: 4.6, rating_count: 210}
            ]
          }
        },
        {
          id: 'e4f5a6b7-1111-4000-8000-000000000014',
          type: 'full_banner',
          position: 3,
          device_target: 'mobile',
          audience: 'guest',
          cache_ttl_seconds: 300,
          data: {
            image_url: 'https://cdn.example.com/banners/summer-sale-desktop.jpg',
            mobile_image_url: 'https://cdn.example.com/banners/summer-sale-mobile.jpg',
            link_url: '/browse/product/fashion',
            link_type: 'category',
            link_reference_id: 'cat-fashion-uuid',
            alt_text: bilingual('Summer Sale', 'تخفيضات الصيف'),
            aspect_ratio: '16:9',
            mobile_aspect_ratio: '1:1'
          }
        },
        {
          id: 'f5a6b7c8-1111-4000-8000-000000000015',
          type: 'category_pills',
          position: 4,
          device_target: 'all',
          audience: null,
          cache_ttl_seconds: 300,
          data: {
            title: bilingual('Shop by Category', 'تسوق حسب الفئة'),
            items: [
              {id: 'cat-electronics-uuid', name: bilingual('Electronics', 'إلكترونيات'), slug: 'electronics', icon: null, product_count: 1204},
              {id: 'cat-fashion-uuid', name: bilingual('Fashion', 'أزياء'), slug: 'fashion', icon: null, product_count: 843}
            ]
          }
        },
        {
          id: 'a6b7c8d9-1111-4000-8000-000000000016',
          type: 'text_block',
          position: 5,
          device_target: 'all',
          audience: null,
          cache_ttl_seconds: 3600,
          data: {content_html: bilingual('<p>Free shipping over 500 EGP</p>', '<p>شحن مجاني للطلبات فوق 500 جنيه</p>'), text_align: 'center', max_width: '960px'}
        }
      ]
    }
  ]
};

const listingCard = {
  listing_id: '11111111-2222-4000-8000-000000000001',
  listing_ref: 'LST-000123',
  sku: 'SKU-BT-HDPH-BLK',
  vendor_sku: 'VS-8891',
  product_id: '22222222-3333-4000-8000-000000000002',
  product_slug: 'bluetooth-headphones-x200',
  slug: 'bluetooth-headphones-x200',
  name_en: 'Bluetooth Headphones X200',
  name_ar: 'سماعة بلوتوث اكس 200',
  thumbnail: 'https://cdn.example.com/products/bt-headphones.jpg',
  price_cents: 29900,
  price_formatted: 'EGP 299.00',
  currency: 'EGP',
  condition: 'new',
  is_admin_listing: false,
  is_express_fbn: true,
  fulfillment_model: 'fbn',
  vendor: {id: '33333333-4444-4000-8000-000000000003', store_name: 'TechHub Store', rating: 4.7},
  shipping_badge: {label_en: 'Express', label_ar: 'اكسبريس', color_hex: '#1DBF73', text_color_hex: '#FFFFFF', delivery_days_min: 1, delivery_days_max: 2},
  rating_avg: 4.6,
  rating_count: 210,
  total_sold: 1520,
  is_wishlisted: false,
  is_sponsored: false
};

const addressExample = {
  id: 501,
  label: 'Home',
  recipient_name: 'Youssef Magdy',
  recipient_phone: '+201001234567',
  country_id: 1,
  city_id: '44444444-5555-4000-8000-000000000004',
  area: 'Nasr City',
  street_address: '12 Makram Ebeid St.',
  building: '5',
  floor: '3',
  apartment: '12',
  postal_code: '11765',
  landmark: 'Next to City Center Mall',
  latitude: 30.0596,
  longitude: 31.3238,
  is_default: true,
  address_type: 'shipping',
  full_address: '12 Makram Ebeid St., Building 5, Floor 3, Apt 12, Nasr City, Cairo, Egypt'
};

// ─────────────────────────────────────────────────────────────────────────
// AUTH / PROFILE / SECURITY / QR
// ─────────────────────────────────────────────────────────────────────────
const authFolder = folder('Auth', [
  req({
    name: 'Register',
    method: 'POST',
    path: '/auth/register',
    auth: false,
    desc: 'Create a new customer account and receive a JWT token pair. Rate limited to 10 requests/min.',
    body: raw({
      name: 'Youssef Magdy',
      email: 'dev.youssefmagdy@gmail.com',
      phone: '+201001234567',
      password: 'Str0ngP@ssw0rd',
      password_confirmation: 'Str0ngP@ssw0rd',
      referral_code: 'AB12CD34'
    }),
    tests: authTokenScript,
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Registered successfully.', data: {customer: customerResource, ...tokenPair}}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({email: ['The email has already been taken.'], password: ['The password must be at least 8 characters.']})},
      {name: 'Too Many Requests', status: 'Too Many Requests', code: 429, body: {message: 'Too Many Attempts.'}}
    ]
  }),
  req({
    name: 'Login',
    method: 'POST',
    path: '/auth/login',
    auth: false,
    desc: 'Authenticate with email/phone + password. `email_or_phone` accepts either an email address or a phone number. Rate limited to 10 requests/min.',
    body: raw({email_or_phone: 'dev.youssefmagdy@gmail.com', password: 'Str0ngP@ssw0rd'}),
    tests: authTokenScript,
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Logged in successfully.', data: {customer: customerResource, ...tokenPair}}},
      {name: 'Invalid Credentials', status: 'Unauthorized', code: 401, body: {success: false, message: 'Invalid credentials.'}},
      {name: 'Account Suspended', status: 'Forbidden', code: 403, body: {success: false, message: 'Your account has been suspended.'}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({email_or_phone: ['The email or phone field is required.']})}
    ]
  }),
  req({
    name: 'Refresh Token',
    method: 'POST',
    path: '/auth/refresh-token',
    auth: false,
    desc: 'Exchange a refresh_token for a new access/refresh token pair.',
    body: raw({refresh_token: '{{refresh_token}}'}),
    tests: authTokenScript,
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Token refreshed.', data: tokenPair}},
      {name: 'Invalid/Expired Refresh Token', status: 'Unauthorized', code: 401, body: {success: false, message: 'Refresh token is invalid or expired.'}}
    ]
  }),
  req({
    name: 'Forgot Password',
    method: 'POST',
    path: '/auth/forgot-password',
    auth: false,
    desc: 'Trigger a password reset email/SMS. Always returns a generic success message (anti user-enumeration). Rate limited to 5 requests/min.',
    body: raw({email_or_phone: 'dev.youssefmagdy@gmail.com'}),
    responses: [
      {name: 'Success (generic)', status: 'OK', code: 200, body: {success: true, message: 'If an account exists, a reset link has been sent.', data: null}},
      {name: 'Too Many Requests', status: 'Too Many Requests', code: 429, body: {message: 'Too Many Attempts.'}}
    ]
  }),
  req({
    name: 'Reset Password',
    method: 'POST',
    path: '/auth/reset-password',
    auth: false,
    desc: '`token` is the 64-char token from the reset email link.',
    body: raw({token: 'a'.repeat(64), password: 'N3wStr0ngP@ss', password_confirmation: 'N3wStr0ngP@ss'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Password reset successfully.', data: null}},
      {name: 'Invalid/Expired Token', status: 'Unprocessable Entity', code: 422, body: validationErr({token: ['This password reset token is invalid or has expired.']})}
    ]
  }),
  req({
    name: 'Verify Email (via link token)',
    method: 'POST',
    path: '/auth/verify-email',
    auth: false,
    desc: '`token` is the 64-char token from the verification email link.',
    body: raw({token: 'b'.repeat(64)}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Email verified successfully.', data: null}},
      {name: 'Invalid/Expired Token', status: 'Unprocessable Entity', code: 422, body: validationErr({token: ['This verification token is invalid or has expired.']})}
    ]
  }),
  req({
    name: 'Logout',
    method: 'POST',
    path: '/auth/logout',
    desc: 'Invalidates the current JWT.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Logged out successfully.', data: null}},
      {name: 'Unauthenticated', status: 'Unauthorized', code: 401, body: unauth}
    ]
  }),
  req({
    name: 'Get Current Customer (Me)',
    method: 'GET',
    path: '/auth/me',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'OK', data: customerResource}},
      {name: 'Unauthenticated', status: 'Unauthorized', code: 401, body: unauth}
    ]
  }),
  req({
    name: 'Resend Verification Email',
    method: 'POST',
    path: '/auth/resend-verification',
    desc: 'Rate limited to 3 requests/min.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Verification email sent.', data: null}},
      {name: 'Already Verified', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Email is already verified.'}},
      {name: 'Too Many Requests', status: 'Too Many Requests', code: 429, body: {message: 'Too Many Attempts.'}}
    ]
  })
]);

const profileFolder = folder('Profile', [
  req({
    name: 'Get Profile',
    method: 'GET', path: '/profile',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'OK', data: customerResource}}]
  }),
  req({
    name: 'Update Profile',
    method: 'PUT', path: '/profile',
    body: raw({name: 'Youssef Magdy', date_of_birth: '1996-04-12', phone: '+201001234567'}),
    desc: '`date_of_birth` must be before today. Changing `phone` resets phone_verified status.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Profile updated.', data: customerResource}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({phone: ['The phone has already been taken.']})}
    ]
  }),
  req({
    name: 'Update Password',
    method: 'PUT', path: '/profile/password',
    body: raw({current_password: 'Str0ngP@ssw0rd', password: 'N3wStr0ngP@ss', password_confirmation: 'N3wStr0ngP@ss'}),
    desc: 'Invalidates all existing JWTs on success (re-login required on other devices).',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Password updated. Please log in again.', data: null}},
      {name: 'Wrong Current Password', status: 'Unprocessable Entity', code: 422, body: validationErr({current_password: ['The current password is incorrect.']})}
    ]
  }),
  req({
    name: 'Delete Account',
    method: 'DELETE', path: '/profile',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Account deleted.', data: null}}]
  }),
  req({
    name: 'Get QR Code',
    method: 'GET', path: '/profile/qr-code',
    desc: 'Note: response is NOT wrapped in the standard success/data envelope.',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {qr_url: 'https://cdn.example.com/qr/YM4X9K2P.png', referral_code: 'YM4X9K2P', referral_link: 'https://noon.com/eg/r/YM4X9K2P'}}]
  }),
  req({
    name: 'Regenerate QR Code',
    method: 'POST', path: '/profile/qr-code/regenerate',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {qr_url: 'https://cdn.example.com/qr/ZP9Q2M7T.png', referral_code: 'ZP9Q2M7T', referral_link: 'https://noon.com/eg/r/ZP9Q2M7T'}}]
  })
]);

const securityFolder = folder('Security', [
  req({
    name: 'Change Password',
    method: 'POST', path: '/security/change-password',
    body: raw({current_password: 'Str0ngP@ssw0rd', new_password: 'N3wStr0ngP@ss', new_password_confirmation: 'N3wStr0ngP@ss'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Password changed. Please log in again.', data: null}},
      {name: 'Wrong Current Password', status: 'Unprocessable Entity', code: 422, body: validationErr({current_password: ['The current password is incorrect.']})}
    ]
  }),
  req({
    name: 'Send Email Verification OTP',
    method: 'POST', path: '/security/verify-email/send-otp',
    desc: 'Rate limited 5/min; also has a 60s cooldown between sends.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'OTP sent to d***@gmail.com.', data: null}},
      {name: 'Already Verified', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Email is already verified.'}},
      {name: 'Cooldown Active', status: 'Too Many Requests', code: 429, body: {success: false, message: 'Please wait before requesting another OTP.'}}
    ]
  }),
  req({
    name: 'Verify Email OTP',
    method: 'POST', path: '/security/verify-email/verify',
    body: raw({otp: '482913'}),
    desc: 'OTP is 6 digits, valid for 15 minutes. Rate limited 10/min.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Email verified.', data: null}},
      {name: 'Invalid/Expired OTP', status: 'Unprocessable Entity', code: 422, body: validationErr({otp: ['This OTP is invalid or has expired.']})}
    ]
  }),
  req({
    name: 'Send Phone Verification OTP',
    method: 'POST', path: '/security/verify-phone/send-otp',
    desc: 'Rate limited 5/min; 60s cooldown between sends.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'OTP sent to +2010****4567.', data: null}},
      {name: 'Cooldown Active', status: 'Too Many Requests', code: 429, body: {success: false, message: 'Please wait before requesting another OTP.'}}
    ]
  }),
  req({
    name: 'Verify Phone OTP',
    method: 'POST', path: '/security/verify-phone/verify',
    body: raw({otp: '731044'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Phone verified.', data: null}},
      {name: 'Invalid/Expired OTP', status: 'Unprocessable Entity', code: 422, body: validationErr({otp: ['This OTP is invalid or has expired.']})}
    ]
  }),
  req({
    name: 'List Active Sessions',
    method: 'GET', path: '/security/active-sessions',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'OK', data: {devices: [
      {id: '55555555-6666-4000-8000-000000000005', platform: 'ios', last_used_at: '2026-07-15T08:00:00+00:00', token_masked: 'eyJ0...IjE1'},
      {id: '66666666-7777-4000-8000-000000000006', platform: 'web', last_used_at: '2026-07-14T20:30:00+00:00', token_masked: 'eyJ0...OjE1'}
    ], session_count: 2}}}]
  }),
  req({
    name: 'Revoke All Devices',
    method: 'DELETE', path: '/security/sessions/all',
    body: raw({current_device_token: '{{access_token}}'}),
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'All other sessions revoked.', data: null}}]
  }),
  req({
    name: 'Revoke Device',
    method: 'DELETE', path: '/security/sessions/{{device_token_id}}',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Device revoked.', data: null}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Device not found.')}
    ]
  })
]);

fs.writeFileSync('/tmp/gen_part1.json', JSON.stringify({authFolder, profileFolder, securityFolder}, null, 2));
console.log('part1 written, folders:', [authFolder, profileFolder, securityFolder].map(f => f.item.length));

// ─────────────────────────────────────────────────────────────────────────
// CATALOG: home, nav, categories, pages, blocks, browse, listings, products,
// vendors, brands, coupons, search
// ─────────────────────────────────────────────────────────────────────────
const categoryNode = {
  id: 'cat-electronics-uuid', type: 'product', name: bilingual('Electronics', 'إلكترونيات'), slug: 'electronics',
  parent_id: null, image_url: 'https://cdn.example.com/categories/electronics.jpg', product_count: 1204,
  brands: [{id: 'brand-uuid-1', name: 'Samsung', slug: 'samsung', logo_url: 'https://cdn.example.com/brands/samsung.png'}],
  attributes: [{id: 'attr-uuid-1', code: 'color', name: bilingual('Color', 'اللون'), type: 'select', unit: null, is_required: false,
    values: [{id: 'val-uuid-1', value: bilingual('Black', 'أسود'), color_hex: '#000000'}]}],
  children: []
};

const catalogFolder = folder('Catalog', [
  req({
    name: 'Home',
    method: 'GET', path: '/home', auth: false,
    desc: 'Composite home page: nav tree, dynamic page-builder blocks, and curated sections.',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'OK', data: {
      nav: [{section: 'products', label: bilingual('Shop', 'تسوق'), nodes: [categoryNode]}],
      page_builder: pageBuilderExample,
      sections: [
        {section_type: 'banner_carousel', items: []},
        {section_type: 'flash_sale', title: 'Flash Deals', ends_at: '2026-07-15T20:00:00Z', items: [listingCard]},
        {section_type: 'featured_categories', title: 'Shop by Category', items: [{id: 'cat-electronics-uuid', name: bilingual('Electronics', 'إلكترونيات'), slug: 'electronics', icon: null, image_url: 'https://cdn.example.com/categories/electronics.jpg'}]},
        {section_type: 'product_carousel', title: 'Top Picks for You', items: [listingCard]}
      ],
      meta: {country_code: 'eg', currency: 'EGP', locale: 'ar'}
    }}}]
  }),
  req({
    name: 'Navigation Tree',
    method: 'GET', path: '/nav', auth: false,
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'OK', data: {nav: [
      {section: 'products', label: bilingual('Shop', 'تسوق'), nodes: [
        {type: 'product', id: 'cat-electronics-uuid', name: bilingual('Electronics', 'إلكترونيات'), slug: 'electronics', icon: null, children: []}
      ]},
      {section: 'classifieds', label: bilingual('Classifieds', 'الإعلانات المبوّبة'), nodes: [
        {type: 'classified', id: 'cls-cat-uuid-1', name: bilingual('Real Estate', 'عقارات'), slug: 'real-estate', icon: null, children: []}
      ]},
      {section: 'travel', label: bilingual('Travel', 'السفر'), link: '/travel', nodes: [
        {type: 'travel', id: 'trv-cat-uuid-1', name: bilingual('Hajj & Umrah', 'حج وعمرة'), slug: 'hajj-umrah', icon: null, children: []}
      ]}
    ]}}}]
  }),
  req({
    name: 'Categories Tree',
    method: 'GET', path: '/categories', auth: false,
    desc: 'Merged product + classified + travel category tree, cached 10 min per country.',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'OK', data: [categoryNode]}}]
  }),
  req({
    name: 'Legacy Category Redirect',
    method: 'GET', path: '/categories/electronics', auth: false,
    desc: 'Legacy alias — 301 redirects to /browse/product/{category_id}.',
    responses: [{name: '301 Redirect', status: 'Moved Permanently', code: 301, body: {}}]
  }),
  req({
    name: 'Get Page (Page Builder Renderer)',
    method: 'GET', path: '/pages/home',
    query: [{key: 'slug', value: 'eg-home-default', description: 'Optional page slug filter'}],
    headers: [{key: 'X-Session-Id', value: '{{session_id}}', description: 'Optional guest session id for click tracking correlation'}],
    auth: false,
    desc: 'Renders a merchandising page (home, category landing, campaign page, etc.) built from admin-configured blocks/sections. `{type}` route segment selects the page (e.g. home, category). See block type catalog in description:\n\n- hero_slider, countdown_deal/countdown_timer, video_banner, product_row, flash_sale, deal_of_day, ad_images_2col, ad_images_4col, full_banner, category_pills, brand_strip, search_trends, text_block, divider, newsletter_signup\n\nBlocks whose data can\'t be hydrated (expired countdown/deal, missing banner image, no active flash sale) are dropped from the response entirely.',
    responses: [
      {name: 'Success (full block catalog)', status: 'OK', code: 200, body: {success: true, data: pageBuilderExample}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Page not found.')}
    ]
  }),
  req({
    name: 'Track Block Click',
    method: 'POST', path: '/blocks/{{block_id}}/click', auth: false,
    desc: 'Fire-and-forget click tracking for a page-builder block. Throttled 60/min.',
    body: raw({click_target: '/browse/product/electronics', click_target_type: 'category', session_id: '{{session_id}}', device_type: 'desktop'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({click_target_type: ['The selected click_target_type is invalid. Allowed: product, category, url, cta.']})},
      {name: 'Block Not Found', status: 'Not Found', code: 404, body: {success: false, message: 'Block not found.'}}
    ]
  }),
  req({
    name: 'Browse (Product/Classified/Travel)',
    method: 'GET', path: '/browse/product/{{category_id}}', auth: false,
    query: [
      {key: 'price_min', value: '', description: 'Minimum price filter (numeric)'},
      {key: 'price_max', value: '', description: 'Maximum price filter (numeric)'},
      {key: 'brand', value: '', description: 'Brand UUID'},
      {key: 'rating_min', value: '', description: 'Minimum rating (1-5)'},
      {key: 'condition', value: '', description: 'Enum: new, like_new, good, acceptable, refurbished'},
      {key: 'fulfillment_model', value: '', description: 'Enum: fbm, fbn, cross_dock'},
      {key: 'include_oos', value: '', description: 'Boolean — include out-of-stock listings'},
      {key: 'attributes', value: '', description: 'Attribute filter payload (array)'},
      {key: 'sort', value: 'relevance', description: 'Enum: relevance, price_asc, price_desc, rating, newest, best_selling'},
      {key: 'per_page', value: '20'},
      {key: 'page', value: '1'}
    ],
    desc: '`{type}` route segment IN (product, classified, travel); `{id}` = category UUID (or "all" for travel).',
    responses: [
      {name: 'Success (product)', status: 'OK', code: 200, body: {success: true, data: {
        category: categoryNode, category_node: categoryNode, page_builder: pageBuilderExample,
        listings: {items: [listingCard], facets: {brands: [{id: 'brand-uuid-1', name: 'Samsung', count: 340}], price_range: {min: 500, max: 950000}}, meta: {current_page: 1, last_page: 12, per_page: 20, total: 231}}
      }}},
      {name: 'Invalid Type', status: 'Not Found', code: 404, body: notFound('Category not found.')}
    ]
  }),
  req({
    name: 'Travel Index',
    method: 'GET', path: '/travel', auth: false,
    desc: 'All active travel packages, unfiltered (equivalent to /browse/travel/all).',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
      category: {id: 'trv-cat-uuid-1', source_type: 'travel', name: bilingual('All Packages', 'كل الباقات'), slug: 'all', link: '/travel', travel_category_slug: null},
      available_categories: [{id: 'trv-cat-uuid-1', name: bilingual('Hajj & Umrah', 'حج وعمرة'), slug: 'hajj-umrah', icon: null, package_count: 18}],
      page_builder: pageBuilderExample,
      listings: {items: [], meta: {current_page: 1, last_page: 1, per_page: 20, total: 0}}
    }}}]
  }),
  req({
    name: 'Listing Detail (Legacy /l/{identifier})',
    method: 'GET', path: '/l/bluetooth-headphones-x200', auth: false,
    query: [{key: 'source', value: 'direct', description: 'View-logging source tag'}],
    desc: 'identifier IN (listing UUID, variant SKU, listing_ref, product slug).',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        listing: {listing_id: listingCard.listing_id, listing_ref: listingCard.listing_ref, vendor_sku: listingCard.vendor_sku, sku: listingCard.sku, price_cents: 29900, price_formatted: 'EGP 299.00', currency: 'EGP', condition: 'new', condition_notes: null, is_admin_listing: false, is_express_fbn: true, fulfillment_model: 'fbn', global_system_type: 'marketplace', status: 'active', max_order_quantity: 10, total_sold: 1520, rating_avg: 4.6, rating_count: 210, is_global_shipping: false, is_wishlisted: false},
        seller: {id: listingCard.vendor.id, store_name: 'TechHub Store', rating_avg: 4.7, rating_count: 980, is_admin_listing: false},
        delivery_options: [{method_code: 'express', method_name: 'Express Delivery', badge_label: 'Express', badge_color_hex: '#1DBF73', badge_text_color_hex: '#FFFFFF', delivery_label: '1-2 days', delivery_days_min: 1, delivery_days_max: 2}],
        best_seller_badge: true, coupons: [{code: 'SAVE10', description: '10% off'}], payment_options: ['card', 'wallet', 'cod'],
        product: {id: listingCard.product_id, slug: listingCard.product_slug, name: bilingual('Bluetooth Headphones X200', 'سماعة بلوتوث اكس 200'), description: bilingual('Premium wireless headphones with ANC.', 'سماعة لاسلكية بخاصية إلغاء الضوضاء.'), brand: {id: 'brand-uuid-1', name: 'Samsung', slug: 'samsung'}, category: {id: 'cat-electronics-uuid', name: bilingual('Electronics', 'إلكترونيات'), slug: 'electronics'}, breadcrumbs: [{name: bilingual('Electronics', 'إلكترونيات'), slug: 'electronics'}], images: [{id: 'img-1', url: 'https://cdn.example.com/products/bt-headphones.jpg', is_primary: true, position: 0}], rating_avg: 4.6, rating_count: 210, attributes_summary: [{code: 'color', name: bilingual('Color', 'اللون'), value: bilingual('Black', 'أسود')}], highlights: ['40h battery life', 'Active Noise Cancellation'], specifications: [{group: 'General', items: [{key: 'Weight', value: '250g'}]}], seo: {title: 'Bluetooth Headphones X200', description: 'Buy Bluetooth Headphones X200 online.'}},
        variant: {id: 'var-1', sku: listingCard.sku, barcode: '6291041500213', variant_name: 'Black / Standard', is_default: true, attributes: [{attribute_code: 'color', value: bilingual('Black', 'أسود'), color_hex: '#000000'}]},
        other_sellers: [{vendor: {id: 'vendor-2', store_name: 'ElectroWorld'}, price_cents: 30500, price_formatted: 'EGP 305.00'}],
        other_variants: [{id: 'var-2', sku: 'SKU-BT-HDPH-WHT', variant_name: 'White / Standard', is_default: false}],
        reviews: {rating_avg: 4.6, rating_count: 210, rating_percentage: 92, rating_breakdown: {5: 140, 4: 50, 3: 12, 2: 5, 1: 3}, items: []},
        frequently_bought_together: {items: [], total_price_cents: 0, total_price_formatted: 'EGP 0.00', currency: 'EGP'},
        warranty_plans: [{id: 'wp-1', name: bilingual('1-Year Extended Warranty', 'ضمان ممتد لمدة سنة'), duration_months: 12, price_cents: 4900}]
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Listing not found or not available in this country.')}
    ]
  }),
  req({
    name: 'Unified Listing Detail (Product)',
    method: 'GET', path: '/listings/product/bluetooth-headphones-x200', auth: false,
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
      id: listingCard.product_id, slug: listingCard.product_slug, name: bilingual('Bluetooth Headphones X200', 'سماعة بلوتوث اكس 200'),
      description: bilingual('Premium wireless headphones.', 'سماعة لاسلكية مميزة.'), short_description: bilingual('Wireless ANC headphones', 'سماعة لاسلكية'),
      model_number: 'X200-BLK', gtin: '6291041500213', is_age_restricted: false, min_age: null, is_hazardous: false, has_variants: true,
      rating_avg: 4.6, rating_count: 210, rating_breakdown: {5: 140, 4: 50, 3: 12, 2: 5, 1: 3}, total_sold: 1520,
      brand: {id: 'brand-uuid-1', name: 'Samsung', slug: 'samsung'}, category: {id: 'cat-electronics-uuid', name: bilingual('Electronics', 'إلكترونيات'), slug: 'electronics'},
      breadcrumbs: [{name: bilingual('Electronics', 'إلكترونيات'), slug: 'electronics'}], highlights: ['40h battery life'], specifications: [{group: 'General', items: [{key: 'Weight', value: '250g'}]}],
      images: [{id: 'img-1', url: 'https://cdn.example.com/products/bt-headphones.jpg', alt: 'Bluetooth Headphones X200', is_primary: true, position: 0, variant_id: null}],
      variants: [{id: 'var-1', sku: listingCard.sku, variant_name: 'Black / Standard', is_default: true, position: 0, attributes: [{attribute_id: 'attr-uuid-1', attribute_code: 'color', attribute_name: bilingual('Color', 'اللون'), value_id: 'val-uuid-1', value: bilingual('Black', 'أسود'), color_hex: '#000000'}]}],
      sellers: [{vendor: {id: listingCard.vendor.id, store_name: 'TechHub Store', rating: 4.7}, price_cents: 29900, is_admin_listing: false}],
      offers_by_variant: {'var-1': [{vendor_id: listingCard.vendor.id, price_cents: 29900}]},
      reviews: [], related: [{id: 'prod-uuid-3', slug: 'wireless-earbuds-mini', name: bilingual('Wireless Earbuds Mini', 'سماعة أذن لاسلكية'), primary_image: 'https://cdn.example.com/products/earbuds.jpg', price_range: {min: 15900, max: 17900}, rating_avg: 4.3, rating_count: 88, seller_count: 2, is_in_stock: true, is_sponsored: false, is_wishlisted: false}],
      is_wishlisted: false, seo: {title: 'Bluetooth Headphones X200', description: 'Buy online.'},
      best_seller_badge: true, delivery_options: [], coupons: [], payment_options: ['card', 'wallet', 'cod']
    }}}]
  }),
  req({
    name: 'Unified Listing Detail (Classified)',
    method: 'GET', path: '/listings/classified/CL-000482', auth: false,
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        listing_number: 'CL-000482', slug: 'CL-000482', title: bilingual('2BR Apartment for Rent', 'شقة غرفتين للإيجار'), description: bilingual('Fully furnished, near metro.', 'مفروشة بالكامل، قريبة من المترو.'),
        listing_purpose: 'rent', price_cents: 1200000, currency: 'EGP', price_negotiable: true, attributes: {bedrooms: 2, bathrooms: 1, area_sqm: 120},
        location: {city: 'Cairo'}, category: {id: 'cls-cat-uuid-1', name: bilingual('Real Estate', 'عقارات')},
        images: [{id: 'img-c1', url: 'https://cdn.example.com/classifieds/apt1.jpg', position: 0}],
        seller: {id: 'cust-uuid-9', name: 'Ahmed S.', phone_masked: '+2010****234'}, views_count: 342, expires_at: '2026-08-15T00:00:00+00:00', created_at: '2026-07-01T09:00:00+00:00'
      }}},
      {name: 'Not Found / Inactive', status: 'Not Found', code: 404, body: notFound('Listing not found or no longer active.')}
    ]
  }),
  req({
    name: 'Unified Listing Detail (Travel)',
    method: 'GET', path: '/listings/travel/77777777-8888-4000-8000-000000000007', auth: false,
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        id: '77777777-8888-4000-8000-000000000007', slug: '77777777-8888-4000-8000-000000000007', title: bilingual('Umrah Package - 10 Days', 'باقة عمرة - 10 أيام'),
        description: bilingual('Includes flights, hotel, and transport.', 'تشمل الطيران والفندق والمواصلات.'), destination_country: 'Saudi Arabia', destination_city: 'Makkah',
        price_cents: 3500000, currency: 'EGP', duration_days: 10, duration_nights: 9, departure_date: '2026-09-01', return_date: '2026-09-10',
        inclusions: ['Flights', 'Hotel', 'Visa'], images: [{id: 'img-t1', url: 'https://cdn.example.com/travel/umrah1.jpg', position: 0}],
        categories: [{id: 'trv-cat-uuid-1', name: bilingual('Hajj & Umrah', 'حج وعمرة'), slug: 'hajj-umrah'}],
        agency: {id: 'agency-uuid-1', name: 'Al-Noor Travel', logo_url: 'https://cdn.example.com/agencies/alnoor.png', license_number: 'TRV-2019-0042'}, status: 'active'
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Package not found or no longer available.')}
    ]
  }),
  req({
    name: 'Create Classified Inquiry',
    method: 'POST', path: '/listings/classified/CL-000482/inquiries',
    body: raw({message: 'Is this still available? Can I view it this weekend?', contact_phone: '+201001234567', marketer_id: null}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Inquiry sent.', data: {id: 'inq-uuid-1', listing_slug: 'CL-000482', status: 'new', created_at: '2026-07-15T10:00:00+00:00'}}},
      {name: 'Listing Inactive', status: 'Not Found', code: 404, body: notFound('Listing not found or no longer active.')},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({message: ['The message field is required.']})}
    ]
  }),
  req({
    name: 'Create Travel Booking',
    method: 'POST', path: '/listings/travel/77777777-8888-4000-8000-000000000007/bookings',
    body: {mode: 'formdata', formdata: [
      {key: 'travelers_count', value: '2', type: 'text'},
      {key: 'passport_file', type: 'file', src: []}
    ]},
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Booking created.', data: {id: 'booking-uuid-1', booking_number: 'TB-000915', status: 'pending_documents', travelers_count: 2, total_price_cents: 7000000, currency: 'EGP', created_at: '2026-07-15T10:00:00+00:00'}}},
      {name: 'Package Not Found/Expired', status: 'Not Found', code: 404, body: notFound('Package not found or no longer available.')},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({travelers_count: ['The travelers count must be at least 1.']})}
    ]
  }),
  req({
    name: 'Sign Travel Booking Contract',
    method: 'POST', path: '/listings/travel/77777777-8888-4000-8000-000000000007/bookings/TB-000915/contract',
    body: raw({signature_data: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB...'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Contract signed.', data: {id: 'booking-uuid-1', booking_number: 'TB-000915', contract_signed_at: '2026-07-15T10:05:00+00:00', status: 'confirmed'}}},
      {name: 'Package Not Found', status: 'Not Found', code: 404, body: notFound('Package not found.')}
    ]
  }),
  req({
    name: 'Product List',
    method: 'GET', path: '/products', auth: false,
    query: [
      {key: 'category', value: '', description: 'Category UUID'}, {key: 'brand', value: '', description: 'Brand UUID'},
      {key: 'price_min', value: ''}, {key: 'price_max', value: ''}, {key: 'rating_min', value: '', description: '1-5'},
      {key: 'condition', value: '', description: 'Enum: new, like_new, good, acceptable, refurbished'},
      {key: 'fulfillment_model', value: '', description: 'Enum: fbm, fbn, cross_dock'},
      {key: 'sort', value: 'relevance', description: 'Enum: relevance, price_asc, price_desc, rating, newest, best_selling'},
      {key: 'include_oos', value: 'false'}, {key: 'per_page', value: '20'}, {key: 'page', value: '1'}
    ],
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [listingCard], facets: {brands: [], price_range: {min: 500, max: 950000}}, meta: {current_page: 1, last_page: 12, per_page: 20, total: 231}}}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({sort: ['The selected sort is invalid.']})}
    ]
  }),
  req({
    name: 'Vendor Storefront Page',
    method: 'GET', path: '/vendors/{{vendor_id}}', auth: false,
    query: [{key: 'per_page', value: '20'}],
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {vendor: {id: listingCard.vendor.id, store_name: 'TechHub Store', store_rating_avg: 4.7, store_rating_count: 980, country: 'EG'}, page_builder: pageBuilderExample, listings: {items: [listingCard], meta: {current_page: 1, last_page: 3, per_page: 20, total: 54}}}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Vendor not found.')}
    ]
  }),
  req({
    name: 'Brand Page',
    method: 'GET', path: '/brands/{{brand_id}}', auth: false,
    query: [{key: 'per_page', value: '20'}],
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {brand: {id: 'brand-uuid-1', name: 'Samsung', slug: 'samsung', logo_url: 'https://cdn.example.com/brands/samsung.png', description: 'Official Samsung store.', is_verified: true}, page_builder: pageBuilderExample, listings: {items: [listingCard], meta: {current_page: 1, last_page: 8, per_page: 20, total: 152}}}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Brand not found.')}
    ]
  }),
  req({
    name: 'Coupon Detail',
    method: 'GET', path: '/coupons/SAVE10', auth: false,
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {code: 'SAVE10', name: bilingual('Save 10%', 'خصم 10%'), description: bilingual('10% off your order.', 'خصم 10% على طلبك.'), type: 'percentage', value: 10, min_order_amount: 20000, max_discount: 10000, valid_until: '2026-12-31T23:59:59+00:00', is_stackable: false}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Coupon not found.')}
    ]
  }),
  req({
    name: 'Search',
    method: 'GET', path: '/search', auth: false,
    query: [
      {key: 'q', value: 'headphones', description: 'Required search query'},
      {key: 'source_type', value: 'product', description: 'Enum: product, classified, travel, all'},
      {key: 'category', value: ''}, {key: 'brand', value: ''}, {key: 'price_min', value: ''}, {key: 'price_max', value: ''},
      {key: 'rating_min', value: ''}, {key: 'condition', value: ''}, {key: 'fulfillment_model', value: ''},
      {key: 'sort', value: 'relevance'}, {key: 'include_oos', value: 'false'}, {key: 'per_page', value: '20'}, {key: 'page', value: '1'}
    ],
    responses: [
      {name: 'Success (product)', status: 'OK', code: 200, body: {success: true, data: {items: [listingCard], facets: {}, meta: {current_page: 1, last_page: 3, per_page: 20, total: 47}}}},
      {name: 'Success (source_type=all)', status: 'OK', code: 200, body: {success: true, data: {source_type: 'all', products: {items: [listingCard], total: 47}, classifieds: {items: [], total: 3}, travel: {items: [], total: 0}, meta: {query: 'headphones', total_results: 50}}}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({q: ['The q field is required.']})}
    ]
  }),
  req({
    name: 'Search Suggestions',
    method: 'GET', path: '/search/suggestions', auth: false,
    query: [{key: 'q', value: 'blue', description: 'Required, min 1 char'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: [
      {id: 'prod-uuid-2', product_id: 'prod-uuid-2', slug: 'bluetooth-headphones-x200', name: 'Bluetooth Headphones X200', vendor: 'TechHub Store', type: 'product'}
    ]}}]
  })
]);

fs.writeFileSync('/tmp/gen_part2.json', JSON.stringify({catalogFolder}, null, 2));
console.log('part2 written, folder items:', catalogFolder.item.length);

// ─────────────────────────────────────────────────────────────────────────
// CART / WISHLIST / CHECKOUT / ORDERS
// ─────────────────────────────────────────────────────────────────────────
const cartItemExample = {
  cart_item_id: 'ci-uuid-1', listing_id: listingCard.listing_id, listing_ref: listingCard.listing_ref, sku: listingCard.sku, vendor_sku: listingCard.vendor_sku,
  name: bilingual('Bluetooth Headphones X200', 'سماعة بلوتوث اكس 200'), thumbnail: listingCard.thumbnail, unit_price_cents: 29900, quantity: 2, line_total_cents: 59800,
  max_order_quantity: 10, vendor: {id: listingCard.vendor.id, store_name: 'TechHub Store'}, is_admin_listing: false,
  shipping_badge: {label: 'Express', color_hex: '#1DBF73', text_color_hex: '#FFFFFF', delivery_days_min: 1, delivery_days_max: 2}, in_stock: true, price_changed: false
};
const cartExample = (guest) => ({
  cart_id: 'cart-uuid-1', session_token: guest ? '{{cart_token}}' : null, currency: 'EGP',
  summary: {subtotal_cents: 59800, discount_cents: 0, estimated_shipping_cents: 0, estimated_tax_cents: 0, estimated_total_cents: 59800, item_count: 2},
  coupon: null, items: [cartItemExample], expires_at: '2026-07-22T00:00:00+00:00',
  ...(guest ? {guest_cart_token: '{{cart_token}}'} : {})
});

const cartHeaders = [{key: 'X-Cart-Token', value: '{{cart_token}}', description: 'Guest cart session token — omit when authenticated', disabled: false}];

const cartFolder = folder('Cart & Wishlist', [
  req({
    name: 'Get Cart',
    method: 'GET', path: '/cart', auth: false, headers: cartHeaders,
    desc: 'Works for guests (via X-Cart-Token) and authenticated customers.',
    tests: cartTokenScript,
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: cartExample(true)}}]
  }),
  req({
    name: 'Add Item to Cart',
    method: 'POST', path: '/cart/items', auth: false, headers: cartHeaders,
    body: raw({vendor_listing_id: listingCard.listing_id, quantity: 1}),
    tests: cartTokenScript,
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Item added to cart.', data: {...cartExample(true), item: cartItemExample, listing_ref: listingCard.listing_ref}}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({vendor_listing_id: ['The selected vendor listing id is invalid.']})},
      {name: 'Domain Error (e.g. out of stock)', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'This item is out of stock.'}}
    ]
  }),
  req({
    name: 'Add Items to Cart (Bulk)',
    method: 'POST', path: '/cart/items/bulk', auth: false, headers: cartHeaders,
    body: raw({items: [{vendor_listing_id: listingCard.listing_id, quantity: 1}, {vendor_listing_id: '99999999-aaaa-4000-8000-000000000099', quantity: 2}]}),
    tests: cartTokenScript,
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Items added to cart.', data: cartExample(true)}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({'items': ['The items field is required.'], 'items.0.quantity': ['The items.0.quantity must be at least 1.']})}
    ]
  }),
  req({
    name: 'Update Cart Item Quantity',
    method: 'PUT', path: '/cart/items/{{cart_item_id}}', auth: false, headers: cartHeaders,
    body: raw({quantity: 3}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Cart item updated.', data: cartExample(true)}},
      {name: 'Item Not Found', status: 'Not Found', code: 404, body: notFound('Cart item not found.')},
      {name: 'Domain Error', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Requested quantity exceeds available stock.'}}
    ]
  }),
  req({
    name: 'Remove Cart Item',
    method: 'DELETE', path: '/cart/items/{{cart_item_id}}', auth: false, headers: cartHeaders,
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Cart item removed.', data: cartExample(true)}},
      {name: 'Item Not Found', status: 'Not Found', code: 404, body: notFound('Cart item not found.')}
    ]
  }),
  req({
    name: 'Clear Cart',
    method: 'DELETE', path: '/cart', auth: false, headers: cartHeaders,
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Cart cleared.', data: null}}]
  }),
  req({
    name: 'Apply Coupon to Cart',
    method: 'POST', path: '/cart/coupon', auth: false, headers: cartHeaders,
    body: raw({code: 'SAVE10'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Coupon applied.', data: {...cartExample(true), coupon: {code: 'SAVE10', type: 'percentage', description: '10% off'}}}},
      {name: 'Coupon Not Found', status: 'Not Found', code: 404, body: notFound('Coupon not found.')},
      {name: 'Domain Error', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'This coupon does not meet the minimum order requirement.'}}
    ]
  }),
  req({
    name: 'Remove Coupon from Cart',
    method: 'DELETE', path: '/cart/coupon', auth: false, headers: cartHeaders,
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Coupon removed.', data: cartExample(true)}}]
  }),
  req({
    name: 'Merge Guest Cart into Account',
    method: 'POST', path: '/cart/merge',
    body: raw({guest_cart_token: '{{cart_token}}'}),
    desc: 'Call right after login/register when the customer had a guest cart.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Cart merged.', data: cartExample(false)}},
      {name: 'Missing Token', status: 'Unprocessable Entity', code: 422, body: validationErr({guest_cart_token: ['The guest cart token field is required.']})}
    ]
  }),
  req({
    name: 'List Wishlist',
    method: 'GET', path: '/wishlist',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'wish-uuid-1', added_at: '2026-07-10T12:00:00+00:00', listing_id: listingCard.listing_id, listing_ref: listingCard.listing_ref, sku: listingCard.sku,
      price_cents: 29900, price_formatted: 'EGP 299.00', currency: 'EGP', status: 'active', is_admin_listing: false,
      product: {id: listingCard.product_id, name: bilingual('Bluetooth Headphones X200', 'سماعة بلوتوث اكس 200'), slug: listingCard.slug, thumbnail: listingCard.thumbnail},
      vendor: {id: listingCard.vendor.id, store_name: 'TechHub Store'},
      shipping_badge: {label: 'Express', color_hex: '#1DBF73', text_color_hex: '#FFFFFF', delivery_days_min: 1, delivery_days_max: 2}
    }], meta: {current_page: 1, last_page: 1, per_page: 20, total: 1}}}}]
  }),
  req({
    name: 'Add to Wishlist',
    method: 'POST', path: '/wishlist',
    body: raw({vendor_listing_id: listingCard.listing_id}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Added to wishlist.', data: {id: 'wish-uuid-1'}}},
      {name: 'Already In Wishlist', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Listing already in wishlist.'}}
    ]
  }),
  req({
    name: 'Remove from Wishlist',
    method: 'DELETE', path: `/wishlist/${listingCard.listing_id}`,
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Removed from wishlist.', data: null}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Item not found in wishlist.')}
    ]
  })
]);

const checkoutFolder = folder('Checkout & Orders', [
  req({
    name: 'Get Shipping Methods',
    method: 'GET', path: '/checkout/shipping-methods',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
      shipping_methods: [{id: 'ship-uuid-1', code: 'express', name: bilingual('Express Delivery', 'توصيل سريع'), badge_label_en: 'Express', badge_label_ar: 'سريع', badge_color_hex: '#1DBF73', badge_text_color_hex: '#FFFFFF', delivery_days_min: 1, delivery_days_max: 2, fee_cents: 3000, is_free: false, cod_extra_fee_cents: 500, cod_available: true}],
      destination_zone: 'Cairo', cod_available_for_address: true
    }}}]
  }),
  req({
    name: 'Prepare Checkout',
    method: 'POST', path: '/checkout/prepare',
    body: raw({address_id: 501, shipping_method_id: 'ship-uuid-1', payment_method: 'card', coupon_code: 'SAVE10', gift_card_code: null, warranty_selections: [{listing_id: listingCard.listing_id, warranty_plan_id: 'wp-1'}]}),
    desc: '`payment_method` enum: card, wallet, cod, bnpl, bank_transfer.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        order_summary: {subtotal_cents: 59800, discount_cents: 5980, shipping_cents: 3000, cod_fee_cents: 0, tax_cents: 0, warranty_total_cents: 4900, total_cents: 61720},
        shipping: {method_id: 'ship-uuid-1', method_name: 'Express Delivery', fee_cents: 3000, is_free: false, estimated_delivery_days_min: 1, estimated_delivery_days_max: 2},
        address: {id: 501, recipient_name: 'Youssef Magdy', street_address: '12 Makram Ebeid St.', city: 'Cairo', country: 'Egypt'},
        payment_method: 'card', available_payment_methods: ['card', 'wallet', 'cod'],
        coupon: {code: 'SAVE10', type: 'percentage', discount_cents: 5980}, gift_card: null,
        items: [{listing_id: listingCard.listing_id, listing_ref: listingCard.listing_ref, sku: listingCard.sku, name_en: 'Bluetooth Headphones X200', quantity: 2, unit_price_cents: 29900, line_total_cents: 59800, thumbnail: listingCard.thumbnail, vendor_name: 'TechHub Store', is_admin_listing: false}]
      }}},
      {name: 'Cart Empty', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Your cart is empty.'}},
      {name: 'Address Not Found', status: 'Not Found', code: 404, body: notFound('Address not found.')},
      {name: 'COD Unavailable', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Cash on delivery is not available for this address.'}}
    ]
  }),
  req({
    name: 'Place Order',
    method: 'POST', path: '/checkout/place-order',
    desc: 'Rate limited to 5/min. `idempotency_key` must be unique per checkout attempt; replays return 409 with the original order_number. `gateway_token`/`gateway` required when payment_method=card.',
    body: raw({address_id: 501, shipping_method_id: 'ship-uuid-1', payment_method: 'card', coupon_code: 'SAVE10', gift_card_code: null, warranty_selections: [{listing_id: listingCard.listing_id, warranty_plan_id: 'wp-1'}], customer_notes: 'Please call before delivery.', idempotency_key: '9f8e7d6c-0000-4000-8000-000000000099', gateway_token: 'tok_visa_4242', gateway: 'stripe'}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Order placed.', data: {
        order_number: 'ORD-000482', status: 'placed', payment_status: 'paid', total_cents: 61720, warranty_total_cents: 4900, currency: 'EGP', placed_at: '2026-07-15T10:10:00+00:00',
        sub_orders: [{sub_order_number: 'SUB-000482-1', vendor: 'TechHub Store', status: 'placed', fulfillment_model: 'fbn', items: [{listing_ref: listingCard.listing_ref, sku: listingCard.sku, name_en: 'Bluetooth Headphones X200', quantity: 2, unit_price_cents: 29900, line_total_cents: 59800}]}]
      }}},
      {name: 'Idempotent Replay', status: 'Conflict', code: 409, body: {success: false, message: 'Order already placed.', data: {order_number: 'ORD-000482'}}},
      {name: 'Insufficient Stock', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'One or more items are no longer available in the requested quantity.'}},
      {name: 'Address Not Found', status: 'Not Found', code: 404, body: notFound('Address not found.')}
    ]
  }),
  req({
    name: 'Order Confirmation',
    method: 'GET', path: '/checkout/{{order_number}}/confirmation',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {order_number: 'ORD-000482', status: 'placed', payment_status: 'paid', total_cents: 61720, currency: 'EGP', placed_at: '2026-07-15T10:10:00+00:00'}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Order not found.')}
    ]
  }),
  req({
    name: 'List Orders',
    method: 'GET', path: '/orders',
    query: [
      {key: 'status', value: '', description: 'Enum: placed, confirmed, partially_shipped, shipped, partially_delivered, delivered, completed, cancelled, refunded, disputed'},
      {key: 'date_from', value: '', description: 'Y-m-d'}, {key: 'date_to', value: '', description: 'Y-m-d, must be after_or_equal date_from'}, {key: 'page', value: '1'}
    ],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'order-uuid-1', order_number: 'ORD-000482', status: 'placed', payment_status: 'paid', payment_method: 'card', currency: 'EGP',
      subtotal: 598.00, discount: 59.80, shipping: 30.00, tax: 0, cod_fee: 0, total: 617.20, placed_at: '2026-07-15T10:10:00+00:00',
      shipping_address: {recipient_name: 'Youssef Magdy', street_address: '12 Makram Ebeid St.', city: 'Cairo'}, coupon_code_used: 'SAVE10',
      sub_orders: [{id: 'sub-uuid-1', sub_order_number: 'SUB-000482-1', status: 'placed', fulfillment_model: 'fbn', vendor_name: 'TechHub Store', subtotal: 598.00, shipping: 30.00, tax: 0, tracking_number: null, estimated_delivery_date: '2026-07-17', sla_ship_deadline: '2026-07-16T00:00:00+00:00', items: [{id: 'item-uuid-1', product: {name: 'Bluetooth Headphones X200'}, sku: listingCard.sku, listing_ref: listingCard.listing_ref, vendor_sku: listingCard.vendor_sku, quantity: 2, unit_price: 299.00, line_total: 598.00, fulfillment_status: 'pending', return_eligible_until: '2026-08-14T00:00:00+00:00'}]}]
    }], meta: {current_page: 1, last_page: 1, per_page: 15, total: 14}}}}]
  }),
  req({
    name: 'Get Order Detail',
    method: 'GET', path: '/orders/{{order_number}}',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        order_number: 'ORD-000482', status: 'placed', status_label_en: 'Placed', status_label_ar: 'تم الطلب', payment_method: 'card', payment_status: 'paid', placed_at: '2026-07-15T10:10:00+00:00', currency: 'EGP',
        summary: {subtotal_cents: 59800, discount_cents: 5980, shipping_cents: 3000, cod_fee_cents: 0, tax_cents: 0, total_cents: 61720},
        shipping_address: {recipient_name: 'Youssef Magdy', recipient_phone: '+201001234567', street_address: '12 Makram Ebeid St.', area: 'Nasr City', city: 'Cairo', country: 'Egypt'},
        sub_orders: [{
          id: 'sub-uuid-1', sub_order_number: 'SUB-000482-1', status: 'placed', fulfillment_model: 'fbn', vendor: {id: listingCard.vendor.id, store_name: 'TechHub Store'},
          tracking: {tracking_number: null, carrier: null, estimated_delivery_date: '2026-07-17', shipped_at: null, delivered_at: null, events: []},
          delivery_agent: null,
          items: [{id: 'item-uuid-1', sku: listingCard.sku, listing_ref: listingCard.listing_ref, name_en: 'Bluetooth Headphones X200', name_ar: 'سماعة بلوتوث اكس 200', thumbnail: listingCard.thumbnail, quantity: 2, unit_price_cents: 29900, line_total_cents: 59800, fulfillment_status: 'pending', return_eligible_until: '2026-08-14T00:00:00+00:00', can_return: false, can_review: false}]
        }],
        marketer_ref: null
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Order not found.')}
    ]
  }),
  req({
    name: 'Cancel Order',
    method: 'POST', path: '/orders/{{order_number}}/cancel',
    body: raw({reason: 'Changed my mind about this purchase.'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Order cancelled.', data: null}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Order not found.')},
      {name: 'Cannot Cancel', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'This order cannot be cancelled in its current status.'}}
    ]
  }),
  req({
    name: 'Track Sub-Order',
    method: 'POST', path: '/sub-orders/{{sub_order_id}}/track',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        sub_order_number: 'SUB-000482-1', status: 'shipped', fulfillment_model: 'fbn', vendor: {id: listingCard.vendor.id, store_name: 'TechHub Store'},
        tracking: {tracking_number: 'TRK9988776', carrier: 'Aramex', estimated_delivery_date: '2026-07-17', shipped_at: '2026-07-15T14:00:00+00:00', delivered_at: null, events: [{status: 'shipped', status_label_en: 'Shipped', status_label_ar: 'تم الشحن', location: 'Cairo Hub', description: 'Package left the warehouse.', occurred_at: '2026-07-15T14:00:00+00:00'}]},
        delivery_agent: {name: 'Mohamed A.', status: 'assigned', otp_required: true, otp_verified: false},
        timeline: [{source: 'carrier', status: 'shipped', status_label_en: 'Shipped', status_label_ar: 'تم الشحن', location: 'Cairo Hub', description: 'Package left the warehouse.', occurred_at: '2026-07-15T14:00:00+00:00'}]
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Sub-order not found.')}
    ]
  })
]);

fs.writeFileSync('/tmp/gen_part3.json', JSON.stringify({cartFolder, checkoutFolder}, null, 2));
console.log('part3 written:', cartFolder.item.length, checkoutFolder.item.length);

// ─────────────────────────────────────────────────────────────────────────
// ADDRESSES / PAYMENT METHODS / WALLET / GIFT CARDS
// ─────────────────────────────────────────────────────────────────────────
const addressFolder = folder('Addresses', [
  req({
    name: 'List Addresses',
    method: 'GET', path: '/addresses',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: [addressExample]}}]
  }),
  req({
    name: 'Create Address',
    method: 'POST', path: '/addresses',
    desc: '`address_type` enum: shipping, billing, both.',
    body: raw({label: 'Home', recipient_name: 'Youssef Magdy', recipient_phone: '+201001234567', city_id: addressExample.city_id, area: 'Nasr City', street_address: '12 Makram Ebeid St.', building: '5', floor: '3', apartment: '12', landmark: 'Next to City Center Mall', latitude: 30.0596, longitude: 31.3238, address_type: 'shipping', is_default: true}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Address added.', data: addressExample}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({street_address: ['The street address field is required.'], address_type: ['The selected address type is invalid.']})}
    ]
  }),
  req({
    name: 'Update Address',
    method: 'PUT', path: '/addresses/501',
    body: raw({label: 'Home', recipient_name: 'Youssef Magdy', recipient_phone: '+201001234567', city_id: addressExample.city_id, area: 'Nasr City', street_address: '12 Makram Ebeid St.', building: '5', floor: '3', apartment: '12', landmark: 'Next to City Center Mall', latitude: 30.0596, longitude: 31.3238, address_type: 'shipping', is_default: true}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Address updated.', data: addressExample}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Address not found.')}
    ]
  }),
  req({
    name: 'Delete Address',
    method: 'DELETE', path: '/addresses/501',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Address deleted.', data: null}},
      {name: 'In Use (has orders)', status: 'Conflict', code: 409, body: {success: false, message: 'This address is used by existing orders and cannot be deleted.'}}
    ]
  }),
  req({
    name: 'Set Default Address',
    method: 'PUT', path: '/addresses/501/set-default',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Default address updated.', data: addressExample}}]
  })
]);

const paymentMethodExample = {
  id: 'pm-uuid-1', type: 'card', gateway: 'stripe', card_brand: 'visa', card_last4: '4242', card_exp_month: 12, card_exp_year: 2028,
  is_default: true, card_display: 'Visa **** 4242', billing_address: addressExample
};

const paymentMethodFolder = folder('Payment Methods & Wallet', [
  req({
    name: 'List Payment Methods',
    method: 'GET', path: '/payment-methods',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: [paymentMethodExample]}}]
  }),
  req({
    name: 'Add Payment Method',
    method: 'POST', path: '/payment-methods',
    desc: '`type` enum: card, wallet, bank. `gateway` e.g. stripe, thawani, tap.',
    body: raw({type: 'card', gateway: 'stripe', gateway_token: 'tok_visa_4242', card_brand: 'visa', card_last4: '4242', card_exp_month: 12, card_exp_year: 2028, billing_address_id: 501, is_default: true}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Payment method added.', data: paymentMethodExample}},
      {name: 'Gateway Unavailable', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'This payment gateway is not available in your country.'}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({gateway_token: ['The gateway token field is required.']})}
    ]
  }),
  req({
    name: 'Set Default Payment Method',
    method: 'PATCH', path: '/payment-methods/{{payment_method_id}}/default',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Default payment method updated.', data: paymentMethodExample}},
      {name: 'Forbidden', status: 'Forbidden', code: 403, body: forbidden()}
    ]
  }),
  req({
    name: 'Delete Payment Method',
    method: 'DELETE', path: '/payment-methods/{{payment_method_id}}',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Payment method removed.', data: null}},
      {name: 'Forbidden', status: 'Forbidden', code: 403, body: forbidden()}
    ]
  }),
  req({
    name: 'Get Wallet',
    method: 'GET', path: '/wallet',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {id: 'wallet-uuid-1', balance_cents: 125000, pending_balance_cents: 5000, currency: 'EGP', formatted_balance: 'EGP 1,250.00', is_frozen: false}}}]
  }),
  req({
    name: 'Wallet Transactions',
    method: 'GET', path: '/wallet/transactions',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [
      {id: 'wtx-uuid-1', type: 'credit', amount_cents: 5000, balance_after_cents: 125000, source_type: 'refund', description: 'Refund for ORD-000410', created_at: '2026-07-10T09:00:00+00:00'}
    ], meta: {current_page: 1, last_page: 1, per_page: 15, total: 1}}}}]
  }),
  req({
    name: 'Request Withdrawal',
    method: 'POST', path: '/wallet/withdrawal',
    body: raw({amount_cents: 50000, bank_name: 'National Bank of Egypt', bank_iban: 'EG380019000500000000263180002'}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Withdrawal requested.', data: {id: 'wd-uuid-1', amount_cents: 50000, status: 'pending', created_at: '2026-07-15T10:00:00+00:00'}}},
      {name: 'Insufficient Balance', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Insufficient wallet balance.'}},
      {name: 'Wallet Frozen', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Your wallet is currently frozen.'}}
    ]
  })
]);

const giftCardFolder = folder('Gift Cards', [
  req({
    name: 'Check Gift Card Balance',
    method: 'POST', path: '/gift-cards/check-balance', auth: false,
    body: raw({code: 'NOON-A1B2-C3D4-E5F6'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {balance_cents: 20000, currency: 'EGP', status: 'active', expires_at: '2027-01-01T00:00:00+00:00'}}},
      {name: 'Not Found/Inactive', status: 'Not Found', code: 404, body: notFound('Gift card not found or inactive.')}
    ]
  }),
  req({
    name: 'My Gift Cards',
    method: 'GET', path: '/gift-cards',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [
      {id: 'gc-uuid-1', code: 'NOON-****-****-E5F6', denomination_cents: 20000, balance_cents: 20000, currency: 'EGP', status: 'active', recipient_name: 'Youssef Magdy', recipient_email: 'dev.youssefmagdy@gmail.com', personal_message: 'Happy Birthday!', expires_at: '2027-01-01T00:00:00+00:00', created_at: '2026-07-01T00:00:00+00:00'}
    ], meta: {current_page: 1, last_page: 1, per_page: 15, total: 1}}}}]
  }),
  req({
    name: 'Purchase Gift Card',
    method: 'POST', path: '/gift-cards/purchase',
    desc: '`denomination_cents` enum: 5000, 10000, 25000, 50000, 100000 (i.e. EGP 50/100/250/500/1000). Provide `recipient_email` or `recipient_phone`. `code` is returned unmasked only in this purchase response.',
    body: raw({denomination_cents: 20000, currency: 'EGP', recipient_email: 'friend@example.com', recipient_phone: null, recipient_name: 'Sara Ali', personal_message: 'Happy Birthday!'}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Gift card purchased.', data: {id: 'gc-uuid-2', code: 'NOON-A1B2-C3D4-E5F6', denomination_cents: 20000, balance_cents: 20000, currency: 'EGP', status: 'active', recipient_name: 'Sara Ali', recipient_email: 'friend@example.com', personal_message: 'Happy Birthday!', expires_at: '2027-07-15T00:00:00+00:00', created_at: '2026-07-15T10:00:00+00:00'}}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({denomination_cents: ['The selected denomination cents is invalid.'], recipient_email: ['The recipient email field is required when recipient phone is not present.']})}
    ]
  })
]);

fs.writeFileSync('/tmp/gen_part4.json', JSON.stringify({addressFolder, paymentMethodFolder, giftCardFolder}, null, 2));
console.log('part4 written:', addressFolder.item.length, paymentMethodFolder.item.length, giftCardFolder.item.length);

// ─────────────────────────────────────────────────────────────────────────
// POST-SALE: returns, disputes, reviews, refunds, warranty, support, notifications
// ─────────────────────────────────────────────────────────────────────────
const postSaleFolder = folder('Post-Sale (Returns, Disputes, Reviews, Refunds, Warranty, Support)', [
  req({
    name: 'Create Return Request',
    method: 'POST', path: '/orders/{{order_number}}/returns',
    desc: '`reason` enum: changed_mind, wrong_item, defective, damaged, not_as_described, size_issue, quality_issue, arrived_late, other.\n`return_type` enum: refund, exchange, store_credit.',
    body: raw({order_item_ids: ['item-uuid-1'], reason: 'defective', return_type: 'refund', comments: 'The item stopped working after 2 days.'}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Return request submitted.', data: {id: 'ret-uuid-1', return_number: 'RET-000221', order_number: 'ORD-000482', reason: 'defective', return_type: 'refund', status: 'pending', created_at: '2026-07-15T10:00:00+00:00'}}},
      {name: 'Order Not Found', status: 'Not Found', code: 404, body: notFound('Order not found.')},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({reason: ['The selected reason is invalid.'], order_item_ids: ['The order item ids field is required.']})}
    ]
  }),
  req({
    name: 'List Returns',
    method: 'GET', path: '/returns',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'ret-uuid-1', return_number: 'RET-000221', order_number: 'ORD-000482', reason: 'defective', reason_description: 'Defective', return_type: 'refund', status: 'pending',
      refund_amount: 299.00, rejection_reason: null, created_at: '2026-07-15T10:00:00+00:00', items: [{order_item_id: 'item-uuid-1', quantity: 1, product_snapshot: {name: 'Bluetooth Headphones X200', sku: listingCard.sku}}]
    }], meta: {current_page: 1, last_page: 1, per_page: 15, total: 1}}}}]
  }),
  req({
    name: 'Get Return Detail',
    method: 'GET', path: '/returns/RET-000221',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {id: 'ret-uuid-1', return_number: 'RET-000221', order_number: 'ORD-000482', reason: 'defective', reason_description: 'Defective', return_type: 'refund', status: 'approved', refund_amount: 299.00, rejection_reason: null, created_at: '2026-07-15T10:00:00+00:00', items: [{order_item_id: 'item-uuid-1', quantity: 1, product_snapshot: {name: 'Bluetooth Headphones X200', sku: listingCard.sku}}]}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Return request not found.')}
    ]
  }),
  req({
    name: 'Create Dispute',
    method: 'POST', path: '/orders/{{order_number}}/disputes',
    desc: '`reason` enum: item_not_received, item_damaged, item_not_as_described, counterfeit, wrong_item, quality_issue, seller_unresponsive, refund_not_received, other.',
    body: raw({reason: 'item_not_received', description: 'It has been 10 days and the tracking shows no movement.'}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Dispute opened.', data: {id: 'disp-uuid-1', dispute_number: 'DSP-000091', order_number: 'ORD-000482', reason: 'item_not_received', status: 'open', created_at: '2026-07-15T10:00:00+00:00'}}},
      {name: 'Order Not Found', status: 'Not Found', code: 404, body: notFound('Order not found.')},
      {name: 'No Sub-Orders', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'No sub-orders found for this order.'}}
    ]
  }),
  req({
    name: 'List Disputes',
    method: 'GET', path: '/disputes',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'disp-uuid-1', dispute_number: 'DSP-000091', order_number: 'ORD-000482', reason: 'item_not_received', description: 'It has been 10 days...', status: 'open',
      resolution: null, resolution_notes: null, compensation: null, resolved_at: null, created_at: '2026-07-15T10:00:00+00:00'
    }], meta: {current_page: 1, last_page: 1, per_page: 15, total: 1}}}}]
  }),
  req({
    name: 'Get Dispute Detail',
    method: 'GET', path: '/disputes/DSP-000091',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        id: 'disp-uuid-1', dispute_number: 'DSP-000091', order_number: 'ORD-000482', reason: 'item_not_received', description: 'It has been 10 days...', status: 'under_review',
        resolution: null, resolution_notes: null, compensation: null, resolved_at: null, created_at: '2026-07-15T10:00:00+00:00',
        messages: [{id: 'msg-uuid-1', sender_role: 'customer', message: 'Any update on this?', created_at: '2026-07-15T11:00:00+00:00', attachments: []}]
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Dispute not found.')}
    ]
  }),
  req({
    name: 'Add Dispute Message',
    method: 'POST', path: '/disputes/DSP-000091/messages',
    body: {mode: 'formdata', formdata: [{key: 'message', value: 'Any update on this?', type: 'text'}, {key: 'attachment', type: 'file', src: []}]},
    desc: '`attachment` accepts jpg,jpeg,png,pdf,mp4, max 10MB.',
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {id: 'msg-uuid-2', message: 'Any update on this?', created_at: '2026-07-15T12:00:00+00:00'}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Dispute not found.')},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({message: ['The message field is required.']})}
    ]
  }),
  req({
    name: 'Update Review',
    method: 'PUT', path: '/reviews/{{review_id}}',
    body: raw({rating: 5, comment: 'Updated my review after using it for a month — still great!'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Review updated.', data: {id: 'rev-uuid-1', rating: 5, comment: 'Updated my review...', created_at: '2026-07-01T00:00:00+00:00'}}},
      {name: 'Not Found / Not Owned', status: 'Not Found', code: 404, body: notFound('Review not found.')}
    ]
  }),
  req({
    name: 'Mark Review Helpful',
    method: 'POST', path: '/reviews/{{review_id}}/helpful',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Marked as helpful.', data: null}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Review not found.')}
    ]
  }),
  req({
    name: 'Create Review (on Order)',
    method: 'POST', path: '/orders/{{order_number}}/reviews',
    body: {mode: 'formdata', formdata: [
      {key: 'order_item_id', value: 'item-uuid-1', type: 'text'},
      {key: 'rating', value: '5', type: 'text'},
      {key: 'comment', value: 'Excellent sound quality and battery life!', type: 'text'},
      {key: 'images[]', type: 'file', src: []}
    ]},
    desc: '`rating` 1-5. Up to 5 images, 5MB each.',
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Review submitted.', data: {id: 'rev-uuid-2', rating: 5, comment: 'Excellent sound quality...', created_at: '2026-07-15T10:00:00+00:00'}}},
      {name: 'Order Not Found', status: 'Not Found', code: 404, body: notFound('Order not found.')},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({rating: ['The rating must be between 1 and 5.']})}
    ]
  }),
  req({
    name: 'List Product Reviews (Public)',
    method: 'GET', path: '/products/bluetooth-headphones-x200/reviews', auth: false,
    query: [{key: 'page', value: '1'}],
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
        id: 'rev-uuid-1', rating: 5, title: 'Great headphones', body: 'Excellent sound quality and battery life!', is_verified_purchase: true, helpful_count: 12, not_helpful_count: 1,
        reviewer_name: 'Y. M.', created_at: '2026-07-01T00:00:00+00:00', images: ['https://cdn.example.com/reviews/rev1.jpg'], listing_id: listingCard.listing_id,
        seller: {id: listingCard.vendor.id, store_name: 'TechHub Store'}, variant: {id: 'var-1', variant_name: 'Black / Standard', attributes: [{name: bilingual('Color', 'اللون'), value: bilingual('Black', 'أسود')}]},
        vendor_reply: {body: 'Thank you for your feedback!', created_at: '2026-07-02T00:00:00+00:00'}
      }], meta: {current_page: 1, last_page: 1, per_page: 15, total: 1, rating_breakdown: {5: 140, 4: 50, 3: 12, 2: 5, 1: 3}}}}},
      {name: 'Product Not Found', status: 'Not Found', code: 404, body: notFound('Product not found.')}
    ]
  }),
  req({
    name: 'List Refunds',
    method: 'GET', path: '/refunds',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [
      {id: 'rfd-uuid-1', order_number: 'ORD-000410', amount: 150.00, currency: 'EGP', reason: 'return_approved', refund_type: 'wallet', status: 'completed', created_at: '2026-07-10T09:00:00+00:00'}
    ], meta: {current_page: 1, last_page: 1, per_page: 15, total: 1}}}}]
  }),
  req({
    name: 'Get Refund Detail',
    method: 'GET', path: '/refunds/{{refund_id}}',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {id: 'rfd-uuid-1', order_number: 'ORD-000410', amount: 150.00, currency: 'EGP', reason: 'return_approved', refund_type: 'wallet', status: 'completed', created_at: '2026-07-10T09:00:00+00:00'}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Refund not found.')}
    ]
  }),
  req({
    name: 'List Warranty Claims',
    method: 'GET', path: '/warranty-claims',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'wc-uuid-1', claim_number: 'WC-000034', status: 'under_review', resolution: null, issue_type: 'defective', issue_description: 'The device stopped charging.',
      purchase_date: '2026-06-01', warranty_expires_at: '2027-06-01', covered_by_platform_warranty: true, evidence_files: [], resolved_at: null, created_at: '2026-07-15T10:00:00+00:00',
      product: {name: 'Bluetooth Headphones X200', image: listingCard.thumbnail}, vendor: {name: 'TechHub Store'}
    }], meta: {current_page: 1, last_page: 1, per_page: 15, total: 1}}}}]
  }),
  req({
    name: 'Create Warranty Claim',
    method: 'POST', path: '/warranty-claims',
    desc: '`issue_type` enum: defective, not_working, physical_damage, missing_parts, software_issue, other. `warranty_expires_at` required only if the order item has no active warranty purchase. Order item must belong to a `delivered` order.',
    body: {mode: 'formdata', formdata: [
      {key: 'order_item_id', value: 'item-uuid-1', type: 'text'},
      {key: 'issue_type', value: 'defective', type: 'text'},
      {key: 'issue_description', value: 'The device stopped charging after two weeks of normal use.', type: 'text'},
      {key: 'warranty_expires_at', value: '2027-06-01', type: 'text'},
      {key: 'evidence_files[]', type: 'file', src: []}
    ]},
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Warranty claim submitted.', data: {id: 'wc-uuid-2', claim_number: 'WC-000035', status: 'submitted', created_at: '2026-07-15T10:00:00+00:00'}}},
      {name: 'Order Item Not Eligible', status: 'Unprocessable Entity', code: 422, body: validationErr({order_item_id: ['This item is not eligible for a warranty claim (order not delivered or not owned by you).']})}
    ]
  }),
  req({
    name: 'Get Warranty Claim Detail',
    method: 'GET', path: '/warranty-claims/{{warranty_claim_id}}',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        id: 'wc-uuid-1', claim_number: 'WC-000034', status: 'under_review', resolution: null, issue_type: 'defective', issue_description: 'The device stopped charging.',
        purchase_date: '2026-06-01', warranty_expires_at: '2027-06-01', covered_by_platform_warranty: true, evidence_files: ['https://cdn.example.com/claims/evidence1.jpg'], resolved_at: null, created_at: '2026-07-15T10:00:00+00:00',
        product: {name: 'Bluetooth Headphones X200', image: listingCard.thumbnail}, vendor: {name: 'TechHub Store'},
        messages: [{id: 'wcm-uuid-1', sender_role: 'customer', message: 'Any update?', created_at: '2026-07-15T11:00:00+00:00'}]
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Warranty claim not found.')}
    ]
  }),
  req({
    name: 'Add Warranty Claim Message',
    method: 'POST', path: '/warranty-claims/{{warranty_claim_id}}/messages',
    body: raw({message: 'Just checking in on the status of this claim.'}),
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {id: 'wcm-uuid-2', sender_role: 'customer', message: 'Just checking in on the status of this claim.', created_at: '2026-07-15T12:00:00+00:00'}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Warranty claim not found.')},
      {name: 'Claim Closed', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'This claim is closed and no longer accepts messages.'}}
    ]
  }),
  req({
    name: 'List Warranty Purchases',
    method: 'GET', path: '/warranties',
    query: [{key: 'status', value: '', description: 'Enum: pending, active, expired, cancelled. Defaults to excluding cancelled.'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: [{
      id: 'wp-purch-uuid-1', status: 'active', coverage_starts_at: '2026-07-15', coverage_ends_at: '2027-07-15', price_paid_cents: 4900, currency: 'EGP',
      plan: {name: '1-Year Extended Warranty', duration_months: 12, duration_label: '1 Year', features: ['Free repairs', 'Free pickup & delivery']},
      product: {name: 'Bluetooth Headphones X200', sku: listingCard.sku}, order_id: 'order-uuid-1', created_at: '2026-07-15T10:10:00+00:00', is_claimable: true
    }]}}]
  }),
  req({
    name: 'Get Warranty Purchase Detail',
    method: 'GET', path: '/warranties/{{warranty_purchase_id}}',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        id: 'wp-purch-uuid-1', status: 'active', coverage_starts_at: '2026-07-15', coverage_ends_at: '2027-07-15', price_paid_cents: 4900, currency: 'EGP',
        plan: {name: '1-Year Extended Warranty', duration_months: 12, duration_label: '1 Year', features: ['Free repairs', 'Free pickup & delivery']},
        product: {name: 'Bluetooth Headphones X200', sku: listingCard.sku}, order_id: 'order-uuid-1', created_at: '2026-07-15T10:10:00+00:00', is_claimable: true
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Warranty purchase not found.')}
    ]
  }),
  req({
    name: 'List Support Tickets',
    method: 'GET', path: '/support/tickets',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'tkt-uuid-1', ticket_number: 'TKT-000551', category: 'order_issue', priority: 'normal', status: 'open', subject: 'Missing item in my order', created_at: '2026-07-15T09:00:00+00:00',
      resolved_at: null, satisfaction_rating: null, satisfaction_comment: null
    }], meta: {current_page: 1, last_page: 1, per_page: 15, total: 1}}}}]
  }),
  req({
    name: 'Create Support Ticket',
    method: 'POST', path: '/support/tickets',
    desc: '`category` enum: order_issue, payment_issue, account, technical, product_inquiry, policy, payout, catalog, other. `priority` enum: low, normal, high, urgent.',
    body: {mode: 'formdata', formdata: [
      {key: 'category', value: 'order_issue', type: 'text'},
      {key: 'subject', value: 'Missing item in my order', type: 'text'},
      {key: 'message', value: 'I ordered 2 headphones but only received 1.', type: 'text'},
      {key: 'priority', value: 'normal', type: 'text'},
      {key: 'order_number', value: 'ORD-000482', type: 'text'},
      {key: 'attachment', type: 'file', src: []}
    ]},
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Support ticket created.', data: {id: 'tkt-uuid-1', ticket_number: 'TKT-000551', category: 'order_issue', priority: 'normal', status: 'open', created_at: '2026-07-15T09:00:00+00:00'}}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({category: ['The selected category is invalid.'], subject: ['The subject field is required.']})}
    ]
  }),
  req({
    name: 'Get Support Ticket Detail',
    method: 'GET', path: '/support/tickets/TKT-000551',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        id: 'tkt-uuid-1', ticket_number: 'TKT-000551', category: 'order_issue', priority: 'normal', status: 'in_progress', subject: 'Missing item in my order', created_at: '2026-07-15T09:00:00+00:00',
        resolved_at: null, satisfaction_rating: null, satisfaction_comment: null,
        messages: [{id: 'tktm-uuid-1', sender_role: 'customer', message: 'I ordered 2 headphones but only received 1.', created_at: '2026-07-15T09:00:00+00:00', attachments: []}]
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Support ticket not found.')}
    ]
  }),
  req({
    name: 'Add Support Ticket Message',
    method: 'POST', path: '/support/tickets/TKT-000551/messages',
    body: {mode: 'formdata', formdata: [{key: 'message', value: 'Any update on this?', type: 'text'}, {key: 'attachment', type: 'file', src: []}]},
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {id: 'tktm-uuid-2', message: 'Any update on this?', created_at: '2026-07-15T12:00:00+00:00'}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Support ticket not found.')}
    ]
  }),
  req({
    name: 'Rate Support Ticket',
    method: 'PUT', path: '/support/tickets/TKT-000551/rate',
    body: raw({satisfaction_rating: 5, satisfaction_comment: 'Resolved quickly, thank you!'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Thanks for your feedback!', data: {id: 'tkt-uuid-1', satisfaction_rating: 5, satisfaction_comment: 'Resolved quickly, thank you!'}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Support ticket not found.')},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({satisfaction_rating: ['The satisfaction rating must be between 1 and 5.']})}
    ]
  })
]);

const notificationFolder = folder('Notifications', [
  req({
    name: 'List Notifications',
    method: 'GET', path: '/notifications',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'notif-uuid-1', type: 'order_status', data: {title_en: 'Order Shipped', title_ar: 'تم شحن الطلب', body_en: 'Your order ORD-000482 has shipped.', body_ar: 'تم شحن طلبك ORD-000482.', action_type: 'order', action_id: 'ORD-000482', icon: 'truck'}, is_read: false, created_at: '2026-07-15T14:00:00+00:00'
    }], meta: {current_page: 1, last_page: 1, per_page: 20, total: 1}, unread_count: 3}}}]
  }),
  req({
    name: 'Unread Notification Count',
    method: 'GET', path: '/notifications/unread-count',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {unread_count: 3}}}]
  }),
  req({
    name: 'Mark All Notifications Read',
    method: 'PATCH', path: '/notifications/read-all',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'All notifications marked as read.', data: null}}]
  }),
  req({
    name: 'Mark Notification Read',
    method: 'PATCH', path: '/notifications/{{notification_id}}/read',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {id: 'notif-uuid-1', type: 'order_status', is_read: true, created_at: '2026-07-15T14:00:00+00:00'}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Notification not found.')}
    ]
  }),
  req({
    name: 'Register Device Token (Push)',
    method: 'POST', path: '/notifications/device-token',
    desc: '`platform` enum: ios, android.',
    body: raw({token: 'fcm-device-token-abc123xyz', platform: 'android'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Device registered.', data: null}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({platform: ['The selected platform is invalid.']})}
    ]
  }),
  req({
    name: 'Remove Device Token',
    method: 'DELETE', path: '/notifications/device-token',
    body: raw({token: 'fcm-device-token-abc123xyz'}),
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Device removed.', data: null}}]
  })
]);

fs.writeFileSync('/tmp/gen_part5.json', JSON.stringify({postSaleFolder, notificationFolder}, null, 2));
console.log('part5 written:', postSaleFolder.item.length, notificationFolder.item.length);

// ─────────────────────────────────────────────────────────────────────────
// ACCOUNT: dashboard, classified-listings (as seller), travel-bookings, inquiries (as buyer)
// ─────────────────────────────────────────────────────────────────────────
const classifiedListingDetail = {
  id: 'cls-uuid-1', listing_number: 'CL-000482', title: bilingual('2BR Apartment for Rent', 'شقة غرفتين للإيجار'), description: bilingual('Fully furnished, near metro.', 'مفروشة بالكامل، قريبة من المترو.'),
  listing_purpose: 'rent', price_cents: 1200000, currency: 'EGP', price_negotiable: true, attributes: {bedrooms: 2, bathrooms: 1, area_sqm: 120}, latitude: 30.0596, longitude: 31.3238,
  status: 'active', rejection_reason: null, views_count: 342, expires_at: '2026-08-15T00:00:00+00:00', created_at: '2026-07-01T09:00:00+00:00',
  country_id: 1, city_id: addressExample.city_id, category: {id: 'cls-cat-uuid-1', name: bilingual('Real Estate', 'عقارات'), slug: 'real-estate'},
  images: [{id: 'img-c1', url: 'https://cdn.example.com/classifieds/apt1.jpg', position: 0, is_primary: true}],
  sketch_file_url: null, attachments: [], contract: {accepted_at: '2026-07-01T09:05:00+00:00', has_signature: true}, inquiries_count: 4
};

const accountFolder = folder('Account (Dashboard, My Listings, Travel Bookings, Inquiries)', [
  req({
    name: 'Account Dashboard',
    method: 'GET', path: '/account/dashboard',
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
      customer: {name: 'Youssef Magdy', member_since: '2023-02-11T10:00:00+00:00', loyalty_points: 860},
      orders_summary: {total_orders: 14, pending_orders: 1, total_spent: 3420.50},
      wallet: {balance_cents: 125000, currency: 'EGP'},
      recent_orders: [{order_number: 'ORD-000482', status: 'placed', total: 617.20, currency: 'EGP', placed_at: '2026-07-15T10:10:00+00:00'}],
      unread_notifications: 3
    }}}]
  }),
  req({
    name: 'List My Classified Listings',
    method: 'GET', path: '/account/classified-listings',
    query: [{key: 'status', value: '', description: 'Enum: draft, pending_contract, pending_review, active, paused, sold, expired, rejected'}, {key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'cls-uuid-1', listing_number: 'CL-000482', title: bilingual('2BR Apartment for Rent', 'شقة غرفتين للإيجار'), price_cents: 1200000, currency: 'EGP', status: 'active', views_count: 342, primary_image: 'https://cdn.example.com/classifieds/apt1.jpg', created_at: '2026-07-01T09:00:00+00:00'
    }], meta: {current_page: 1, last_page: 1, per_page: 20, total: 1}}}}]
  }),
  req({
    name: 'Create Classified Listing',
    method: 'POST', path: '/account/classified-listings',
    desc: '`listing_purpose` enum: sale, rent. `attributes` shape depends on `classified_category_id` (category-specific fields e.g. bedrooms, area_sqm for real estate).',
    body: {mode: 'formdata', formdata: [
      {key: 'classified_category_id', value: 'cls-cat-uuid-1', type: 'text'},
      {key: 'country_id', value: '1', type: 'text'},
      {key: 'city_id', value: addressExample.city_id, type: 'text'},
      {key: 'listing_purpose', value: 'rent', type: 'text'},
      {key: 'title_en', value: '2BR Apartment for Rent', type: 'text'},
      {key: 'title_ar', value: 'شقة غرفتين للإيجار', type: 'text'},
      {key: 'description_en', value: 'Fully furnished, near metro.', type: 'text'},
      {key: 'description_ar', value: 'مفروشة بالكامل، قريبة من المترو.', type: 'text'},
      {key: 'price_cents', value: '1200000', type: 'text'},
      {key: 'currency', value: 'EGP', type: 'text'},
      {key: 'price_negotiable', value: 'true', type: 'text'},
      {key: 'attributes[bedrooms]', value: '2', type: 'text'},
      {key: 'attributes[bathrooms]', value: '1', type: 'text'},
      {key: 'attributes[area_sqm]', value: '120', type: 'text'},
      {key: 'latitude', value: '30.0596', type: 'text'},
      {key: 'longitude', value: '31.3238', type: 'text'},
      {key: 'images[]', type: 'file', src: []},
      {key: 'sketch_file', type: 'file', src: []},
      {key: 'attachments[]', type: 'file', src: []}
    ]},
    responses: [
      {name: 'Success', status: 'Created', code: 201, body: {success: true, message: 'Listing submitted for review.', data: classifiedListingDetail}},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({images: ['The images field is required.'], listing_purpose: ['The selected listing purpose is invalid.']})}
    ]
  }),
  req({
    name: 'Get My Classified Listing Detail',
    method: 'GET', path: '/account/classified-listings/CL-000482',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: classifiedListingDetail}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Listing not found.')}
    ]
  }),
  req({
    name: 'Update My Classified Listing',
    method: 'PUT', path: '/account/classified-listings/CL-000482',
    desc: 'Only listings with status draft or rejected can be edited.',
    body: raw({title_en: '2BR Apartment for Rent - Renovated', title_ar: 'شقة غرفتين للإيجار - مجددة', price_cents: 1250000, price_negotiable: false}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Listing updated.', data: classifiedListingDetail}},
      {name: 'Not Editable', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Only draft or rejected listings can be edited.'}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Listing not found.')}
    ]
  }),
  req({
    name: 'Delete My Classified Listing',
    method: 'DELETE', path: '/account/classified-listings/CL-000482',
    desc: 'Only listings with status draft, rejected, or expired can be deleted.',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Listing deleted.', data: null}},
      {name: 'Not Deletable', status: 'Unprocessable Entity', code: 422, body: {success: false, message: 'Only draft, rejected, or expired listings can be deleted.'}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Listing not found.')}
    ]
  }),
  req({
    name: 'My Listing Inquiries (as Seller)',
    method: 'GET', path: '/account/classified-listings/CL-000482/inquiries',
    query: [{key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [
      {id: 'inq-uuid-1', buyer_name: 'A*** S.', message: 'Is this still available?', contact_phone: '+2010****234', status: 'new', created_at: '2026-07-15T10:00:00+00:00'}
    ], meta: {current_page: 1, last_page: 1, per_page: 20, total: 1}}}}]
  }),
  req({
    name: 'List My Travel Bookings',
    method: 'GET', path: '/account/travel-bookings',
    query: [{key: 'status', value: '', description: 'Enum: pending_documents, confirmed, cancelled, completed'}, {key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'booking-uuid-1', booking_number: 'TB-000915', status: 'confirmed', travelers_count: 2, total_price_cents: 7000000, currency: 'EGP', total_price_formatted: 'EGP 70,000.00',
      passport_uploaded: true, contract_signed_at: '2026-07-15T10:05:00+00:00', created_at: '2026-07-15T10:00:00+00:00',
      package: {id: '77777777-8888-4000-8000-000000000007', title: bilingual('Umrah Package - 10 Days', 'باقة عمرة - 10 أيام'), price_cents: 3500000, currency: 'EGP', agency: {id: 'agency-uuid-1', name: 'Al-Noor Travel'}, cover_image: 'https://cdn.example.com/travel/umrah1.jpg'}
    }], meta: {current_page: 1, last_page: 1, per_page: 20, total: 1}}}}]
  }),
  req({
    name: 'Get My Travel Booking Detail',
    method: 'GET', path: '/account/travel-bookings/{{travel_booking_id}}',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        id: 'booking-uuid-1', booking_number: 'TB-000915', status: 'confirmed', travelers_count: 2, total_price_cents: 7000000, currency: 'EGP', total_price_formatted: 'EGP 70,000.00',
        passport_uploaded: true, contract_signed_at: '2026-07-15T10:05:00+00:00', created_at: '2026-07-15T10:00:00+00:00',
        package: {id: '77777777-8888-4000-8000-000000000007', title: bilingual('Umrah Package - 10 Days', 'باقة عمرة - 10 أيام'), price_cents: 3500000, currency: 'EGP', agency: {id: 'agency-uuid-1', name: 'Al-Noor Travel'}, cover_image: 'https://cdn.example.com/travel/umrah1.jpg'}
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Booking not found.')}
    ]
  }),
  req({
    name: 'Cancel My Travel Booking',
    method: 'POST', path: '/account/travel-bookings/{{travel_booking_id}}/cancel',
    body: raw({reason: 'Change of travel plans.'}),
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, message: 'Booking cancelled.', data: {status: 'cancelled'}}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Booking not found.')},
      {name: 'Validation Error', status: 'Unprocessable Entity', code: 422, body: validationErr({reason: ['The reason field is required.']})}
    ]
  }),
  req({
    name: 'My Classified Inquiries (as Buyer)',
    method: 'GET', path: '/account/inquiries',
    query: [{key: 'status', value: '', description: 'Enum: new, replied, closed'}, {key: 'page', value: '1'}],
    responses: [{name: 'Success', status: 'OK', code: 200, body: {success: true, data: {items: [{
      id: 'inq-uuid-1', message: 'Is this still available?', contact_phone: '+201001234567', status: 'replied', created_at: '2026-07-15T10:00:00+00:00',
      listing: {id: 'cls-uuid-1', listing_number: 'CL-000482', title: bilingual('2BR Apartment for Rent', 'شقة غرفتين للإيجار'), status: 'active', primary_image: 'https://cdn.example.com/classifieds/apt1.jpg'}
    }], meta: {current_page: 1, last_page: 1, per_page: 20, total: 1}}}}]
  }),
  req({
    name: 'Get My Classified Inquiry Detail (as Buyer)',
    method: 'GET', path: '/account/inquiries/{{inquiry_id}}',
    responses: [
      {name: 'Success', status: 'OK', code: 200, body: {success: true, data: {
        id: 'inq-uuid-1', message: 'Is this still available?', contact_phone: '+201001234567', status: 'replied', created_at: '2026-07-15T10:00:00+00:00',
        listing: {id: 'cls-uuid-1', listing_number: 'CL-000482', title: bilingual('2BR Apartment for Rent', 'شقة غرفتين للإيجار'), status: 'active', primary_image: 'https://cdn.example.com/classifieds/apt1.jpg'}
      }}},
      {name: 'Not Found', status: 'Not Found', code: 404, body: notFound('Inquiry not found.')}
    ]
  })
]);

fs.writeFileSync('/tmp/gen_part6.json', JSON.stringify({accountFolder}, null, 2));
console.log('part6 written:', accountFolder.item.length);

// ─────────────────────────────────────────────────────────────────────────
// FINAL ASSEMBLY
// ─────────────────────────────────────────────────────────────────────────
const collection = {
  info: {
    name: 'Marketplace — Customer API',
    description: 'Customer-facing API for the marketplace platform (`routes/api_customer.php`).\n\nBase path: `/api/customer/v1/{country}` — `{country}` is a site code (e.g. `eg`, `sa`) resolved by the `detect.country` middleware.\n\n**Auth**: JWT via the `customer` guard. Call **Auth > Register** or **Auth > Login** first — a test script automatically captures `access_token` / `refresh_token` into collection variables, and every other request in this collection uses `{{access_token}}` as its Bearer token. **Auth > Refresh Token** rotates both automatically the same way.\n\n**Guest cart**: cart endpoints work without auth using the `X-Cart-Token` header; the response test script captures the server-issued guest token into `{{cart_token}}` automatically after the first call to **Cart & Wishlist > Get Cart** or **Add Item to Cart**.\n\n**Response envelope**: `{ "success": bool, "message": string, "data"?: any, "errors"?: object }`. Paginated endpoints return `data: { items: [...], meta: { current_page, last_page, per_page, total } }`.',
    schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
  },
  item: [
    authFolder,
    profileFolder,
    securityFolder,
    catalogFolder,
    cartFolder,
    checkoutFolder,
    addressFolder,
    paymentMethodFolder,
    giftCardFolder,
    postSaleFolder,
    notificationFolder,
    accountFolder
  ],
  variable: [
    {key: 'base_url', value: 'http://localhost:8000', type: 'string'},
    {key: 'country', value: 'egy', type: 'string', description: 'Site code, e.g. egy, ksa, uae'},
    {key: 'access_token', value: '', type: 'string'},
    {key: 'refresh_token', value: '', type: 'string'},
    {key: 'cart_token', value: '', type: 'string'},
    {key: 'session_id', value: 'guest-session-abc123', type: 'string'},
    {key: 'order_number', value: 'ORD-000482', type: 'string'},
    {key: 'device_token_id', value: '55555555-6666-4000-8000-000000000005', type: 'string'},
    {key: 'cart_item_id', value: 'ci-uuid-1', type: 'string'},
    {key: 'block_id', value: 'b1a2c3d4-1111-4000-8000-000000000011', type: 'string'},
    {key: 'category_id', value: 'cat-electronics-uuid', type: 'string'},
    {key: 'vendor_id', value: '33333333-4444-4000-8000-000000000003', type: 'string'},
    {key: 'brand_id', value: 'brand-uuid-1', type: 'string'},
    {key: 'payment_method_id', value: 'pm-uuid-1', type: 'string'},
    {key: 'sub_order_id', value: 'sub-uuid-1', type: 'string'},
    {key: 'review_id', value: 'rev-uuid-1', type: 'string'},
    {key: 'refund_id', value: 'rfd-uuid-1', type: 'string'},
    {key: 'warranty_claim_id', value: 'wc-uuid-1', type: 'string'},
    {key: 'warranty_purchase_id', value: 'wp-purch-uuid-1', type: 'string'},
    {key: 'notification_id', value: 'notif-uuid-1', type: 'string'},
    {key: 'travel_booking_id', value: 'booking-uuid-1', type: 'string'},
    {key: 'inquiry_id', value: 'inq-uuid-1', type: 'string'}
  ],
  auth: {type: 'bearer', bearer: [{key: 'token', value: '{{access_token}}', type: 'string'}]},
  event: []
};

const outPath = process.argv[2] || __dirname + '/Marketplace Customer API.postman_collection.json';
fs.writeFileSync(outPath, JSON.stringify(collection, null, 2));
const totalItems = collection.item.reduce((sum, f) => sum + f.item.length, 0);
console.log('FINAL collection written to', outPath, '- folders:', collection.item.length, '- total requests:', totalItems);
