<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-500 text-sm">Total Volunteers</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ overviewStats.volunteersTotal }}</p>
          <p class="text-xs text-green-600 mt-2">Active: {{ overviewStats.volunteersActive }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-500 text-sm">Total Contacts</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ overviewStats.contactsTotal }}</p>
          <p class="text-xs text-blue-600 mt-2">New: {{ overviewStats.contactsNew }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-500 text-sm">Newsletter</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ overviewStats.subscribersConfirmed }}</p>
          <p class="text-xs text-purple-600 mt-2">Pending: {{ overviewStats.subscribersPending }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-500 text-sm">Event RSVPs</p>
          <p class="text-3xl font-bold text-dark mt-2">{{ overviewStats.rsvpsTotal }}</p>
          <p class="text-xs text-orange-600 mt-2">Confirmed: {{ overviewStats.rsvpsConfirmed }}</p>
        </div>
      </div>

      <!-- Growth Trend Chart -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-dark mb-4">30-Day Growth Trend</h3>
        <div v-if="growthTrend && growthTrend.length > 0" class="overflow-x-auto">
          <div class="flex items-end gap-1 h-64 min-w-full pb-4">
            <div v-for="(day, index) in growthTrend" :key="index" class="flex-1 flex flex-col items-center gap-2">
              <div class="flex gap-0.5 h-full items-end">
                <div class="bg-blue-400 flex-1 rounded-t" :style="{ height: `${(day.volunteers / maxTrendValue) * 100}%` }" :title="`${day.date}: ${day.volunteers} volunteers`" />
                <div class="bg-green-400 flex-1 rounded-t" :style="{ height: `${(day.contacts / maxTrendValue) * 100}%` }" :title="`${day.date}: ${day.contacts} contacts`" />
                <div class="bg-purple-400 flex-1 rounded-t" :style="{ height: `${(day.subscribers / maxTrendValue) * 100}%` }" :title="`${day.date}: ${day.subscribers} subscribers`" />
              </div>
              <span class="text-xs text-gray-500 text-center whitespace-nowrap">{{ day.date }}</span>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-500">
          <p>No growth trend data available</p>
        </div>
        <div class="flex gap-6 mt-6 pt-4 border-t text-sm">
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-blue-400 rounded" />
            <span class="text-gray-600">Volunteers</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-green-400 rounded" />
            <span class="text-gray-600">Contacts</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-purple-400 rounded" />
            <span class="text-gray-600">Subscribers</span>
          </div>
        </div>
      </div>

      <!-- Geographic & Skills -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- LGA Breakdown -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-dark mb-4">Volunteers by LGA (Top 10)</h3>
          <div v-if="lgaBreakdown && lgaBreakdown.length > 0" class="space-y-3">
            <div v-for="(lga, index) in lgaBreakdown.slice(0, 10)" :key="index" class="flex items-center gap-3">
              <div class="flex-1">
                <div class="flex justify-between items-center mb-1">
                  <span class="text-sm font-medium text-dark">{{ lga.lga }}</span>
                  <span class="text-xs font-semibold text-gray-600">{{ lga.count }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-500 h-2 rounded-full" :style="{ width: `${(lga.count / maxLga) * 100}%` }" />
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            <p>No LGA data available</p>
          </div>
        </div>

        <!-- Skills Inventory -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-dark mb-4">Volunteer Skills Inventory</h3>
          <div v-if="sortedSkills && sortedSkills.length > 0" class="space-y-3">
            <div v-for="(skill, index) in sortedSkills.slice(0, 10)" :key="index" class="flex items-center gap-3">
              <div class="flex-1">
                <div class="flex justify-between items-center mb-1">
                  <span class="text-sm font-medium text-dark capitalize">{{ skill.name }}</span>
                  <span class="text-xs font-semibold text-gray-600">{{ skill.count }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-green-500 h-2 rounded-full" :style="{ width: `${(skill.count / maxSkill) * 100}%` }" />
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            <p>No skills data available</p>
          </div>
        </div>
      </div>

      <!-- Newsletter Funnel & Event Attendance -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Newsletter Funnel -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-dark mb-6">Newsletter Funnel</h3>
          <div class="space-y-4">
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-dark">Pending</span>
                <span class="text-sm font-bold">{{ newsletterFunnel.pending }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-yellow-500 h-3 rounded-full" :style="{ width: pendingWidth + '%' }" />
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-dark">Confirmed</span>
                <span class="text-sm font-bold">{{ newsletterFunnel.confirmed }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-green-500 h-3 rounded-full" :style="{ width: confirmedWidth + '%' }" />
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-dark">Unsubscribed</span>
                <span class="text-sm font-bold">{{ newsletterFunnel.unsubscribed }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-red-500 h-3 rounded-full" :style="{ width: unsubscribedWidth + '%' }" />
              </div>
            </div>
          </div>
          <div class="mt-6 pt-4 border-t">
            <p class="text-sm text-gray-600">
              <span class="font-semibold text-green-600">{{ confirmedPercentage }}%</span> conversion rate to confirmed
            </p>
          </div>
        </div>

        <!-- Event Attendance Rates -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-dark mb-4">Event Attendance Rates</h3>
          <div class="space-y-4">
            <div v-if="!eventAttendance || eventAttendance.length === 0" class="text-center py-8">
              <p class="text-gray-400 text-sm">No events with RSVPs yet</p>
            </div>
            <div v-for="event in eventAttendance && eventAttendance.length > 0 ? eventAttendance.slice(0, 5) : []" :key="event.id" class="border-b pb-4 last:border-b-0">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <p class="text-sm font-medium text-dark">{{ event.title }}</p>
                  <p class="text-xs text-gray-500">{{ event.confirmed }} / {{ event.capacity }}</p>
                </div>
                <span class="text-sm font-bold text-blue-600">{{ event.attendanceRate }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-500 h-2 rounded-full" :style="{ width: event.attendanceRate + '%' }" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

const overviewStats = computed(() => page.props.overviewStats || {})
const growthTrend = computed(() => page.props.growthTrend || [])
const lgaBreakdown = computed(() => {
  const data = page.props.lgaBreakdown || {}
  return Array.isArray(data) ? data : (data.volunteers || [])
})
const skillsInventory = computed(() => page.props.skillsInventory || {})
const newsletterFunnel = computed(() => page.props.newsletterFunnel || {})
const eventAttendance = computed(() => page.props.eventAttendance || [])

const maxTrendValue = computed(() => {
  if (!Array.isArray(growthTrend.value) || growthTrend.value.length === 0) {
    return 1
  }
  return Math.max(
    ...growthTrend.value.map(d => Math.max(d.volunteers || 0, d.contacts || 0, d.subscribers || 0)),
    1
  )
})

const maxLga = computed(() => {
  if (!Array.isArray(lgaBreakdown.value) || lgaBreakdown.value.length === 0) {
    return 1
  }
  return Math.max(...lgaBreakdown.value.map(l => l.count || 0), 1)
})

const sortedSkills = computed(() => {
  const entries = Object.entries(skillsInventory.value).map(([name, count]) => ({
    name,
    count
  }))
  return entries.sort((a, b) => b.count - a.count)
})

const maxSkill = computed(() => {
  if (!Array.isArray(sortedSkills.value) || sortedSkills.value.length === 0) {
    return 1
  }
  return Math.max(...sortedSkills.value.map(s => s.count || 0), 1)
})

const totalSubscribers = computed(() => {
  if (!newsletterFunnel.value) return 0
  return (newsletterFunnel.value.pending || 0) + (newsletterFunnel.value.confirmed || 0) + (newsletterFunnel.value.unsubscribed || 0)
})

const pendingWidth = computed(() => {
  if (!newsletterFunnel.value || !totalSubscribers.value) return 0
  return (newsletterFunnel.value.pending || 0) / totalSubscribers.value * 100
})

const confirmedWidth = computed(() => {
  if (!newsletterFunnel.value || !totalSubscribers.value) return 0
  return (newsletterFunnel.value.confirmed || 0) / totalSubscribers.value * 100
})

const unsubscribedWidth = computed(() => {
  if (!newsletterFunnel.value || !totalSubscribers.value) return 0
  return (newsletterFunnel.value.unsubscribed || 0) / totalSubscribers.value * 100
})

const confirmedPercentage = computed(() => {
  if (!newsletterFunnel.value) return 0
  const total = (newsletterFunnel.value.pending || 0) + (newsletterFunnel.value.confirmed || 0)
  return total ? Math.round((newsletterFunnel.value.confirmed || 0) / total * 100) : 0
})
</script>
