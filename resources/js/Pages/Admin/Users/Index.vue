<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header with Create Button -->
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-dark">Admin Users</h2>
        <Link
          href="/admin/users/create"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          Create User
        </Link>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Role</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Created</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <p class="font-medium text-dark">{{ user.name }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ user.email }}</td>
              <td class="px-6 py-4">
                <span class="text-xs font-medium px-3 py-1 rounded-full" :class="roleBadgeColor(user.role)">
                  {{ capitalize(user.role) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(user.created_at) }}</td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-3">
                  <Link
                    :href="`/admin/users/${user.id}/edit`"
                    class="text-primary hover:text-primary-dark font-medium text-sm"
                  >
                    Edit
                  </Link>
                  <button
                    v-if="user.id !== authUser.id"
                    @click="deleteUser(user.id)"
                    class="text-red-600 hover:text-red-800 font-medium text-sm"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="users.length === 0" class="text-center py-12">
          <p class="text-gray-500">No users found</p>
        </div>
      </div>

      <!-- Pagination Info -->
      <div v-if="page.props.users?.data && page.props.users.data.length > 0" class="text-center text-sm text-gray-600">
        Showing {{ page.props.users.from }} to {{ page.props.users.to }} of {{ page.props.users.total }} users
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()
const confirmDialog = ref(null)

const users = computed(() => page.props.users?.data || [])
const authUser = computed(() => page.props.auth?.user || {})

const roleBadgeColor = (role) => {
  const colors = {
    'admin': 'bg-red-100 text-red-800',
    'editor': 'bg-blue-100 text-blue-800',
    'moderator': 'bg-green-100 text-green-800',
  }
  return colors[role] || 'bg-gray-100 text-gray-800'
}

const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const deleteUser = async (userId) => {
  const confirmed = await confirmDialog.value.open(
    'Delete User',
    'Are you sure you want to delete this user? This action cannot be undone.',
    {
      confirmText: 'Delete',
      isDangerous: true
    }
  )
  if (confirmed) {
    router.delete(`/admin/users/${userId}`)
  }
}
</script>
