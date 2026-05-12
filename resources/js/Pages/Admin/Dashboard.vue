<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Active Volunteers -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm font-medium">Active Volunteers</p>
              <p class="text-3xl font-bold text-dark mt-2">{{ stats.volunteersActive }}</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- New Contacts -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm font-medium">New Contacts (7d)</p>
              <p class="text-3xl font-bold text-dark mt-2">{{ stats.contactsNew }}</p>
            </div>
            <div class="bg-green-100 p-3 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Newsletter Subscribers -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm font-medium">Newsletter Confirmed</p>
              <p class="text-3xl font-bold text-dark mt-2">{{ stats.subscribersConfirmed }}</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Total RSVPs -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm font-medium">Event RSVPs (7d)</p>
              <p class="text-3xl font-bold text-dark mt-2">{{ stats.rsvpsConfirmed }}</p>
            </div>
            <div class="bg-orange-100 p-3 rounded-lg">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm font-medium">Pending Approvals</p>
              <p class="text-3xl font-bold text-dark mt-2">{{ stats.volunteersPending }}</p>
            </div>
            <div class="bg-red-100 p-3 rounded-lg">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Top LGA -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm font-medium">Top LGA</p>
              <p class="text-2xl font-bold text-dark mt-2">{{ lgaTop5[0]?.lga || 'N/A' }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ lgaTop5[0]?.count || 0 }} volunteers</p>
            </div>
            <div class="bg-indigo-100 p-3 rounded-lg">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Growth Trend Chart & Recent Activity -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 7-Day Growth Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-dark mb-4">7-Day Registrations</h3>
          <div v-if="growthTrend && growthTrend.length > 0" class="space-y-3">
            <div v-for="(day, index) in growthTrend" :key="index" class="flex items-end gap-2 h-24">
              <div class="flex-1">
                <div class="flex gap-1 items-end justify-center h-full">
                  <div class="bg-blue-400 rounded-t w-1/3" :style="{ height: `${(day.volunteers / maxGrowth) * 100}%` }" title="Volunteers" />
                  <div class="bg-green-400 rounded-t w-1/3" :style="{ height: `${(day.contacts / maxGrowth) * 100}%` }" title="Contacts" />
                  <div class="bg-purple-400 rounded-t w-1/3" :style="{ height: `${(day.subscribers / maxGrowth) * 100}%` }" title="Subscribers" />
                </div>
              </div>
              <div class="text-xs text-gray-500 w-12 text-right">{{ day.date }}</div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            <p>No growth data available yet</p>
          </div>
          <div class="flex gap-4 mt-6 pt-4 border-t text-sm">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-blue-400 rounded" />
              <span class="text-gray-600">Volunteers</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-green-400 rounded" />
              <span class="text-gray-600">Contacts</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-purple-400 rounded" />
              <span class="text-gray-600">Subscribers</span>
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
                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                  <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 100-2 1 1 0 000 2zm6 0a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-dark truncate">{{ log.action }}</p>
                <p class="text-xs text-gray-500">{{ log.subject_type }} • {{ formatDate(log.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Contacts & RSVPs -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Contacts -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-dark">Recent Contacts</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <tbody>
                <tr v-for="contact in recentContacts" :key="contact.id" class="border-b hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div>
                      <p class="text-sm font-medium text-dark">{{ contact.name }}</p>
                      <p class="text-xs text-gray-500">{{ contact.email }}</p>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <span class="text-xs font-medium px-2 py-1 rounded-full" :class="statusColor(contact.status)">
                      {{ contact.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="px-6 py-4 bg-gray-50 text-center">
            <Link href="/admin/contacts" class="text-sm text-primary hover:text-primary-dark font-medium">
              View All Contacts →
            </Link>
          </div>
        </div>

        <!-- Recent RSVPs -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-dark">Recent Event RSVPs</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <tbody>
                <tr v-for="rsvp in recentRsvps" :key="rsvp.id" class="border-b hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div>
                      <p class="text-sm font-medium text-dark">{{ rsvp.name }}</p>
                      <p class="text-xs text-gray-500">{{ rsvp.event?.title }}</p>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <span class="text-xs font-medium px-2 py-1 rounded-full" :class="statusColor(rsvp.status)">
                      {{ rsvp.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="px-6 py-4 bg-gray-50 text-center">
            <Link href="/admin/events" class="text-sm text-primary hover:text-primary-dark font-medium">
              View All RSVPs →
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

const maxGrowth = computed(() => {
  if (!Array.isArray(growthTrend.value) || growthTrend.value.length === 0) {
    return 1
  }
  return Math.max(
    ...growthTrend.value.map(d => Math.max(d.volunteers || 0, d.contacts || 0, d.subscribers || 0)),
    1
  )
})

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
