<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-2 sm:py-3">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-xl p-3 sm:p-4 border border-gray-100 max-h-[calc(100vh-4rem)] flex flex-col">
          <!-- Header -->
          <div class="text-center mb-2 sm:mb-3 flex-shrink-0">
            <h1 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Paymob Secure Checkout</h1>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 text-xs text-gray-600">
              <p><strong>Order:</strong> <span class="font-mono text-xs">{{ order_number }}</span></p>
              <p><strong>Amount:</strong> <span class="text-blue-600 font-bold">${{ amountValue.toFixed(2) }}</span></p>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex items-center justify-center py-4 flex-shrink-0">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-2 text-xs text-gray-700">Loading payment form...</span>
          </div>

          <!-- Error State -->
          <div v-if="error" class="mb-2 bg-red-50 border-l-4 border-red-500 text-red-700 px-2 py-1.5 rounded-r-lg text-xs flex-shrink-0">
            <div class="flex items-center">
              <svg class="w-3 h-3 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              {{ error }}
            </div>
          </div>

          <!-- Iframe Container -->
          <div v-if="iframe_url && !loading && !error" class="mb-2 flex-1 min-h-0">
            <div class="border-2 border-gray-200 rounded-lg overflow-hidden bg-gray-50 shadow-inner h-full">
              <iframe
                :src="iframe_url"
                :title="`Paymob Secure Payment - Order ${order_number}`"
                class="w-full h-full"
                style="min-height: 350px; max-height: calc(100vh - 250px); border: none;"
                sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-top-navigation"
                @load="handleIframeLoad"
              ></iframe>
            </div>
          </div>

          <!-- Fallback Button & Instructions -->
          <div v-if="iframe_url && !loading" class="mt-2 space-y-1.5 flex-shrink-0">
            <div class="text-center">
              <button
                @click="openInNewWindow"
                class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-3 py-1.5 rounded-lg hover:from-blue-700 hover:to-purple-700 font-medium text-xs transition-all shadow-sm hover:shadow-md"
              >
                Open in New Window
              </button>
            </div>
            <div class="p-2 bg-blue-50 border-l-3 border-blue-500 rounded-r-lg">
              <p class="text-xs text-blue-800">
                <strong>Note:</strong> Complete payment above. You'll be redirected automatically.
              </p>
            </div>
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

