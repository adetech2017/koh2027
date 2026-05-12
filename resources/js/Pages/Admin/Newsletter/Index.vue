<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Funnel Summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm font-medium">Pending Confirmation</p>
          <p class="text-3xl font-bold text-yellow-600 mt-2">{{ funnel.pending }}</p>
          <p class="text-xs text-gray-500 mt-2">Awaiting confirmation</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm font-medium">Confirmed</p>
          <p class="text-3xl font-bold text-green-600 mt-2">{{ funnel.confirmed }}</p>
          <p class="text-xs text-gray-500 mt-2">{{ confirmedPercentage }}% conversion</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm font-medium">Unsubscribed</p>
          <p class="text-3xl font-bold text-red-600 mt-2">{{ funnel.unsubscribed }}</p>
          <p class="text-xs text-gray-500 mt-2">Opted out</p>
        </div>
      </div>

      <!-- Conversion Funnel Visualization -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-dark mb-6">Subscriber Funnel</h3>
        <div class="space-y-4">
          <div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-dark">All Subscriptions</span>
              <span class="text-sm font-bold">{{ totalSubscribers }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
              <div class="bg-blue-500 h-4 rounded-full" style="width: 100%" />
            </div>
          </div>
          <div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-dark">Confirmed</span>
              <span class="text-sm font-bold">{{ funnel.confirmed }} ({{ confirmedPercentage }}%)</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
              <div class="bg-green-500 h-4 rounded-full" :style="{ width: confirmedPercentage + '%' }" />
            </div>
          </div>
          <div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-dark">Unsubscribed</span>
              <span class="text-sm font-bold">{{ funnel.unsubscribed }} ({{ unsubscribedPercentage }}%)</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
              <div class="bg-red-500 h-4 rounded-full" :style="{ width: unsubscribedPercentage + '%' }" />
            </div>
          </div>
        </div>
      </div>

      <!-- Filters & Export -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row gap-4 items-end">
          <div class="flex-1">
            <label class="text-sm font-semibold text-gray-600 mb-2 block">Status</label>
            <div class="flex gap-2 overflow-x-auto pb-2">
              <button
                v-for="status in ['all', 'pending', 'confirmed', 'unsubscribed']"
                :key="status"
                @click="filterStatus = status"
                class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
                :class="
                  filterStatus === status
                    ? 'bg-primary text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                "
              >
                {{ capitalize(status) }}
              </button>
            </div>
          </div>
          <a
            href="/admin/exports/subscribers"
            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors whitespace-nowrap"
          >
            Export CSV
          </a>
        </div>
      </div>

      <!-- Subscribers Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Confirmed</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Joined</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="subscriber in filteredSubscribers" :key="subscriber.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-medium text-dark">{{ subscriber.email }}</td>
              <td class="px-6 py-4 text-gray-600">{{ subscriber.name || 'N/A' }}</td>
              <td class="px-6 py-4">
                <span class="text-xs font-medium px-3 py-1 rounded-full" :class="statusBadgeColor(subscriber.status)">
                  {{ capitalize(subscriber.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ subscriber.confirmed_at ? formatDate(subscriber.confirmed_at) : 'Pending' }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(subscriber.created_at) }}</td>
            </tr>
          </tbody>
        </table>

        <div v-if="filteredSubscribers.length === 0" class="text-center py-12">
          <p class="text-gray-500">No subscribers found</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

const subscribers = computed(() => page.props.subscribers || { data: [] })
const funnel = computed(() => page.props.funnel || {})

const filterStatus = ref('all')

const filteredSubscribers = computed(() => {
  let filtered = subscribers.value.data || []

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(s => s.status === filterStatus.value)
  }

  return filtered
})

const totalSubscribers = computed(() => {
  return (funnel.value.pending || 0) + (funnel.value.confirmed || 0) + (funnel.value.unsubscribed || 0)
})

const confirmedPercentage = computed(() => {
  if (totalSubscribers.value === 0) return 0
  return Math.round((funnel.value.confirmed / totalSubscribers.value) * 100)
})

const unsubscribedPercentage = computed(() => {
  if (totalSubscribers.value === 0) return 0
  return Math.round((funnel.value.unsubscribed / totalSubscribers.value) * 100)
})

const statusBadgeColor = (status) => {
  const colors = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'confirmed': 'bg-green-100 text-green-800',
    'unsubscribed': 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>
