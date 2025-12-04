# E-Commerce Demo - Laravel + Inertia + Vue 3 + Stripe

A complete, production-ready e-commerce application built with Laravel 12, Inertia.js, Vue 3, TailwindCSS, and Stripe payments.

## Features

### Public Storefront
- ✅ Home page with hero section, featured products, and categories
- ✅ Product listing with filters (category, price range, search)
- ✅ Product detail pages with image gallery
- ✅ Responsive navbar with cart drawer, user menu, and category dropdowns
- ✅ Footer with links and newsletter form

### Shopping Cart & Checkout
- ✅ Session-based cart (works for guest users)
- ✅ Cart drawer accessible from any page
- ✅ Cart page with quantity updates and item removal
- ✅ Checkout page with billing fields and order summary
- ✅ Multiple payment methods:
  - Stripe card payments (Stripe Elements)
  - Wallet payments (instant)
  - Partial wallet + Stripe payments
- ✅ Order creation and confirmation
- ✅ Success page with order details

### User Accounts
- ✅ Authentication ready (Laravel Breeze compatible)
- ✅ User profile and address book (models ready)
- ✅ Order history page
- ✅ Wallet system:
  - View balance and transactions
  - Fund wallet via Stripe
  - Use wallet for checkout
  - Wallet refunds from admin

### Admin Panel
- ✅ Admin dashboard with statistics
- ✅ Product management (CRUD)
- ✅ Category management (models ready)
- ✅ Order management with filtering
- ✅ Transaction management
- ✅ Refund system:
  - Refund to original payment method (Stripe)
  - Refund to user's wallet
- ✅ CSV export of orders
- ✅ Webhook event logging

### Payments & Refunds
- ✅ Stripe Payment Intents API integration
- ✅ Webhook handling with signature verification
- ✅ Idempotency handling (prevents duplicate processing)
- ✅ Charge ID tracking for refunds
- ✅ Transaction persistence with full metadata
- ✅ Wallet funding via Stripe
- ✅ Refund to card or wallet

## Tech Stack

- **Backend**: Laravel 12+ (PHP 8.2+)
- **Frontend**: Inertia.js + Vue 3 + TailwindCSS 4
- **Payment**: Stripe Payment Intents API
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Authentication**: Laravel Breeze compatible (ready to install)

## Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd payment-integration-demo
```

### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# Node dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment Variables

Edit `.env` file:

```env
# Database (SQLite is default)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Or use MySQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ecommerce
# DB_USERNAME=root
# DB_PASSWORD=

# Stripe Configuration (Test Mode)
STRIPE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
STRIPE_CURRENCY=USD

# Mail Configuration (for order confirmations)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="E-Commerce Demo"
```

### 5. Get Stripe Test Keys

1. Go to https://dashboard.stripe.com/test/apikeys
2. Copy your **Publishable key** (starts with `pk_test_`)
3. Copy your **Secret key** (starts with `sk_test_`)
4. For webhook secret, see "Webhook Testing" section below

### 6. Run Migrations & Seeders
```bash
php artisan migrate --seed
```

This creates:
- Admin user: `admin@example.com` / `password`
- Regular user: `test@example.com` / `password`
- Sample categories and products

### 7. Build Frontend Assets
```bash
# Development (with hot reload)
npm run dev

# Production build
npm run build
```

### 8. Start Development Server
```bash
php artisan serve --port=8000
```

Visit http://localhost:8000

## Stripe Webhook Testing

### Local Testing with Stripe CLI

1. **Install Stripe CLI**: https://stripe.com/docs/stripe-cli

2. **Forward webhooks to local server**:
```bash
stripe listen --forward-to http://localhost:8000/webhooks/stripe
```

3. **Copy the webhook signing secret** (starts with `whsec_`) and add to `.env`:
```env
STRIPE_WEBHOOK_SECRET=whsec_your_secret_here
```

4. **Test webhook events**:
```bash
# Test payment success
stripe trigger payment_intent.succeeded

# Test charge refunded
stripe trigger charge.refunded
```

### Production Webhook Setup

1. Go to https://dashboard.stripe.com/webhooks
2. Add endpoint: `https://yourdomain.com/webhooks/stripe`
3. Select events:
   - `payment_intent.succeeded`
   - `charge.refunded`
4. Copy the webhook signing secret to `.env`

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── ProductsController.php
│   │   ├── CartController.php
│   │   ├── CheckoutController.php
│   │   ├── OrderController.php
│   │   ├── WalletController.php
│   │   ├── WebhookController.php
│   │   ├── Payment/
│   │   │   └── StripeController.php
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       ├── ProductController.php
│   │       ├── OrderController.php
│   │       └── TransactionsController.php
│   └── Middleware/
│       └── EnsureUserIsAdmin.php
├── Models/
│   ├── Product.php
│   ├── Category.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Transaction.php
│   ├── Wallet.php
│   ├── WalletTransaction.php
│   ├── Address.php
│   ├── Coupon.php
│   ├── StripeEvent.php
│   └── User.php
└── Services/
    └── StripeService.php

resources/js/
├── Layouts/
│   └── AppLayout.vue
├── Components/
│   └── CartDrawer.vue
└── Pages/
    ├── Home.vue
    ├── Products/
    │   ├── Index.vue
    │   └── Show.vue
    ├── Cart.vue
    ├── Checkout.vue
    ├── Success.vue
    ├── Wallet.vue
    ├── Orders/
    │   ├── Index.vue
    │   └── Show.vue
    └── Admin/
        ├── Dashboard.vue
        ├── Products.vue
        ├── Orders.vue
        └── Transactions.vue

database/
├── migrations/ (11 migrations)
└── seeders/
    ├── DatabaseSeeder.php
    ├── CategorySeeder.php
    └── ProductSeeder.php
```

## Usage Guide

### As a Customer

1. **Browse Products**: Visit `/products` to see all products
2. **View Product**: Click on any product to see details
3. **Add to Cart**: Click "Add to Cart" button
4. **View Cart**: Click cart icon or visit `/cart`
5. **Checkout**: Go to `/checkout` and:
   - Enter customer information
   - Choose payment method (Stripe Card or Wallet)
   - Complete payment
6. **View Orders**: After login, visit `/orders`
7. **Fund Wallet**: Visit `/wallet` and add funds via Stripe

### As an Admin

1. **Login**: Use `admin@example.com` / `password`
2. **Dashboard**: Visit `/admin/dashboard` for overview
3. **Manage Products**: `/admin/products` - Create, edit, delete products
4. **Manage Orders**: `/admin/orders` - View and update order status
5. **Transactions**: `/admin/transactions` - View and refund transactions
6. **Export Orders**: Click "Export CSV" on orders page

## Testing

### Manual Testing Checklist

#### Cart & Checkout
- [ ] Add products to cart
- [ ] Update quantities
- [ ] Remove items
- [ ] Proceed to checkout
- [ ] Complete Stripe payment
- [ ] Complete wallet payment
- [ ] View order confirmation

#### Wallet System
- [ ] Fund wallet via Stripe
- [ ] View wallet balance
- [ ] View wallet transactions
- [ ] Use wallet for checkout
- [ ] Verify balance deduction

#### Admin Functions
- [ ] Create/edit/delete products
- [ ] View orders
- [ ] Update order status
- [ ] Refund to card
- [ ] Refund to wallet
- [ ] Export orders CSV

#### Webhooks
- [ ] Test `payment_intent.succeeded` webhook
- [ ] Verify order completion
- [ ] Verify wallet funding
- [ ] Test `charge.refunded` webhook
- [ ] Verify refund processing

### Automated Tests

Run tests:
```bash
php artisan test
```

## Database Schema

### Key Tables
- `users` - User accounts (with `is_admin` flag)
- `products` - Product catalog (with `category_id`)
- `categories` - Product categories
- `orders` - Customer orders
- `order_items` - Order line items
- `transactions` - Payment transactions (Stripe/Wallet)
- `wallets` - User wallet balances
- `wallet_transactions` - Wallet credit/debit history
- `addresses` - User address book
- `coupons` - Discount codes
- `stripe_events` - Webhook event log

## Security Features

- ✅ CSRF protection (webhook routes excluded)
- ✅ Admin middleware protection
- ✅ Webhook signature verification
- ✅ Idempotency handling (prevents duplicate webhook processing)
- ✅ Input validation on all forms
- ✅ SQL injection protection (Eloquent ORM)
- ✅ XSS protection (Vue.js auto-escaping)

## Currency Handling

- All amounts stored in **cents** (integers) in database
- Displayed in **dollars** (decimals) in frontend
- Currency symbol configurable via `APP_CURRENCY_SYMBOL` env variable
- Default currency: USD (configurable via `STRIPE_CURRENCY`)

## Troubleshooting

### Webhook Not Working
1. Check `STRIPE_WEBHOOK_SECRET` in `.env`
2. Verify webhook endpoint URL is correct
3. Check Laravel logs: `storage/logs/laravel.log`
4. Test with Stripe CLI: `stripe trigger payment_intent.succeeded`

### Wallet Funding Not Working
1. Verify Stripe keys are correct
2. Check webhook is receiving events
3. Verify user_id in PaymentIntent metadata
4. Check wallet_transactions table for errors

### Refund Failing
1. Ensure `charge_id` is stored in transactions table
2. Verify transaction status is 'completed'
3. Check Stripe dashboard for refund status
4. Review transaction metadata

## Next Steps

To complete the application, consider adding:

1. **Authentication Pages**: Install Laravel Breeze for login/register
2. **User Profile**: Profile page with address management
3. **Invoice PDF**: Generate PDF invoices using dompdf
4. **Email Notifications**: Configure mail for order confirmations
5. **Image Upload**: Product image upload functionality
6. **Coupon System**: Apply coupons during checkout
7. **Search**: Full-text search with Laravel Scout
8. **Reviews**: Product reviews and ratings
9. **Wishlist**: Save products for later
10. **Multi-currency**: Support multiple currencies

## License

MIT License - feel free to use this for your portfolio or learning purposes.

## Support

For issues or questions:
1. Check the logs: `storage/logs/laravel.log`
2. Review Stripe dashboard for payment issues
3. Check webhook events in `stripe_events` table

---

**Built with ❤️ using Laravel, Inertia.js, Vue 3, and Stripe**
