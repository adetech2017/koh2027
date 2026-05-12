<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black/20 flex items-center justify-center z-50 pointer-events-auto">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm mx-4 pointer-events-auto">
      <h3 class="text-lg font-bold text-dark mb-2">{{ title }}</h3>
      <p class="text-gray-600 mb-6">{{ message }}</p>
      <div class="flex gap-3 justify-end">
        <button
          @click="cancel"
          class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium transition-colors"
        >
          Cancel
        </button>
        <button
          @click="confirm"
          :class="[
            'px-4 py-2 text-white rounded-lg font-medium transition-colors',
            isDangerous
              ? 'bg-red-600 hover:bg-red-700'
              : 'bg-primary hover:bg-primary-dark'
          ]"
        >
          {{ confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const isOpen = ref(false)
const title = ref('')
const message = ref('')
const confirmText = ref('Confirm')
const isDangerous = ref(false)
let resolveCallback = null

defineExpose({
  open(dialogTitle, dialogMessage, options = {}) {
    return new Promise((resolve) => {
      title.value = dialogTitle
      message.value = dialogMessage
      confirmText.value = options.confirmText || 'Confirm'
      isDangerous.value = options.isDangerous || false
      isOpen.value = true
      resolveCallback = resolve
    })
  }
})

const confirm = () => {
  isOpen.value = false
  if (resolveCallback) {
    resolveCallback(true)
  }
}

const cancel = () => {
  isOpen.value = false
  if (resolveCallback) {
    resolveCallback(false)
  }
}
</script>
