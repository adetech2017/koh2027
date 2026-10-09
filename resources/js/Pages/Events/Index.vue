<template>
  <AppLayout>
    <Head title="Campaign Events" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign Events</h1>
          <p class="text-xl text-gray-200">Join us on the campaign trail</p>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Upcoming / Past -->
        <div class="flex gap-2 mb-6" role="tablist" aria-label="Which events">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            role="tab"
            :aria-selected="filters.when === tab.key"
            class="px-5 py-2 rounded-full font-medium text-sm transition-colors"
            :class="filters.when === tab.key ? 'bg-primary text-white' : 'bg-light-gray text-body hover:bg-gray-200'"
            @click="visit({ when: tab.key === 'upcoming' ? undefined : tab.key })"
          >
            {{ tab.label }} <span class="ml-1 opacity-75">{{ counts[tab.key] }}</span>
          </button>
        </div>

        <!-- Filters -->
        <div class="bg-light-gray rounded-lg p-4 sm:p-6 mb-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
          <div>
            <label for="event-lga" class="block text-dark font-medium mb-2">Location (LGA)</label>
            <select
              id="event-lga"
              :value="filters.lga || ''"
              class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary"
              @change="visit({ lga: $event.target.value || undefined })"
            >
              <option value="">All locations</option>
              <option v-for="lga in lgas" :key="lga" :value="lga">{{ lga }}</option>
            </select>
          </div>
          <div>
            <label for="event-type" class="block text-dark font-medium mb-2">Event type</label>
            <select
              id="event-type"
              :value="filters.type || ''"
              class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary"
              @change="visit({ type: $event.target.value || undefined })"
            >
              <option value="">All types</option>
              <option v-for="(label, value) in EVENT_TYPES" :key="value" :value="value">{{ label }}</option>
            </select>
          </div>
          <div v-if="filters.lga || filters.type">
            <button type="button" class="text-sm font-semibold text-primary hover:underline py-2" @click="visit({ lga: undefined, type: undefined })">
              Clear filters
            </button>
          </div>
        </div>

        <div v-if="events.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
          <EventCard v-for="event in events.data" :key="event.id" :event="event" />
        </div>

        <!-- Empty states -->
        <div v-else-if="filters.lga || filters.type" class="text-center py-16">
          <p class="text-lg font-semibold text-dark mb-2">No {{ filters.when }} events match these filters.</p>
          <button type="button" class="text-primary font-semibold hover:underline" @click="visit({ lga: undefined, type: undefined })">Show all {{ filters.when }} events</button>
        </div>
        <div v-else-if="filters.when === 'upcoming'" class="bg-light-gray rounded-lg p-8 md:p-10 grid grid-cols-1 md:grid-cols-2 gap-8 items-center mb-12">
          <div>
            <p class="text-xl font-bold text-dark mb-2">New events are being planned</p>
            <p class="text-body mb-4">Town halls, rallies and community meetings are announced here first. Subscribe and we'll let you know when one is coming to your area.</p>
            <button v-if="counts.past" type="button" class="text-sm font-semibold text-primary hover:underline" @click="visit({ when: 'past' })">
              See {{ counts.past }} past event{{ counts.past !== 1 ? 's' : '' }} →
            </button>
          </div>
          <NewsletterForm id="events-page-newsletter" />
        </div>
        <p v-else class="text-center text-body py-16">No past events yet.</p>

        <Pagination v-if="events.last_page > 1" :links="events.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EventCard from '@/Components/EventCard.vue'
import Pagination from '@/Components/Pagination.vue'
import NewsletterForm from '@/Components/NewsletterForm.vue'
import { EVENT_TYPES } from '@/Utils/lagos'

const props = defineProps({
  events: { type: Object, required: true },
  filters: { type: Object, default: () => ({ when: 'upcoming' }) },
  counts: { type: Object, default: () => ({ upcoming: 0, past: 0 }) },
  lgas: { type: Array, default: () => [] },
})

const tabs = [
  { key: 'upcoming', label: 'Upcoming' },
  { key: 'past', label: 'Past' },
]

// Previously referenced undefined `filters` and `route` here, so every filter change threw an error
const visit = (changes) => {
  const query = {
    when: props.filters.when === 'past' ? 'past' : undefined,
    lga: props.filters.lga || undefined,
    type: props.filters.type || undefined,
    ...changes,
  }
  router.get('/events', query, { preserveScroll: true, preserveState: true, replace: true })
}
</script>
