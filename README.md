# E-Commerce Demo - Laravel + Inertia + Vue 3 + Stripe

A complete, production-ready e-commerce application built with Laravel 12, Inertia.js, Vue 3, TailwindCSS, and Stripe payments.

## ✨ Features

### 🛍️ Storefront
- **Home Page** - Hero section, featured products, category showcase
- **Product Catalog** - Listing with filters (category, price, search), pagination
- **Product Details** - Full product information with image gallery
- **Responsive Design** - Mobile-first, works on all devices

### 🛒 Shopping Cart
- **Session-based Cart** - Works for guest and authenticated users
- **Cart Drawer** - Slide-out cart accessible from any page
- **Cart Management** - Add, update quantities, remove items

### 💳 Checkout & Payments
- **Multiple Payment Methods**:
  - 💳 Stripe Card Payments (Stripe Elements)
  - 🌍 Paymob Payments (iframe integration)
  - 💰 Wallet Payments (instant)
  - 🔄 Partial Wallet + Stripe
- **Order Management** - Order creation, tracking, history
- **Payment Processing** - Secure server-side PaymentIntent creation

### 👤 User Accounts
- **Authentication** - Login, register, logout
- **Order History** - View past orders with details
- **Wallet System**:
  - View balance and transactions
  - Fund wallet via Stripe
  - Use wallet for checkout
  - Receive refunds to wallet

### 🔐 Admin Panel
- **Dashboard** - Sales statistics and overview
- **Product Management** - CRUD operations for products
- **Order Management** - View, filter, update order status
- **Transaction Management** - View all payments, process refunds
- **Refund System**:
  - Refund to original payment method (Stripe)
  - Refund to user's wallet
- **CSV Export** - Export orders data

### 🔔 Webhooks & Integrations
- **Stripe Webhooks** - Signature verification, idempotency handling
- **Event Logging** - Track all webhook events
- **Charge Tracking** - Store charge_id for refunds

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm
- Stripe account (test mode)

### Installation

```bash
# 1. Install dependencies
composer install
npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Configure payment provider keys in .env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Paymob configuration (optional)
PAYMOB_API_KEY=your_paymob_api_key
PAYMOB_IFRAME_ID=your_iframe_id
PAYMOB_INTEGRATOR=your_integration_id
PAYMOB_HMAC=your_hmac_secret
PAYMOB_API_BASE=https://accept.paymob.com/api

# 4. Run migrations and seeders
php artisan migrate --seed

# 5. Build frontend
npm run dev

# 6. Start server
php artisan serve --port=8000
```

### Stripe Webhook Setup (Local)

```bash
# Install Stripe CLI: https://stripe.com/docs/stripe-cli
stripe listen --forward-to http://localhost:8000/webhooks/stripe

# Copy webhook secret to .env
STRIPE_WEBHOOK_SECRET=whsec_...

# Test webhook
stripe trigger payment_intent.succeeded
```

### Paymob Integration Setup

Paymob is integrated as a second payment provider alongside Stripe. Follow these steps to set it up:

#### 1. Get Paymob Credentials

1. Sign up for a Paymob account at https://paymob.com
2. Navigate to your Paymob dashboard
3. Get the following credentials:
   - **API Key** (`PAYMOB_API_KEY`) - Your authentication API key
   - **Iframe ID** (`PAYMOB_IFRAME_ID`) - Your iframe integration ID
   - **Integration ID** (`PAYMOB_INTEGRATOR`) - Your payment integration ID
   - **HMAC Secret** (`PAYMOB_HMAC`) - Secret key for webhook signature verification

#### 2. Configure Environment Variables

Add these to your `.env` file:

```env
PAYMOB_API_KEY=your_paymob_api_key_here
PAYMOB_IFRAME_ID=your_iframe_id_here
PAYMOB_INTEGRATOR=your_integration_id_here
PAYMOB_HMAC=your_hmac_secret_here
PAYMOB_API_BASE=https://accept.paymob.com/api
```

**Note:** Use test mode credentials first. Paymob provides test credentials in their sandbox environment.

#### 3. Run Migrations

```bash
php artisan migrate
```

This will add `payment_key` and `raw_response` columns to the `transactions` table.

#### 4. Configure Webhook/Callback URL

In your Paymob dashboard, configure the callback/webhook URL:

**For local development (using ngrok):**
```bash
# Install ngrok: https://ngrok.com
ngrok http 8000

# Use the ngrok URL in Paymob dashboard:
# https://your-ngrok-url.ngrok.io/webhooks/paymob
```

**For production:**
```
https://yourdomain.com/webhooks/paymob
```

#### 5. Testing Paymob Integration

**Test Payment Flow:**

1. Add items to cart and proceed to checkout
2. Select "Pay with Paymob" as payment method
3. Complete the order form and submit
4. You'll be redirected to the Paymob iframe page
5. Complete payment using Paymob test cards
6. Paymob will send a callback to `/webhooks/paymob`
7. Order status will update to "paid" upon successful payment

**Test Cards (Paymob Sandbox):**
- Use test card numbers provided by Paymob in their documentation
- Common test cards: `4987654321098769` (adjust based on Paymob's test cards)

**Test Callback Locally:**

```bash
# Using ngrok to expose local server
ngrok http 8000

# Configure Paymob webhook URL to: https://your-ngrok-url.ngrok.io/webhooks/paymob

# Or test manually with curl:
curl -X POST http://localhost:8000/webhooks/paymob \
  -H "Content-Type: application/json" \
  -d '{
    "id": "charge_12345",
    "order": {"id": "123456"},
    "success": true,
    "amount_cents": 1000,
    "hmac": "computed_hmac_signature"
  }'
```

#### 6. Paymob API Flow

The integration follows this flow:

1. **Authentication** - `POST /auth/tokens` - Get auth token using API key
2. **Create Order** - `POST /ecommerce/orders` - Create Paymob order with amount
3. **Create Payment Key** - `POST /acceptance/payment_keys` - Generate payment token
4. **Generate Iframe URL** - Construct iframe URL with payment token
5. **User Payment** - User completes payment in Paymob iframe
6. **Callback/Webhook** - Paymob sends callback with transaction result
7. **HMAC Verification** - Verify callback signature using HMAC secret
8. **Update Transaction** - Store payment result and update order status

#### 7. Refunds

Paymob refunds can be processed via the admin panel:

1. Navigate to `/admin/transactions`
2. Find the Paymob transaction
3. Click "Refund" button
4. System calls Paymob refund API and updates transaction status

**Note:** Refund API endpoint may vary. Adjust `PaymobService::refund()` method based on Paymob's actual refund API.

#### 8. Troubleshooting Paymob

**Issue: Payment iframe doesn't load**
- Verify `PAYMOB_IFRAME_ID` is correct
- Check browser console for CORS errors
- Ensure Paymob allows your domain in iframe settings

**Issue: Callback not received**
- Verify webhook URL is correctly configured in Paymob dashboard
- Check ngrok is running (for local testing)
- Review Laravel logs: `storage/logs/laravel.log`

**Issue: HMAC verification fails**
- Verify `PAYMOB_HMAC` secret matches Paymob dashboard
- Check Paymob documentation for HMAC computation order
- Adjust `PaymobService::verifyHmac()` method if Paymob uses different field order

**Issue: Transaction not found in callback**
- Ensure `payment_key` or `paymob_order_id` matches in callback payload
- Check transaction was created before callback arrives
- Review callback payload structure in logs

#### 9. Paymob Documentation

Refer to Paymob's official documentation for:
- API endpoint details
- HMAC signature computation
- Webhook payload structure
- Test card numbers
- Refund API endpoints

**Paymob API Base URL:** `https://accept.paymob.com/api`

#### 10. Security Best Practices

- ✅ Never commit Paymob credentials to version control
- ✅ Use environment variables for all secrets
- ✅ Verify HMAC signature on all callbacks
- ✅ Store raw callback payloads for audit trail
- ✅ Use HTTPS in production
- ✅ Test thoroughly in sandbox before going live

## 📋 Default Credentials

**Admin User:**
- Email: `admin@example.com`
- Password: `password`

**Test User:**
- Email: `test@example.com`
- Password: `password`

## 🏗️ Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Inertia.js + Vue 3 + TailwindCSS 4
- **Payment**: Stripe Payment Intents API, Paymob iframe integration
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Authentication**: Laravel session-based auth

## 📁 Project Structure

```
app/
├── Http/Controllers/
│   ├── Auth/              # Authentication
│   ├── Admin/             # Admin panel
│   ├── Payment/           # Stripe integration
│   └── ...                # Public controllers
├── Models/                # Eloquent models
├── Services/              # StripeService
└── Middleware/           # Auth, admin protection

resources/js/
├── Layouts/              # AppLayout with navbar/footer
├── Components/           # CartDrawer, etc.
└── Pages/                # Vue pages
    ├── Auth/             # Login, Register
    ├── Products/          # Index, Show
    ├── Admin/            # Dashboard, Products, Orders
    └── ...
```

## 🎯 Key Features Implemented

✅ **Complete Storefront** - Home, products, cart, checkout  
✅ **Stripe Integration** - Payment Intents, Elements, webhooks  
✅ **Wallet System** - Funding, checkout, refunds  
✅ **Admin Panel** - Products, orders, transactions management  
✅ **Refund System** - To card or wallet  
✅ **Order Management** - Tracking, status updates, CSV export  
✅ **User Authentication** - Login, register, profile  
✅ **Responsive UI** - Mobile-friendly, TailwindCSS  
✅ **Webhook Handling** - Signature verification, idempotency  

## 🔧 Configuration

### Environment Variables

```env
# Stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CURRENCY=USD

# Database (SQLite default)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Mail (for order confirmations)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
```

## 📝 Testing

```bash
# Run tests
php artisan test

# Test Stripe webhooks locally
stripe listen --forward-to http://localhost:8000/webhooks/stripe
stripe trigger payment_intent.succeeded
```

## 🎨 UI Features

- **Cart Drawer** - Slide-out cart from right
- **Responsive Navbar** - Mobile menu, user dropdown
- **Product Cards** - Hover effects, quick view
- **Filter System** - Category, price range, search
- **Status Badges** - Color-coded order/transaction status
- **Toast Notifications** - Success/error messages

## 🔒 Security

- CSRF protection (webhook routes excluded)
- Admin middleware protection
- Webhook signature verification
- Input validation on all forms
- SQL injection protection (Eloquent)
- XSS protection (Vue auto-escaping)
- **Duplicate charge protection** (see below)

## 🛡️ Preventing Duplicate Charges (Double Clicks & Retries)

The application implements comprehensive duplicate charge protection across frontend, backend, database, and webhooks to prevent accidental double charges from:
- User double-clicking the payment button
- Network retries
- Browser back/forward navigation
- Duplicate webhook deliveries

### Frontend Protection

**Checkout.vue** implements:
- ✅ Payment button disabled after first click with loading state
- ✅ UUID v4 idempotency key generation and reuse (5-minute TTL)
- ✅ Form submission prevention (Enter key disabled during processing)
- ✅ Idempotency key sent in `Idempotency-Key` header to backend

**How it works:**
1. On checkout, frontend generates a UUID v4 idempotency key
2. Key is reused for 5 minutes if user retries
3. Key is sent in `Idempotency-Key` header to `/api/payment/stripe/create-intent`
4. Button shows loading spinner and is disabled during processing

### Backend Protection

**StripeController** implements:
- ✅ Validates `Idempotency-Key` header (required, must be UUID)
- ✅ Checks for existing successful transaction (returns existing PaymentIntent)
- ✅ Checks for existing payment attempt with same key (returns existing client_secret)
- ✅ Uses Stripe idempotency key in PaymentIntent creation
- ✅ Stores payment attempts in `payment_attempts` table

**Payment Attempt Flow:**
1. Request arrives with `Idempotency-Key` header
2. Check if order already has successful transaction → return existing
3. Check `payment_attempts` table for same key → return existing client_secret
4. Create new PaymentIntent with Stripe idempotency key
5. Store attempt in `payment_attempts` table
6. Update order with `last_idempotency_key` and timestamp

### Database Protection

**Unique Constraints:**
- `payment_attempts`: Unique on `(order_id, idempotency_key)` - prevents duplicate attempts
- `transactions`: Unique on `(order_id, payment_provider, transaction_id)` - prevents duplicate transaction records

**Tables:**
- `payment_attempts`: Tracks all payment attempts with idempotency keys
- `orders`: Stores `last_idempotency_key` and `last_payment_attempt_at` for quick lookups
- `stripe_events`: Stores webhook event IDs for deduplication

### Webhook Protection

**WebhookController** implements:
- ✅ Event ID deduplication using `stripe_events` table
- ✅ Checks if event already processed before handling
- ✅ Prevents duplicate transaction creation
- ✅ Auto-refund duplicate charges (optional, disabled by default)

**Event Processing Flow:**
1. Webhook arrives with Stripe signature
2. Verify signature
3. Check `stripe_events` table for `event.id`
4. If already processed → return 200, skip processing
5. Store event record
6. Process event (create/update transaction)
7. Mark event as processed

**Duplicate Charge Detection:**
- If duplicate `payment_intent.succeeded` webhook received:
  - Check if transaction already exists with same `payment_intent_id`
  - If exists and `status = completed` → log warning, skip processing
  - If `AUTO_REFUND_DUPLICATES=true` → attempt automatic refund

### Refund Protection

**TransactionsController** implements:
- ✅ Idempotent refund: checks transaction status before refunding
- ✅ If already refunded → returns `already_refunded` status
- ✅ Prevents duplicate Stripe refund API calls

### Configuration

**Environment Variables:**

```env
# Enable automatic refund of duplicate charges (default: false)
# WARNING: Only enable if you understand the implications
AUTO_REFUND_DUPLICATES=false
```

**Testing Duplicate Prevention:**

```bash
# 1. Start server
php artisan serve --port=8000

# 2. Forward webhooks
stripe listen --forward-to http://localhost:8000/webhooks/stripe

# 3. Test double-click protection:
# - Go to checkout page
# - Click "Pay" button twice quickly
# - Verify only one PaymentIntent created
# - Check browser console for idempotency key reuse

# 4. Test webhook deduplication:
# - Trigger same webhook twice:
stripe trigger payment_intent.succeeded
stripe trigger payment_intent.succeeded

# - Verify only one transaction created in database
# - Check stripe_events table for duplicate event_id

# 5. Test refund idempotency:
# - Refund a transaction twice via admin panel
# - Verify second refund returns "already_refunded" status
```

### How to Test Locally

**1. Test Frontend Double-Click:**
```javascript
// In browser console on checkout page:
// Click pay button, then immediately click again
// Should see: "Using existing payment attempt" in response
```

**2. Test Backend Idempotency:**
```bash
# Send two requests with same idempotency key
IDEMPOTENCY_KEY=$(uuidgen)

curl -X POST http://localhost:8000/api/payment/stripe/create-intent \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $IDEMPOTENCY_KEY" \
  -H "X-CSRF-TOKEN: <token>" \
  -d '{"order_id": 1}'

# Send again with same key
curl -X POST http://localhost:8000/api/payment/stripe/create-intent \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $IDEMPOTENCY_KEY" \
  -H "X-CSRF-TOKEN: <token>" \
  -d '{"order_id": 1}'

# Should return same client_secret
```

**3. Test Webhook Deduplication:**
```bash
# Trigger same webhook event twice
stripe trigger payment_intent.succeeded --override payment_intent:metadata[order_id]=1
stripe trigger payment_intent.succeeded --override payment_intent:metadata[order_id]=1

# Check database:
php artisan tinker
>>> \App\Models\StripeEvent::where('event_id', '<event_id>')->count()
# Should be 1

>>> \App\Models\Transaction::where('order_id', 1)->count()
# Should be 1
```

### Best Practices

1. **Always use idempotency keys** - Frontend automatically generates and reuses them
2. **Monitor payment_attempts table** - Check for failed attempts
3. **Review stripe_events table** - Monitor webhook processing
4. **Keep AUTO_REFUND_DUPLICATES disabled** - Review duplicates manually first
5. **Test with Stripe test cards** - Use test mode for all testing

### Troubleshooting

**Issue: Multiple PaymentIntents created**
- Check if `Idempotency-Key` header is being sent
- Verify UUID format is correct
- Check `payment_attempts` table for duplicate keys

**Issue: Duplicate transactions in database**
- Check unique constraint on `transactions` table
- Verify webhook event deduplication is working
- Review `stripe_events` table for duplicate `event_id`

**Issue: Refund called twice**
- Check transaction status before refunding
- Verify idempotency check in refund endpoint
- Review logs for "already_refunded" messages

## 📊 Database Schema

- `users` - User accounts (with `is_admin` flag)
- `products` - Product catalog (with categories)
- `categories` - Product categories
- `orders` - Customer orders
- `order_items` - Order line items
- `transactions` - Payment transactions
- `wallets` - User wallet balances
- `wallet_transactions` - Wallet history
- `addresses` - User address book
- `coupons` - Discount codes
- `stripe_events` - Webhook event log

## 🚦 Routes

**Public:**
- `/` - Home page
- `/products` - Product listing
- `/products/{id}` - Product details
- `/cart` - Shopping cart
- `/checkout` - Checkout page
- `/login` - Login page
- `/register` - Registration page

**Authenticated:**
- `/orders` - Order history
- `/orders/{id}` - Order details
- `/wallet` - Wallet management

**Admin:**
- `/admin/dashboard` - Admin dashboard
- `/admin/products` - Product management
- `/admin/orders` - Order management
- `/admin/transactions` - Transaction management

## 💡 Usage Examples

### As Customer
1. Browse products → Add to cart → Checkout
2. Choose payment: Stripe card or Wallet
3. View orders in `/orders`
4. Fund wallet in `/wallet`

### As Admin
1. Login with admin credentials
2. Manage products in `/admin/products`
3. View orders in `/admin/orders`
4. Process refunds in `/admin/transactions`

## 📚 Documentation

- `COMPLETE_IMPLEMENTATION_GUIDE.md` - Detailed implementation notes
- `FILES_CREATED_SUMMARY.md` - List of all files created/updated

## 🐛 Troubleshooting

**Images not loading?**
- Check if image URLs are full URLs (https://) or relative paths
- Ensure storage link: `php artisan storage:link`

**Webhook not working?**
- Verify `STRIPE_WEBHOOK_SECRET` in `.env`
- Check Laravel logs: `storage/logs/laravel.log`
- Test with Stripe CLI

**Wallet funding fails?**
- Verify Stripe keys are correct
- Check webhook is receiving events
- Review `wallet_transactions` table

## 📄 License

MIT License - Free to use for portfolio and learning purposes.

---

**Built with ❤️ using Laravel, Inertia.js, Vue 3, and Stripe**
