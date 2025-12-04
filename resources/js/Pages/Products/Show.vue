<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="product" class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Product Image -->
        <div>
          <img :src="product.image_url || product.image" :alt="product.name" class="w-full rounded-lg shadow-lg">
        </div>

        <!-- Product Details -->
        <div>
          <h1 class="text-3xl font-bold mb-4">{{ product.name }}</h1>
          <p class="text-2xl font-bold text-blue-600 mb-4">${{ product.price.toFixed(2) }}</p>
          <p class="text-gray-600 mb-6">{{ product.description }}</p>
          
          <div class="mb-6">
            <p class="text-sm text-gray-500 mb-2">Stock: {{ product.stock }} available</p>
            <div class="flex items-center space-x-4">
              <label class="block">
                <span class="text-gray-700">Quantity:</span>
                <input v-model.number="quantity" type="number" min="1" :max="product.stock" class="mt-1 block w-20 px-3 py-2 border border-gray-300 rounded-md">
              </label>
              <button 
                @click="addToCart" 
                :disabled="!product.is_in_stock || quantity > product.stock"
                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
              >
                Add to Cart
              </button>
            </div>
          </div>

          <div v-if="success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ success }}
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

const route = ziggyRoute

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const quantity = ref(1)
const success = ref('')

function addToCart() {
  router.post(route('cart.add', props.product.id), {
    quantity: quantity.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      success.value = 'Product added to cart!'
      setTimeout(() => success.value = '', 3000)
    },
  })
}
</script>

