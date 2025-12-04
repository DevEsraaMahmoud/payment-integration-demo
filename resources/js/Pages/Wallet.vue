<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-4xl font-bold text-gray-900 mb-2">My Wallet</h1>
          <p class="text-gray-600">Manage your wallet balance and transactions</p>
        </div>
        <button
          @click="goToProducts"
          class="bg-white text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 border border-gray-300 transition-colors flex items-center gap-2"
        >
          <span>←</span> Back to Products
        </button>
      </div>

      <!-- Success/Error Messages -->
      <div v-if="success" class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-sm">
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <p class="font-medium">{{ success }}</p>
        </div>
      </div>
      <div v-if="error" class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm">
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <p class="font-medium">{{ error }}</p>
        </div>
      </div>

      <!-- Wallet Balance Card -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-xl p-8 mb-8 text-white">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-blue-100 text-sm font-medium mb-2">Current Balance</p>
            <h2 class="text-5xl font-bold mb-2">${{ wallet.balance.toFixed(2) }}</h2>
            <p class="text-blue-100 text-sm">Available for purchases</p>
          </div>
          <div class="bg-white bg-opacity-20 rounded-full p-4">
            <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
              <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
              <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Fund Wallet Section -->
        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center mb-6">
            <div class="bg-green-100 rounded-full p-3 mr-3">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Fund Wallet</h2>
          </div>

          <form @submit.prevent="handleFund" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Amount to Add ($)
              </label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-lg">$</span>
                <input
                  v-model.number="fundAmount"
                  type="number"
                  step="0.01"
                  min="1"
                  placeholder="10.00"
                  required
                  class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                />
              </div>
              <p class="text-xs text-gray-500 mt-1">Minimum amount: $1.00</p>
            </div>

            <button
              type="submit"
              :disabled="processing || !stripeLoaded || fundAmount < 1"
              class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold transition-colors flex items-center justify-center gap-2"
            >
              <svg v-if="!processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              <span v-if="processing" class="animate-spin">⏳</span>
              {{ processing ? 'Processing...' : `Add $${fundAmount.toFixed(2)} to Wallet` }}
            </button>
          </form>

          <!-- Payment Form (shown after clicking Fund) -->
          <div v-if="showPaymentForm" class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h3>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Card Details *
              </label>
              <div
                id="card-element-fund"
                class="px-4 py-3 border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500"
              ></div>
              <div id="card-errors-fund" class="text-red-600 text-sm mt-2"></div>
            </div>
            <div class="flex gap-3">
              <button
                @click="confirmFunding"
                :disabled="processing || !stripeLoaded"
                class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold transition-colors"
              >
                {{ processing ? 'Processing...' : `Pay $${fundAmount.toFixed(2)}` }}
              </button>
              <button
                @click="cancelFunding"
                class="bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 font-semibold transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-xl shadow-lg p-6">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Wallet Statistics</h2>
          <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg">
              <div>
                <p class="text-sm text-gray-600">Total Credits</p>
                <p class="text-2xl font-bold text-blue-600">${{ totalCredits.toFixed(2) }}</p>
              </div>
              <div class="bg-blue-100 rounded-full p-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
              </div>
            </div>
            <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg">
              <div>
                <p class="text-sm text-gray-600">Total Debits</p>
                <p class="text-2xl font-bold text-red-600">${{ totalDebits.toFixed(2) }}</p>
              </div>
              <div class="bg-red-100 rounded-full p-3">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
              </div>
            </div>
            <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
              <div>
                <p class="text-sm text-gray-600">Total Transactions</p>
                <p class="text-2xl font-bold text-gray-700">{{ transactions.length }}</p>
              </div>
              <div class="bg-gray-100 rounded-full p-3">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Transaction History -->
      <div class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Transaction History
          </h2>
        </div>

        <div v-if="transactions.length > 0" class="divide-y divide-gray-200">
          <div
            v-for="transaction in transactions"
            :key="transaction.id"
            class="px-6 py-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-4">
                <div
                  :class="{
                    'bg-green-100': transaction.type === 'credit',
                    'bg-red-100': transaction.type === 'debit',
                  }"
                  class="rounded-full p-3"
                >
                  <svg
                    v-if="transaction.type === 'credit'"
                    class="w-6 h-6 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                  </svg>
                  <svg
                    v-else
                    class="w-6 h-6 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                  </svg>
                </div>
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <span
                      :class="{
                        'bg-green-100 text-green-800': transaction.type === 'credit',
                        'bg-red-100 text-red-800': transaction.type === 'debit',
                      }"
                      class="px-2 py-1 text-xs font-semibold rounded"
                    >
                      {{ transaction.type === 'credit' ? 'Credit' : 'Debit' }}
                    </span>
                    <span class="text-sm text-gray-500">
                      {{ formatDate(transaction.created_at) }}
                    </span>
                  </div>
                  <p v-if="transaction.meta?.description" class="text-sm text-gray-600">
                    {{ transaction.meta.description }}
                  </p>
                  <p v-else-if="transaction.meta?.order_number" class="text-sm text-gray-600">
                    Order: {{ transaction.meta.order_number }}
                  </p>
                  <p v-else class="text-sm text-gray-500 italic">
                    {{ transaction.type === 'credit' ? 'Wallet funding' : 'Payment for order' }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <span
                  :class="{
                    'text-green-600': transaction.type === 'credit',
                    'text-red-600': transaction.type === 'debit',
                  }"
                  class="text-xl font-bold"
                >
                  {{ transaction.type === 'credit' ? '+' : '-' }}${{ transaction.amount.toFixed(2) }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="px-6 py-12 text-center">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          <p class="text-gray-500 text-lg">No transactions yet</p>
          <p class="text-gray-400 text-sm mt-2">Fund your wallet to get started!</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { loadStripe } from '@stripe/stripe-js'
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'

const route = ziggyRoute

const props = defineProps({
  wallet: {
    type: Object,
    required: true,
  },
  transactions: {
    type: Array,
    default: () => [],
  },
  stripeKey: {
    type: String,
    required: true,
  },
})

const fundAmount = ref(10.00)
const showPaymentForm = ref(false)
const processing = ref(false)
const error = ref('')
const success = ref('')
const stripeLoaded = ref(false)
let stripe = null
let elements = null
let cardElement = null
let clientSecret = null

// Computed properties for statistics
const totalCredits = computed(() => {
  if (!props.transactions || props.transactions.length === 0) return 0
  return props.transactions
    .filter(t => t.type === 'credit' && typeof t.amount === 'number')
    .reduce((sum, t) => sum + (t.amount || 0), 0)
})

const totalDebits = computed(() => {
  if (!props.transactions || props.transactions.length === 0) return 0
  return props.transactions
    .filter(t => t.type === 'debit' && typeof t.amount === 'number')
    .reduce((sum, t) => sum + (t.amount || 0), 0)
})


// Format date for display
function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function goToProducts() {
  router.visit(route('products.index'))
}

onMounted(async () => {
  try {
    stripe = await loadStripe(props.stripeKey)
    if (!stripe) {
      error.value = 'Failed to load Stripe'
      return
    }
    stripeLoaded.value = true
  } catch (err) {
    error.value = 'Failed to initialize Stripe: ' + err.message
  }
})

onUnmounted(() => {
  if (cardElement) {
    cardElement.unmount()
  }
})

async function handleFund() {
  const amount = parseFloat(fundAmount.value)
  
  if (isNaN(amount) || amount < 1) {
    error.value = 'Minimum funding amount is $1.00'
    return
  }

  error.value = ''
  success.value = ''
  processing.value = true

  try {
    const response = await fetch(route('wallet.fund'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({
        amount: amount,
      }),
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.error || 'Failed to create payment intent')
    }

    clientSecret = data.clientSecret
    showPaymentForm.value = true

    // Initialize Stripe Elements
    await initializeCardElement()
  } catch (err) {
    error.value = err.message || 'An error occurred'
    processing.value = false
  } finally {
    processing.value = false
  }
}

async function initializeCardElement() {
  if (!stripe || cardElement) return

  // Wait for DOM to be ready
  await new Promise(resolve => setTimeout(resolve, 100))

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

  cardElement.mount('#card-element-fund')
  cardElement.on('change', ({ error: cardError }) => {
    const displayError = document.getElementById('card-errors-fund')
    if (cardError) {
      displayError.textContent = cardError.message
    } else {
      displayError.textContent = ''
    }
  })
}

async function confirmFunding() {
  if (!stripe || !cardElement || !clientSecret) {
    error.value = 'Payment form not ready'
    return
  }

  error.value = ''
  success.value = ''
  processing.value = true

  try {
    const { error: confirmError } = await stripe.confirmCardPayment(
      clientSecret,
      {
        payment_method: {
          card: cardElement,
        },
      }
    )

    if (confirmError) {
      throw new Error(confirmError.message)
    }

    success.value = `Wallet funded successfully with $${fundAmount.value.toFixed(2)}!`
    fundAmount.value = 10.00
    showPaymentForm.value = false

    // Clean up card element
    if (cardElement) {
      cardElement.unmount()
      cardElement = null
    }
    clientSecret = null

    // Reload page to refresh balance and transactions
    setTimeout(() => {
      router.reload({
        onSuccess: () => {
          success.value = ''
        }
      })
    }, 2000)
  } catch (err) {
    error.value = err.message || 'Payment failed'
  } finally {
    processing.value = false
  }
}

function cancelFunding() {
  showPaymentForm.value = false
  fundAmount.value = 10.00
  if (cardElement) {
    cardElement.unmount()
    cardElement = null
  }
  clientSecret = null
  error.value = ''
}
</script>

<style scoped>
/* Additional custom styles if needed */
</style>
