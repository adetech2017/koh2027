<template>
  <div>
    <p v-if="done" class="rounded-lg px-4 py-3 text-sm font-medium" :class="dark ? 'bg-white/10 text-white' : 'bg-green-50 text-green-800'" role="status">
      {{ done }}
    </p>
    <form v-else novalidate @submit.prevent="submit">
      <label :for="id" class="sr-only">Email address</label>
      <div :class="stacked ? 'space-y-2' : 'flex flex-col sm:flex-row gap-2'">
        <input
          :id="id"
          v-model="form.email"
          type="email"
          inputmode="email"
          autocomplete="email"
          placeholder="Your email address"
          :aria-invalid="!!form.errors.email"
          :aria-describedby="form.errors.email ? `${id}-error` : undefined"
          class="w-full flex-grow px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-gold"
          :class="[dark ? 'bg-gray-800 text-white placeholder-gray-400' : 'bg-white text-dark placeholder-gray-400', form.errors.email ? 'ring-2 ring-red-400' : '']"
        />
        <button type="submit" class="btn-gold whitespace-nowrap" :class="{ 'w-full': stacked }" :disabled="form.processing">
          {{ form.processing ? 'Subscribing…' : 'Subscribe' }}
        </button>
      </div>
      <p v-if="form.errors.email" :id="`${id}-error`" class="text-sm mt-2" :class="dark ? 'text-red-300' : 'text-red-200'" role="alert">
        {{ friendlyError }}
      </p>
    </form>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  id: { type: String, default: 'newsletter-email' },
  dark: { type: Boolean, default: false },
  stacked: { type: Boolean, default: false },
})

const page = usePage()
const form = useForm({ email: '' })
const done = ref('')

// The server checks the address's domain can receive mail; explain that plainly
const friendlyError = computed(() => {
  const error = form.errors.email || ''
  if (/valid email/i.test(error)) return 'Please check your email address; it doesn\'t look right.'
  if (/too many/i.test(error)) return 'Too many attempts. Please try again in a few minutes.'
  return error
})

const submit = () => {
  if (!form.email.trim()) {
    form.setError('email', 'Please enter your email address.')
    return
  }
  form.post('/newsletter/subscribe', {
    preserveScroll: true,
    onSuccess: () => {
      done.value = page.props.flash?.success || page.props.flash?.info || 'Thanks! Check your inbox to confirm your subscription.'
      form.reset()
    },
  })
}
</script>
