<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 overflow-hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>
    
    <!-- Drawer -->
    <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl">
      <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
          <h2 class="text-xl font-bold text-gray-900 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Shopping Cart
          </h2>
          <button @click="$emit('close')" class="p-2 hover:bg-white rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="items.length === 0" class="text-center py-16">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
            </div>
            <p class="text-gray-600 text-lg font-medium mb-2">Your cart is empty</p>
            <a :href="route('products.index')" class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
              Continue Shopping
            </a>
          </div>
          <div v-else class="space-y-4">
            <div v-for="item in items" :key="item.id" class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
              <img :src="item.image || '/images/placeholder.png'" :alt="item.name" class="w-20 h-20 object-cover rounded-lg shadow-sm">
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 truncate">{{ item.name }}</h3>
                <p class="text-sm text-gray-600 mt-1">${{ item.price.toFixed(2) }} × {{ item.quantity }}</p>
                <p class="font-bold text-gray-900 mt-2">${{ item.subtotal.toFixed(2) }}</p>
              </div>
              <button @click="removeItem(item.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="items.length > 0" class="border-t border-gray-200 p-6 bg-gradient-to-br from-gray-50 to-blue-50 space-y-4">
          <div class="flex justify-between items-center">
            <span class="text-lg font-semibold text-gray-700">Total:</span>
            <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">${{ total.toFixed(2) }}</span>
          </div>
          <a 
            :href="route('checkout.index')" 
            class="block w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center py-4 rounded-xl font-bold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] transform" 
            @click="$emit('close')"
          >
            Proceed to Checkout
          </a>
          <a 
            :href="route('cart.index')" 
            class="block w-full text-center py-3 text-gray-700 hover:text-gray-900 font-medium transition-colors"
          >
            View Full Cart
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

