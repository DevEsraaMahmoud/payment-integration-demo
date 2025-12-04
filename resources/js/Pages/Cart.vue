<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
        <a
          :href="route('products.index')"
          class="text-blue-600 hover:text-blue-800"
        >
          Continue Shopping
        </a>
      </div>

      <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        {{ $page.props.flash.success }}
      </div>

      <div v-if="items.length === 0" class="bg-white rounded-lg shadow-md p-8 text-center">
        <p class="text-gray-600 text-lg mb-4">Your cart is empty</p>
        <a
          :href="route('products.index')"
          class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
        >
          Browse Products
        </a>
      </div>

      <div v-else class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="divide-y divide-gray-200">
          <div
            v-for="item in items"
            :key="item.id"
            class="p-6 flex items-center justify-between"
          >
            <div class="flex items-center flex-1">
              <img
                :src="item.image"
                :alt="item.name"
                class="w-20 h-20 object-cover rounded mr-4"
              />
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">
                  {{ item.name }}
                </h3>
                <p class="text-gray-600">${{ item.price.toFixed(2) }} each</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="flex items-center space-x-2">
                <label class="text-sm text-gray-600">Qty:</label>
                <input
                  type="number"
                  :value="item.quantity"
                  min="1"
                  @change="updateQuantity(item.id, $event.target.value)"
                  class="w-16 px-2 py-1 border border-gray-300 rounded text-center"
                />
              </div>
              <span class="text-lg font-semibold text-gray-900 w-24 text-right">
                ${{ item.subtotal.toFixed(2) }}
              </span>
              <button
                @click="removeItem(item.id)"
                class="text-red-600 hover:text-red-800"
              >
                Remove
              </button>
            </div>
          </div>
        </div>
        <div class="p-6 bg-gray-50 border-t border-gray-200">
          <div class="flex justify-between items-center mb-4">
            <span class="text-xl font-semibold text-gray-900">Total:</span>
            <span class="text-2xl font-bold text-gray-900">
              ${{ total.toFixed(2) }}
            </span>
          </div>
          <a
            :href="route('checkout.index')"
            class="block w-full bg-blue-600 text-white text-center py-3 px-4 rounded-lg hover:bg-blue-700"
          >
            Proceed to Checkout
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'

defineProps({
  items: {
    type: Array,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
})

// Make route available in template
const route = ziggyRoute

function updateQuantity(productId, quantity) {
  router.patch(route('cart.update', productId), {
    quantity: parseInt(quantity),
  })
}

function removeItem(productId) {
  router.delete(route('cart.remove', productId))
}
</script>

