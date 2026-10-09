<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <Link href="/admin/events" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        All events
      </Link>

      <!-- Event Header -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-2xl font-bold text-dark break-words">{{ event.title }}</h1>
              <span v-if="!event.rsvp_enabled" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">RSVPs closed</span>
            </div>
            <dl class="mt-2 space-y-1 text-sm text-gray-600">
              <div class="flex gap-2">
                <dt class="sr-only">When</dt>
                <dd>{{ eventDate }}</dd>
              </div>
              <div class="flex gap-2">
                <dt class="sr-only">Where</dt>
                <dd>{{ event.venue_name }}<template v-if="event.address">, {{ event.address }}</template><template v-if="event.lga"> ({{ event.lga }})</template></dd>
              </div>
            </dl>
          </div>
          <div class="flex flex-wrap gap-2 flex-shrink-0">
            <Link
              v-if="canManage"
              :href="`/admin/events/${event.id}/edit`"
              class="px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
            >
              Edit event
            </Link>
            <a
              v-if="canManage"
              :href="`/admin/exports/events/${event.id}/rsvps`"
              class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Export CSV
            </a>
          </div>
        </div>

        <!-- Capacity -->
        <div class="mt-5 pt-5 border-t">
          <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
            <p class="text-sm text-gray-600">
              <span class="text-2xl font-bold text-dark">{{ statusCounts.confirmed || 0 }}</span>
              confirmed<template v-if="event.capacity"> of {{ event.capacity }} places</template>
            </p>
            <p class="text-sm text-gray-500">
              <template v-if="event.capacity">{{ placesLeftText }} · </template>{{ statusCounts.cancelled || 0 }} cancelled
            </p>
          </div>
          <div v-if="event.capacity" class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full"
              :class="fillPercent >= 100 ? 'bg-red-500' : fillPercent >= 80 ? 'bg-amber-500' : 'bg-green-500'"
              :style="{ width: Math.min(fillPercent, 100) + '%' }"
            />
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-4">
        <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
          <button
            v-for="status in statuses"
            :key="status"
            type="button"
            @click="visit({ status: status === 'all' ? undefined : status })"
            class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
            :class="activeStatus === status ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          >
            {{ capitalize(status) }}
            <span class="ml-2 font-semibold">{{ statusCounts[status] || 0 }}</span>
          </button>
        </div>
        <div class="relative">
          <label for="rsvp-search" class="sr-only">Search</label>
          <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            id="rsvp-search"
            v-model="searchQuery"
            type="search"
            placeholder="Search name, email or phone..."
            class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>
      </div>

      <!-- RSVPs List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b text-xs font-semibold text-gray-600 uppercase">
          <div class="col-span-4">Attendee</div>
          <div class="col-span-2">Phone</div>
          <div class="col-span-2">LGA</div>
          <div class="col-span-1">Status</div>
          <div class="col-span-1">RSVP'd</div>
          <div class="col-span-2 text-right">Action</div>
        </div>
        <div class="divide-y">
          <div
            v-for="rsvp in rsvps.data"
            :key="rsvp.id"
            class="md:grid md:grid-cols-12 md:gap-4 md:items-center px-4 sm:px-6 py-4"
            :class="{ 'opacity-60': rsvp.status === 'cancelled' }"
          >
            <div class="md:col-span-4 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <p class="font-medium text-dark truncate">{{ rsvp.name }}</p>
                <span class="md:hidden text-xs font-medium px-2.5 py-1 rounded-full flex-shrink-0" :class="statusBadgeColor(rsvp.status)">
                  {{ capitalize(rsvp.status) }}
                </span>
              </div>
              <a :href="`mailto:${rsvp.email}`" class="text-sm text-gray-500 hover:text-primary truncate block">{{ rsvp.email }}</a>
            </div>
            <div class="md:col-span-2 text-sm mt-1 md:mt-0">
              <a v-if="rsvp.phone" :href="`tel:${rsvp.phone}`" class="text-gray-600 hover:text-primary">{{ rsvp.phone }}</a>
              <span v-else class="hidden md:inline text-gray-400">—</span>
            </div>
            <div class="md:col-span-2 text-sm text-gray-600">{{ rsvp.lga || '' }}<span v-if="!rsvp.lga" class="hidden md:inline text-gray-400">—</span></div>
            <div class="hidden md:block md:col-span-1">
              <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusBadgeColor(rsvp.status)">
                {{ capitalize(rsvp.status) }}
              </span>
            </div>
            <div class="md:col-span-1 text-sm text-gray-500" :title="fullDate(rsvp.created_at)">
              <span class="md:hidden">RSVP'd </span>{{ formatDate(rsvp.created_at) }}
            </div>
            <div class="md:col-span-2 mt-2 md:mt-0 flex gap-2 md:justify-end">
              <button
                type="button"
                :disabled="updatingId === rsvp.id"
                class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
                @click="toggleStatus(rsvp)"
              >
                {{ rsvp.status === 'confirmed' ? 'Cancel' : 'Restore' }}
              </button>
              <button
                v-if="canManage"
                type="button"
                class="px-3 py-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg font-medium text-sm transition-colors"
                @click="deleteRsvp(rsvp)"
              >
                Delete
              </button>
            </div>
          </div>
        </div>

        <div v-if="!rsvps.data?.length" class="text-center py-12 px-4">
          <p class="text-gray-500">{{ emptyMessage }}</p>
          <button
            v-if="isFiltered"
            type="button"
            class="mt-3 text-sm text-primary hover:text-primary-dark font-medium"
            @click="clearFilters"
          >
            Clear filters
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="rsvps.data?.length" class="space-y-2">
        <Pagination v-if="rsvps.last_page > 1" :links="rsvps.links" />
        <p class="text-center text-sm text-gray-600">
          Showing {{ rsvps.from }} to {{ rsvps.to }} of {{ rsvps.total }} RSVPs
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

const event = computed(() => page.props.event || {})
const rsvps = computed(() => page.props.rsvps || { data: [] })
const statusCounts = computed(() => page.props.statusCounts || {})
const filters = computed(() => page.props.filters || {})
const canManage = computed(() => ['admin', 'editor'].includes(page.props.auth?.user?.role))

// Matches the event_rsvps.status column
const statuses = ['all', 'confirmed', 'cancelled']

const activeStatus = computed(() => filters.value.status || 'all')
const searchQuery = ref(filters.value.search || '')
const updatingId = ref(null)

const isFiltered = computed(() => !!(filters.value.status || filters.value.search))

const fillPercent = computed(() =>
  event.value.capacity ? Math.round(((statusCounts.value.confirmed || 0) / event.value.capacity) * 100) : 0
)

const placesLeftText = computed(() => {
  const left = (event.value.capacity || 0) - (statusCounts.value.confirmed || 0)
  if (left <= 0) return 'Full'
  return `${left} place${left === 1 ? '' : 's'} left`
})

const eventDate = computed(() => {
  if (!event.value.starts_at) return ''
  const start = new Date(event.value.starts_at)
  const text = start.toLocaleString(undefined, {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: 'numeric', minute: '2-digit',
  })
  if (!event.value.ends_at) return text
  return `${text} – ${new Date(event.value.ends_at).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })}`
})

const emptyMessage = computed(() => {
  if (filters.value.search) return `No RSVPs match "${filters.value.search}"`
  if (filters.value.status) return `No ${filters.value.status} RSVPs`
  return event.value.rsvp_enabled ? 'No one has RSVP\'d yet' : 'RSVPs are turned off for this event'
})

const visit = (params) => {
  const query = {
    status: filters.value.status || undefined,
    search: searchQuery.value.trim() || undefined,
    ...params,
  }
  router.get(`/admin/events/${event.value.id}/rsvps`, query, { preserveState: true, preserveScroll: true, replace: true })
}

let searchTimer
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => visit({}), 300)
})

const clearFilters = () => {
  searchQuery.value = ''
  clearTimeout(searchTimer)
  router.get(`/admin/events/${event.value.id}/rsvps`, {}, { preserveState: true, replace: true })
}

const toggleStatus = async (rsvp) => {
  const status = rsvp.status === 'confirmed' ? 'cancelled' : 'confirmed'
  if (status === 'cancelled') {
    const ok = await confirmDialog.value.open('Cancel RSVP', `Cancel ${rsvp.name}'s RSVP? This frees up their place.`, {
      confirmText: 'Cancel RSVP',
    })
    if (!ok) return
  }

  updatingId.value = rsvp.id
  router.patch(`/admin/events/${event.value.id}/rsvps/${rsvp.id}`, { status }, {
    preserveScroll: true,
    onFinish: () => { updatingId.value = null },
  })
}

const deleteRsvp = async (rsvp) => {
  const confirmed = await confirmDialog.value.open(
    'Delete RSVP',
    `Permanently delete ${rsvp.name}'s RSVP? To keep a record, cancel it instead.`,
    { confirmText: 'Delete', isDangerous: true }
  )
  if (confirmed) {
    router.delete(`/admin/events/${event.value.id}/rsvps/${rsvp.id}`, { preserveScroll: true })
  }
}

const statusBadgeColor = (status) => ({
  confirmed: 'bg-green-100 text-green-800',
  cancelled: 'bg-gray-100 text-gray-700',
}[status] || 'bg-gray-100 text-gray-800')

const capitalize = (str) => (str ? str.charAt(0).toUpperCase() + str.slice(1) : '')

const formatDate = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  if (d.getFullYear() === new Date().getFullYear()) {
    return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' })
  }
  return d.toLocaleDateString()
}

const fullDate = (date) => (date ? new Date(date).toLocaleString() : '')
</script>
