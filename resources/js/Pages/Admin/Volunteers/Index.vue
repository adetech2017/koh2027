<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Filter Controls -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="space-y-4">
          <!-- Status Tabs -->
          <div>
            <p class="text-sm font-semibold text-gray-600 mb-2">Status</p>
            <div class="flex gap-2 overflow-x-auto pb-2">
              <button
                v-for="status in ['all', 'pending', 'approved', 'active', 'inactive']"
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

          <!-- LGA & Vehicle Filter -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label for="lga-filter" class="text-sm font-semibold text-gray-600 mb-2 block">LGA</label>
              <select
                id="lga-filter"
                v-model="filterLga"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">All LGAs</option>
                <option v-for="lga in lgaList" :key="lga" :value="lga">
                  {{ lga }}
                </option>
              </select>
            </div>
            <div>
              <label for="vehicle-filter" class="text-sm font-semibold text-gray-600 mb-2 block">Vehicle</label>
              <select
                id="vehicle-filter"
                v-model="filterVehicle"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">All</option>
                <option value="true">Has Vehicle</option>
                <option value="false">No Vehicle</option>
              </select>
            </div>
            <div class="flex items-end">
              <a
                href="/admin/exports/volunteers"
                class="w-full text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
              >
                Export CSV
              </a>
            </div>
          </div>

          <!-- Search -->
          <div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by name, email..."
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>
        </div>
      </div>

      <!-- Volunteers Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">LGA</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Skills</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="volunteer in filteredVolunteers" :key="volunteer.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <p class="font-medium text-dark">{{ volunteer.name }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ volunteer.email }}</td>
              <td class="px-6 py-4 text-sm">
                <span class="font-medium text-dark">{{ volunteer.lga }}</span>
                <span v-if="volunteer.has_vehicle" class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">🚗</span>
              </td>
              <td class="px-6 py-4 text-sm">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="skill in (volunteer.skills || []).slice(0, 2)"
                    :key="skill"
                    class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded"
                  >
                    {{ skill }}
                  </span>
                  <span
                    v-if="(volunteer.skills || []).length > 2"
                    class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded"
                  >
                    +{{ (volunteer.skills || []).length - 2 }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-xs font-medium px-3 py-1 rounded-full" :class="statusBadgeColor(volunteer.status)">
                  {{ capitalize(volunteer.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <Link
                  :href="`/admin/volunteers/${volunteer.id}`"
                  class="text-primary hover:text-primary-dark font-medium text-sm"
                >
                  View
                </Link>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="filteredVolunteers.length === 0" class="text-center py-12">
          <p class="text-gray-500">No volunteers found</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

const volunteers = computed(() => page.props.volunteers || { data: [] })
const statusCounts = computed(() => page.props.statusCounts || {})
const lgaList = computed(() => page.props.lgaList || [])

const filterStatus = ref('all')
const filterLga = ref('')
const filterVehicle = ref('')
const searchQuery = ref('')

const filteredVolunteers = computed(() => {
  let filtered = volunteers.value.data || []

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(v => v.status === filterStatus.value)
  }

  if (filterLga.value) {
    filtered = filtered.filter(v => v.lga === filterLga.value)
  }

  if (filterVehicle.value) {
    const hasVehicle = filterVehicle.value === 'true'
    filtered = filtered.filter(v => v.has_vehicle === hasVehicle)
  }

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(v =>
      v.name.toLowerCase().includes(query) ||
      v.email.toLowerCase().includes(query)
    )
  }

  return filtered
})

const statusBadgeColor = (status) => {
  const colors = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-gray-100 text-gray-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}
</script>
