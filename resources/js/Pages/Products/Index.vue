<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="text-center mb-12">
        <h1 class="text-5xl font-bold text-gray-900 mb-3">Our Products</h1>
        <p class="text-gray-600 text-lg">Discover our amazing collection</p>
      </div>

      <!-- Filters -->
      <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
          </svg>
          Filter Products
        </h3>
        <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input 
              v-model="filters.search" 
              type="text" 
              placeholder="Search products..." 
              class="w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select 
              v-model="filters.category" 
              class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            >
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Min Price</label>
            <input 
              v-model.number="filters.min_price" 
              type="number" 
              step="0.01" 
              placeholder="0" 
              class="w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Max Price</label>
            <input 
              v-model.number="filters.max_price" 
              type="number" 
              step="0.01" 
              placeholder="1000" 
              class="w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            >
          </div>
        </form>
      </div>

      <!-- Products Grid -->
      <div v-if="products.data && products.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <ProductCard 
          v-for="product in products.data" 
          :key="product.id" 
          :product="product"
        />
      </div>
      <div v-else class="text-center py-12">
        <p class="text-gray-500 text-lg">No products found</p>
      </div>

      <!-- Pagination -->
      <div v-if="products.links" class="mt-8 flex justify-center">
        <div class="flex gap-2">
          <a v-for="link in products.links" :key="link.label" 
             :href="link.url" 
             v-html="link.label"
             :class="[
               'px-4 py-2 rounded',
               link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100',
               !link.url ? 'opacity-50 cursor-not-allowed' : ''
             ]"
          ></a>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'

const route = ziggyRoute

const props = defineProps({
  products: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
})

const filters = ref({ ...props.filters })

function applyFilters() {
  router.get(route('products.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Watch for filter changes and auto-apply
watch(filters, () => {
  applyFilters()
}, { deep: true })
</script>

