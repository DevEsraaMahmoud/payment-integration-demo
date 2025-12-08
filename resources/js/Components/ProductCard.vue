<template>
  <div class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
    <!-- Product Image -->
    <div class="relative overflow-hidden bg-gray-100">
      <img 
        :src="product.image || '/images/placeholder.png'" 
        :alt="product.name"
        class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500"
      />
      <!-- Stock Badge -->
      <div v-if="product.stock > 0 && product.stock < 10" class="absolute top-3 right-3 bg-orange-500 text-white text-xs font-semibold px-2 py-1 rounded-full">
        Only {{ product.stock }} left
      </div>
      <div v-else-if="product.stock === 0" class="absolute top-3 right-3 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded-full">
        Out of Stock
      </div>
      <!-- Quick View Overlay -->
      <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
        <a 
          :href="route('products.show', product.id)"
          class="bg-white text-gray-900 px-6 py-3 rounded-lg font-semibold transform translate-y-4 group-hover:translate-y-0 transition-all duration-300 hover:bg-gray-100"
        >
          Quick View
        </a>
      </div>
    </div>

    <!-- Product Info -->
    <div class="p-5">
      <!-- Category Badge -->
      <div v-if="product.category" class="text-xs text-gray-500 uppercase tracking-wide mb-2">
        {{ product.category }}
      </div>
      
      <!-- Product Name -->
      <h3 class="font-semibold text-lg text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
        {{ product.name }}
      </h3>
      
      <!-- Description -->
      <p v-if="showDescription" class="text-sm text-gray-600 mb-4 line-clamp-2">
        {{ product.description }}
      </p>

      <!-- Price and Action -->
      <div class="flex items-center justify-between">
        <div>
          <span class="text-2xl font-bold text-gray-900">${{ product.price.toFixed(2) }}</span>
          <span v-if="product.original_price && product.original_price > product.price" class="text-sm text-gray-500 line-through ml-2">
            ${{ product.original_price.toFixed(2) }}
          </span>
        </div>
        <a 
          :href="route('products.show', product.id)"
          class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-2.5 rounded-lg font-medium hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
        >
          View Details
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { route as ziggyRoute } from 'ziggy-js'

const route = ziggyRoute

defineProps({
  product: {
    type: Object,
    required: true,
  },
  showDescription: {
    type: Boolean,
    default: true,
  },
})
</script>

