<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-md p-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-4">Paymob Secure Checkout</h1>
          
          <div class="mb-4 text-sm text-gray-600">
            <p><strong>Order:</strong> {{ order_number }}</p>
            <p><strong>Amount:</strong> ${{ amountValue.toFixed(2) }}</p>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex items-center justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-3 text-gray-700">Loading secure payment form...</span>
          </div>

          <!-- Error State -->
          <div v-if="error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ error }}
          </div>

          <!-- Iframe Container -->
          <div v-if="iframe_url && !loading && !error" class="mb-4">
            <div class="border border-gray-300 rounded-lg overflow-hidden bg-gray-50">
              <iframe
                :src="iframe_url"
                :title="`Paymob Secure Payment - Order ${order_number}`"
                class="w-full"
                style="min-height: 600px; border: none;"
                sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-top-navigation"
                @load="handleIframeLoad"
              ></iframe>
            </div>
          </div>

          <!-- Fallback Button -->
          <div v-if="iframe_url && !loading" class="mt-4 text-center">
            <p class="text-sm text-gray-600 mb-3">
              If the payment form doesn't load, click the button below to open it in a new window.
            </p>
            <button
              @click="openInNewWindow"
              class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold transition-colors"
            >
              Open Secure Checkout in New Window
            </button>
          </div>

          <!-- Instructions -->
          <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="font-semibold text-blue-900 mb-2">Payment Instructions:</h3>
            <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
              <li>Complete your payment in the secure form above</li>
              <li>You will be redirected after successful payment</li>
              <li>If nothing happens, use the "Open in New Window" button</li>
              <li>Do not close this page until payment is complete</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

const route = ziggyRoute

const props = defineProps({
  iframe_url: {
    type: String,
    required: true,
  },
  order_id: {
    type: Number,
    required: true,
  },
  order_number: {
    type: String,
    required: true,
  },
  amount: {
    type: [Number, String],
    required: true,
  },
})

// Ensure amount is always a number (Laravel decimal fields may come as strings)
const amountValue = computed(() => {
  return typeof props.amount === 'string' ? parseFloat(props.amount) : props.amount
})

const loading = ref(true)
const error = ref('')

onMounted(() => {
  // Listen for postMessage events from Paymob iframe (if supported)
  window.addEventListener('message', handlePostMessage)
  
  // Set a timeout to stop loading state
  setTimeout(() => {
    loading.value = false
  }, 2000)
})

function handleIframeLoad() {
  loading.value = false
  console.log('Paymob iframe loaded')
}

function handlePostMessage(event) {
  // Paymob may send postMessage events on payment completion
  // Adjust based on Paymob's actual postMessage implementation
  if (event.data && typeof event.data === 'object') {
    if (event.data.type === 'paymob_payment_success' || event.data.success === true) {
      // Payment successful - redirect to success page
      router.visit(route('checkout.success', { order: props.order_id }))
    } else if (event.data.type === 'paymob_payment_failed' || event.data.success === false) {
      error.value = 'Payment failed. Please try again.'
    }
  }
}

function openInNewWindow() {
  if (props.iframe_url) {
    window.open(props.iframe_url, '_blank', 'width=800,height=600')
  }
}
</script>

