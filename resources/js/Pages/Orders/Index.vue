<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
        <p class="text-gray-600 mt-2">View your order history and track your purchases</p>
      </div>

      <!-- Orders List -->
      <div v-if="orders && orders.length > 0" class="space-y-4">
        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-4 mb-2">
                <h3 class="text-lg font-semibold text-gray-900">
                  Order #{{ order.order_number }}
                </h3>
                <span
                  :class="{
                    'bg-green-100 text-green-800': order.status === 'completed',
                    'bg-yellow-100 text-yellow-800': order.status === 'pending',
                    'bg-red-100 text-red-800': order.status === 'refunded',
                    'bg-gray-100 text-gray-800': order.status === 'cancelled',
                  }"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ order.status }}
                </span>
              </div>
              <div class="text-sm text-gray-600 space-y-1">
                <p>
                  <span class="font-medium">Items:</span> {{ order.items_count }}
                </p>
                <p>
                  <span class="font-medium">Total:</span> ${{ order.total_amount.toFixed(2) }}
                </p>
                <p>
                  <span class="font-medium">Date:</span> {{ order.created_at }}
                </p>
              </div>
            </div>
            <div class="mt-4 sm:mt-0">
              <a
                :href="route('orders.show', order.id)"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                View Details
                <svg
                  class="ml-2 h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                  />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg
          class="mx-auto h-16 w-16 text-gray-400 mb-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
          />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No orders yet</h3>
        <p class="text-gray-600 mb-6">Start shopping to see your orders here</p>
        <a
          :href="route('products.index')"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Browse Products
        </a>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { route as ziggyRoute } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  orders: {
    type: Array,
    required: true,
  },
})

// Make route available in template
const route = ziggyRoute
</script>

