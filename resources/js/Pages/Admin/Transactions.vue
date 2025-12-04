<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">All Transactions</h1>
          <p class="text-gray-600 mt-1">View and manage all payment transactions</p>
        </div>
        <button
          @click="goToProducts"
          class="text-blue-600 hover:text-blue-800 flex items-center gap-2"
        >
          <span>←</span> Back to Products
        </button>
      </div>

      <!-- Success/Error Messages -->
      <div v-if="success" class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-sm">
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <p class="font-medium">{{ success }}</p>
        </div>
      </div>
      <div v-if="error" class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm">
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <p class="font-medium">{{ error }}</p>
        </div>
      </div>

      <!-- Transactions Table -->
      <div v-if="transactions && transactions.length > 0" class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ID
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  User
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Order Number
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Amount
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Provider
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Payment Method
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  #{{ transaction.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ transaction.user_name || 'Guest' }}</div>
                  <div class="text-sm text-gray-500">{{ transaction.user_email || '-' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ transaction.order_number || transaction.order_id || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ transaction.currency }} {{ transaction.amount.toFixed(2) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="{
                      'bg-green-100 text-green-800': transaction.status === 'completed',
                      'bg-yellow-100 text-yellow-800': transaction.status === 'pending',
                      'bg-red-100 text-red-800': transaction.status === 'failed' || transaction.status === 'refunded',
                      'bg-gray-100 text-gray-800': transaction.status === 'cancelled',
                    }"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ transaction.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="{
                          'bg-blue-100 text-blue-800': transaction.payment_provider === 'stripe',
                          'bg-green-100 text-green-800': transaction.payment_provider === 'wallet',
                        }">
                    {{ transaction.payment_provider }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ transaction.payment_method || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ transaction.paid_at || transaction.created_at }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div v-if="transaction.can_refund || transaction.can_refund_to_wallet" class="flex gap-2">
                    <!-- Refund to Original Payment Method -->
                    <button
                      v-if="transaction.can_refund"
                      @click="processRefund(transaction.id, 'stripe')"
                      :disabled="refunding === transaction.id"
                      class="text-red-600 hover:text-red-900 disabled:text-gray-400 disabled:cursor-not-allowed text-xs px-3 py-1 border border-red-300 rounded hover:bg-red-50 transition-colors"
                      title="Refund to original payment method"
                    >
                      {{ refunding === transaction.id ? 'Processing...' : 'Refund' }}
                    </button>
                    <!-- Refund to Wallet -->
                    <button
                      v-if="transaction.can_refund_to_wallet"
                      @click="processRefund(transaction.id, 'wallet')"
                      :disabled="refunding === transaction.id"
                      class="text-green-600 hover:text-green-900 disabled:text-gray-400 disabled:cursor-not-allowed text-xs px-3 py-1 border border-green-300 rounded hover:bg-green-50 transition-colors"
                      title="Refund to user's wallet"
                    >
                      {{ refunding === transaction.id ? 'Processing...' : 'Refund to Wallet' }}
                    </button>
                  </div>
                  <span v-else class="text-gray-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-gray-600 text-lg font-medium">No transactions found</p>
        <p class="text-gray-400 text-sm mt-2">Transactions will appear here once customers make purchases</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'

const route = ziggyRoute

const props = defineProps({
  transactions: {
    type: Array,
    default: () => [],
  },
})

const error = ref('')
const success = ref('')
const refunding = ref(null)

function goToProducts() {
  router.visit(route('products.index'))
}

async function processRefund(transactionId, refundType = 'stripe') {
  const refundTypeText = refundType === 'wallet' ? 'to wallet' : 'to original payment method'
  
  if (!confirm(`Are you sure you want to refund this transaction ${refundTypeText}?`)) {
    return
  }

  refunding.value = transactionId
  error.value = ''
  success.value = ''

  try {
    const endpoint = refundType === 'wallet' 
      ? route('admin.transactions.refund-to-wallet', transactionId)
      : route('admin.transactions.refund', transactionId)

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.error || 'Refund failed')
    }

    success.value = data.message || `Refund ${refundTypeText} processed successfully`
    
    if (data.wallet_balance !== undefined) {
      success.value += ` (New wallet balance: $${data.wallet_balance.toFixed(2)})`
    }
    
    // Reload the page to refresh transaction status
    setTimeout(() => {
      router.reload()
    }, 2000)
  } catch (err) {
    error.value = err.message || 'An error occurred while processing the refund'
  } finally {
    refunding.value = null
  }
}
</script>

<style scoped>
/* Additional styles if needed */
</style>
