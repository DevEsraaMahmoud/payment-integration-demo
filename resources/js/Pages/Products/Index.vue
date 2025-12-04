<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Products</h1>
      </div>

      <!-- Filters -->
      <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input v-model="filters.search" type="text" placeholder="Search products..." class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select v-model="filters.category" class="w-full px-3 py-2 border border-gray-300 rounded-md">
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Min Price</label>
            <input v-model.number="filters.min_price" type="number" step="0.01" placeholder="0" class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
            <input v-model.number="filters.max_price" type="number" step="0.01" placeholder="1000" class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white">
          </div>
        </form>
      </div>

      <!-- Products Grid -->
      <div v-if="products.data && products.data.length > 0" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div v-for="product in products.data" :key="product.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
          <img :src="product.image" :alt="product.name" class="w-full h-48 object-cover">
          <div class="p-4">
            <h3 class="font-semibold text-lg mb-2">{{ product.name }}</h3>
            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ product.description }}</p>
            <div class="flex justify-between items-center">
              <span class="text-xl font-bold text-blue-600">${{ product.price.toFixed(2) }}</span>
              <a :href="route('products.show', product.id)" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                View
              </a>
            </div>
          </div>
        </div>
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

