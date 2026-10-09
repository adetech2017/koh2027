<template>
  <figure class="h-full bg-white rounded-lg p-6 sm:p-8 border-l-4 border-gold shadow-card flex flex-col">
    <svg class="w-10 h-10 text-gold opacity-30 mb-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M3 21c3 0 7-1 7-8V5c0-1.25-4.716-2.5-7-2.5S0 3.75 0 5v8c0 7 4 8 7 8z" />
      <path d="M15 21c3 0 7-1 7-8V5c0-1.25-4.716-2.5-7-2.5s-7 1.25-7 2.5v8c0 7 4 8 7 8z" />
    </svg>
    <blockquote class="text-lg text-body italic mb-6 leading-relaxed flex-grow">{{ testimonial.quote }}</blockquote>
    <figcaption class="flex items-center gap-4">
      <img
        v-if="testimonial.avatar_url && !avatarFailed"
        :src="testimonial.avatar_url"
        alt=""
        class="w-12 h-12 rounded-full object-cover flex-shrink-0"
        loading="lazy"
        @error="avatarFailed = true"
      />
      <div v-else class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-semibold flex-shrink-0" aria-hidden="true">
        {{ initials }}
      </div>
      <div>
        <p class="font-bold text-dark">{{ testimonial.author_name }}</p>
        <p class="text-sm text-body">{{ [testimonial.author_title, testimonial.author_lga].filter(Boolean).join(', ') }}</p>
      </div>
    </figcaption>
  </figure>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  testimonial: { type: Object, required: true },
})

const avatarFailed = ref(false)

// Skip honorifics so "Dr. Ngozi Eze" becomes "NE"
const initials = computed(() =>
  (props.testimonial.author_name || '?')
    .replace(/^(mr|mrs|ms|dr|prof|chief|hon|engr|alhaji|alhaja)\.?\s+/i, '')
    .split(/\s+/)
    .slice(0, 2)
    .map(part => part[0])
    .join('')
    .toUpperCase()
)
</script>
