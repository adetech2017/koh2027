<template>
  <AdminLayout>
    <div class="w-full">
      <div class="mb-6">
        <Link href="/admin/hero-slides" class="text-primary hover:text-primary-dark mb-4 inline-block">
          ← Back to Hero Slides
        </Link>
        <h2 class="text-2xl font-bold text-dark">Edit Hero Slide</h2>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submitForm" class="grid grid-cols-12 gap-8">
          <!-- Left Column: Content (6 cols) -->
          <div class="col-span-12 md:col-span-6 space-y-6">
            <h3 class="text-lg font-semibold text-dark">Slide Content</h3>

            <!-- Tagline -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Tagline</label>
              <input
                v-model="form.tagline"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Short promotional text (max 100 chars)"
              />
              <p v-if="form.errors.tagline" class="text-red-600 text-sm mt-1">{{ form.errors.tagline }}</p>
            </div>

            <!-- Headline -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Headline</label>
              <input
                v-model="form.headline"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Main headline (max 200 chars)"
              />
              <p v-if="form.errors.headline" class="text-red-600 text-sm mt-1">{{ form.errors.headline }}</p>
            </div>

            <!-- Subtitle -->
            <div>
              <label class="block text-sm font-medium text-dark mb-2">Subtitle</label>
              <textarea
                v-model="form.subtitle"
                rows="4"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Supporting text (max 300 chars)"
              />
              <p v-if="form.errors.subtitle" class="text-red-600 text-sm mt-1">{{ form.errors.subtitle }}</p>
            </div>

            <!-- CTA Section -->
            <div class="border-t pt-6 mt-6">
              <h3 class="text-lg font-semibold text-dark mb-4">Call to Action</h3>
              <div class="space-y-4">
                <!-- CTA Text -->
                <div>
                  <label class="block text-sm font-medium text-dark mb-2">CTA Button Text</label>
                  <input
                    v-model="form.cta_text"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="Button label"
                  />
                  <p v-if="form.errors.cta_text" class="text-red-600 text-sm mt-1">{{ form.errors.cta_text }}</p>
                </div>

                <!-- CTA URL -->
                <div>
                  <label class="block text-sm font-medium text-dark mb-2">CTA URL (Optional)</label>
                  <input
                    v-model="form.cta_url"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="https://... or /path"
                  />
                  <p v-if="form.errors.cta_url" class="text-red-600 text-sm mt-1">{{ form.errors.cta_url }}</p>
                </div>

                <!-- CTA Style -->
                <div>
                  <label class="block text-sm font-medium text-dark mb-2">CTA Style</label>
                  <select
                    v-model="form.cta_style"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  >
                    <option value="">Select Style</option>
                    <option value="primary">Primary (Blue)</option>
                    <option value="secondary">Secondary (Gray)</option>
                  </select>
                  <p v-if="form.errors.cta_style" class="text-red-600 text-sm mt-1">{{ form.errors.cta_style }}</p>
                </div>

                <!-- Sort Order -->
                <div>
                  <label class="block text-sm font-medium text-dark mb-2">Sort Order</label>
                  <input
                    v-model.number="form.sort_order"
                    type="number"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="0"
                  />
                  <p v-if="form.errors.sort_order" class="text-red-600 text-sm mt-1">{{ form.errors.sort_order }}</p>
                </div>
              </div>
            </div>

            <!-- Active -->
            <label class="flex items-center gap-2 cursor-pointer mt-6">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-dark font-medium">Active</span>
            </label>
          </div>

          <!-- Right Column: Image (6 cols) -->
          <div class="col-span-12 md:col-span-6">
            <!-- Image Upload Section -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center h-full flex flex-col justify-center sticky top-6">
              <div v-if="!imagePreview" class="py-8">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <label class="mt-6 block">
                  <span class="block text-sm font-semibold text-dark mb-1">Hero Slide Image</span>
                  <span class="text-xs text-gray-500 block mb-4">JPEG, PNG, GIF, WebP</span>
                  <span class="text-xs text-gray-400 block mb-4">Max 2MB</span>
                  <input
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    @change="handleImageUpload"
                    class="hidden"
                  />
                  <span class="inline-block px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium cursor-pointer transition-colors text-sm">
                    Choose Image
                  </span>
                </label>
                <p v-if="form.errors.image_path" class="text-red-600 text-sm mt-4">{{ form.errors.image_path }}</p>
              </div>

              <div v-else class="py-4">
                <img
                  :src="imagePreview"
                  alt="Preview"
                  class="w-full h-80 object-cover rounded-lg shadow-lg mb-4"
                />
                <label class="inline-block">
                  <input
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    @change="handleImageUpload"
                    class="hidden"
                  />
                  <span class="inline-block px-4 py-2 bg-gray-200 hover:bg-gray-300 text-dark rounded-lg font-medium cursor-pointer transition-colors text-sm">
                    Change Image
                  </span>
                </label>
                <p v-if="form.errors.image_path" class="text-red-600 text-sm mt-3">{{ form.errors.image_path }}</p>
              </div>
            </div>

            <!-- Image Alt Text -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-dark mb-2">Image Alt Text</label>
              <input
                v-model="form.image_alt"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                placeholder="Descriptive alt text for accessibility"
              />
              <p v-if="form.errors.image_alt" class="text-red-600 text-sm mt-1">{{ form.errors.image_alt }}</p>
            </div>
          </div>

          <!-- Buttons (Full Width) -->
          <div class="col-span-12 flex gap-4 pt-6 border-t">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <span v-if="form.processing" class="inline-block animate-spin">⟳</span>
              {{ form.processing ? 'Updating...' : 'Update Slide' }}
            </button>
            <Link
              href="/admin/hero-slides"
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
import { ref, computed, watch } from 'vue'
import { usePage, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()
const imageInput = ref(null)
const imagePreview = ref(null)

const slide = computed(() => page.props.slide || {})

const form = useForm({
  tagline: '',
  headline: '',
  subtitle: '',
  cta_text: '',
  cta_url: '',
  cta_style: 'primary',
  image_path: null,
  image_alt: '',
  is_active: false,
  sort_order: 0,
})

watch(slide, (newSlide) => {
  if (newSlide && newSlide.id) {
    form.tagline = newSlide.tagline || ''
    form.headline = newSlide.headline || ''
    form.subtitle = newSlide.subtitle || ''
    form.cta_text = newSlide.cta_text || ''
    form.cta_url = newSlide.cta_url || ''
    form.cta_style = newSlide.cta_style || 'primary'
    form.image_alt = newSlide.image_alt || ''
    form.is_active = newSlide.is_active || false
    form.sort_order = newSlide.sort_order || 0

    // Load existing image preview
    if (newSlide.image_url) {
      imagePreview.value = newSlide.image_url
    }
  }
}, { immediate: true, deep: true })

const handleImageUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.image_path = file
    const reader = new FileReader()
    reader.onload = (event) => {
      imagePreview.value = event.target.result
    }
    reader.readAsDataURL(file)
  }
}

const submitForm = () => {
  form.put(`/admin/hero-slides/${slide.value.id}`, {
    onSuccess: () => {
      // Success - page redirects automatically
    },
  })
}
</script>
