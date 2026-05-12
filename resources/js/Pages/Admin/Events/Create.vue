<template>
  <AdminLayout>
    <div class="max-w-2xl">
      <!-- Header -->
      <div class="mb-6">
        <Link href="/admin/events" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to Events
        </Link>
        <h2 class="text-2xl font-bold text-dark">Create Event</h2>
      </div>

      <!-- Form -->
      <div class="bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Title</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Event title"
            />
            <p v-if="errors.title" class="text-red-600 text-sm mt-1">{{ errors.title }}</p>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Description</label>
            <textarea
              v-model="form.description"
              rows="4"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Event description"
            />
            <p v-if="errors.description" class="text-red-600 text-sm mt-1">{{ errors.description }}</p>
          </div>

          <!-- Two Column Grid -->
          <div class="grid grid-cols-2 gap-6">
            <!-- Venue Name -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Venue Name</label>
              <input
                v-model="form.venue_name"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Venue name"
              />
              <p v-if="errors.venue_name" class="text-red-600 text-sm mt-1">{{ errors.venue_name }}</p>
            </div>

            <!-- Address -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Address</label>
              <input
                v-model="form.address"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Event address"
              />
              <p v-if="errors.address" class="text-red-600 text-sm mt-1">{{ errors.address }}</p>
            </div>

            <!-- LGA -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">LGA</label>
              <input
                v-model="form.lga"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Local Government Area"
              />
              <p v-if="errors.lga" class="text-red-600 text-sm mt-1">{{ errors.lga }}</p>
            </div>

            <!-- Event Type -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Event Type</label>
              <select
                v-model="form.event_type"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">Select Type</option>
                <option value="rally">Rally</option>
                <option value="townhall">Town Hall</option>
                <option value="fundraiser">Fundraiser</option>
                <option value="workshop">Workshop</option>
                <option value="meeting">Meeting</option>
                <option value="other">Other</option>
              </select>
              <p v-if="errors.event_type" class="text-red-600 text-sm mt-1">{{ errors.event_type }}</p>
            </div>

            <!-- Capacity -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Capacity</label>
              <input
                v-model.number="form.capacity"
                type="number"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="0"
                min="1"
              />
              <p v-if="errors.capacity" class="text-red-600 text-sm mt-1">{{ errors.capacity }}</p>
            </div>

            <!-- Start Date -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Start Date & Time</label>
              <input
                v-model="form.starts_at"
                type="datetime-local"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              />
              <p v-if="errors.starts_at" class="text-red-600 text-sm mt-1">{{ errors.starts_at }}</p>
            </div>

            <!-- End Date -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">End Date & Time</label>
              <input
                v-model="form.ends_at"
                type="datetime-local"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              />
              <p v-if="errors.ends_at" class="text-red-600 text-sm mt-1">{{ errors.ends_at }}</p>
            </div>
          </div>

          <!-- Image Upload -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Event Image</label>
            <div class="flex gap-4">
              <div class="flex-1">
                <input
                  ref="imageInput"
                  type="file"
                  accept="image/*"
                  @change="handleImageUpload"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                />
                <p v-if="errors.image" class="text-red-600 text-sm mt-1">{{ errors.image }}</p>
              </div>
              <img
                v-if="imagePreview"
                :src="imagePreview"
                alt="Preview"
                class="w-24 h-24 object-cover rounded-lg"
              />
            </div>
          </div>

          <!-- Map Embed URL -->
          <div>
            <label class="block text-sm font-medium text-dark mb-2">Map Embed URL</label>
            <input
              v-model="form.map_embed_url"
              type="url"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="https://maps.google.com/..."
            />
            <p v-if="errors.map_embed_url" class="text-red-600 text-sm mt-1">{{ errors.map_embed_url }}</p>
          </div>

          <!-- Checkboxes -->
          <div class="space-y-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.rsvp_enabled" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-dark font-medium">Enable RSVP</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-dark font-medium">Active</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_featured" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-dark font-medium">Featured</span>
            </label>
          </div>

          <!-- Buttons -->
          <div class="flex gap-4 pt-6">
            <button
              type="submit"
              class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
            >
              Create Event
            </button>
            <Link
              href="/admin/events"
              class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium transition-colors"
            >
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()
const errors = ref({})
const imageInput = ref(null)
const imagePreview = ref(null)

const form = reactive({
  title: '',
  description: '',
  venue_name: '',
  address: '',
  lga: '',
  event_type: '',
  starts_at: '',
  ends_at: '',
  capacity: '',
  rsvp_enabled: true,
  image: null,
  map_embed_url: '',
  is_active: true,
  is_featured: false,
})

const handleImageUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (event) => {
      imagePreview.value = event.target.result
    }
    reader.readAsDataURL(file)
  }
}

const submitForm = () => {
  errors.value = {}

  const formData = new FormData()
  Object.keys(form).forEach(key => {
    if (form[key] !== null && form[key] !== undefined) {
      formData.append(key, form[key])
    }
  })

  router.post('/admin/events', formData, {
    onError: (err) => {
      errors.value = err
    },
  })
}
</script>
