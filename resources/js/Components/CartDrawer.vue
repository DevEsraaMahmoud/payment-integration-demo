<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 overflow-hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>
    
    <!-- Drawer -->
    <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl">
      <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b">
          <h2 class="text-lg font-semibold">Shopping Cart</h2>
          <button @click="$emit('close')" class="p-2 hover:bg-gray-100 rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4">
          <div v-if="items.length === 0" class="text-center text-gray-500 py-12">
            <p>Your cart is empty</p>
            <a :href="route('products.index')" class="text-blue-600 hover:underline mt-4 inline-block">Continue Shopping</a>
          </div>
          <div v-else class="space-y-4">
            <div v-for="item in items" :key="item.id" class="flex items-center space-x-4 border-b pb-4">
              <img :src="item.image" :alt="item.name" class="w-16 h-16 object-cover rounded">
              <div class="flex-1">
                <h3 class="font-medium">{{ item.name }}</h3>
                <p class="text-sm text-gray-500">${{ item.price.toFixed(2) }} x {{ item.quantity }}</p>
                <p class="font-semibold">${{ item.subtotal.toFixed(2) }}</p>
              </div>
              <button @click="removeItem(item.id)" class="text-red-600 hover:text-red-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="items.length > 0" class="border-t p-4 space-y-4">
          <div class="flex justify-between text-lg font-semibold">
            <span>Total:</span>
            <span>${{ total.toFixed(2) }}</span>
          </div>
          <a :href="route('checkout.index')" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700" @click="$emit('close')">
            Checkout
          </a>
          <a :href="route('cart.index')" class="block w-full text-center py-2 text-gray-700 hover:text-gray-900">
            View Cart
          </a>
        </div>
      </div>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { route as ziggyRoute } from 'ziggy-js'
import { router } from '@inertiajs/vue3'

const route = ziggyRoute

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  items: {
    type: Array,
    default: () => [],
  },
  total: {
    type: Number,
    default: 0,
  },
})

defineEmits(['close'])

function removeItem(productId) {
  router.delete(route('cart.remove', productId), {
    preserveScroll: true,
  })
}
</script>

