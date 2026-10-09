<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Filters & Create -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              type="button"
              @click="visit({ when: tab.key })"
              class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
              :class="filters.when === tab.key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            >
              {{ tab.label }}
              <span class="ml-2 font-semibold">{{ counts[tab.key] || 0 }}</span>
            </button>
          </div>
          <Link
            href="/admin/events/create"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New event
          </Link>
        </div>
        <div class="relative">
          <label for="event-search" class="sr-only">Search</label>
          <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            id="event-search"
            v-model="searchQuery"
            type="search"
            placeholder="Search title, venue or LGA..."
            class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>
      </div>

      <!-- Events List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="divide-y">
          <div v-for="event in events.data" :key="event.id" class="flex flex-col md:flex-row md:items-center gap-4 px-4 sm:px-6 py-4">
            <!-- Date block -->
            <div class="flex md:block items-center gap-4 flex-shrink-0">
              <div class="w-14 text-center rounded-lg border overflow-hidden" :class="isPast(event) ? 'opacity-60' : ''">
                <div class="bg-primary text-white text-[11px] font-semibold uppercase py-0.5">{{ month(event.starts_at) }}</div>
                <div class="text-xl font-bold text-dark py-1">{{ day(event.starts_at) }}</div>
              </div>
              <img
                v-if="event.image_url"
                :src="event.image_url"
                alt=""
                class="md:hidden w-20 h-14 object-cover rounded"
              />
            </div>

            <img
              v-if="event.image_url"
              :src="event.image_url"
              alt=""
              class="hidden md:block w-24 h-16 object-cover rounded flex-shrink-0"
            />

            <!-- Details -->
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <Link :href="`/admin/events/${event.id}/edit`" class="font-semibold text-dark hover:text-primary break-words">
                  {{ event.title }}
                </Link>
                <span v-if="!event.is_active" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">Draft</span>
                <span v-if="event.is_featured" class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Featured</span>
                <span v-if="isPast(event)" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Past</span>
              </div>
              <p class="text-sm text-gray-600 mt-0.5">
                {{ timeRange(event) }} · {{ typeLabel(event.event_type) }}
              </p>
              <p class="text-sm text-gray-500 truncate">{{ event.venue_name }}<template v-if="event.lga">, {{ event.lga }}</template></p>
            </div>

            <!-- RSVPs -->
            <div class="md:w-44 flex-shrink-0">
              <template v-if="event.rsvp_enabled">
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                  <span>{{ event.confirmed_rsvps_count }} RSVPs</span>
                  <span v-if="event.capacity">of {{ event.capacity }}</span>
                </div>
                <div v-if="event.capacity" class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full"
                    :class="fillPercent(event) >= 100 ? 'bg-red-500' : fillPercent(event) >= 80 ? 'bg-amber-500' : 'bg-green-500'"
                    :style="{ width: Math.min(fillPercent(event), 100) + '%' }"
                  />
                </div>
                <p v-else class="text-xs text-gray-400">No capacity limit</p>
              </template>
              <p v-else class="text-xs text-gray-400">RSVPs off</p>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 md:justify-end flex-shrink-0">
              <Link
                :href="`/admin/events/${event.id}/edit`"
                class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
              >
                Edit
              </Link>
              <Link
                :href="`/admin/events/${event.id}/rsvps`"
                class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
              >
                RSVPs
              </Link>
              <button
                v-if="canDelete"
                type="button"
                class="px-3 py-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg font-medium text-sm transition-colors"
                @click="deleteEvent(event)"
              >
                Delete
              </button>
            </div>
          </div>
        </div>

        <div v-if="!events.data?.length" class="text-center py-12 px-4">
          <p class="text-gray-500">{{ emptyMessage }}</p>
          <Link
            v-if="!filters.search && filters.when !== 'past'"
            href="/admin/events/create"
            class="inline-block mt-3 text-sm text-primary hover:text-primary-dark font-medium"
          >
            Create an event →
          </Link>
          <button
            v-else-if="filters.search"
            type="button"
            class="mt-3 text-sm text-primary hover:text-primary-dark font-medium"
            @click="searchQuery = ''"
          >
            Clear search
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="events.data?.length" class="space-y-2">
        <Pagination v-if="events.last_page > 1" :links="events.links" />
        <p class="text-center text-sm text-gray-600">
          Showing {{ events.from }} to {{ events.to }} of {{ events.total }} events
        </p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import Pagination from '@/Components/Pagination.vue'

const page = usePage()
const confirmDialog = ref(null)

const events = computed(() => page.props.events || { data: [] })
const counts = computed(() => page.props.counts || {})
const filters = computed(() => page.props.filters || { when: 'upcoming' })
const canDelete = computed(() => page.props.auth?.user?.role === 'admin')

const tabs = [
  { key: 'upcoming', label: 'Upcoming' },
  { key: 'past', label: 'Past' },
  { key: 'all', label: 'All' },
]

const searchQuery = ref(filters.value.search || '')

const visit = (params) => {
  const query = {
    when: filters.value.when,
    search: searchQuery.value.trim() || undefined,
    ...params,
  }
  if (query.when === 'upcoming') query.when = undefined
  router.get('/admin/events', query, { preserveState: true, preserveScroll: true, replace: true })
}

let searchTimer
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => visit({}), 300)
})

const emptyMessage = computed(() => {
  if (filters.value.search) return `No events match "${filters.value.search}"`
  if (filters.value.when === 'past') return 'No past events'
  if (filters.value.when === 'upcoming') return 'No upcoming events scheduled'
  return 'No events yet'
})

const typeLabels = {
  rally: 'Rally',
  townhall: 'Town Hall',
  fundraiser: 'Fundraiser',
  workshop: 'Workshop',
  meeting: 'Meeting',
  other: 'Other',
}
const typeLabel = (type) => typeLabels[type] || type

const isPast = (event) => new Date(event.ends_at || event.starts_at) < new Date()

const fillPercent = (event) => (event.capacity ? Math.round((event.confirmed_rsvps_count / event.capacity) * 100) : 0)

const month = (date) => new Date(date).toLocaleDateString(undefined, { month: 'short' })
const day = (date) => new Date(date).getDate()

const timeRange = (event) => {
  const start = new Date(event.starts_at)
  const dateText = start.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
  const time = (d) => d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
  if (!event.ends_at) return `${dateText}, ${time(start)}`
  const end = new Date(event.ends_at)
  return start.toDateString() === end.toDateString()
    ? `${dateText}, ${time(start)} – ${time(end)}`
    : `${dateText}, ${time(start)} – ${end.toLocaleDateString(undefined, { day: 'numeric', month: 'short' })} ${time(end)}`
}

const deleteEvent = async (event) => {
  const rsvpNote = event.confirmed_rsvps_count
    ? ` Its ${event.confirmed_rsvps_count} RSVPs will be deleted too.`
    : ''
  const confirmed = await confirmDialog.value.open(
    'Delete event',
    `Delete "${event.title}"? This cannot be undone.${rsvpNote}`,
    { confirmText: 'Delete', isDangerous: true }
  )
  if (confirmed) {
    router.delete(`/admin/events/${event.id}`, { preserveScroll: true })
  }
}
</script>
