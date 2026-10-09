<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Welcome & Quick Actions -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <p class="text-lg font-semibold text-dark">{{ greeting }}, {{ firstName }}</p>
          <p class="text-sm text-gray-500">{{ today }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link
            v-for="action in quickActions"
            :key="action.href"
            :href="action.href"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-white border border-gray-200 text-dark hover:border-primary hover:text-primary transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.icon" />
            </svg>
            {{ action.label }}
          </Link>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link
          v-for="card in statCards"
          :key="card.label"
          :href="card.href"
          :class="['group bg-white rounded-lg shadow p-6 border-l-4 hover:shadow-md transition-shadow', card.border]"
        >
          <div class="flex justify-between items-start">
            <div class="min-w-0">
              <p class="text-gray-500 text-sm font-medium">{{ card.label }}</p>
              <p :class="['font-bold text-dark mt-2 truncate', card.small ? 'text-2xl' : 'text-3xl']">{{ card.value }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ card.sub }}</p>
            </div>
            <div :class="['p-3 rounded-lg flex-shrink-0', card.iconBg]">
              <svg :class="['w-6 h-6', card.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
              </svg>
            </div>
          </div>
        </Link>
      </div>

      <!-- Growth Trend Chart & Recent Activity -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 7-Day Growth Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-dark mb-4">7-Day Registrations</h3>
          <div v-if="hasGrowth" class="flex items-stretch gap-2 sm:gap-4 h-48">
            <div v-for="day in growthTrend" :key="day.date" class="flex-1 flex flex-col min-w-0">
              <div class="flex-1 flex gap-0.5 sm:gap-1 items-end justify-center border-b border-gray-200">
                <div
                  v-for="series in chartSeries"
                  :key="series.key"
                  :class="['w-1/3 max-w-4 rounded-t', series.bar]"
                  :style="{ height: barHeight(day[series.key]) }"
                  :title="`${series.label}: ${day[series.key] || 0}`"
                />
              </div>
              <div class="text-center mt-2 leading-tight">
                <p class="text-xs font-medium text-gray-600">{{ weekday(day.date) }}</p>
                <p class="text-xs text-gray-400">{{ dayOfMonth(day.date) }}</p>
              </div>
            </div>
          </div>
          <div v-else class="h-48 flex items-center justify-center text-gray-500">
            <p>No registrations in the last 7 days</p>
          </div>
          <div class="flex flex-wrap gap-x-4 gap-y-2 mt-6 pt-4 border-t text-sm">
            <div v-for="series in chartSeries" :key="series.key" class="flex items-center gap-2">
              <div :class="['w-3 h-3 rounded', series.bar]" />
              <span class="text-gray-600">{{ series.label }}</span>
              <span class="font-semibold text-dark">{{ seriesTotal(series.key) }}</span>
            </div>
          </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-dark mb-4">Recent Activity</h3>
          <div class="space-y-3 max-h-96 overflow-y-auto">
            <div v-if="recentActivity.length === 0" class="text-center py-8">
              <p class="text-gray-400 text-sm">No activity yet</p>
            </div>
            <div v-for="log in recentActivity" :key="log.id" class="flex gap-3 pb-3 border-b last:border-b-0">
              <div class="flex-shrink-0">
                <div :class="['w-8 h-8 rounded-full flex items-center justify-center', activityStyle(log.action).bg]">
                  <svg :class="['w-4 h-4', activityStyle(log.action).color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="activityStyle(log.action).icon" />
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-dark">
                  <span class="font-medium">{{ log.user || 'System' }}</span>
                  {{ describeActivity(log) }}
                </p>
                <p class="text-xs text-gray-500">{{ formatDate(log.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Contacts & RSVPs -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Contacts -->
        <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
          <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-dark">Recent Contacts</h3>
          </div>
          <div class="flex-1">
            <p v-if="recentContacts.length === 0" class="text-center py-8 text-gray-400 text-sm">No contact messages yet</p>
            <Link
              v-for="contact in recentContacts"
              :key="contact.id"
              :href="`/admin/contacts/${contact.id}`"
              class="flex items-center justify-between gap-4 px-6 py-4 border-b last:border-b-0 hover:bg-gray-50"
            >
              <div class="min-w-0">
                <p class="text-sm font-medium text-dark truncate">{{ contact.name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ contact.subject || contact.email }}</p>
              </div>
              <div class="flex-shrink-0 text-right">
                <span class="text-xs font-medium px-2 py-1 rounded-full capitalize" :class="statusColor(contact.status)">
                  {{ contact.status }}
                </span>
                <p class="text-xs text-gray-400 mt-1">{{ formatDate(contact.created_at) }}</p>
              </div>
            </Link>
          </div>
          <div class="px-6 py-4 bg-gray-50 text-center">
            <Link href="/admin/contacts" class="text-sm text-primary hover:text-primary-dark font-medium">
              View All Contacts →
            </Link>
          </div>
        </div>

        <!-- Recent RSVPs -->
        <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
          <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-dark">Recent Event RSVPs</h3>
          </div>
          <div class="flex-1">
            <p v-if="recentRsvps.length === 0" class="text-center py-8 text-gray-400 text-sm">No RSVPs yet</p>
            <Link
              v-for="rsvp in recentRsvps"
              :key="rsvp.id"
              :href="`/admin/events/${rsvp.event_id}/rsvps`"
              class="flex items-center justify-between gap-4 px-6 py-4 border-b last:border-b-0 hover:bg-gray-50"
            >
              <div class="min-w-0">
                <p class="text-sm font-medium text-dark truncate">{{ rsvp.name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ rsvp.event?.title }}</p>
              </div>
              <div class="flex-shrink-0 text-right">
                <span class="text-xs font-medium px-2 py-1 rounded-full capitalize" :class="statusColor(rsvp.status)">
                  {{ rsvp.status }}
                </span>
                <p class="text-xs text-gray-400 mt-1">{{ formatDate(rsvp.created_at) }}</p>
              </div>
            </Link>
          </div>
          <div class="px-6 py-4 bg-gray-50 text-center">
            <Link href="/admin/events" class="text-sm text-primary hover:text-primary-dark font-medium">
              View Events →
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

const stats = computed(() => page.props.stats || {})
const growthTrend = computed(() => page.props.growthTrend || [])
const lgaTop5 = computed(() => page.props.lgaTop5 || [])
const recentActivity = computed(() => page.props.recentActivity || [])
const recentContacts = computed(() => page.props.recentContacts || [])
const recentRsvps = computed(() => page.props.recentRsvps || [])
const user = computed(() => page.props.auth?.user)

const icons = {
  userGroup: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
  inbox: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
  mail: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  alert: 'M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  pin: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
  plus: 'M12 4v16m8-8H4',
  bolt: 'M13 10V3L4 14h7v7l9-11h-7z',
  pencil: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
  trash: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
  refresh: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
  dot: 'M12 12h.01',
}

const statCards = computed(() => {
  const s = stats.value
  const pending = s.volunteersPending || 0
  const topLga = lgaTop5.value[0]

  return [
    {
      label: 'Active Volunteers',
      value: (s.volunteersActive || 0) + (s.volunteersApproved || 0),
      sub: `${s.volunteersTotal || 0} registered in total`,
      href: '/admin/volunteers',
      icon: icons.userGroup,
      border: 'border-blue-500', iconBg: 'bg-blue-100', iconColor: 'text-blue-600',
    },
    {
      label: 'Unread Contacts',
      value: s.contactsNew || 0,
      sub: `${s.contactsTotal || 0} messages in total`,
      href: '/admin/contacts?status=new',
      icon: icons.inbox,
      border: 'border-green-500', iconBg: 'bg-green-100', iconColor: 'text-green-600',
    },
    {
      label: 'Newsletter Confirmed',
      value: s.subscribersConfirmed || 0,
      sub: `${s.subscribersPending || 0} awaiting confirmation`,
      href: '/admin/newsletter',
      icon: icons.mail,
      border: 'border-purple-500', iconBg: 'bg-purple-100', iconColor: 'text-purple-600',
    },
    {
      label: 'Confirmed RSVPs',
      value: s.rsvpsConfirmed || 0,
      sub: `${s.rsvpsTotal || 0} RSVPs in total`,
      href: '/admin/events',
      icon: icons.calendar,
      border: 'border-orange-500', iconBg: 'bg-orange-100', iconColor: 'text-orange-600',
    },
    pending > 0
      ? {
          label: 'Pending Approvals',
          value: pending,
          sub: 'Volunteers awaiting review →',
          href: '/admin/volunteers?status=pending',
          icon: icons.alert,
          border: 'border-red-500', iconBg: 'bg-red-100', iconColor: 'text-red-600',
        }
      : {
          label: 'Pending Approvals',
          value: 0,
          sub: 'All caught up',
          href: '/admin/volunteers',
          icon: icons.check,
          border: 'border-gray-300', iconBg: 'bg-gray-100', iconColor: 'text-gray-500',
        },
    {
      label: 'Top LGA',
      value: topLga?.lga || 'N/A',
      sub: `${topLga?.count || 0} volunteers`,
      small: true,
      href: '/admin/analytics',
      icon: icons.pin,
      border: 'border-indigo-500', iconBg: 'bg-indigo-100', iconColor: 'text-indigo-600',
    },
  ]
})

const canEditContent = computed(() => ['admin', 'editor'].includes(user.value?.role))

const quickActions = computed(() => [
  ...(canEditContent.value
    ? [
        { href: '/admin/news/create', label: 'New Article', icon: icons.plus },
        { href: '/admin/events/create', label: 'New Event', icon: icons.plus },
      ]
    : []),
  ...(user.value?.role === 'admin' ? [{ href: '/admin/bulk-email', label: 'Send Email', icon: icons.bolt }] : []),
])

const firstName = computed(() => (user.value?.name || '').split(' ')[0])

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 17) return 'Good afternoon'
  return 'Good evening'
})

const today = new Date().toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

// Chart
const chartSeries = [
  { key: 'volunteers', label: 'Volunteers', bar: 'bg-blue-400' },
  { key: 'contacts', label: 'Contacts', bar: 'bg-green-400' },
  { key: 'subscribers', label: 'Subscribers', bar: 'bg-purple-400' },
]

const maxGrowth = computed(() => Math.max(
  ...growthTrend.value.map(d => Math.max(d.volunteers || 0, d.contacts || 0, d.subscribers || 0)),
  1
))

const hasGrowth = computed(() => growthTrend.value.some(d => d.volunteers || d.contacts || d.subscribers))

// Keep non-zero bars visible even when dwarfed by the max
const barHeight = (value) => (value ? `max(${(value / maxGrowth.value) * 100}%, 4px)` : '0')

const seriesTotal = (key) => growthTrend.value.reduce((sum, d) => sum + (d[key] || 0), 0)

// Dates arrive as YYYY-MM-DD; parse as local to avoid a timezone shift
const parseDay = (date) => {
  const [y, m, d] = date.split('-').map(Number)
  return new Date(y, m - 1, d)
}
const weekday = (date) => parseDay(date).toLocaleDateString(undefined, { weekday: 'short' })
const dayOfMonth = (date) => parseDay(date).getDate()

// Activity feed
const activityStyles = {
  'content.created': { icon: icons.plus, bg: 'bg-green-100', color: 'text-green-600' },
  'content.updated': { icon: icons.pencil, bg: 'bg-blue-100', color: 'text-blue-600' },
  'content.deleted': { icon: icons.trash, bg: 'bg-red-100', color: 'text-red-600' },
  status_updated: { icon: icons.refresh, bg: 'bg-amber-100', color: 'text-amber-600' },
}
const activityStyle = (action) => activityStyles[action] || { icon: icons.dot, bg: 'bg-gray-100', color: 'text-gray-600' }

const activityVerbs = {
  'content.created': 'created',
  'content.updated': 'updated',
  'content.deleted': 'deleted',
  status_updated: 'changed the status of',
}

// "NewsArticle" -> "news article"
const humanize = (name) => (name || 'item').replace(/([a-z])([A-Z])/g, '$1 $2').toLowerCase()

const describeActivity = (log) => {
  const verb = activityVerbs[log.action] || log.action.replace(/[._]/g, ' ')
  const subject = humanize(log.subject)
  const article = /^[aeiou]/.test(subject) ? 'an' : 'a'
  return `${verb} ${article} ${subject}`
}

const statusColor = (status) => {
  const colors = {
    'new': 'bg-blue-100 text-blue-800',
    'read': 'bg-gray-100 text-gray-800',
    'replied': 'bg-green-100 text-green-800',
    'archived': 'bg-gray-100 text-gray-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-gray-100 text-gray-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'confirmed': 'bg-green-100 text-green-800',
    'declined': 'bg-red-100 text-red-800',
    'cancelled': 'bg-gray-100 text-gray-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  const now = new Date()
  const seconds = Math.floor((now - d) / 1000)

  if (seconds < 60) return 'Just now'
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
  if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`

  return d.toLocaleDateString()
}
</script>
