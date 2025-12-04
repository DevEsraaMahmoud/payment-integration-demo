# Payment Integration Demo

A complete Laravel + Inertia.js + Vue 3 e-commerce application with Stripe payment integration for end-to-end testing.

## Features

- ✅ Session-based shopping cart
- ✅ Product listing and management
- ✅ Stripe Elements integration for secure card payments
- ✅ Payment Intent API (server-side)
- ✅ Webhook handling with signature verification
- ✅ Transaction persistence with charge_id tracking
- ✅ Admin panel for viewing and refunding transactions
- ✅ **User Wallet System** with funding and checkout integration
- ✅ Wallet funding via Stripe Payment Intents
- ✅ Wallet-based checkout (instant payments)
- ✅ Full refund support (Stripe and Wallet)
- ✅ Full test coverage with sample data

## Tech Stack

- **Backend**: Laravel 12+ with PHP 8.2+
- **Frontend**: Inertia.js + Vue 3 + TailwindCSS
- **Payment**: Stripe Payment Intents API
- **Database**: SQLite (default) or MySQL

## Installation

### 1. Clone and Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Stripe

Edit `.env` and add your Stripe test keys:

```env
STRIPE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
STRIPE_CURRENCY=USD
```

**Get your Stripe test keys:**
1. Go to https://dashboard.stripe.com/test/apikeys
2. Copy your **Publishable key** (starts with `pk_test_`)
3. Copy your **Secret key** (starts with `sk_test_`)
4. For webhook secret, see "Webhook Testing" section below

### 4. Database Setup

```bash
# Run migrations and seed sample products
php artisan migrate --seed
```

This will create:
- `products` table with 6 sample products
- `orders` table
- `order_items` table
- `transactions` table

### 5. Build Frontend Assets

```bash
# Development (with hot reload)
npm run dev

# Production build
npm run build
```

### 6. Start Development Server

```bash
# In one terminal, start Laravel
php artisan serve --port=8000

# In another terminal (if using npm run dev)
npm run dev
```

Visit: http://localhost:8000

## Webhook Testing

Stripe webhooks require a publicly accessible URL. For local development, use one of these methods:

### Option 1: Stripe CLI (Recommended)

```bash
# Install Stripe CLI: https://stripe.com/docs/stripe-cli

# Forward webhooks to your local server
stripe listen --forward-to http://localhost:8000/api/webhooks/stripe
```

This will output a webhook signing secret. Copy it to your `.env`:
```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

### Option 2: ngrok

```bash
# Install ngrok: https://ngrok.com/

# Expose local server
ngrok http 8000

# Use the ngrok URL in Stripe Dashboard:
# https://dashboard.stripe.com/test/webhooks
# Add endpoint: https://your-ngrok-url.ngrok.io/api/webhooks/stripe
# Copy the webhook signing secret to .env
```

### Testing Webhooks

```bash
# Trigger test events
stripe trigger payment_intent.succeeded
stripe trigger charge.refunded
```

## Stripe Test Cards

Use these test card numbers in the checkout form:

| Card Number | Description |
|------------|-------------|
| `4242 4242 4242 4242` | Visa - Success |
| `4000 0000 0000 9995` | Requires authentication (3D Secure) |
| `4000 0000 0000 0002` | Card declined |
| `4000 0025 0000 3155` | Requires authentication |

**Test Details:**
- **Expiry**: Any future date (e.g., 12/25)
- **CVC**: Any 3 digits (e.g., 123)
- **ZIP**: Any 5 digits (e.g., 12345)

## Usage Flow

1. **Browse Products** (`/products`)
   - View available products
   - Add items to cart

2. **View Cart** (`/cart`)
   - Review items
   - Update quantities
   - Proceed to checkout

3. **Fund Wallet** (`/wallet`) - Optional
   - View wallet balance
   - Fund wallet using Stripe
   - View transaction history

4. **Checkout** (`/checkout`)
   - Enter customer information
   - Choose payment method:
     - **Pay with Card**: Enter card details (Stripe Elements)
     - **Pay with Wallet**: Instant payment if balance is sufficient
   - Complete payment

5. **Success Page** (`/success`)
   - Order confirmation
   - Links to continue shopping or view transactions

6. **Admin Transactions** (`/admin/transactions`)
   - View all transactions (Stripe and Wallet)
   - Refund completed Stripe payments
   - View transaction status and details

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── CartController.php          # Cart management
│   │   ├── CheckoutController.php      # Order creation
│   │   ├── ProductsController.php      # Product listing
│   │   ├── WalletController.php       # Wallet management & funding
│   │   ├── WebhookController.php       # Stripe webhooks (orders & wallet)
│   │   ├── Payment/
│   │   │   └── StripeController.php   # Payment Intent creation
│   │   └── Admin/
│   │       └── TransactionsController.php  # Transaction management & refunds
│   └── Middleware/
│       └── VerifyCsrfToken.php         # CSRF protection (excludes webhooks)
├── Models/
│   ├── Product.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Transaction.php                 # Payment transactions (Stripe/Wallet)
│   ├── Wallet.php                      # User wallets
│   └── WalletTransaction.php           # Wallet credit/debit transactions
└── Services/
    └── StripeService.php               # Stripe API wrapper (PaymentIntent, Refund, Charge retrieval)

resources/js/Pages/
├── ProductsIndex.vue                   # Product listing
├── Cart.vue                            # Shopping cart
├── Checkout.vue                        # Payment form (Stripe Elements + Wallet option)
├── Wallet.vue                          # Wallet balance, funding, transactions
├── Success.vue                         # Success page
└── Admin/
    └── Transactions.vue                # Admin transactions list with refunds

database/
├── migrations/
│   ├── create_products_table.php
│   ├── create_orders_table.php
│   ├── create_order_items_table.php
│   ├── create_transactions_table.php
│   ├── add_charge_id_to_transactions_table.php  # Charge ID column
│   ├── create_wallets_table.php        # User wallets
│   └── create_wallet_transactions_table.php  # Wallet transactions
└── seeders/
    └── ProductSeeder.php                # 6 sample products
```

## API Endpoints

### Web Routes
- `GET /products` - Product listing
- `GET /cart` - View cart
- `POST /cart/add/{product}` - Add to cart
- `DELETE /cart/remove/{product}` - Remove from cart
- `PATCH /cart/update/{product}` - Update quantity
- `GET /checkout` - Checkout page
- `POST /checkout` - Create order (Stripe payment)
- `POST /checkout/wallet` - Process wallet checkout
- `GET /wallet` - Wallet balance and transactions
- `POST /wallet/fund` - Create PaymentIntent for wallet funding
- `GET /success` - Success page
- `GET /admin/transactions` - Admin transactions list
- `POST /admin/transactions/{id}/refund` - Process refund (Stripe only)

### API Routes
- `POST /api/payment/stripe/create-intent` - Create PaymentIntent for orders
- `POST /api/webhooks/stripe` - Stripe webhook endpoint (handles orders & wallet funding)

## Security Features

- ✅ CSRF protection (webhooks excluded)
- ✅ Webhook signature verification
- ✅ No card data stored on server
- ✅ Payment amounts stored in cents
- ✅ Transaction metadata for audit trail
- ✅ Charge ID properly stored for refunds
- ✅ Wallet balance validation before checkout

## Testing

### Manual Testing Checklist

1. ✅ Add products to cart
2. ✅ Update cart quantities
3. ✅ Remove items from cart
4. ✅ Create order with valid card
5. ✅ Test with declined card
6. ✅ Verify webhook updates order status
7. ✅ Process refund from admin panel
8. ✅ Verify refund webhook updates transaction

### Database Inspection

```bash
# Check orders
php artisan tinker
>>> App\Models\Order::all();

# Check transactions
>>> App\Models\Transaction::all();

# Check products
>>> App\Models\Product::all();
```

## Troubleshooting

### Webhook Not Working

1. Verify `STRIPE_WEBHOOK_SECRET` in `.env`
2. Check webhook endpoint is accessible
3. Verify signature verification in logs: `storage/logs/laravel.log`
4. Test with Stripe CLI: `stripe trigger payment_intent.succeeded`

### Payment Intent Creation Fails

1. Verify `STRIPE_SECRET` is set correctly
2. Check order exists and is in `pending` status
3. Verify amount is positive
4. Check Laravel logs for detailed errors

### Frontend Issues

1. Clear browser cache
2. Run `npm run build` to rebuild assets
3. Verify `STRIPE_KEY` is set in `.env`
4. Check browser console for JavaScript errors

## Configuration

After changing `.env`:
```bash
php artisan config:clear
php artisan cache:clear
```

## License

MIT License - feel free to use this project for learning and testing purposes.

## Support

For Stripe-related issues:
- Stripe Documentation: https://stripe.com/docs
- Stripe Support: https://support.stripe.com

For Laravel/Inertia issues:
- Laravel Documentation: https://laravel.com/docs
- Inertia.js Documentation: https://inertiajs.com
