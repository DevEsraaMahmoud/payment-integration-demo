<template>
  <AppLayout>
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 text-white py-24 overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
      </div>
      
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
          Discover Amazing
          <span class="block bg-gradient-to-r from-yellow-300 to-pink-300 bg-clip-text text-transparent">Products</span>
        </h1>
        <p class="text-xl md:text-2xl mb-10 text-gray-100 max-w-2xl mx-auto">
          Shop the latest trends and find everything you need at unbeatable prices
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a :href="route('products.index')" class="inline-block bg-white text-blue-600 px-10 py-4 rounded-xl font-bold text-lg hover:bg-gray-100 transition-all shadow-2xl hover:shadow-3xl hover:scale-105 transform">
            Shop Now →
          </a>
          <a href="#featured" class="inline-block bg-white/10 backdrop-blur-sm text-white px-10 py-4 rounded-xl font-bold text-lg border-2 border-white/30 hover:bg-white/20 transition-all">
            Explore Products
          </a>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section v-if="categories.length > 0" class="py-16 bg-gradient-to-b from-white to-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold text-gray-900 mb-3">Shop by Category</h2>
          <p class="text-gray-600 text-lg">Browse our curated collections</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div 
            v-for="category in categories" 
            :key="category.id" 
            class="group relative bg-white rounded-2xl p-8 hover:shadow-xl transition-all duration-300 cursor-pointer border border-gray-100 hover:border-blue-200 overflow-hidden"
          >
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-purple-50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative">
              <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-500 rounded-2xl flex items-center justify-center mb-4 mx-auto group-hover:scale-110 transition-transform">
                <span class="text-2xl">📦</span>
              </div>
              <h3 class="font-bold text-lg text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">{{ category.name }}</h3>
              <p class="text-sm text-gray-600">{{ category.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Products -->
    <section id="featured" class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold text-gray-900 mb-3">Featured Products</h2>
          <p class="text-gray-600 text-lg">Handpicked selections just for you</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <ProductCard 
            v-for="product in featuredProducts" 
            :key="product.id" 
            :product="product"
          />
        </div>
        <div class="text-center mt-12">
          <a 
            :href="route('products.index')" 
            class="inline-flex items-center bg-gradient-to-r from-blue-600 to-purple-600 text-white px-10 py-4 rounded-xl font-bold text-lg hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl hover:scale-105 transform"
          >
            View All Products
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
          </a>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
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


