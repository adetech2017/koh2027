<template>
  <div class="bg-white rounded-lg p-8 text-center hover:shadow-card-hover transition-all">
    <div class="w-14 h-14 bg-light-blue rounded-lg flex items-center justify-center mx-auto mb-4">
      <component :is="icon" class="w-8 h-8 text-primary" />
    </div>
    <p class="text-4xl font-bold text-gold mb-2">{{ stat.value }}</p>
    <p class="text-body font-medium">{{ stat.label }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { StarIcon, CheckCircleIcon, UsersIcon, SparklesIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  stat: {
    type: Object,
    required: true,
  },
})

const iconMap = {
  star: StarIcon,
  check: CheckCircleIcon,
  users: UsersIcon,
  sparkles: SparklesIcon,
}

const icon = computed(() => {
  return iconMap[props.stat.icon] || SparklesIcon
})
</script>
