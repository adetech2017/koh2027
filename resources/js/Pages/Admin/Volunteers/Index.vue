<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Filter Controls -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="space-y-4">
          <!-- Status Tabs -->
          <div class="flex gap-2 overflow-x-auto pb-2 -mx-1 px-1">
            <button
              v-for="status in statuses"
              :key="status"
              type="button"
              @click="visit({ status: status === 'all' ? undefined : status })"
              class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
              :class="
                activeStatus === status
                  ? 'bg-primary text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              "
            >
              {{ capitalize(status) }}
              <span
                class="ml-2 font-semibold"
                :class="{ 'text-yellow-600': status === 'pending' && activeStatus !== 'pending' && statusCounts.pending }"
              >{{ statusCounts[status] || 0 }}</span>
            </button>
          </div>

          <!-- Search, LGA, Vehicle, Export -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
            <div class="relative sm:col-span-2 lg:col-span-5">
              <label for="volunteer-search" class="sr-only">Search</label>
              <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                id="volunteer-search"
                v-model="searchQuery"
                type="search"
                placeholder="Search name, email or phone..."
                class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>
            <div class="lg:col-span-3">
              <label for="lga-filter" class="sr-only">LGA</label>
              <select
                id="lga-filter"
                :value="filters.lga || ''"
                @change="visit({ lga: $event.target.value || undefined })"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">All LGAs</option>
                <option v-for="lga in lgaList" :key="lga" :value="lga">{{ lga }}</option>
              </select>
            </div>
            <div class="lg:col-span-2">
              <label for="vehicle-filter" class="sr-only">Vehicle</label>
              <select
                id="vehicle-filter"
                :value="filters.vehicle || ''"
                @change="visit({ vehicle: $event.target.value || undefined })"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">Any vehicle</option>
                <option value="yes">Has vehicle</option>
                <option value="no">No vehicle</option>
              </select>
            </div>
            <a
              v-if="canExport"
              :href="exportUrl"
              class="sm:col-span-2 lg:col-span-2 inline-flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Export CSV
            </a>
          </div>
        </div>
      </div>

      <!-- Volunteers List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="hidden lg:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b text-xs font-semibold text-gray-600 uppercase">
          <div class="col-span-3">Volunteer</div>
          <div class="col-span-2">Location</div>
          <div class="col-span-3">Skills</div>
          <div class="col-span-1">Status</div>
          <div class="col-span-1">Joined</div>
          <div class="col-span-2 text-right">Action</div>
        </div>

        <div class="divide-y">
          <div
            v-for="volunteer in volunteers.data"
            :key="volunteer.id"
            class="lg:grid lg:grid-cols-12 lg:gap-4 lg:items-center px-4 sm:px-6 py-4 hover:bg-gray-50 transition-colors cursor-pointer"
            @click="router.visit(`/admin/volunteers/${volunteer.id}`)"
          >
            <div class="lg:col-span-3 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <Link
                  :href="`/admin/volunteers/${volunteer.id}`"
                  class="font-medium text-dark hover:text-primary truncate"
                  @click.stop
                >
                  {{ volunteer.name }}
                </Link>
                <span class="lg:hidden text-xs font-medium px-2.5 py-1 rounded-full flex-shrink-0" :class="statusBadgeColor(volunteer.status)">
                  {{ capitalize(volunteer.status) }}
                </span>
              </div>
              <p class="text-sm text-gray-500 truncate">{{ volunteer.email }}</p>
            </div>
            <div class="lg:col-span-2 min-w-0 mt-1 lg:mt-0 text-sm">
              <p class="text-dark truncate">
                {{ volunteer.lga }}<span v-if="volunteer.ward" class="text-gray-500"> · {{ volunteer.ward }}</span>
              </p>
              <p v-if="volunteer.has_vehicle" class="inline-flex items-center gap-1 text-xs text-blue-700 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l1.5-4.5A2 2 0 018.4 7h7.2a2 2 0 011.9 1.5L19 13M5 13h14M5 13v4a1 1 0 001 1h1a1 1 0 001-1v-1h8v1a1 1 0 001 1h1a1 1 0 001-1v-4M7.5 15.5h.01M16.5 15.5h.01" />
                </svg>
                Has vehicle
              </p>
            </div>
            <div class="lg:col-span-3 mt-2 lg:mt-0">
              <div v-if="volunteer.skills?.length" class="flex flex-wrap gap-1">
                <span
                  v-for="skill in volunteer.skills.slice(0, 3)"
                  :key="skill"
                  class="text-xs bg-gray-100 text-gray-800 px-2 py-0.5 rounded"
                >
                  {{ skill }}
                </span>
                <span
                  v-if="volunteer.skills.length > 3"
                  class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded"
                  :title="volunteer.skills.slice(3).join(', ')"
                >
                  +{{ volunteer.skills.length - 3 }}
                </span>
              </div>
              <span v-else class="hidden lg:inline text-xs text-gray-400">—</span>
            </div>
            <div class="hidden lg:block lg:col-span-1">
              <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusBadgeColor(volunteer.status)">
                {{ capitalize(volunteer.status) }}
              </span>
            </div>
            <div class="hidden lg:block lg:col-span-1 text-sm text-gray-500" :title="fullDate(volunteer.created_at)">
              {{ formatDate(volunteer.created_at) }}
            </div>
            <div class="lg:col-span-2 mt-3 lg:mt-0 flex lg:justify-end">
              <button
                v-if="volunteer.status === 'pending'"
                type="button"
                :disabled="approvingId === volunteer.id"
                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
                @click.stop="approve(volunteer)"
              >
                {{ approvingId === volunteer.id ? 'Approving...' : 'Approve' }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="!volunteers.data?.length" class="text-center py-12 px-4">
          <p class="text-gray-500">{{ isFiltered ? 'No volunteers match these filters' : 'No volunteers have registered yet' }}</p>
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
      <div v-if="volunteers.data?.length" class="space-y-2">
        <Pagination v-if="volunteers.last_page > 1" :links="volunteers.links" />
        <p class="text-center text-sm text-gray-600">
          Showing {{ volunteers.from }} to {{ volunteers.to }} of {{ volunteers.total }} volunteers
        </p>
      </div>
    </div>

    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()

const volunteers = computed(() => page.props.volunteers || { data: [] })
const statusCounts = computed(() => page.props.statusCounts || {})
const lgaList = computed(() => page.props.lgaList || [])
const filters = computed(() => page.props.filters || {})
const canExport = computed(() => ['admin', 'editor'].includes(page.props.auth?.user?.role))

const statuses = ['all', 'pending', 'approved', 'active', 'inactive']

const activeStatus = computed(() => filters.value.status || 'all')
const searchQuery = ref(filters.value.search || '')
const confirmDialog = ref(null)
const approvingId = ref(null)

const isFiltered = computed(() =>
  !!(filters.value.status || filters.value.lga || filters.value.vehicle || filters.value.search)
)

// Filters run on the server so they cover every page, and live in the URL so links like ?status=pending work
const visit = (params) => {
  const query = {
    status: filters.value.status || undefined,
    lga: filters.value.lga || undefined,
    vehicle: filters.value.vehicle || undefined,
    search: searchQuery.value.trim() || undefined,
    ...params,
  }
  router.get('/admin/volunteers', query, { preserveState: true, preserveScroll: true, replace: true })
}

let searchTimer
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => visit({}), 300)
})

const clearFilters = () => {
  searchQuery.value = ''
  clearTimeout(searchTimer)
  router.get('/admin/volunteers', {}, { preserveState: true, replace: true })
}

const exportUrl = computed(() => {
  const params = new URLSearchParams()
  for (const key of ['status', 'lga', 'vehicle']) {
    if (filters.value[key]) params.set(key, filters.value[key])
  }
  const qs = params.toString()
  return qs ? `/admin/exports/volunteers?${qs}` : '/admin/exports/volunteers'
})

const approve = async (volunteer) => {
  const ok = await confirmDialog.value.open(
    'Approve volunteer',
    `Approve ${volunteer.name}? They will receive a confirmation email.`,
    { confirmText: 'Approve' }
  )
  if (!ok) return

  approvingId.value = volunteer.id
  router.patch(`/admin/volunteers/${volunteer.id}`, { status: 'approved' }, {
    preserveScroll: true,
    onFinish: () => { approvingId.value = null },
  })
}

const statusBadgeColor = (status) => {
  const colors = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-blue-100 text-blue-800',
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-gray-100 text-gray-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

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
