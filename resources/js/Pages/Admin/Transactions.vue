<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Transactions</h1>
        <a
          :href="route('products.index')"
          class="text-blue-600 hover:text-blue-800"
        >
          Back to Products
        </a>
      </div>

      <div v-if="error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>
      <div v-if="success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        {{ success }}
      </div>

      <div v-if="transactions && transactions.length > 0" class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ID
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
            <tr v-for="transaction in transactions" :key="transaction.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ transaction.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ transaction.order_number || transaction.order_id }}
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
                  }"
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                >
                  {{ transaction.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ transaction.payment_provider }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ transaction.payment_method || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ transaction.paid_at || transaction.created_at }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  v-if="transaction.can_refund"
                  @click="processRefund(transaction.id)"
                  :disabled="refunding === transaction.id"
                  class="text-red-600 hover:text-red-900 disabled:text-gray-400 disabled:cursor-not-allowed"
                >
                  {{ refunding === transaction.id ? 'Processing...' : 'Refund' }}
                </button>
                <span v-else class="text-gray-400">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="bg-white rounded-lg shadow-md p-8 text-center">
        <p class="text-gray-600 text-lg">No transactions found.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'

// Make route available in template
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

async function processRefund(transactionId) {
  if (!confirm('Are you sure you want to refund this transaction?')) {
    return
  }

  refunding.value = transactionId
  error.value = ''
  success.value = ''

  try {
    const response = await fetch(route('admin.transactions.refund', transactionId), {
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

    success.value = data.message || 'Refund processed successfully'
    
    // Reload the page to refresh transaction status
    setTimeout(() => {
      router.reload()
    }, 1500)
  } catch (err) {
    error.value = err.message || 'An error occurred while processing the refund'
  } finally {
    refunding.value = null
  }
}
</script>


