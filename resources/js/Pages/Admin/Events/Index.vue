<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header with Create Button -->
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-dark">Events</h2>
        <Link
          href="/admin/events/create"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          Create Event
        </Link>
      </div>

      <!-- Events Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Title</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Location</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Type</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="event in events" :key="event.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <p class="font-medium text-dark">{{ event.title }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(event.starts_at) }}</td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ event.venue_name }}</td>
              <td class="px-6 py-4">
                <span class="text-xs font-medium px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                  {{ capitalize(event.event_type) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="text-xs font-medium px-3 py-1 rounded-full" :class="statusBadgeColor(event.is_active)">
                  {{ event.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-3">
                  <Link
                    :href="`/admin/events/${event.id}/edit`"
                    class="text-primary hover:text-primary-dark font-medium text-sm"
                  >
                    Edit
                  </Link>
                  <Link
                    :href="`/admin/events/${event.id}/rsvps`"
                    class="text-blue-600 hover:text-blue-800 font-medium text-sm"
                  >
                    RSVPs
                  </Link>
                  <button
                    @click="deleteEvent(event.id)"
                    class="text-red-600 hover:text-red-800 font-medium text-sm"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="events.length === 0" class="text-center py-12">
          <p class="text-gray-500">No events found</p>
        </div>
      </div>

      <!-- Pagination Info -->
      <div v-if="page.props.events?.data && page.props.events.data.length > 0" class="text-center text-sm text-gray-600">
        Showing {{ page.props.events.from }} to {{ page.props.events.to }} of {{ page.props.events.total }} events
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()
const confirmDialog = ref(null)

const events = computed(() => page.props.events?.data || [])

const statusBadgeColor = (isActive) => {
  return isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
}

const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const deleteEvent = async (eventId) => {
  const confirmed = await confirmDialog.value.open(
    'Delete Event',
    'Are you sure you want to delete this event? This action cannot be undone.',
    {
      confirmText: 'Delete',
      isDangerous: true
    }
  )
  if (confirmed) {
    router.delete(`/admin/events/${eventId}`)
  }
}
</script>
