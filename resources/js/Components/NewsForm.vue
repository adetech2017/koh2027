<template>
  <form class="grid grid-cols-1 lg:grid-cols-3 gap-6" @submit.prevent="submit">
    <!-- Main column -->
    <div class="lg:col-span-2 space-y-6">
      <div v-if="hasErrors" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
        <p class="font-semibold">Please fix the highlighted fields.</p>
      </div>

      <section class="bg-white rounded-lg shadow p-6 space-y-5">
        <div>
          <label for="n-title" class="block text-sm font-medium text-dark mb-1">Title <span class="text-red-600">*</span></label>
          <input id="n-title" v-model="form.title" type="text" maxlength="200" placeholder="Article headline" :class="[inputClass('title'), 'text-lg font-semibold']" />
          <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-dark mb-1">Content <span class="text-red-600">*</span></label>
          <div :class="{ 'ring-1 ring-red-400 rounded-lg': form.errors.body }">
            <Editor v-model="form.body" />
          </div>
          <p v-if="form.errors.body" class="text-red-600 text-sm mt-1">{{ form.errors.body }}</p>
          <p v-else class="text-xs text-gray-500 mt-1">{{ wordCount }} words · about {{ readMinutes }} min read</p>
        </div>

        <div>
          <div class="flex justify-between items-baseline mb-1">
            <label for="n-excerpt" class="block text-sm font-medium text-dark">Summary</label>
            <span class="text-xs" :class="form.excerpt.length > 300 ? 'text-red-600' : 'text-gray-400'">{{ form.excerpt.length }}/300</span>
          </div>
          <textarea id="n-excerpt" v-model="form.excerpt" rows="3" :placeholder="excerptPlaceholder" :class="inputClass('excerpt')" />
          <p v-if="form.errors.excerpt" class="text-red-600 text-sm mt-1">{{ form.errors.excerpt }}</p>
          <p v-else class="text-xs text-gray-500 mt-1">Shown on news cards, search results and link previews. Leave empty to use the start of the article.</p>
        </div>
      </section>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
      <!-- Publishing -->
      <section class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold text-dark">Publishing</h3>
        <fieldset class="space-y-2">
          <legend class="sr-only">Status</legend>
          <label v-for="option in statusOptions" :key="option.value" class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer" :class="form.status === option.value ? 'border-primary bg-blue-50/50' : 'border-gray-200'">
            <input v-model="form.status" type="radio" :value="option.value" class="mt-0.5 text-primary focus:ring-primary" />
            <span>
              <span class="block text-sm font-medium text-dark">{{ option.label }}</span>
              <span class="block text-xs text-gray-500">{{ option.help }}</span>
            </span>
          </label>
        </fieldset>
        <div v-if="form.status === 'scheduled'">
          <label for="n-when" class="block text-sm font-medium text-dark mb-1">Go live on</label>
          <input id="n-when" v-model="form.published_at" type="datetime-local" :min="nowLocal" :class="inputClass('published_at')" />
          <p v-if="form.errors.published_at" class="text-red-600 text-sm mt-1">{{ form.errors.published_at }}</p>
        </div>
        <p v-else-if="article?.published_at && form.status === 'published'" class="text-xs text-gray-500">
          First published {{ formatDateTime(article.published_at) }}
        </p>

        <label class="flex items-start gap-3 cursor-pointer pt-2 border-t">
          <input v-model="form.is_featured" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
          <span>
            <span class="block text-sm font-medium text-dark">Featured</span>
            <span class="block text-xs text-gray-500">Highlight this article on the homepage.</span>
          </span>
        </label>

        <div class="flex flex-col gap-2 pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
          >
            {{ form.processing ? 'Saving…' : submitLabel }}
          </button>
          <a
            v-if="article && article.status === 'published'"
            :href="`/news/${article.slug}`"
            target="_blank"
            rel="noopener"
            class="w-full text-center px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary rounded-lg text-sm font-medium"
          >
            View on site ↗
          </a>
          <p v-if="isDirty" class="text-xs text-amber-700 text-center">You have unsaved changes.</p>
        </div>
      </section>

      <!-- Details -->
      <section class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold text-dark">Details</h3>
        <div>
          <label for="n-category" class="block text-sm font-medium text-dark mb-1">Category <span class="text-red-600">*</span></label>
          <select v-if="!customCategory" id="n-category" v-model="form.category" :class="inputClass('category')" @change="onCategoryChange">
            <option value="" disabled>Choose a category</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ categoryLabel(cat) }}</option>
            <option value="__new">+ New category…</option>
          </select>
          <div v-else class="flex gap-2">
            <input id="n-category" v-model="form.category" type="text" maxlength="100" placeholder="New category name" :class="inputClass('category')" />
            <button type="button" class="text-sm text-gray-600 hover:text-dark" @click="customCategory = false; form.category = ''">Cancel</button>
          </div>
          <p v-if="form.errors.category" class="text-red-600 text-sm mt-1">{{ form.errors.category }}</p>
        </div>
        <div>
          <label for="n-author" class="block text-sm font-medium text-dark mb-1">Author</label>
          <input id="n-author" v-model="form.author_name" type="text" maxlength="100" placeholder="KOH Campaign Team" :class="inputClass('author_name')" />
          <p v-if="form.errors.author_name" class="text-red-600 text-sm mt-1">{{ form.errors.author_name }}</p>
        </div>
      </section>

      <!-- Image -->
      <section class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold text-dark">Cover image</h3>
        <div class="aspect-video rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center">
          <img v-if="previewUrl" :src="previewUrl" alt="" class="w-full h-full object-cover" />
          <span v-else class="text-xs text-gray-400">No image</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <label for="n-image" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-dark hover:border-primary hover:text-primary cursor-pointer">
            {{ previewUrl ? 'Replace' : 'Choose image' }}
          </label>
          <input id="n-image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" @change="onImage" />
          <button v-if="previewUrl" type="button" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg" @click="removeImage">Remove</button>
        </div>
        <p class="text-xs text-gray-500">Optional. Landscape works best. JPG, PNG, GIF or WebP, up to 2 MB.</p>
        <p v-if="form.errors.image" class="text-red-600 text-sm">{{ form.errors.image }}</p>
        <div v-if="previewUrl">
          <label for="n-alt" class="block text-sm font-medium text-dark mb-1">Image description</label>
          <input id="n-alt" v-model="form.image_alt" type="text" maxlength="200" placeholder="What the image shows" :class="inputClass('image_alt')" />
          <p v-if="form.errors.image_alt" class="text-red-600 text-sm mt-1">{{ form.errors.image_alt }}</p>
        </div>
      </section>
    </div>
  </form>
</template>

<script setup>
import { computed, ref, onBeforeUnmount, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import Editor from '@/Components/Editor.vue'

const props = defineProps({
  article: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
})

const statusOptions = [
  { value: 'draft', label: 'Draft', help: 'Only visible in the admin.' },
  { value: 'published', label: 'Published', help: 'Live on the website now.' },
  { value: 'scheduled', label: 'Scheduled', help: 'Goes live automatically at a set time.' },
]

// Stored in UTC; the inputs work in local time, matching how the public site shows dates
const pad = (n) => String(n).padStart(2, '0')
const toLocalInput = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}
const toUtcString = (local) => (local ? new Date(local).toISOString().slice(0, 16).replace('T', ' ') : null)
const nowLocal = toLocalInput(new Date().toISOString())

const a = props.article || {}
const form = useForm({
  title: a.title || '',
  body: a.body || '',
  excerpt: a.excerpt || '',
  category: a.category || '',
  author_name: a.author_name || '',
  image: null,
  remove_image: false,
  image_alt: a.image_alt || '',
  is_featured: !!a.is_featured,
  status: a.status || 'draft',
  published_at: a.status === 'scheduled' ? toLocalInput(a.published_at) : '',
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)
const isDirty = computed(() => form.isDirty && !form.processing)

const customCategory = ref(false)
const onCategoryChange = () => {
  if (form.category === '__new') {
    form.category = ''
    customCategory.value = true
  }
}

const categoryLabel = (cat) => cat.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())

const plainText = computed(() => form.body.replace(/<[^>]+>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim())
const wordCount = computed(() => (plainText.value ? plainText.value.split(' ').length : 0))
const readMinutes = computed(() => Math.max(1, Math.round(wordCount.value / 200)))
const excerptPlaceholder = computed(() =>
  plainText.value ? `${plainText.value.slice(0, 160)}${plainText.value.length > 160 ? '…' : ''}` : 'A one or two sentence summary'
)

const submitLabel = computed(() => {
  if (form.status === 'draft') return props.article ? 'Save draft' : 'Save as draft'
  if (form.status === 'scheduled') return 'Schedule'
  return props.article?.status === 'published' ? 'Update' : 'Publish'
})

// Image
const localPreview = ref(null)
const previewUrl = computed(() => {
  if (localPreview.value) return localPreview.value
  if (form.remove_image) return null
  return a.image_path ? a.image_url : null
})

const onImage = (evt) => {
  const file = evt.target.files[0]
  evt.target.value = ''
  if (!file) return
  form.image = file
  form.remove_image = false
  if (localPreview.value) URL.revokeObjectURL(localPreview.value)
  localPreview.value = URL.createObjectURL(file)
}

const removeImage = () => {
  if (localPreview.value) URL.revokeObjectURL(localPreview.value)
  localPreview.value = null
  form.image = null
  form.remove_image = true
  form.image_alt = ''
}

const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const formatDateTime = (iso) => new Date(iso).toLocaleString(undefined, {
  day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit',
})

const submit = () => {
  // File uploads must be POST with a spoofed method, since PHP can't parse multipart PUT bodies
  form
    .transform((data) => ({
      ...data,
      published_at: data.status === 'scheduled' ? toUtcString(data.published_at) : null,
      ...(props.article ? { _method: 'put' } : {}),
    }))
    .post(props.article ? `/admin/news/${props.article.id}` : '/admin/news', {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        // Saved: the current values become the new baseline for "unsaved changes"
        form.defaults()
        form.image = null
      },
      onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    })
}

// Warn before leaving with unsaved edits
let removeBeforeListener
const beforeUnload = (e) => {
  if (form.isDirty) {
    e.preventDefault()
    e.returnValue = ''
  }
}
onMounted(() => {
  window.addEventListener('beforeunload', beforeUnload)
  removeBeforeListener = router.on('before', (event) => {
    const method = event.detail.visit.method
    if (form.isDirty && !form.processing && method === 'get') {
      return window.confirm('You have unsaved changes. Leave without saving?')
    }
  })
})
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', beforeUnload)
  removeBeforeListener?.()
  if (localPreview.value) URL.revokeObjectURL(localPreview.value)
})
</script>
