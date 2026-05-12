<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Filter Tabs & Search -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col gap-4">
          <!-- Status Tabs -->
          <div class="flex gap-2 overflow-x-auto pb-2">
            <button
              v-for="status in ['all', 'new', 'read', 'replied', 'archived']"
              :key="status"
              @click="selectedStatus = status"
              class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
              :class="
                selectedStatus === status
                  ? 'bg-primary text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              "
            >
              {{ capitalize(status) }}
              <span class="ml-2 font-semibold">{{ statusCounts[status] || 0 }}</span>
            </button>
          </div>

          <!-- Search & Export -->
          <div class="flex gap-4 items-center">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by name, email..."
              class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            />
            <a
              href="/admin/exports/contacts"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
            >
              Export CSV
            </a>
          </div>
        </div>
      </div>

      <!-- Contacts Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="contact in filteredContacts" :key="contact.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <p class="font-medium text-dark">{{ contact.name }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ contact.email }}</td>
              <td class="px-6 py-4">
                <span class="text-xs font-medium px-3 py-1 rounded-full" :class="statusBadgeColor(contact.status)">
                  {{ capitalize(contact.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(contact.created_at) }}</td>
              <td class="px-6 py-4 text-right">
                <Link
                  :href="`/admin/contacts/${contact.id}`"
                  class="text-primary hover:text-primary-dark font-medium text-sm"
                >
                  View
                </Link>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="filteredContacts.length === 0" class="text-center py-12">
          <p class="text-gray-500">No contacts found</p>
        </div>
      </div>

      <!-- Pagination Info -->
      <div v-if="contacts.data && contacts.data.length > 0" class="text-center text-sm text-gray-600">
        Showing {{ contacts.from }} to {{ contacts.to }} of {{ contacts.total }} contacts
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

const contacts = computed(() => page.props.contacts || { data: [] })
const statusCounts = computed(() => page.props.statusCounts || {})

const selectedStatus = ref('all')
const searchQuery = ref('')

const filteredContacts = computed(() => {
  let filtered = contacts.value.data || []

  if (selectedStatus.value !== 'all') {
    filtered = filtered.filter(c => c.status === selectedStatus.value)
  }

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(c =>
      c.name.toLowerCase().includes(query) ||
      c.email.toLowerCase().includes(query)
    )
  }

  return filtered
})

const statusBadgeColor = (status) => {
  const colors = {
    'new': 'bg-blue-100 text-blue-800',
    'read': 'bg-gray-100 text-gray-800',
    'replied': 'bg-green-100 text-green-800',
    'archived': 'bg-gray-100 text-gray-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>
