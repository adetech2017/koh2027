<template>
  <div class="bg-white rounded-lg p-8 border-l-4 border-gold shadow-card">
    <svg class="w-12 h-12 text-gold opacity-20 mb-4" fill="currentColor" viewBox="0 0 24 24">
      <path d="M3 21c3 0 7-1 7-8V5c0-1.25-4.716-2.5-7-2.5S0 3.75 0 5v8c0 7 4 8 7 8z" />
      <path d="M15 21c3 0 7-1 7-8V5c0-1.25-4.716-2.5-7-2.5s-7 1.25-7 2.5v8c0 7 4 8 7 8z" />
    </svg>
    <p class="text-lg text-body italic mb-6 leading-relaxed">{{ testimonial.quote }}</p>
    <div class="flex items-center space-x-4">
      <img
        v-if="testimonial.avatar_url"
        :src="testimonial.avatar_url"
        :alt="testimonial.author_name"
        class="w-12 h-12 rounded-full object-cover"
      />
      <div v-else class="w-12 h-12 rounded-full bg-light-blue flex items-center justify-center">
        <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
        </svg>
      </div>
      <div>
        <p class="font-bold text-dark">{{ testimonial.author_name }}</p>
        <p class="text-sm text-body">{{ testimonial.author_title }}, {{ testimonial.author_lga }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  testimonial: {
    type: Object,
    required: true,
  },
})
</script>
