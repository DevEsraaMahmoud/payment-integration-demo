<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <div class="flex items-center">
            <a :href="route('home')" class="text-2xl font-bold text-blue-600">
              E-Commerce
            </a>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden md:flex items-center space-x-8">
            <a :href="route('home')" class="text-gray-700 hover:text-blue-600">Home</a>
            <a :href="route('products.index')" class="text-gray-700 hover:text-blue-600">Products</a>
            
            <div v-if="page.props.auth?.user" class="flex items-center space-x-4">
              <a :href="route('orders.index')" class="text-gray-700 hover:text-blue-600">My Orders</a>
              <a :href="route('wallet.index')" class="text-gray-700 hover:text-blue-600">Wallet</a>
              <span class="text-gray-700">{{ page.props.auth.user.name }}</span>
              <form @submit.prevent="logout" method="POST">
                <button type="submit" class="text-gray-700 hover:text-blue-600">Logout</button>
              </form>
            </div>
            <div v-else class="flex items-center space-x-4">
              <a :href="route('login')" class="text-gray-700 hover:text-blue-600">Login</a>
              <a :href="route('register')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Register</a>
            </div>

            <!-- Cart Icon -->
            <button @click="toggleCart" class="relative p-2 text-gray-700 hover:text-blue-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              <span v-if="cartCount > 0" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
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
    <footer class="bg-gray-800 text-white mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <h3 class="text-lg font-semibold mb-4">E-Commerce</h3>
            <p class="text-gray-400">Your trusted online shopping destination</p>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Shop</h4>
            <ul class="space-y-2 text-gray-400">
              <li><a :href="route('products.index')" class="hover:text-white">All Products</a></li>
              <li><a href="#" class="hover:text-white">Categories</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Account</h4>
            <ul class="space-y-2 text-gray-400">
              <li v-if="page.props.auth?.user"><a :href="route('orders.index')" class="hover:text-white">My Orders</a></li>
              <li v-if="page.props.auth?.user"><a :href="route('wallet.index')" class="hover:text-white">Wallet</a></li>
              <li v-else><a :href="route('login')" class="hover:text-white">Login</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Newsletter</h4>
            <p class="text-gray-400 mb-4">Subscribe to get updates</p>
            <form @submit.prevent="subscribeNewsletter" class="flex">
              <input v-model="newsletterEmail" type="email" placeholder="Your email" class="flex-1 px-4 py-2 rounded-l-lg text-gray-900">
              <button type="submit" class="bg-blue-600 px-4 py-2 rounded-r-lg hover:bg-blue-700">Subscribe</button>
            </form>
          </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
          <p>&copy; {{ new Date().getFullYear() }} E-Commerce. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <!-- Cart Drawer -->
    <CartDrawer :open="cartDrawerOpen" @close="cartDrawerOpen = false" />
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
  
  // Load cart data when drawer opens
  if (cartDrawerOpen.value && cartItems.value.length === 0) {
    try {
      const response = await fetch(route('cart.data'))
      const data = await response.json()
      cartItems.value = data.items || []
      cartTotal.value = data.total || 0
    } catch (error) {
      console.error('Failed to load cart data:', error)
    }
  }
}

// Watch for cart changes and reload
router.on('success', () => {
  if (cartDrawerOpen.value) {
    fetch(route('cart.data'))
      .then(res => res.json())
      .then(data => {
        cartItems.value = data.items || []
        cartTotal.value = data.total || 0
      })
  }
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

