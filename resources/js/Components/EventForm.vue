<template>
  <form class="space-y-6" @submit.prevent="submit">
    <div v-if="hasErrors" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
      <p class="font-semibold">Please fix the highlighted fields.</p>
    </div>

    <!-- Details -->
    <section class="bg-white rounded-lg shadow p-6 space-y-5">
      <h3 class="text-lg font-semibold text-dark">Details</h3>
      <div>
        <label for="ev-title" class="block text-sm font-medium text-dark mb-1">Title <span class="text-red-600">*</span></label>
        <input id="ev-title" v-model="form.title" type="text" maxlength="200" :class="inputClass('title')" placeholder="e.g. Ikeja Town Hall Meeting" />
        <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
      </div>
      <div>
        <label for="ev-type" class="block text-sm font-medium text-dark mb-1">Event type <span class="text-red-600">*</span></label>
        <select id="ev-type" v-model="form.event_type" :class="inputClass('event_type')">
          <option value="" disabled>Select a type</option>
          <option v-for="(label, value) in eventTypes" :key="value" :value="value">{{ label }}</option>
        </select>
        <p v-if="form.errors.event_type" class="text-red-600 text-sm mt-1">{{ form.errors.event_type }}</p>
      </div>
      <div>
        <label for="ev-description" class="block text-sm font-medium text-dark mb-1">Description <span class="text-red-600">*</span></label>
        <textarea id="ev-description" v-model="form.description" rows="5" :class="inputClass('description')" placeholder="What is the event about and who should attend?" />
        <p v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</p>
      </div>
    </section>

    <!-- When -->
    <section class="bg-white rounded-lg shadow p-6 space-y-5">
      <h3 class="text-lg font-semibold text-dark">When</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label for="ev-start" class="block text-sm font-medium text-dark mb-1">Starts <span class="text-red-600">*</span></label>
          <input id="ev-start" v-model="form.starts_at" type="datetime-local" :class="inputClass('starts_at')" />
          <p v-if="form.errors.starts_at" class="text-red-600 text-sm mt-1">{{ form.errors.starts_at }}</p>
        </div>
        <div>
          <label for="ev-end" class="block text-sm font-medium text-dark mb-1">Ends</label>
          <input id="ev-end" v-model="form.ends_at" type="datetime-local" :min="form.starts_at || undefined" :class="inputClass('ends_at')" />
          <p v-if="form.errors.ends_at" class="text-red-600 text-sm mt-1">{{ form.errors.ends_at }}</p>
          <p v-else class="text-xs text-gray-500 mt-1">Optional</p>
        </div>
      </div>
    </section>

    <!-- Where -->
    <section class="bg-white rounded-lg shadow p-6 space-y-5">
      <h3 class="text-lg font-semibold text-dark">Where</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label for="ev-venue" class="block text-sm font-medium text-dark mb-1">Venue name <span class="text-red-600">*</span></label>
          <input id="ev-venue" v-model="form.venue_name" type="text" maxlength="200" :class="inputClass('venue_name')" placeholder="e.g. Ikeja City Hall" />
          <p v-if="form.errors.venue_name" class="text-red-600 text-sm mt-1">{{ form.errors.venue_name }}</p>
        </div>
        <div>
          <label for="ev-lga" class="block text-sm font-medium text-dark mb-1">LGA <span class="text-red-600">*</span></label>
          <input id="ev-lga" v-model="form.lga" type="text" list="ev-lga-options" maxlength="100" :class="inputClass('lga')" placeholder="Local Government Area" />
          <datalist id="ev-lga-options">
            <option v-for="lga in lgaOptions" :key="lga" :value="lga" />
          </datalist>
          <p v-if="form.errors.lga" class="text-red-600 text-sm mt-1">{{ form.errors.lga }}</p>
        </div>
        <div class="sm:col-span-2">
          <label for="ev-address" class="block text-sm font-medium text-dark mb-1">Address <span class="text-red-600">*</span></label>
          <input id="ev-address" v-model="form.address" type="text" maxlength="300" :class="inputClass('address')" placeholder="Street address" />
          <p v-if="form.errors.address" class="text-red-600 text-sm mt-1">{{ form.errors.address }}</p>
        </div>
        <div class="sm:col-span-2">
          <label for="ev-map" class="block text-sm font-medium text-dark mb-1">Google Maps embed URL</label>
          <input id="ev-map" v-model="form.map_embed_url" type="url" :class="inputClass('map_embed_url')" placeholder="https://www.google.com/maps/embed?pb=..." />
          <p v-if="form.errors.map_embed_url" class="text-red-600 text-sm mt-1">{{ form.errors.map_embed_url }}</p>
          <p v-else class="text-xs text-gray-500 mt-1">Optional. In Google Maps choose Share → Embed a map, and copy the <code>src</code> link.</p>
        </div>
      </div>
    </section>

    <!-- Image -->
    <section class="bg-white rounded-lg shadow p-6 space-y-4">
      <h3 class="text-lg font-semibold text-dark">Image <span v-if="!event" class="text-red-600">*</span></h3>
      <div class="flex flex-col sm:flex-row gap-4 sm:items-start">
        <div class="w-full sm:w-48 aspect-video rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center flex-shrink-0">
          <img v-if="previewUrl" :src="previewUrl" alt="" class="w-full h-full object-cover" />
          <svg v-else class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="flex-1 space-y-3 min-w-0">
          <div>
            <label for="ev-image" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-dark hover:border-primary hover:text-primary cursor-pointer transition-colors">
              {{ previewUrl ? 'Replace image' : 'Choose image' }}
            </label>
            <input id="ev-image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" @change="onImage" />
            <p class="text-xs text-gray-500 mt-1">
              <template v-if="form.image">{{ form.image.name }} · </template>JPG, PNG, GIF or WebP, up to 2 MB.
              <template v-if="event && !form.image"> Leave empty to keep the current image.</template>
            </p>
            <p v-if="form.errors.image" class="text-red-600 text-sm mt-1">{{ form.errors.image }}</p>
          </div>
          <div>
            <label for="ev-alt" class="block text-sm font-medium text-dark mb-1">Image description</label>
            <input id="ev-alt" v-model="form.image_alt" type="text" maxlength="200" :class="inputClass('image_alt')" placeholder="Describe the image for screen readers" />
            <p v-if="form.errors.image_alt" class="text-red-600 text-sm mt-1">{{ form.errors.image_alt }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- RSVP & visibility -->
    <section class="bg-white rounded-lg shadow p-6 space-y-5">
      <h3 class="text-lg font-semibold text-dark">RSVPs & visibility</h3>
      <div class="sm:w-1/2">
        <label for="ev-capacity" class="block text-sm font-medium text-dark mb-1">Capacity</label>
        <input id="ev-capacity" v-model.number="form.capacity" type="number" min="1" :class="inputClass('capacity')" placeholder="Unlimited" />
        <p v-if="form.errors.capacity" class="text-red-600 text-sm mt-1">{{ form.errors.capacity }}</p>
        <p v-else class="text-xs text-gray-500 mt-1">
          Leave empty for no limit.<template v-if="rsvpCount"> {{ rsvpCount }} confirmed so far.</template>
        </p>
      </div>
      <div class="space-y-4">
        <label v-for="toggle in toggles" :key="toggle.key" class="flex items-start gap-3 cursor-pointer">
          <input v-model="form[toggle.key]" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
          <span>
            <span class="block text-sm font-medium text-dark">{{ toggle.label }}</span>
            <span class="block text-xs text-gray-500">{{ toggle.help }}</span>
          </span>
        </label>
      </div>
    </section>

    <!-- Actions -->
    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
      <Link href="/admin/events" class="px-6 py-2 text-center bg-gray-100 hover:bg-gray-200 text-dark rounded-lg font-medium transition-colors">
        Cancel
      </Link>
      <button
        type="submit"
        :disabled="form.processing"
        class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
      >
        {{ form.processing ? 'Saving...' : (event ? 'Save changes' : 'Create event') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  event: { type: Object, default: null },
  lgaOptions: { type: Array, default: () => [] },
  rsvpCount: { type: Number, default: 0 },
})

const eventTypes = {
  rally: 'Rally',
  townhall: 'Town Hall',
  fundraiser: 'Fundraiser',
  workshop: 'Workshop',
  meeting: 'Meeting',
  other: 'Other',
}

const toggles = [
  { key: 'is_active', label: 'Published', help: 'Show this event on the public website.' },
  { key: 'rsvp_enabled', label: 'Accept RSVPs', help: 'Let visitors register to attend.' },
  { key: 'is_featured', label: 'Featured', help: 'Highlight this event on the homepage.' },
]

// Times are stored in UTC; the inputs work in the admin's local time, matching how the public site displays them
const toLocalInput = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}
const toUtcString = (local) => (local ? new Date(local).toISOString().slice(0, 16).replace('T', ' ') : '')

const e = props.event || {}
const form = useForm({
  title: e.title || '',
  event_type: e.event_type || '',
  description: e.description || '',
  starts_at: toLocalInput(e.starts_at),
  ends_at: toLocalInput(e.ends_at),
  venue_name: e.venue_name || '',
  lga: e.lga || '',
  address: e.address || '',
  map_embed_url: e.map_embed_url || '',
  image: null,
  image_alt: e.image_alt || '',
  capacity: e.capacity ?? '',
  is_active: props.event ? !!e.is_active : true,
  rsvp_enabled: props.event ? !!e.rsvp_enabled : true,
  is_featured: !!e.is_featured,
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

const localPreview = ref(null)
const previewUrl = computed(() => localPreview.value || e.image_url || null)

const onImage = (evt) => {
  const file = evt.target.files[0]
  if (!file) return
  form.image = file
  if (localPreview.value) URL.revokeObjectURL(localPreview.value)
  localPreview.value = URL.createObjectURL(file)
}

onBeforeUnmount(() => {
  if (localPreview.value) URL.revokeObjectURL(localPreview.value)
})

const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const submit = () => {
  // Inertia sends booleans as 1/0 in multipart bodies, which Laravel's boolean rule accepts.
  // File uploads must be POST with a spoofed method, since PHP can't parse multipart PATCH bodies.
  form
    .transform((data) => ({
      ...data,
      starts_at: toUtcString(data.starts_at),
      ends_at: toUtcString(data.ends_at),
      capacity: data.capacity === '' ? null : data.capacity,
      ...(props.event ? { _method: 'patch' } : {}),
    }))
    .post(props.event ? `/admin/events/${props.event.id}` : '/admin/events', {
      forceFormData: true,
      preserveScroll: true,
      onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    })
}
</script>
