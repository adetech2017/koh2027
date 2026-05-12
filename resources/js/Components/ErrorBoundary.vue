<template>
  <div v-if="hasError" class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-2xl mx-auto">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h1 class="text-xl font-bold text-red-800 mb-2">Something went wrong</h1>
        <p class="text-red-700 mb-4">{{ error.message }}</p>
        <div class="space-y-2">
          <button
            @click="resetError"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium"
          >
            Try again
          </button>
          <a
            href="/"
            class="inline-block px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium ml-2"
          >
            Go to home
          </a>
        </div>
      </div>
    </div>
  </div>
  <div v-else>
    <slot />
  </div>
</template>

<script setup>
import { ref, onErrorCaptured } from 'vue'

const hasError = ref(false)
const error = ref(null)

onErrorCaptured((err) => {
  hasError.value = true
  error.value = err
  return false
})

const resetError = () => {
  hasError.value = false
  error.value = null
}
</script>
