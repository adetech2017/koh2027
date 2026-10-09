<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-600">
          {{ activeCount }} active team member{{ activeCount !== 1 ? 's' : '' }}<template v-if="suspendedCount"> · {{ suspendedCount }} suspended</template>
        </p>
        <Link
          href="/admin/users/create"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add team member
        </Link>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden divide-y">
        <div
          v-for="user in users"
          :key="user.id"
          class="flex flex-col md:flex-row md:items-center gap-4 px-4 sm:px-6 py-4"
          :class="{ 'bg-gray-50': user.deactivated_at }"
        >
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0" :class="user.deactivated_at ? 'bg-gray-200 text-gray-500' : 'bg-primary/10 text-primary'">
              {{ initials(user.name) }}
            </div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <Link :href="`/admin/users/${user.id}/edit`" class="font-semibold text-dark hover:text-primary" :class="{ 'text-gray-500': user.deactivated_at }">{{ user.name }}</Link>
                <span v-if="user.id === authUser.id" class="text-[11px] font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">You</span>
                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full" :class="roleStyle(user.role)">{{ capitalize(user.role) }}</span>
                <span v-if="user.deactivated_at" class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-gray-200 text-gray-700">Suspended</span>
              </div>
              <p class="text-sm text-gray-500 truncate">{{ user.email }}</p>
            </div>
          </div>

          <div class="md:w-48 text-xs text-gray-500 flex-shrink-0">
            <p v-if="user.deactivated_at">Suspended {{ relative(user.deactivated_at) }}</p>
            <p v-else-if="isActiveNow(user)" class="inline-flex items-center gap-1.5 text-green-700 font-medium">
              <span class="w-2 h-2 rounded-full bg-green-500" aria-hidden="true" />Active now
            </p>
            <p v-else-if="user.last_active_at" :title="new Date(user.last_active_at).toLocaleString()">Last active {{ relative(user.last_active_at) }}</p>
            <p v-else class="text-amber-700">Hasn't signed in yet</p>
            <p>Added {{ formatDate(user.created_at) }}</p>
          </div>

          <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
            <Link
              :href="`/admin/users/${user.id}/edit`"
              class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
            >
              Edit
            </Link>
            <template v-if="user.id !== authUser.id">
              <button
                type="button"
                class="px-3 py-1.5 rounded-lg font-medium text-sm transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                :class="user.deactivated_at ? 'text-green-700 hover:bg-green-50' : 'text-gray-700 hover:bg-gray-100'"
                :disabled="isLastAdmin(user)"
                :title="isLastAdmin(user) ? 'The only active admin cannot be suspended' : ''"
                @click="toggleAccess(user)"
              >
                {{ user.deactivated_at ? 'Restore access' : 'Suspend' }}
              </button>
              <button
                type="button"
                class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg font-medium text-sm transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="isLastAdmin(user)"
                :title="isLastAdmin(user) ? 'The only active admin cannot be deleted' : ''"
                @click="deleteUser(user)"
              >
                Delete
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- What each role can do -->
      <details class="bg-white rounded-lg shadow p-4 sm:p-6 group">
        <summary class="cursor-pointer font-semibold text-dark list-none flex items-center justify-between">
          What can each role do?
          <span class="text-gray-400 group-open:rotate-180 transition-transform">⌄</span>
        </summary>
        <div class="overflow-x-auto mt-4">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b">
                <th class="py-2 pr-4 font-medium">Can…</th>
                <th class="py-2 px-3 font-medium text-center">Moderator</th>
                <th class="py-2 px-3 font-medium text-center">Editor</th>
                <th class="py-2 px-3 font-medium text-center">Admin</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="row in permissions" :key="row.label">
                <td class="py-2 pr-4 text-gray-700">{{ row.label }}</td>
                <td v-for="(allowed, i) in row.roles" :key="i" class="py-2 px-3 text-center" :aria-label="allowed ? 'Yes' : 'No'">
                  <span :class="allowed ? 'text-green-600' : 'text-gray-300'">{{ allowed ? '✓' : '✕' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </details>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
  users: { type: Array, default: () => [] },
  activeAdminCount: { type: Number, default: 1 },
})

const page = usePage()
const confirmDialog = ref(null)
const authUser = computed(() => page.props.auth?.user || {})

const activeCount = computed(() => props.users.filter(u => !u.deactivated_at).length)
const suspendedCount = computed(() => props.users.length - activeCount.value)

const isLastAdmin = (user) => user.role === 'admin' && !user.deactivated_at && props.activeAdminCount <= 1

// Mirrors the server's middleware and gates
const permissions = [
  { label: 'View contacts, volunteers & newsletter', roles: [true, true, true] },
  { label: 'Update statuses, add notes & tags', roles: [true, true, true] },
  { label: 'View analytics', roles: [true, true, true] },
  { label: 'Edit website content (news, events, gallery…)', roles: [false, true, true] },
  { label: 'Export CSVs', roles: [false, true, true] },
  { label: 'Delete content', roles: [false, false, true] },
  { label: 'Send bulk emails', roles: [false, false, true] },
  { label: 'Manage team members', roles: [false, false, true] },
]

// The signed-in viewer is active by definition; others count as active within the last 5 minutes
const isActiveNow = (user) =>
  user.id === authUser.value.id || (user.last_active_at && Date.now() - new Date(user.last_active_at) < 5 * 60 * 1000)

const roleStyle = (role) => ({
  admin: 'bg-primary text-white',
  editor: 'bg-blue-100 text-blue-800',
  moderator: 'bg-gray-100 text-gray-700',
}[role] || 'bg-gray-100 text-gray-700')

const capitalize = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : '')
const initials = (name) => (name || '?').split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase()
const formatDate = (iso) => new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })

const relative = (iso) => {
  const seconds = Math.floor((Date.now() - new Date(iso)) / 1000)
  if (seconds < 60) return 'just now'
  if (seconds < 3600) return `${Math.floor(seconds / 60)} min ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} h ago`
  if (seconds < 86400 * 30) return `${Math.floor(seconds / 86400)} days ago`
  return `on ${formatDate(iso)}`
}

const toggleAccess = async (user) => {
  if (!user.deactivated_at) {
    const ok = await confirmDialog.value.open(
      'Suspend access',
      `${user.name} will be signed out straight away and won't be able to sign in. Their notes and history stay. You can restore access at any time.`,
      { confirmText: 'Suspend' }
    )
    if (!ok) return
  }
  router.patch(`/admin/users/${user.id}/access`, {}, { preserveScroll: true })
}

const deleteUser = async (user) => {
  const ok = await confirmDialog.value.open(
    'Delete team member',
    `Permanently delete ${user.name}'s account? Their notes and activity history are kept but will show as from a deleted user. To just block sign-in, suspend them instead.`,
    { confirmText: 'Delete', isDangerous: true }
  )
  if (ok) router.delete(`/admin/users/${user.id}`, { preserveScroll: true })
}
</script>
