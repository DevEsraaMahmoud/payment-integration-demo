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

# 3. Configure Stripe keys in .env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

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
- **Payment**: Stripe Payment Intents API
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
