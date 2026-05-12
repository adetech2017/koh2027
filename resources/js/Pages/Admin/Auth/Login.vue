<template>
  <div class="min-h-screen bg-linear-to-br from-primary to-primary-dark flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <h1 class="text-5xl font-bold text-gold">KOH 2027</h1>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-lg shadow-2xl p-8">
        <h2 class="text-2xl font-bold text-dark mb-6 text-center">Admin Login</h2>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
              Email Address
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="admin@example.com"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
              required
              autofocus
            />
            <p v-if="errors.email" class="text-sm text-red-600 mt-1">{{ errors.email[0] }}</p>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
              Password
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
              required
            />
            <p v-if="errors.password" class="text-sm text-red-600 mt-1">{{ errors.password[0] }}</p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center gap-2">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              class="w-4 h-4 text-primary rounded focus:ring-primary"
            />
            <label for="remember" class="text-sm text-gray-700">Remember me</label>
          </div>

          <!-- Error Message -->
          <div v-if="errors.message" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ errors.message }}
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed mt-6"
          >
            <span v-if="!loading">Sign In</span>
            <span v-else>Signing in...</span>
          </button>
        </form>

        <!-- Footer -->
        <p class="text-center text-sm text-gray-600 mt-6">
          Authorized personnel only
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false
})

const loading = ref(false)
const errors = reactive({
  email: [],
  password: [],
  message: ''
})

const submitForm = () => {
  loading.value = true

  form.post('/admin/login', {
    onSuccess: () => {
      loading.value = false
      window.location.href = '/admin'
    },
    onError: (fieldErrors) => {
      loading.value = false

      if (fieldErrors.email) {
        errors.email = Array.isArray(fieldErrors.email) ? fieldErrors.email : [fieldErrors.email]
      } else {
        errors.email = []
      }

      if (fieldErrors.password) {
        errors.password = Array.isArray(fieldErrors.password) ? fieldErrors.password : [fieldErrors.password]
      } else {
        errors.password = []
      }

      if (fieldErrors.message || Object.keys(fieldErrors).length > 0) {
        errors.message = fieldErrors.message || 'Invalid email or password'
      }
    }
  })
}
</script>
