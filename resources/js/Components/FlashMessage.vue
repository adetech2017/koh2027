<template>
  <Transition name="fade">
    <div v-if="message" class="fixed top-20 right-4 z-40 max-w-sm animate-pulse">
      <div :class="['p-4 rounded-lg text-white font-medium', messageClass]">
        {{ message }}
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const message = ref('')
const type = ref('')

const messageClass = computed(() => {
  if (type.value === 'success') return 'bg-success'
  if (type.value === 'error') return 'bg-red-500'
  return 'bg-blue-500'
})

watch(
  () => page.props.flash,
  (flash) => {
    if (flash.success) {
      message.value = flash.success
      type.value = 'success'
      setTimeout(() => {
        message.value = ''
      }, 5000)
    }
    if (flash.error) {
      message.value = flash.error
      type.value = 'error'
      setTimeout(() => {
        message.value = ''
      }, 5000)
    }
    if (flash.info) {
      message.value = flash.info
      type.value = 'info'
      setTimeout(() => {
        message.value = ''
      }, 5000)
    }
  },
  { immediate: true, deep: true },
)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
