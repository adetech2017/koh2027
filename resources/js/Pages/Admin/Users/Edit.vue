<template>
  <AdminLayout>
    <div class="max-w-4xl space-y-6">
      <div>
        <Link href="/admin/users" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All users
        </Link>
        <div class="flex flex-wrap items-center gap-3 mt-2">
          <h1 class="text-2xl font-bold text-dark">{{ isSelf ? 'Your account' : user.name }}</h1>
          <span v-if="user.deactivated_at" class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-200 text-gray-700">Suspended</span>
        </div>
        <p class="text-sm text-gray-500 mt-1">
          Added {{ formatDate(user.created_at) }} ·
          <template v-if="isSelf">Signed in now</template>
          <template v-else-if="user.last_active_at">Last active {{ formatDateTime(user.last_active_at) }}</template>
          <template v-else>Hasn't signed in yet</template>
        </p>
      </div>
      <UserForm :user="user" :is-self="isSelf" :is-last-admin="isLastAdmin" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import UserForm from '@/Components/UserForm.vue'

defineProps({
  user: { type: Object, required: true },
  isSelf: { type: Boolean, default: false },
  isLastAdmin: { type: Boolean, default: false },
})

const formatDate = (iso) => new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
const formatDateTime = (iso) => new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' })
</script>
