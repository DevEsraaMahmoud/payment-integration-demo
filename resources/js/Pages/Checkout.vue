<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-4 sm:py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-4 sm:mb-6">
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-1 sm:mb-2">Checkout</h1>
          <p class="text-gray-600 text-sm sm:text-base">Complete your purchase securely</p>
        </div>

        <!-- Status Messages -->
        <div v-if="error" class="mb-3 sm:mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 px-3 sm:px-4 py-2 sm:py-3 rounded-r-xl shadow-lg">
          <div class="flex items-center">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span class="text-xs sm:text-sm">{{ error }}</span>
          </div>
        </div>
        <div v-if="processing" class="mb-3 sm:mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 px-3 sm:px-4 py-2 sm:py-3 rounded-r-xl shadow-lg">
          <div class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 sm:mr-3 h-4 w-4 sm:h-5 sm:w-5 text-blue-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs sm:text-sm">Processing payment...</span>
          </div>
        </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
        <!-- Order Summary -->
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-xl p-4 sm:p-6 lg:p-6 border border-gray-100 lg:sticky lg:top-20 lg:self-start lg:max-h-[calc(100vh-6rem)] lg:overflow-hidden lg:flex lg:flex-col">
          <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4 flex items-center flex-shrink-0">
            <svg class="w-5 h-5 mr-2 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Order Summary
          </h2>
          <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar mb-3 sm:mb-4">
            <div class="space-y-2 sm:space-y-3">
              <div
                v-for="item in items"
                :key="item.id"
                class="flex items-center justify-between p-2 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div class="flex items-center space-x-2 sm:space-x-3 flex-1 min-w-0">
                  <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                    <img 
                      :src="item.image || 'https://via.placeholder.com/200x200?text=' + encodeURIComponent(item.name)" 
                      :alt="item.name" 
                      class="w-full h-full object-cover"
                      @error="handleImageError"
                    >
                  </div>
                  <div class="flex-1 min-w-0">
                    <span class="font-semibold text-gray-900 block text-xs sm:text-sm truncate">{{ item.name }}</span>
                    <span class="text-xs text-gray-600">Qty: {{ item.quantity }}</span>
                  </div>
                </div>
                <span class="font-bold text-gray-900 text-xs sm:text-sm ml-2 flex-shrink-0">${{ item.subtotal.toFixed(2) }}</span>
              </div>
            </div>
          </div>
          <div class="border-t-2 border-gray-200 pt-3 sm:pt-4 flex-shrink-0">
            <div class="flex justify-between items-center text-lg sm:text-xl font-bold text-gray-900">
              <span>Total:</span>
              <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">${{ total.toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- Checkout Form -->
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-xl p-4 sm:p-6 lg:p-6 border border-gray-100">
          <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            Payment Details
          </h2>
          <form @submit.prevent="handleSubmit" class="space-y-0">
            <!-- Customer Information -->
            <div class="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                  Full Name *
                </label>
                <input
                  v-model="form.customer_name"
                  type="text"
                  required
                  class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border-2 border-gray-200 rounded-xl text-sm sm:text-base text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  placeholder="John Doe"
                />
              </div>
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                  Email *
                </label>
                <input
                  v-model="form.customer_email"
                  type="email"
                  required
                  class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border-2 border-gray-200 rounded-xl text-sm sm:text-base text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  placeholder="john@example.com"
                />
              </div>
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                  Phone
                </label>
                <input
                  v-model="form.customer_phone"
                  type="tel"
                  class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border-2 border-gray-200 rounded-xl text-sm sm:text-base text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  placeholder="+1 (555) 123-4567"
                />
              </div>
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                  Shipping Address
                </label>
                <textarea
                  v-model="form.shipping_address"
                  rows="2"
                  class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border-2 border-gray-200 rounded-xl text-sm sm:text-base text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                  placeholder="123 Main St, City, State, ZIP"
                ></textarea>
              </div>
            </div>

            <!-- Payment Method Selection -->
            <div class="mb-4 sm:mb-6">
              <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2 sm:mb-3">
                Payment Method *
              </label>
              <div class="space-y-2">
                <label class="flex items-center p-3 sm:p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all group">
                  <input
                    v-model="paymentMethod"
                    type="radio"
                    value="stripe"
                    class="mr-3 sm:mr-4 w-4 h-4 sm:w-5 sm:h-5 text-blue-600 focus:ring-blue-500 flex-shrink-0"
                  />
                  <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm sm:text-base text-gray-900 group-hover:text-blue-600 transition-colors">💳 Pay with Card</div>
                    <div class="text-xs sm:text-sm text-gray-600">Stripe secure payment</div>
                  </div>
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 group-hover:text-blue-600 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </label>
                <label class="flex items-center p-3 sm:p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all group">
                  <input
                    v-model="paymentMethod"
                    type="radio"
                    value="paymob"
                    class="mr-3 sm:mr-4 w-4 h-4 sm:w-5 sm:h-5 text-blue-600 focus:ring-blue-500 flex-shrink-0"
                  />
                  <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm sm:text-base text-gray-900 group-hover:text-blue-600 transition-colors">🌍 Pay with Paymob</div>
                    <div class="text-xs sm:text-sm text-gray-600">Secure payment gateway</div>
                  </div>
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 group-hover:text-blue-600 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </label>
                <label
                  v-if="walletBalance > 0"
                  class="flex items-center p-3 sm:p-4 border-2 rounded-xl cursor-pointer transition-all group"
                  :class="{
                    'border-gray-200 hover:border-blue-400 hover:bg-blue-50': canUseWallet,
                    'border-gray-100 opacity-50 cursor-not-allowed': !canUseWallet,
                  }"
                >
                  <input
                    v-model="paymentMethod"
                    type="radio"
                    value="wallet"
                    :disabled="!canUseWallet"
                    class="mr-3 sm:mr-4 w-4 h-4 sm:w-5 sm:h-5 text-blue-600 focus:ring-blue-500 flex-shrink-0"
                  />
                  <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm sm:text-base text-gray-900 group-hover:text-blue-600 transition-colors">💰 Pay with Wallet</div>
                    <div class="text-xs sm:text-sm text-gray-600">
                      Balance: ${{ walletBalance.toFixed(2) }}
                      <span v-if="!canUseWallet" class="text-red-600 block">
                        (Insufficient balance)
                      </span>
                    </div>
                  </div>
                  <svg v-if="canUseWallet" class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 group-hover:text-blue-600 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </label>
                <div v-if="walletBalance === 0" class="text-sm text-gray-500 p-2">
                  <a :href="route('wallet.index')" class="text-blue-600 hover:underline">
                    Fund your wallet
                  </a>
                  to use wallet payments
                </div>
              </div>
            </div>

            <!-- Stripe Card Element (only show if Stripe selected) -->
            <div v-if="paymentMethod === 'stripe'" class="mb-4">
              <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
                Card Details *
              </label>
              <div
                id="card-element"
                class="px-3 sm:px-4 py-2 sm:py-2.5 border-2 border-gray-200 rounded-xl focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent transition-all"
              ></div>
              <div id="card-errors" class="text-red-600 text-xs mt-1.5"></div>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="processing || (paymentMethod === 'stripe' && !stripeLoaded)"
              class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4 sm:px-6 rounded-xl hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed font-bold text-sm sm:text-base transition-all shadow-lg hover:shadow-xl hover:scale-[1.01] transform disabled:transform-none"
            >
              <span v-if="processing" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing payment...
              </span>
              <span v-else class="flex items-center justify-center">
                Complete Order - ${{ total.toFixed(2) }}
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
              </span>
            </button>
            
            <!-- Idempotency Key Info (for debugging) -->
            <div v-if="idempotencyKey && processing" class="mt-2 text-xs text-gray-500 text-center">
              <span class="font-mono">Idempotency Key: {{ idempotencyKey.substring(0, 8) }}...</span>
            </div>
          </form>
        </div>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { loadStripe } from '@stripe/stripe-js'
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

// Make route available in template
const route = ziggyRoute

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
  stripeKey: {
    type: String,
    required: true,
  },
  walletBalance: {
    type: Number,
    default: 0,
  },
  canUseWallet: {
    type: Boolean,
    default: false,
  },
})

const stripeLoaded = ref(false)
const processing = ref(false)
const error = ref('')
const paymentMethod = ref('stripe') // 'stripe', 'paymob', or 'wallet'
let stripe = null
let elements = null
let cardElement = null

// Idempotency key for duplicate charge prevention
const idempotencyKey = ref(null)
const idempotencyKeyExpiry = ref(null)
const IDEMPOTENCY_KEY_TTL = 5 * 60 * 1000 // 5 minutes

const form = ref({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  shipping_address: '',
})

// Handle image loading errors
function handleImageError(event) {
  event.target.src = 'https://via.placeholder.com/200x200?text=' + encodeURIComponent(event.target.alt || 'Product')
}

/**
 * Generate or retrieve idempotency key
 * Reuses the same key for 5 minutes to prevent duplicate charges on retries
 */
function getOrCreateIdempotencyKey() {
  const now = Date.now()
  
  // Reuse existing key if still valid
  if (idempotencyKey.value && idempotencyKeyExpiry.value && now < idempotencyKeyExpiry.value) {
    return idempotencyKey.value
  }
  
  // Generate new UUID v4
  const newKey = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    const r = Math.random() * 16 | 0
    const v = c === 'x' ? r : (r & 0x3 | 0x8)
    return v.toString(16)
  })
  
  idempotencyKey.value = newKey
  idempotencyKeyExpiry.value = now + IDEMPOTENCY_KEY_TTL
  
  return newKey
}

onMounted(async () => {
  try {
    stripe = await loadStripe(props.stripeKey)
    if (!stripe) {
      error.value = 'Failed to load Stripe'
      return
    }

    stripeLoaded.value = true
    
    // Initialize card element when Stripe payment method is selected
    if (paymentMethod.value === 'stripe') {
      initializeCardElement()
    }
  } catch (err) {
    error.value = 'Failed to initialize Stripe: ' + err.message
  }
})

function initializeCardElement() {
  if (!stripe || cardElement) return

  elements = stripe.elements()
  cardElement = elements.create('card', {
    style: {
      base: {
        fontSize: '16px',
        color: '#424770',
        '::placeholder': {
          color: '#aab7c4',
        },
      },
      invalid: {
        color: '#9e2146',
      },
    },
  })

  cardElement.mount('#card-element')
  cardElement.on('change', ({ error: cardError }) => {
    const displayError = document.getElementById('card-errors')
    if (cardError) {
      displayError.textContent = cardError.message
    } else {
      displayError.textContent = ''
    }
  })
}

// Watch payment method changes
watch(paymentMethod, (newMethod) => {
  if (newMethod === 'stripe' && stripeLoaded.value && !cardElement) {
    setTimeout(() => initializeCardElement(), 100)
  } else if (newMethod === 'wallet' && cardElement) {
    cardElement.unmount()
    cardElement = null
  }
})

onUnmounted(() => {
  if (cardElement) {
    cardElement.unmount()
  }
})

async function handleSubmit() {
  // Prevent double submission
  if (processing.value) {
    return
  }

  // Handle wallet payment
  if (paymentMethod.value === 'wallet') {
    await handleWalletPayment()
    return
  }

  // Handle Paymob payment
  if (paymentMethod.value === 'paymob') {
    await handlePaymobPayment()
    return
  }

  // Handle Stripe payment
  if (!stripe || !cardElement) {
    error.value = 'Stripe not loaded'
    return
  }

  error.value = ''
  processing.value = true

  // Disable form submission via keyboard (Enter key)
  const formElement = document.querySelector('form')
  if (formElement) {
    formElement.addEventListener('keydown', preventEnterSubmit, { once: true })
  }

  try {
    // Step 1: Create order
    const orderResponse = await fetch(route('checkout.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(form.value),
    })

    if (!orderResponse.ok) {
      const errorData = await orderResponse.json()
      throw new Error(errorData.error || 'Failed to create order')
    }

    const { order_id } = await orderResponse.json()

    // Step 2: Create payment intent with idempotency key
    const idempotencyKeyValue = getOrCreateIdempotencyKey()
    
    const intentResponse = await fetch(route('api.stripe.create-intent'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Idempotency-Key': idempotencyKeyValue, // Add idempotency key header
      },
      body: JSON.stringify({
        order_id: order_id,
      }),
    })

    if (!intentResponse.ok) {
      const errorData = await intentResponse.json()
      throw new Error(errorData.error || 'Failed to create payment intent')
    }

    const { clientSecret, message } = await intentResponse.json()
    
    // Log if using existing payment attempt
    if (message) {
      console.log('Payment intent:', message)
    }

    // Step 3: Confirm payment
    const { error: confirmError, paymentIntent } = await stripe.confirmCardPayment(
      clientSecret,
      {
        payment_method: {
          card: cardElement,
          billing_details: {
            name: form.value.customer_name,
            email: form.value.customer_email,
            phone: form.value.customer_phone,
            address: {
              line1: form.value.shipping_address,
            },
          },
        },
      }
    )

    if (confirmError) {
      throw new Error(confirmError.message)
    }

    if (paymentIntent.status === 'succeeded') {
      // Clear idempotency key on success
      idempotencyKey.value = null
      idempotencyKeyExpiry.value = null
      
      // Clear cart after successful payment
      await fetch(route('cart.clear'), {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
      })
      
      // Redirect to success page
      router.visit(route('checkout.success', { order: order_id }))
    } else {
      throw new Error('Payment was not successful')
    }
  } catch (err) {
    error.value = err.message || 'An error occurred during checkout'
    processing.value = false
  }
}

/**
 * Prevent form submission via Enter key when processing
 */
function preventEnterSubmit(e) {
  if (e.key === 'Enter' && processing.value) {
    e.preventDefault()
    e.stopPropagation()
  }
}

async function handlePaymobPayment() {
  error.value = ''
  processing.value = true

  try {
    // Step 1: Create order
    const orderResponse = await fetch(route('checkout.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(form.value),
    })

    if (!orderResponse.ok) {
      const errorData = await orderResponse.json()
      throw new Error(errorData.error || 'Failed to create order')
    }

    const { order_id } = await orderResponse.json()

    // Step 2: Start Paymob checkout
    const paymobResponse = await fetch(route('payment.paymob.start'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({
        order_id: order_id,
      }),
    })

    if (!paymobResponse.ok) {
      const errorData = await paymobResponse.json()
      throw new Error(errorData.error || 'Failed to start Paymob checkout')
    }

    const { iframe_url } = await paymobResponse.json()

    // Step 3: Redirect to Paymob iframe page
    router.visit(route('payment.paymob.iframe', order_id))
  } catch (err) {
    error.value = err.message || 'An error occurred during checkout'
    processing.value = false
  }
}

async function handleWalletPayment() {
  error.value = ''
  processing.value = true

  try {
    const response = await fetch(route('checkout.wallet'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(form.value),
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.error || 'Failed to process wallet payment')
    }

    // Clear cart after successful wallet payment
    await fetch(route('cart.clear'), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
    })
    
    // Redirect to success page
    router.visit(route('checkout.success', { order: data.order_id }))
  } catch (err) {
    error.value = err.message || 'An error occurred during checkout'
    processing.value = false
  }
}
</script>


