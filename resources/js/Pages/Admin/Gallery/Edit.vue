<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <Link :href="backHref" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            {{ image.category?.name || 'Uncategorized' }}
          </Link>
          <h1 class="text-2xl font-bold text-dark mt-2">Photo details</h1>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <span class="text-gray-500">{{ position }} of {{ siblingCount }}</span>
          <Link
            :href="prevId ? `/admin/gallery/${prevId}/edit` : '#'"
            :class="['px-3 py-1.5 border rounded-lg', prevId ? 'border-gray-300 hover:border-primary hover:text-primary' : 'border-gray-200 text-gray-300 pointer-events-none']"
            :aria-disabled="!prevId"
          >← Previous</Link>
          <Link
            :href="nextId ? `/admin/gallery/${nextId}/edit` : '#'"
            :class="['px-3 py-1.5 border rounded-lg', nextId ? 'border-gray-300 hover:border-primary hover:text-primary' : 'border-gray-200 text-gray-300 pointer-events-none']"
            :aria-disabled="!nextId"
          >Next →</Link>
        </div>
      </div>

      <form class="grid grid-cols-1 lg:grid-cols-5 gap-6" @submit.prevent="save(false)">
        <!-- Image -->
        <div class="lg:col-span-3">
          <div class="bg-white rounded-lg shadow p-3">
            <img
              v-if="!imageFailed"
              :src="image.image_url"
              :alt="form.alt_text"
              class="w-full max-h-[70vh] object-contain rounded bg-gray-50"
              @error="imageFailed = true"
            />
            <div v-else class="aspect-video flex items-center justify-center text-gray-400 text-sm bg-gray-50 rounded">Image file missing</div>
          </div>
        </div>

        <!-- Details -->
        <div class="lg:col-span-2 space-y-6">
          <div v-if="Object.keys(form.errors).length" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
            Please fix the highlighted fields.
          </div>

          <section class="bg-white rounded-lg shadow p-6 space-y-5">
            <div>
              <label for="g-alt" class="block text-sm font-medium text-dark mb-1">Description <span class="text-red-600">*</span></label>
              <textarea
                id="g-alt"
                v-model="form.alt_text"
                rows="2"
                maxlength="255"
                placeholder="e.g. Volunteers handing out flyers at Ikeja market"
                :class="inputClass('alt_text')"
              />
              <p v-if="form.errors.alt_text" class="text-red-600 text-sm mt-1">{{ form.errors.alt_text }}</p>
              <p v-else class="text-xs text-gray-500 mt-1">What the photo shows. Read aloud by screen readers and used by search engines.</p>
            </div>
            <div>
              <label for="g-title" class="block text-sm font-medium text-dark mb-1">Caption</label>
              <input id="g-title" v-model="form.title" type="text" maxlength="200" placeholder="Optional caption shown with the photo" :class="inputClass('title')" />
              <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label for="g-event" class="block text-sm font-medium text-dark mb-1">Event</label>
                <input id="g-event" v-model="form.event_label" type="text" maxlength="100" placeholder="e.g. Ikeja Town Hall" :class="inputClass('event_label')" />
                <p v-if="form.errors.event_label" class="text-red-600 text-sm mt-1">{{ form.errors.event_label }}</p>
              </div>
              <div>
                <label for="g-date" class="block text-sm font-medium text-dark mb-1">Date taken</label>
                <input id="g-date" v-model="form.taken_on" type="date" :max="today" :class="inputClass('taken_on')" />
                <p v-if="form.errors.taken_on" class="text-red-600 text-sm mt-1">{{ form.errors.taken_on }}</p>
              </div>
            </div>
            <div>
              <label for="g-category" class="block text-sm font-medium text-dark mb-1">Category</label>
              <select id="g-category" v-model="form.category_id" :class="inputClass('category_id')">
                <option :value="null" disabled>Choose a category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <p v-if="form.errors.category_id" class="text-red-600 text-sm mt-1">{{ form.errors.category_id }}</p>
            </div>
          </section>

          <section class="bg-white rounded-lg shadow p-6 space-y-4">
            <label class="flex items-start gap-3 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
              <span>
                <span class="block text-sm font-medium text-dark">Show in public gallery</span>
                <span class="block text-xs text-gray-500">Hidden photos stay here but visitors can't see them.</span>
              </span>
            </label>
            <label class="flex items-start gap-3 cursor-pointer">
              <input v-model="form.is_featured" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
              <span>
                <span class="block text-sm font-medium text-dark">Feature on homepage</span>
                <span class="block text-xs text-gray-500">The homepage shows up to 8 featured photos.</span>
              </span>
            </label>
          </section>

          <div class="flex flex-wrap gap-3 justify-between">
            <button
              v-if="canDelete"
              type="button"
              class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg font-medium text-sm"
              @click="deleteImage"
            >
              Delete photo
            </button>
            <div class="flex flex-wrap gap-3 ml-auto">
              <button
                v-if="nextId"
                type="button"
                :disabled="form.processing"
                class="px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary rounded-lg font-medium text-sm disabled:opacity-50"
                @click="save(true)"
              >
                Save & next
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
              >
                {{ form.processing ? 'Saving…' : 'Save' }}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
  image: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  prevId: { type: Number, default: null },
  nextId: { type: Number, default: null },
  position: { type: Number, default: 1 },
  siblingCount: { type: Number, default: 1 },
})

const page = usePage()
const confirmDialog = ref(null)
const imageFailed = ref(false)
const canDelete = computed(() => page.props.auth?.user?.role === 'admin')
const today = new Date().toISOString().slice(0, 10)

const backHref = computed(() => `/admin/gallery?category=${props.image.category_id ?? 'uncategorized'}`)

// Filenames used as alt text by older uploads are cleared so the admin writes a real description
const form = useForm({
  alt_text: props.image.needs_description ? '' : (props.image.alt_text || ''),
  title: props.image.title && !/\.(jpe?g|png|gif|webp)$/i.test(props.image.title) ? props.image.title : '',
  event_label: props.image.event_label || '',
  taken_on: props.image.taken_on || '',
  category_id: props.image.category_id,
  is_active: !!props.image.is_active,
  is_featured: !!props.image.is_featured,
})

const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const save = (goNext) => {
  form
    .transform((data) => ({ ...data, ...(goNext ? { stay: 1 } : {}) }))
    .put(`/admin/gallery/${props.image.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        if (goNext && props.nextId) router.visit(`/admin/gallery/${props.nextId}/edit`)
      },
    })
}

const deleteImage = async () => {
  const ok = await confirmDialog.value.open('Delete photo', 'Permanently delete this photo? To keep it but stop showing it, untick "Show in public gallery" instead.', {
    confirmText: 'Delete',
    isDangerous: true,
  })
  if (ok) router.delete(`/admin/gallery/${props.image.id}`)
}
</script>
