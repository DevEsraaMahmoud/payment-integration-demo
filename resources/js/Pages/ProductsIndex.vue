<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Products</h1>
        <div class="flex gap-3">
          <button
            @click="goToWallet"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
          >
            💰 My Wallet
          </button>
          <button
            @click="goToCart"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
          >
            🛒 View Cart
          </button>
        </div>
      </div>

      <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        {{ $page.props.flash.success }}
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="product in products"
          :key="product.id"
          class="bg-white rounded-lg shadow-md overflow-hidden"
        >
          <img
            :src="product.image"
            :alt="product.name"
            class="w-full h-48 object-cover"
          />
          <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ product.name }}
            </h3>
            <p class="text-gray-600 mb-4">{{ product.description }}</p>
            <div class="flex justify-between items-center mb-4">
              <span class="text-2xl font-bold text-gray-900">
                ${{ product.price.toFixed(2) }}
              </span>
              <span class="text-sm text-gray-500">
                Stock: {{ product.stock }}
              </span>
            </div>
            <form @submit.prevent="addToCart(product.id)">
              <button
                type="submit"
                :disabled="product.stock === 0"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
              >
                {{ product.stock > 0 ? 'Add to Cart' : 'Out of Stock' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'

defineProps({
  products: {
    type: Array,
    required: true,
  },
})

// Make route available in template
const route = ziggyRoute

function addToCart(productId) {
  router.post(route('cart.add', productId), {
    quantity: 1,
  })
}

function goToWallet() {
  router.visit(route('wallet.index'))
}

function goToCart() {
  router.visit(route('cart.index'))
}
</script>

