<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
          <a
            :href="route('products.index')"
            class="text-blue-600 hover:text-blue-800 font-medium"
          >
            ← Continue Shopping
          </a>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ $page.props.flash.success }}
        </div>

        <!-- Empty Cart State -->
        <div v-if="!items || items.length === 0" class="bg-white rounded-lg shadow-md p-12 text-center">
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
          <p class="text-gray-600 text-lg mb-4">Your cart is empty</p>
          <a
            :href="route('products.index')"
            class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
          >
            Browse Products
          </a>
        </div>

        <!-- Cart Table -->
        <div v-else class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Product
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Price
                  </th>
                  <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Quantity
                  </th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Subtotal
                  </th>
                  <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <img
                        :src="item.image"
                        :alt="item.name"
                        class="w-16 h-16 object-cover rounded mr-4"
                      />
                      <div>
                        <h3 class="text-sm font-medium text-gray-900">
                          {{ item.name }}
                        </h3>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${{ item.price.toFixed(2) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <input
                      type="number"
                      :value="item.quantity"
                      min="1"
                      @change="updateQuantity(item.id, $event.target.value)"
                      class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="text-sm font-semibold text-gray-900">
                      ${{ item.subtotal.toFixed(2) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <button
                      @click="removeItem(item.id)"
                      class="text-red-600 hover:text-red-800 font-medium"
                      title="Remove item"
                    >
                      <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Cart Summary & Checkout -->
          <div class="bg-gray-50 px-6 py-6 border-t border-gray-200">
            <div class="flex justify-end mb-6">
              <div class="w-full max-w-md">
                <div class="flex justify-between items-center mb-2">
                  <span class="text-lg text-gray-600">Subtotal:</span>
                  <span class="text-lg font-semibold text-gray-900">${{ total.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between items-center mb-4">
                  <span class="text-xl font-semibold text-gray-900">Total:</span>
                  <span class="text-2xl font-bold text-blue-600">
                    ${{ total.toFixed(2) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 justify-end">
              <a
                :href="route('products.index')"
                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-center font-medium"
              >
                Continue Shopping
              </a>
              <a
                :href="route('checkout.index')"
                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-center font-semibold"
              >
                Proceed to Checkout →
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  total: {
    type: Number,
    default: 0,
  },
})

// Make route available in template
const route = ziggyRoute

function updateQuantity(productId, quantity) {
  const qty = parseInt(quantity)
  if (qty < 1) {
    removeItem(productId)
    return
  }
  router.patch(route('cart.update', productId), {
    quantity: qty,
  }, {
    preserveScroll: true,
  })
}

function removeItem(productId) {
  if (confirm('Are you sure you want to remove this item from your cart?')) {
    router.delete(route('cart.remove', productId), {
      preserveScroll: true,
    })
  }
}
</script>

