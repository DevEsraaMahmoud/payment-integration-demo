<template>
  <AppLayout>
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-6xl font-bold mb-4">Welcome to E-Commerce</h1>
        <p class="text-xl mb-8">Discover amazing products at great prices</p>
        <a :href="route('products.index')" class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
          Shop Now
        </a>
      </div>
    </section>

    <!-- Categories -->
    <section v-if="categories.length > 0" class="py-12 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-8">Shop by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div v-for="category in categories" :key="category.id" class="text-center">
            <div class="bg-gray-100 rounded-lg p-6 hover:bg-gray-200 transition cursor-pointer">
              <h3 class="font-semibold text-lg">{{ category.name }}</h3>
              <p class="text-sm text-gray-600 mt-2">{{ category.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="py-12 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-8">Featured Products</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
          <div v-for="product in featuredProducts" :key="product.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
            <img :src="product.image" :alt="product.name" class="w-full h-48 object-cover">
            <div class="p-4">
              <h3 class="font-semibold text-lg mb-2">{{ product.name }}</h3>
              <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ product.description }}</p>
              <div class="flex justify-between items-center">
                <span class="text-2xl font-bold text-blue-600">${{ product.price.toFixed(2) }}</span>
                <a :href="route('products.show', product.id)" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                  View
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="text-center mt-8">
          <a :href="route('products.index')" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700">
            View All Products
          </a>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { route as ziggyRoute } from 'ziggy-js'

const route = ziggyRoute

defineProps({
  featuredProducts: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
})
</script>


