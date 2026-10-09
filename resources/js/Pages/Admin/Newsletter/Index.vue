<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Summary -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
          <p class="text-gray-600 text-sm font-medium">Confirmed</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ funnel.confirmed || 0 }}</p>
          <p class="text-xs text-gray-500 mt-2">Receiving the newsletter</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
          <p class="text-gray-600 text-sm font-medium">Pending</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ funnel.pending || 0 }}</p>
          <p class="text-xs text-gray-500 mt-2">Haven't clicked the confirm link</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
          <p class="text-gray-600 text-sm font-medium">Unsubscribed</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ funnel.unsubscribed || 0 }}</p>
          <p class="text-xs text-gray-500 mt-2">Opted out</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
          <p class="text-gray-600 text-sm font-medium">New this week</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ newThisWeek }}</p>
          <p class="text-xs text-gray-500 mt-2">Sign-ups in the last 7 days</p>
        </div>
      </div>

      <!-- Breakdown -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
          <h3 class="text-lg font-semibold text-dark">Sign-up breakdown</h3>
          <p class="text-sm text-gray-600">
            <span class="font-semibold text-dark">{{ percent(funnel.confirmed) }}%</span> of {{ total }} sign-ups confirmed
          </p>
        </div>
        <div v-if="total" class="flex w-full h-4 rounded-full overflow-hidden bg-gray-100">
          <div
            v-for="segment in segments"
            :key="segment.key"
            :class="segment.bar"
            :style="{ width: percentExact(funnel[segment.key]) + '%' }"
            :title="`${segment.label}: ${funnel[segment.key] || 0}`"
          />
        </div>
        <p v-else class="text-sm text-gray-500">No sign-ups yet</p>
        <div class="flex flex-wrap gap-x-6 gap-y-2 mt-4 text-sm">
          <div v-for="segment in segments" :key="segment.key" class="flex items-center gap-2">
            <span :class="['w-3 h-3 rounded', segment.bar]" />
            <span class="text-gray-600">{{ segment.label }}</span>
            <span class="font-semibold text-dark">{{ funnel[segment.key] || 0 }} ({{ percent(funnel[segment.key]) }}%)</span>
          </div>
        </div>
      </div>

      <!-- Filters, Search & Export -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-4">
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
            <span class="ml-2 font-semibold">{{ status === 'all' ? total : (funnel[status] || 0) }}</span>
          </button>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
          <div class="relative flex-1">
            <label for="subscriber-search" class="sr-only">Search</label>
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              id="subscriber-search"
              v-model="searchQuery"
              type="search"
              placeholder="Search email or name..."
              class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>
          <a
            v-if="canManage"
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

      <!-- Subscribers List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b text-xs font-semibold text-gray-600 uppercase">
          <div class="col-span-5">Subscriber</div>
          <div class="col-span-2">Status</div>
          <div class="col-span-2">Signed up</div>
          <div class="col-span-3 text-right">Action</div>
        </div>

        <div class="divide-y">
          <div
            v-for="subscriber in subscribers.data"
            :key="subscriber.id"
            class="md:grid md:grid-cols-12 md:gap-4 md:items-center px-4 sm:px-6 py-4"
          >
            <div class="md:col-span-5 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <p class="font-medium text-dark truncate">{{ subscriber.email }}</p>
                <span class="md:hidden text-xs font-medium px-2.5 py-1 rounded-full flex-shrink-0" :class="statusBadgeColor(subscriber.status)">
                  {{ capitalize(subscriber.status) }}
                </span>
              </div>
              <p class="text-sm text-gray-500 truncate">{{ subscriber.name || 'No name given' }}</p>
            </div>
            <div class="hidden md:block md:col-span-2">
              <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusBadgeColor(subscriber.status)">
                {{ capitalize(subscriber.status) }}
              </span>
              <p v-if="statusDate(subscriber)" class="text-xs text-gray-500 mt-1">{{ statusDate(subscriber) }}</p>
            </div>
            <div class="md:col-span-2 mt-1 md:mt-0 text-sm text-gray-500" :title="fullDate(subscriber.created_at)">
              <span class="md:hidden">Signed up </span>{{ formatDate(subscriber.created_at) }}
            </div>
            <div class="md:col-span-3 mt-2 md:mt-0 flex md:justify-end">
              <button
                v-if="canManage && subscriber.status === 'pending'"
                type="button"
                :disabled="resendingId === subscriber.id"
                class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
                @click="resend(subscriber)"
              >
                {{ resendingId === subscriber.id ? 'Sending...' : 'Resend confirmation' }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="!subscribers.data?.length" class="text-center py-12 px-4">
          <p class="text-gray-500">{{ isFiltered ? 'No subscribers match these filters' : 'No one has signed up yet' }}</p>
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
      <div v-if="subscribers.data?.length" class="space-y-2">
        <Pagination v-if="subscribers.last_page > 1" :links="subscribers.links" />
        <p class="text-center text-sm text-gray-600">
          Showing {{ subscribers.from }} to {{ subscribers.to }} of {{ subscribers.total }} subscribers
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
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()

const subscribers = computed(() => page.props.subscribers || { data: [] })
const funnel = computed(() => page.props.funnel || {})
const newThisWeek = computed(() => page.props.newThisWeek || 0)
const filters = computed(() => page.props.filters || {})
const canManage = computed(() => ['admin', 'editor'].includes(page.props.auth?.user?.role))

const statuses = ['all', 'confirmed', 'pending', 'unsubscribed']
const segments = [
  { key: 'confirmed', label: 'Confirmed', bar: 'bg-green-500' },
  { key: 'pending', label: 'Pending', bar: 'bg-yellow-400' },
  { key: 'unsubscribed', label: 'Unsubscribed', bar: 'bg-gray-400' },
]

const activeStatus = computed(() => filters.value.status || 'all')
const searchQuery = ref(filters.value.search || '')
const confirmDialog = ref(null)
const resendingId = ref(null)

const isFiltered = computed(() => !!(filters.value.status || filters.value.search))

const total = computed(() =>
  (funnel.value.pending || 0) + (funnel.value.confirmed || 0) + (funnel.value.unsubscribed || 0)
)
const percentExact = (n) => (total.value ? ((n || 0) / total.value) * 100 : 0)
const percent = (n) => Math.round(percentExact(n))

// Filters run on the server so they cover every page
const visit = (params) => {
  const query = {
    status: filters.value.status || undefined,
    search: searchQuery.value.trim() || undefined,
    ...params,
  }
  router.get('/admin/newsletter', query, { preserveState: true, preserveScroll: true, replace: true })
}

let searchTimer
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => visit({}), 300)
})

const clearFilters = () => {
  searchQuery.value = ''
  clearTimeout(searchTimer)
  router.get('/admin/newsletter', {}, { preserveState: true, replace: true })
}

const exportUrl = computed(() =>
  filters.value.status ? `/admin/exports/subscribers?status=${filters.value.status}` : '/admin/exports/subscribers'
)

const resend = async (subscriber) => {
  const ok = await confirmDialog.value.open(
    'Resend confirmation',
    `Send the confirmation email to ${subscriber.email} again?`,
    { confirmText: 'Send' }
  )
  if (!ok) return

  resendingId.value = subscriber.id
  router.post(`/admin/newsletter/${subscriber.id}/resend`, {}, {
    preserveScroll: true,
    onFinish: () => { resendingId.value = null },
  })
}

const statusDate = (subscriber) => {
  if (subscriber.status === 'confirmed' && subscriber.confirmed_at) return `since ${formatDate(subscriber.confirmed_at)}`
  if (subscriber.status === 'unsubscribed' && subscriber.unsubscribed_at) return `on ${formatDate(subscriber.unsubscribed_at)}`
  return ''
}

const statusBadgeColor = (status) => {
  const colors = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'confirmed': 'bg-green-100 text-green-800',
    'unsubscribed': 'bg-gray-100 text-gray-700',
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
