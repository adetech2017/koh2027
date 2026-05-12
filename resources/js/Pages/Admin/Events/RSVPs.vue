<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Event Header -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
          <div>
            <h2 class="text-2xl font-bold text-dark mb-2">{{ event.title }}</h2>
            <div class="text-gray-600 space-y-1">
              <p>📅 {{ formatDate(event.date) }}</p>
              <p v-if="event.location">📍 {{ event.location }}</p>
              <p v-if="event.capacity">👥 Capacity: {{ event.capacity }} people</p>
            </div>
          </div>
          <a
            :href="`/admin/exports/events/${event.id}/rsvps`"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
          >
            Export RSVPs
          </a>
        </div>
      </div>

      <!-- RSVP Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm font-medium">Total RSVPs</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ statusCounts.total || 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm font-medium">Confirmed</p>
          <p class="text-3xl font-bold text-green-600 mt-2">{{ statusCounts.confirmed || 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm font-medium">Pending</p>
          <p class="text-3xl font-bold text-yellow-600 mt-2">{{ statusCounts.pending || 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm font-medium">Declined</p>
          <p class="text-3xl font-bold text-red-600 mt-2">{{ statusCounts.declined || 0 }}</p>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex gap-2 overflow-x-auto pb-2">
          <button
            v-for="status in ['all', 'pending', 'confirmed', 'declined', 'cancelled']"
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
            <span class="ml-2 font-semibold">{{ statusCounts[status] || 0 }}</span>
          </button>
        </div>
      </div>

      <!-- RSVPs Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">LGA</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="rsvp in filteredRsvps" :key="rsvp.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <p class="font-medium text-dark">{{ rsvp.volunteer?.name }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ rsvp.volunteer?.email }}</td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ rsvp.volunteer?.lga || 'N/A' }}</td>
              <td class="px-6 py-4">
                <select
                  :value="rsvp.status"
                  @change="(e) => updateRsvpStatus(rsvp.id, e.target.value)"
                  class="text-xs font-medium px-3 py-1 rounded border focus:outline-none focus:ring-2 focus:ring-primary"
                >
                  <option value="pending">Pending</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="declined">Declined</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(rsvp.created_at) }}</td>
              <td class="px-6 py-4 text-right">
                <button
                  @click="deleteRsvp(rsvp.id)"
                  class="text-red-600 hover:text-red-800 font-medium text-sm"
                >
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="filteredRsvps.length === 0" class="text-center py-12">
          <p class="text-gray-500">No RSVPs found</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()
const confirmDialog = ref(null)

const event = computed(() => page.props.event || {})
const rsvps = computed(() => page.props.rsvps || { data: [] })
const statusCounts = computed(() => page.props.statusCounts || {})

const filterStatus = ref('all')
const updatingRsvp = ref(null)

const filteredRsvps = computed(() => {
  let filtered = rsvps.value.data || []

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(r => r.status === filterStatus.value)
  }

  return filtered
})

const updateRsvpStatus = (rsvpId, newStatus) => {
  updatingRsvp.value = rsvpId

  router.patch(`/admin/events/${event.value.id}/rsvps/${rsvpId}`, {
    status: newStatus
  }, {
    onSuccess: () => {
      updatingRsvp.value = null
    },
    onError: () => {
      updatingRsvp.value = null
    }
  })
}

const deleteRsvp = async (rsvpId) => {
  const confirmed = await confirmDialog.value.open(
    'Delete RSVP',
    'Are you sure you want to delete this RSVP? This action cannot be undone.',
    {
      confirmText: 'Delete',
      isDangerous: true
    }
  )
  if (confirmed) {
    router.delete(`/admin/events/${event.value.id}/rsvps/${rsvpId}`)
  }
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
