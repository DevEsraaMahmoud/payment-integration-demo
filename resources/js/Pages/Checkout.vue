<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>

      <!-- Status Messages -->
      <div v-if="error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>
      <div v-if="processing" class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
        Processing payment...
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
          <div class="space-y-3 mb-4">
            <div
              v-for="item in items"
              :key="item.id"
              class="flex justify-between text-sm text-gray-700"
            >
              <span class="text-gray-900">{{ item.name }} x{{ item.quantity }}</span>
              <span class="text-gray-900 font-medium">${{ item.subtotal.toFixed(2) }}</span>
            </div>
          </div>
          <div class="border-t pt-4 flex justify-between text-lg font-semibold text-gray-900">
            <span>Total:</span>
            <span class="text-blue-600">${{ total.toFixed(2) }}</span>
          </div>
        </div>

        <!-- Checkout Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Details</h2>
          <form @submit.prevent="handleSubmit">
            <!-- Customer Information -->
            <div class="space-y-4 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Full Name *
                </label>
                <input
                  v-model="form.customer_name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Email *
                </label>
                <input
                  v-model="form.customer_email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Phone
                </label>
                <input
                  v-model="form.customer_phone"
                  type="tel"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Shipping Address
                </label>
                <textarea
                  v-model="form.shipping_address"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                ></textarea>
              </div>
            </div>

            <!-- Payment Method Selection -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Payment Method *
              </label>
              <div class="space-y-2">
                <label class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                  <input
                    v-model="paymentMethod"
                    type="radio"
                    value="stripe"
                    class="mr-3"
                  />
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">Pay with Card</div>
                    <div class="text-sm text-gray-500">Stripe secure payment</div>
                  </div>
                </label>
                <label
                  v-if="walletBalance > 0"
                  class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50"
                  :class="{
                    'opacity-50 cursor-not-allowed': !canUseWallet,
                  }"
                >
                  <input
                    v-model="paymentMethod"
                    type="radio"
                    value="wallet"
                    :disabled="!canUseWallet"
                    class="mr-3"
                  />
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">Pay with Wallet</div>
                    <div class="text-sm text-gray-500">
                      Balance: ${{ walletBalance.toFixed(2) }}
                      <span v-if="!canUseWallet" class="text-red-600">
                        (Insufficient balance)
                      </span>
                    </div>
                  </div>
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
            <div v-if="paymentMethod === 'stripe'" class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Card Details *
              </label>
              <div
                id="card-element"
                class="px-3 py-2 border border-gray-300 rounded-md"
              ></div>
              <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="processing || (paymentMethod === 'stripe' && !stripeLoaded)"
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold transition-colors"
            >
              <span v-if="processing" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing payment...
              </span>
              <span v-else>
                Pay ${{ total.toFixed(2) }}
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
const paymentMethod = ref('stripe') // 'stripe' or 'wallet'
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


