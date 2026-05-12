<template>
  <AppLayout>
    <Head :title="safeMeta.title">
      <meta name="description" :content="safeMeta.description" />
      <meta property="og:title" :content="safeMeta.title" />
      <meta property="og:description" :content="safeMeta.description" />
      <meta v-if="safeMeta.image" property="og:image" :content="safeMeta.image" />
      <meta property="og:type" content="event" />
      <meta property="og:url" :content="safeMeta.url" />
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" :content="safeMeta.title" />
      <meta name="twitter:description" :content="safeMeta.description" />
      <meta v-if="safeMeta.image" name="twitter:image" :content="safeMeta.image" />
    </Head>
    <div class="min-h-screen bg-white">
      <div v-if="event.image_url" class="h-96 md:h-screen relative">
        <img :src="event.image_url" :alt="event.title" class="w-full h-full object-cover" />
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="absolute inset-0 flex items-end">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pb-12">
            <h1 class="text-5xl font-bold text-white mb-4">{{ event.title }}</h1>
          </div>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
          <div class="md:col-span-2">
            <div class="prose max-w-none mb-12">
              <h2 class="text-2xl font-bold text-dark mb-4">Event Details</h2>
              <div class="bg-light-gray rounded-lg p-6 mb-8 space-y-4">
                <div>
                  <h3 class="font-semibold text-dark">Date & Time</h3>
                  <p class="text-body">{{ formatDateTime(event.starts_at) }}</p>
                </div>
                <div>
                  <h3 class="font-semibold text-dark">Venue</h3>
                  <p class="text-body">{{ event.venue_name }}</p>
                  <p class="text-body">{{ event.address }}, {{ event.lga }}</p>
                </div>
                <div>
                  <h3 class="font-semibold text-dark">Event Type</h3>
                  <p class="text-body capitalize">{{ event.event_type }}</p>
                </div>
              </div>
              <h2 class="text-2xl font-bold text-dark mb-4">Description</h2>
              <p class="text-body leading-relaxed">{{ event.description }}</p>
              <div class="border-t border-light-gray mt-12 pt-8">
                <ShareButtons :url="safeMeta.url" :title="safeMeta.title" />
              </div>
            </div>
          </div>
          <div>
            <div class="sticky top-24 space-y-6">
              <div class="bg-light-blue rounded-lg p-6">
                <h3 class="text-xl font-bold text-dark mb-4">RSVPs</h3>
                <p class="text-3xl font-bold text-primary">{{ rsvpCount }}</p>
                <p class="text-body text-sm">confirmed registrations</p>
              </div>
              <form v-if="event.rsvp_enabled && !isFull" @submit.prevent="submitRsvp" class="bg-light-gray rounded-lg p-6 space-y-4">
                <h3 class="font-bold text-dark">Register for this event</h3>
                <div>
                  <input v-model="rsvpForm.name" type="text" placeholder="Your name" required class="w-full px-4 py-2 border border-light-gray rounded focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div>
                  <input v-model="rsvpForm.email" type="email" placeholder="Your email" required class="w-full px-4 py-2 border border-light-gray rounded focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <div>
                  <input v-model="rsvpForm.phone" type="tel" placeholder="Your phone (optional)" class="w-full px-4 py-2 border border-light-gray rounded focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>
                <button type="submit" class="btn-primary w-full">RSVP Now</button>
              </form>
              <div v-else-if="isFull" class="bg-red-50 rounded-lg p-6 border border-red-200">
                <p class="text-red-800 font-semibold">This event is full</p>
                <p class="text-red-700 text-sm">No more registrations are being accepted.</p>
              </div>
              <div v-else class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                <p class="text-blue-800 font-semibold">RSVPs are not enabled for this event</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ShareButtons from '@/Components/ShareButtons.vue'

const props = defineProps({
  event: Object,
  rsvpCount: Number,
  isFull: Boolean,
  meta: Object,
})

const safeMeta = computed(() => ({
  title: props.meta?.title || props.event?.title || 'Event',
  description: props.meta?.description || props.event?.description || '',
  image: props.meta?.image || props.event?.image_url || '',
  url: props.meta?.url || (typeof window !== 'undefined' ? window.location.href : ''),
}))

const rsvpForm = useForm({
  name: '',
  email: '',
  phone: '',
  lga: '',
})

const submitRsvp = () => {
  rsvpForm.post(`/events/${event.id}/rsvp`)
}

const formatDateTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
