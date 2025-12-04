<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">
      <div class="mb-4">
        <svg
          class="mx-auto h-16 w-16 text-green-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>
      <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful!</h1>
      <p class="text-gray-600 mb-6">
        Thank you for your purchase. Your order has been received and is being processed.
      </p>
      <div v-if="order_id" class="mb-6">
        <p class="text-sm text-gray-500">Order ID: {{ order_id }}</p>
      </div>
      <div class="space-y-3">
        <a
          :href="route('products.index')"
          class="block w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700"
        >
          Continue Shopping
        </a>
        <a
          :href="route('orders.index')"
          class="block w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-300"
        >
          View Orders
        </a>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { route as ziggyRoute } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  order_id: {
    type: String,
    default: null,
  },
})

// Make route available in template
const route = ziggyRoute
</script>


