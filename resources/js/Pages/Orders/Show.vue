<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Back Button -->
      <div class="mb-6">
        <a
          :href="route('orders.index')"
          class="inline-flex items-center text-blue-600 hover:text-blue-800"
        >
          <svg
            class="mr-2 h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Back to Orders
        </a>
      </div>

      <!-- Order Header -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              Order #{{ order.order_number }}
            </h1>
            <span
              :class="{
                'bg-green-100 text-green-800': order.status === 'completed',
                'bg-yellow-100 text-yellow-800': order.status === 'pending',
                'bg-red-100 text-red-800': order.status === 'refunded',
                'bg-gray-100 text-gray-800': order.status === 'cancelled',
              }"
              class="px-3 py-1 text-sm font-medium rounded-full"
            >
              {{ order.status }}
            </span>
          </div>
          <div class="mt-4 sm:mt-0 text-right">
            <p class="text-sm text-gray-600">Order Date</p>
            <p class="text-lg font-semibold text-gray-900">{{ order.created_at }}</p>
          </div>
        </div>
        <!-- Download Invoice Button -->
        <div class="mt-4 pt-4 border-t border-gray-200">
          <a
            :href="route('orders.invoice', order.id)"
            target="_blank"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download Invoice PDF
          </a>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
        <div class="space-y-4">
          <div
            v-for="item in order.items"
            :key="item.id"
            class="flex items-center justify-between py-4 border-b border-gray-200 last:border-0"
          >
            <div class="flex-1">
              <h3 class="text-lg font-medium text-gray-900">{{ item.product_name }}</h3>
              <p class="text-sm text-gray-600">Quantity: {{ item.quantity }}</p>
            </div>
            <div class="text-right">
              <p class="text-lg font-semibold text-gray-900">
                ${{ item.subtotal.toFixed(2) }}
              </p>
              <p class="text-sm text-gray-600">${{ item.price.toFixed(2) }} each</p>
            </div>
          </div>
        </div>
        <div class="mt-6 pt-6 border-t border-gray-200">
          <div class="flex justify-between items-center">
            <span class="text-xl font-semibold text-gray-900">Total</span>
            <span class="text-2xl font-bold text-gray-900">
              ${{ order.total_amount.toFixed(2) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Customer Information -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Customer Information</h2>
        <div class="space-y-2 text-gray-600">
          <p><span class="font-medium">Name:</span> {{ order.customer_name }}</p>
          <p><span class="font-medium">Email:</span> {{ order.customer_email }}</p>
          <p v-if="order.shipping_address" class="mt-4">
            <span class="font-medium">Shipping Address:</span><br />
            <span class="text-gray-700">{{ order.shipping_address }}</span>
          </p>
        </div>
      </div>

      <!-- Payment Information -->
      <div v-if="order.transactions && order.transactions.length > 0" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Information</h2>
        <div class="space-y-4">
          <div
            v-for="transaction in order.transactions"
            :key="transaction.id"
            class="flex items-center justify-between py-3 border-b border-gray-200 last:border-0"
          >
            <div>
              <p class="font-medium text-gray-900">
                {{ transaction.payment_provider === 'stripe' ? 'Stripe' : transaction.payment_provider }}
              </p>
              <p class="text-sm text-gray-600">{{ transaction.created_at }}</p>
            </div>
            <div class="text-right">
              <p class="text-lg font-semibold text-gray-900">
                ${{ transaction.amount.toFixed(2) }}
              </p>
              <span
                :class="{
                  'bg-green-100 text-green-800': transaction.status === 'completed',
                  'bg-yellow-100 text-yellow-800': transaction.status === 'pending',
                  'bg-red-100 text-red-800': transaction.status === 'refunded',
                }"
                class="px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ transaction.status }}
              </span>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { route as ziggyRoute } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
})

// Make route available in template
const route = ziggyRoute
</script>

