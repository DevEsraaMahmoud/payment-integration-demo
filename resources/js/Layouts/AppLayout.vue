<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white shadow-md border-b border-gray-100 sticky top-0 z-40 backdrop-blur-sm bg-white/95">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
          <!-- Logo -->
          <div class="flex items-center">
            <a :href="route('home')" class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent hover:from-blue-700 hover:to-purple-700 transition-all">
              🛍️ E-Commerce
            </a>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden md:flex items-center space-x-6">
            <a :href="route('home')" class="text-gray-700 hover:text-blue-600 font-medium transition-colors px-3 py-2 rounded-lg hover:bg-gray-50">Home</a>
            <a :href="route('products.index')" class="text-gray-700 hover:text-blue-600 font-medium transition-colors px-3 py-2 rounded-lg hover:bg-gray-50">Products</a>
            
            <div v-if="page.props.auth?.user" class="flex items-center space-x-3 border-l border-gray-200 pl-6">
              <a :href="route('orders.index')" class="text-gray-700 hover:text-blue-600 font-medium transition-colors px-3 py-2 rounded-lg hover:bg-gray-50">Orders</a>
              <a :href="route('wallet.index')" class="text-gray-700 hover:text-blue-600 font-medium transition-colors px-3 py-2 rounded-lg hover:bg-gray-50">Wallet</a>
              <div class="flex items-center space-x-2 px-3 py-2 bg-gray-50 rounded-lg">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                  {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                </div>
                <span class="text-gray-700 font-medium">{{ page.props.auth.user.name }}</span>
              </div>
              <form @submit.prevent="logout" method="POST">
                <button type="submit" class="text-gray-700 hover:text-red-600 font-medium transition-colors px-3 py-2 rounded-lg hover:bg-red-50">Logout</button>
              </form>
            </div>
            <div v-else class="flex items-center space-x-3 border-l border-gray-200 pl-6">
              <a :href="route('login')" class="text-gray-700 hover:text-blue-600 font-medium transition-colors px-4 py-2 rounded-lg hover:bg-gray-50">Login</a>
              <a :href="route('register')" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-5 py-2 rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 transition-all shadow-sm hover:shadow-md">Sign Up</a>
            </div>

            <!-- Cart Icon -->
            <button @click="toggleCart" class="relative p-2 text-gray-700 hover:text-blue-600 transition-colors rounded-lg hover:bg-gray-50">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              <span v-if="cartCount > 0" class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center shadow-lg animate-pulse">
                {{ cartCount }}
              </span>
            </button>
          </div>

          <!-- Mobile menu button -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile menu -->
      <div v-if="mobileMenuOpen" class="md:hidden border-t border-gray-200">
        <div class="px-2 pt-2 pb-3 space-y-1">
          <a :href="route('home')" class="block px-3 py-2 text-gray-700">Home</a>
          <a :href="route('products.index')" class="block px-3 py-2 text-gray-700">Products</a>
          <div v-if="page.props.auth?.user">
            <a :href="route('orders.index')" class="block px-3 py-2 text-gray-700">My Orders</a>
            <a :href="route('wallet.index')" class="block px-3 py-2 text-gray-700">Wallet</a>
            <form @submit.prevent="logout" method="POST">
              <button type="submit" class="block w-full text-left px-3 py-2 text-gray-700">Logout</button>
            </form>
          </div>
          <div v-else>
            <a :href="route('login')" class="block px-3 py-2 text-gray-700">Login</a>
            <a :href="route('register')" class="block px-3 py-2 text-gray-700">Register</a>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
          <div>
            <h3 class="text-2xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">E-Commerce</h3>
            <p class="text-gray-400 leading-relaxed">Your trusted online shopping destination. Quality products, fast delivery, secure payments.</p>
            <div class="flex space-x-4 mt-6">
              <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-600 transition-colors">
                <span class="text-lg">📘</span>
              </a>
              <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-400 transition-colors">
                <span class="text-lg">🐦</span>
              </a>
              <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors">
                <span class="text-lg">📷</span>
              </a>
            </div>
          </div>
          <div>
            <h4 class="font-semibold text-lg mb-4">Shop</h4>
            <ul class="space-y-3 text-gray-400">
              <li><a :href="route('products.index')" class="hover:text-white transition-colors flex items-center group">
                <span class="mr-2 group-hover:translate-x-1 transition-transform">→</span> All Products
              </a></li>
              <li><a href="#" class="hover:text-white transition-colors flex items-center group">
                <span class="mr-2 group-hover:translate-x-1 transition-transform">→</span> Categories
              </a></li>
              <li><a href="#" class="hover:text-white transition-colors flex items-center group">
                <span class="mr-2 group-hover:translate-x-1 transition-transform">→</span> New Arrivals
              </a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold text-lg mb-4">Account</h4>
            <ul class="space-y-3 text-gray-400">
              <li v-if="page.props.auth?.user"><a :href="route('orders.index')" class="hover:text-white transition-colors flex items-center group">
                <span class="mr-2 group-hover:translate-x-1 transition-transform">→</span> My Orders
              </a></li>
              <li v-if="page.props.auth?.user"><a :href="route('wallet.index')" class="hover:text-white transition-colors flex items-center group">
                <span class="mr-2 group-hover:translate-x-1 transition-transform">→</span> Wallet
              </a></li>
              <li v-else><a :href="route('login')" class="hover:text-white transition-colors flex items-center group">
                <span class="mr-2 group-hover:translate-x-1 transition-transform">→</span> Login
              </a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold text-lg mb-4">Newsletter</h4>
            <p class="text-gray-400 mb-4">Get exclusive deals and updates</p>
            <form @submit.prevent="subscribeNewsletter" class="space-y-3">
              <input 
                v-model="newsletterEmail" 
                type="email" 
                placeholder="Enter your email" 
                class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
              <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-3 rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                Subscribe
              </button>
            </form>
          </div>
        </div>
        <div class="border-t border-gray-700 mt-12 pt-8">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <p class="text-gray-400">&copy; {{ new Date().getFullYear() }} E-Commerce. All rights reserved.</p>
            <div class="flex space-x-6 mt-4 md:mt-0">
              <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
              <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
              <a href="#" class="text-gray-400 hover:text-white transition-colors">Contact</a>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <!-- Cart Drawer -->
    <CartDrawer 
      :open="cartDrawerOpen" 
      :items="cartItems" 
      :total="cartTotal"
      @close="cartDrawerOpen = false" 
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import CartDrawer from '@/Components/CartDrawer.vue'

const route = ziggyRoute
const page = usePage()
const mobileMenuOpen = ref(false)
const cartDrawerOpen = ref(false)
const newsletterEmail = ref('')

const cartCount = computed(() => page.props.cartCount || 0)
const cartItems = ref([])
const cartTotal = ref(0)

async function toggleCart() {
  cartDrawerOpen.value = !cartDrawerOpen.value
  
  // Always load cart data when drawer opens to get latest state
  if (cartDrawerOpen.value) {
    try {
      const response = await fetch(route('cart.data'))
      const data = await response.json()
      cartItems.value = data.items || []
      cartTotal.value = data.total || 0
    } catch (error) {
      console.error('Failed to load cart data:', error)
      cartItems.value = []
      cartTotal.value = 0
    }
  }
}

// Watch for cart changes and reload
router.on('success', () => {
  // Reload cart data after any successful navigation/action
  fetch(route('cart.data'))
    .then(res => res.json())
    .then(data => {
      cartItems.value = data.items || []
      cartTotal.value = data.total || 0
    })
    .catch(error => {
      console.error('Failed to reload cart data:', error)
      cartItems.value = []
      cartTotal.value = 0
    })
})

function logout() {
  router.post(route('logout'), {}, {
    preserveState: false,
    preserveScroll: false,
  })
}

function subscribeNewsletter() {
  // TODO: Implement newsletter subscription
  alert('Newsletter subscription coming soon!')
  newsletterEmail.value = ''
}
</script>

