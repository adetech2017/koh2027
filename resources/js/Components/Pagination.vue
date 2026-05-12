<template>
  <div class="flex items-center justify-center space-x-2 my-8">
    <template v-for="link in links" :key="link.label">
      <!-- Previous/Next Buttons and Page Numbers -->
      <Link
        v-if="link.url"
        :href="link.url"
        :class="[
          'px-3 py-2 rounded border transition-colors',
          link.active
            ? 'bg-primary text-white border-primary'
            : 'border-light-gray text-body hover:border-primary hover:text-primary',
        ]"
        v-html="link.label"
      ></Link>
      <span
        v-else
        :class="[
          'px-3 py-2 rounded',
          link.active ? 'bg-primary text-white' : 'text-gray-400',
        ]"
        v-html="link.label"
      ></span>
    </template>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  links: {
    type: Array,
    required: true,
  },
})
</script>
