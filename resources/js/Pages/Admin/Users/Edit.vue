<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto">
      <div class="bg-white rounded-lg shadow p-8">
        <h2 class="text-2xl font-bold text-dark mb-6">Edit User</h2>

        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-semibold text-dark mb-2">Name</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="John Doe"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              required
            />
            <p v-if="errors.name" class="text-sm text-red-600 mt-1">{{ errors.name[0] }}</p>
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-semibold text-dark mb-2">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="john@example.com"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              required
            />
            <p v-if="errors.email" class="text-sm text-red-600 mt-1">{{ errors.email[0] }}</p>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-semibold text-dark mb-2">Password</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="Leave blank to keep current password"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
            <p v-if="errors.password" class="text-sm text-red-600 mt-1">{{ errors.password[0] }}</p>
          </div>

          <!-- Confirm Password -->
          <div v-if="form.password">
            <label for="password_confirmation" class="block text-sm font-semibold text-dark mb-2">Confirm Password</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              placeholder="••••••••"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
            <p v-if="errors.password_confirmation" class="text-sm text-red-600 mt-1">{{ errors.password_confirmation[0] }}</p>
          </div>

          <!-- Role -->
          <div>
            <label for="role" class="block text-sm font-semibold text-dark mb-2">Role</label>
            <select
              id="role"
              v-model="form.role"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            >
              <option value="moderator">Moderator</option>
              <option value="editor">Editor</option>
              <option value="admin">Admin</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">
              <span class="font-semibold">Moderator:</span> View only access
              <span class="ml-2"><span class="font-semibold">Editor:</span> Can manage content</span>
              <span class="ml-2"><span class="font-semibold">Admin:</span> Full access</span>
            </p>
            <p v-if="errors.role" class="text-sm text-red-600 mt-1">{{ errors.role[0] }}</p>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 pt-6">
            <button
              type="submit"
              :disabled="loading"
              class="flex-1 bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-50"
            >
              <span v-if="!loading">Update User</span>
              <span v-else>Updating...</span>
            </button>
            <Link
              href="/admin/users"
              class="px-6 py-3 border border-gray-300 text-dark font-semibold rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()
const user = computed(() => page.props.user || {})

const form = useForm({
  name: user.value.name || '',
  email: user.value.email || '',
  password: '',
  password_confirmation: '',
  role: user.value.role || 'moderator'
})

const loading = ref(false)
const errors = reactive({
  name: [],
  email: [],
  password: [],
  password_confirmation: [],
  role: []
})

const submitForm = () => {
  loading.value = true

  form.patch(`/admin/users/${user.value.id}`, {
    onSuccess: () => {
      loading.value = false
    },
    onError: (fieldErrors) => {
      loading.value = false
      Object.keys(fieldErrors).forEach(key => {
        errors[key] = Array.isArray(fieldErrors[key]) ? fieldErrors[key] : [fieldErrors[key]]
      })
    }
  })
}
</script>
