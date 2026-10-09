<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Filter Tabs & Search -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="flex flex-col gap-4">
          <!-- Status Tabs -->
          <div class="flex gap-2 overflow-x-auto pb-2 -mx-1 px-1">
            <button
              v-for="status in statuses"
              :key="status"
              type="button"
              @click="setStatus(status)"
              class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
              :class="
                activeStatus === status
                  ? 'bg-primary text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              "
            >
              {{ status === 'new' ? 'Unread' : capitalize(status) }}
              <span class="ml-2 font-semibold">{{ statusCounts[status] || 0 }}</span>
            </button>
          </div>

          <!-- Search & Export -->
          <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <div class="relative flex-1">
              <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="searchQuery"
                type="search"
                placeholder="Search name, email or subject..."
                class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>
            <a
              v-if="canExport"
              :href="exportUrl"
              class="inline-flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Export CSV
            </a>
          </div>
        </div>
      </div>

      <!-- Contacts List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b text-xs font-semibold text-gray-600 uppercase">
          <div class="col-span-4">From</div>
          <div class="col-span-5">Message</div>
          <div class="col-span-1">Status</div>
          <div class="col-span-2 text-right">Received</div>
        </div>

        <div class="divide-y">
          <Link
            v-for="contact in contacts.data"
            :key="contact.id"
            :href="`/admin/contacts/${contact.id}`"
            class="block md:grid md:grid-cols-12 md:gap-4 md:items-center px-4 sm:px-6 py-4 hover:bg-gray-50 transition-colors"
            :class="{ 'bg-blue-50/40': contact.status === 'new' }"
          >
            <div class="md:col-span-4 min-w-0 flex items-start gap-2">
              <span
                class="mt-2 w-2 h-2 rounded-full flex-shrink-0"
                :class="contact.status === 'new' ? 'bg-blue-500' : 'bg-transparent'"
                :aria-label="contact.status === 'new' ? 'Unread' : undefined"
              />
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-dark truncate" :class="contact.status === 'new' ? 'font-semibold' : 'font-medium'">{{ contact.name }}</p>
                  <span class="md:hidden text-xs text-gray-500 flex-shrink-0">{{ formatDate(contact.created_at) }}</span>
                </div>
                <p class="text-sm text-gray-500 truncate">{{ contact.email }}</p>
              </div>
            </div>
            <div class="md:col-span-5 min-w-0 mt-2 md:mt-0 pl-4 md:pl-0">
              <p class="text-sm text-dark truncate" :class="{ 'font-semibold': contact.status === 'new' }">{{ contact.subject || '(No subject)' }}</p>
              <p class="text-sm text-gray-500 truncate">{{ contact.preview }}</p>
              <div v-if="contact.tags?.length" class="flex flex-wrap gap-1 mt-1">
                <span
                  v-for="tag in contact.tags"
                  :key="tag.id"
                  class="text-[11px] px-2 py-0.5 rounded-full text-white"
                  :style="{ backgroundColor: tag.color || '#003D82' }"
                >{{ tag.name }}</span>
              </div>
            </div>
            <div class="md:col-span-1 mt-2 md:mt-0 pl-4 md:pl-0">
              <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusBadgeColor(contact.status)">
                {{ statusLabel(contact.status) }}
              </span>
            </div>
            <div class="hidden md:block md:col-span-2 text-right text-sm text-gray-500" :title="fullDate(contact.created_at)">
              {{ formatDate(contact.created_at) }}
            </div>
          </Link>
        </div>

        <div v-if="!contacts.data?.length" class="text-center py-12 px-4">
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
      <div v-if="contacts.data?.length" class="space-y-2">
        <Pagination v-if="contacts.last_page > 1" :links="contacts.links" />
        <p class="text-center text-sm text-gray-600">
          Showing {{ contacts.from }} to {{ contacts.to }} of {{ contacts.total }} contacts
        </p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const page = usePage()

const contacts = computed(() => page.props.contacts || { data: [] })
const statusCounts = computed(() => page.props.statusCounts || {})
const filters = computed(() => page.props.filters || {})
const canExport = computed(() => ['admin', 'editor'].includes(page.props.auth?.user?.role))

const statuses = ['all', 'new', 'read', 'replied', 'archived']

const activeStatus = computed(() => filters.value.status || 'all')
const searchQuery = ref(filters.value.search || '')

const isFiltered = computed(() => activeStatus.value !== 'all' || !!filters.value.search)

// Filtering happens on the server so it covers every page, not just the current 20 rows
const visit = (params) => {
  const query = {
    status: activeStatus.value === 'all' ? undefined : activeStatus.value,
    search: searchQuery.value.trim() || undefined,
    ...params,
  }
  router.get('/admin/contacts', query, { preserveState: true, preserveScroll: true, replace: true })
}

const setStatus = (status) => visit({ status: status === 'all' ? undefined : status })

let searchTimer
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => visit({}), 300)
})

const clearFilters = () => {
  searchQuery.value = ''
  clearTimeout(searchTimer)
  router.get('/admin/contacts', {}, { preserveState: true, replace: true })
}

const exportUrl = computed(() =>
  activeStatus.value === 'all' ? '/admin/exports/contacts' : `/admin/exports/contacts?status=${activeStatus.value}`
)

const emptyMessage = computed(() => {
  if (filters.value.search) return `No contacts match "${filters.value.search}"`
  if (activeStatus.value !== 'all') return `No ${statusLabel(activeStatus.value).toLowerCase()} contacts`
  return 'No contact messages yet'
})

const statusBadgeColor = (status) => {
  const colors = {
    'new': 'bg-blue-100 text-blue-800',
    'read': 'bg-gray-100 text-gray-800',
    'replied': 'bg-green-100 text-green-800',
    'archived': 'bg-amber-100 text-amber-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const statusLabel = (status) => (status === 'new' ? 'Unread' : capitalize(status))

const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

// Today: time; this year: "12 Oct"; older: full date
const formatDate = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  const now = new Date()
  if (d.toDateString() === now.toDateString()) {
    return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
  }
  if (d.getFullYear() === now.getFullYear()) {
    return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' })
  }
  return d.toLocaleDateString()
}

const fullDate = (date) => (date ? new Date(date).toLocaleString() : '')
</script>
