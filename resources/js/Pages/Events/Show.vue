<template>
  <AppLayout>
    <Head :title="event.title" />
    <div class="min-h-screen bg-white">
      <!-- Header -->
      <div class="relative bg-primary">
        <img
          v-if="event.image_url"
          :src="event.image_url"
          :alt="event.image_alt || ''"
          class="absolute inset-0 w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/10" />
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-10 md:pt-40 md:pb-14">
          <Link href="/events" class="inline-flex items-center gap-1 text-sm text-gray-200 hover:text-white mb-4">← All events</Link>
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="bg-gold text-dark px-3 py-1 rounded-full text-xs font-semibold">{{ typeLabel }}</span>
            <span v-if="isPast" class="bg-white/20 text-white px-3 py-1 rounded-full text-xs font-semibold">Past event</span>
          </div>
          <h1 class="text-3xl md:text-5xl font-bold text-white mb-3 max-w-4xl">{{ event.title }}</h1>
          <p class="text-gray-100 text-lg">{{ when }} · {{ event.lga }}</p>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
          <div class="lg:col-span-2 space-y-10">
            <!-- Key details -->
            <dl class="bg-light-gray rounded-lg p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="flex gap-3">
                <CalendarDaysIcon class="w-6 h-6 text-primary flex-shrink-0" aria-hidden="true" />
                <div>
                  <dt class="font-semibold text-dark">Date & time</dt>
                  <dd class="text-body">{{ dateLong }}</dd>
                  <dd class="text-body">{{ timeRange }}</dd>
                </div>
              </div>
              <div class="flex gap-3">
                <MapPinIcon class="w-6 h-6 text-primary flex-shrink-0" aria-hidden="true" />
                <div>
                  <dt class="font-semibold text-dark">Venue</dt>
                  <dd class="text-body">{{ event.venue_name }}</dd>
                  <dd class="text-body">{{ event.address }}, {{ event.lga }}</dd>
                  <dd>
                    <a :href="directionsUrl" target="_blank" rel="noopener" class="text-sm font-semibold text-primary hover:underline">Get directions →</a>
                  </dd>
                </div>
              </div>
            </dl>

            <section>
              <h2 class="text-2xl font-bold text-dark mb-4">About this event</h2>
              <p class="text-body leading-relaxed whitespace-pre-line">{{ event.description }}</p>
            </section>

            <section v-if="event.map_embed_url">
              <h2 class="text-2xl font-bold text-dark mb-4">Location</h2>
              <div class="aspect-video rounded-lg overflow-hidden bg-light-gray">
                <iframe
                  :src="event.map_embed_url"
                  :title="`Map of ${event.venue_name}`"
                  class="w-full h-full border-0"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  allowfullscreen
                />
              </div>
            </section>

            <div class="border-t border-light-gray pt-8">
              <ShareButtons :url="shareUrl" :title="event.title" />
            </div>
          </div>

          <!-- RSVP -->
          <aside>
            <div class="lg:sticky lg:top-24 space-y-4">
              <div v-if="isPast" class="bg-light-gray rounded-lg p-6">
                <p class="font-semibold text-dark">This event has taken place.</p>
                <p class="text-body text-sm mt-1">{{ rsvpCount }} {{ rsvpCount === 1 ? 'person' : 'people' }} registered.</p>
                <Link href="/events" class="inline-block mt-3 text-sm font-semibold text-primary hover:underline">See upcoming events →</Link>
              </div>

              <div v-else-if="registered" class="bg-green-50 border border-green-200 rounded-lg p-6" role="status">
                <CheckCircleIcon class="w-8 h-8 text-success mb-2" aria-hidden="true" />
                <p class="font-bold text-dark">You're registered!</p>
                <p class="text-body text-sm mt-1">We've saved your place for {{ dateLong }}. Bring a friend.</p>
              </div>

              <div v-else-if="!event.rsvp_enabled" class="bg-light-gray rounded-lg p-6">
                <p class="font-semibold text-dark">No registration needed.</p>
                <p class="text-body text-sm mt-1">Just come along, everyone is welcome.</p>
              </div>

              <div v-else-if="isFull" class="bg-red-50 rounded-lg p-6 border border-red-200">
                <p class="text-red-800 font-semibold">This event is full</p>
                <p class="text-red-700 text-sm">No more registrations are being accepted.</p>
              </div>

              <form v-else class="bg-light-gray rounded-lg p-6 space-y-4" novalidate @submit.prevent="submitRsvp">
                <div>
                  <h2 class="text-xl font-bold text-dark">Register to attend</h2>
                  <p class="text-sm text-body mt-1">
                    <template v-if="placesLeft !== null">{{ placesLeft }} place{{ placesLeft !== 1 ? 's' : '' }} left · </template>{{ rsvpCount }} registered
                  </p>
                </div>
                <p v-if="rsvpForm.errors.rsvp" class="text-sm text-red-700 bg-red-50 border border-red-200 rounded p-3" role="alert">{{ rsvpForm.errors.rsvp }}</p>
                <div v-for="field in fields" :key="field.key">
                  <label :for="`rsvp-${field.key}`" class="block text-sm font-medium text-dark mb-1">
                    {{ field.label }} <span v-if="field.optional" class="text-gray-500 font-normal">(optional)</span>
                  </label>
                  <select
                    v-if="field.key === 'lga'"
                    :id="`rsvp-${field.key}`"
                    v-model="rsvpForm.lga"
                    :class="inputClass('lga')"
                  >
                    <option value="">Choose your LGA</option>
                    <option v-for="lga in LAGOS_LGAS" :key="lga" :value="lga">{{ lga }}</option>
                    <option value="Outside Lagos">Outside Lagos</option>
                  </select>
                  <input
                    v-else
                    :id="`rsvp-${field.key}`"
                    v-model="rsvpForm[field.key]"
                    :type="field.type"
                    :autocomplete="field.autocomplete"
                    :inputmode="field.inputmode"
                    :aria-invalid="!!rsvpForm.errors[field.key]"
                    :class="inputClass(field.key)"
                  />
                  <p v-if="rsvpForm.errors[field.key]" class="text-sm text-red-600 mt-1">{{ friendly(field.key) }}</p>
                </div>
                <button type="submit" class="btn-primary w-full" :disabled="rsvpForm.processing">
                  {{ rsvpForm.processing ? 'Registering…' : 'RSVP now' }}
                </button>
                <p class="text-xs text-gray-500">We'll only use your details for this event and campaign updates. <Link href="/privacy" class="underline">Privacy policy</Link></p>
              </form>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { CalendarDaysIcon, CheckCircleIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import ShareButtons from '@/Components/ShareButtons.vue'
import { EVENT_TYPES, LAGOS_LGAS } from '@/Utils/lagos'

const props = defineProps({
  event: { type: Object, required: true },
  rsvpCount: { type: Number, default: 0 },
  isFull: { type: Boolean, default: false },
  isPast: { type: Boolean, default: false },
  placesLeft: { type: Number, default: null },
  shareUrl: { type: String, default: '' },
})

const fields = [
  { key: 'name', label: 'Full name', type: 'text', autocomplete: 'name' },
  { key: 'email', label: 'Email', type: 'email', autocomplete: 'email', inputmode: 'email' },
  { key: 'phone', label: 'Phone', type: 'tel', autocomplete: 'tel', inputmode: 'tel', optional: true },
  { key: 'lga', label: 'Your LGA', optional: true },
]

const typeLabel = computed(() => EVENT_TYPES[props.event.event_type] || 'Event')

const start = computed(() => new Date(props.event.starts_at))
const dateLong = computed(() => start.value.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }))
const time = (d) => d.toLocaleTimeString('en-GB', { hour: 'numeric', minute: '2-digit', hour12: true })
const timeRange = computed(() => props.event.ends_at ? `${time(start.value)} – ${time(new Date(props.event.ends_at))}` : time(start.value))
const when = computed(() => `${start.value.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}, ${time(start.value)}`)

const directionsUrl = computed(() =>
  `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${props.event.venue_name}, ${props.event.address}, ${props.event.lga}, Lagos`)}`
)

const rsvpForm = useForm({ name: '', email: '', phone: '', lga: '' })
const registered = ref(false)

const inputClass = (key) => [
  'w-full px-4 py-2 border rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary',
  rsvpForm.errors[key] ? 'border-red-400' : 'border-gray-300',
]

const friendly = (key) => {
  const error = rsvpForm.errors[key] || ''
  if (key === 'email' && /valid email/i.test(error)) return 'Please check your email address.'
  if (key === 'phone' && /format/i.test(error)) return 'Please enter a valid phone number, e.g. 0803 123 4567.'
  return error
}

// Previously posted to `/events/${event.id}/rsvp`, where `event` was the browser's global, so every RSVP failed
const submitRsvp = () => {
  rsvpForm.post(`/events/${props.event.id}/rsvp`, {
    preserveScroll: true,
    onSuccess: () => {
      if (!Object.keys(rsvpForm.errors).length) {
        registered.value = true
        rsvpForm.reset()
      }
    },
  })
}
</script>
