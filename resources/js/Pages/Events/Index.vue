<template>
  <AppLayout>
    <Head title="Upcoming Campaign Events">
      <meta name="description" content="Upcoming campaign events across Lagos State. Join rallies, town halls, and community engagement activities. RSVP for events near you." />
      <meta property="og:title" content="Upcoming Campaign Events - KOH 2027" />
      <meta property="og:description" content="Join us for upcoming campaign events across Lagos State. Rallies, town halls, and community engagement activities." />
      <meta property="og:type" content="website" />
      <meta name="twitter:card" content="summary" />
      <meta name="twitter:title" content="Upcoming Campaign Events - KOH 2027" />
      <meta name="twitter:description" content="Join us for upcoming campaign events across Lagos State." />
    </Head>
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign Events</h1>
          <p class="text-xl text-gray-200">Join us on the campaign trail</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-light-gray rounded-lg p-6 mb-8">
          <h2 class="font-semibold text-dark mb-4">Filter Events</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-dark font-medium mb-2">Location (LGA)</label>
              <select :value="filters.lga" @change="(e) => updateFilter('lga', e.target.value)" class="w-full px-4 py-2 border border-light-gray rounded focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">All Locations</option>
                <option v-for="lga in lgas" :key="lga" :value="lga">{{ lga }}</option>
              </select>
            </div>
            <div>
              <label class="block text-dark font-medium mb-2">Event Type</label>
              <select :value="filters.type" @change="(e) => updateFilter('type', e.target.value)" class="w-full px-4 py-2 border border-light-gray rounded focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">All Types</option>
                <option value="rally">Rally</option>
                <option value="townhall">Town Hall</option>
                <option value="fundraiser">Fundraiser</option>
                <option value="workshop">Workshop</option>
              </select>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
          <EventCard v-for="event in events.data" :key="event.id" :event="event" />
        </div>
        <Pagination :links="events.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EventCard from '@/Components/EventCard.vue'
import Pagination from '@/Components/Pagination.vue'

defineProps({
  events: Object,
  filters: Object,
  lgas: Array,
})

const updateFilter = (key, value) => {
  const params = { ...filters, [key]: value || undefined }
  router.get(route('events.index'), params)
}
</script>
