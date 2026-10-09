<template>
  <form class="grid grid-cols-1 lg:grid-cols-5 gap-6" @submit.prevent="submit">
    <div class="lg:col-span-3 space-y-6">
      <div v-if="hasErrors" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
        <p class="font-semibold">Please fix the highlighted fields.</p>
      </div>

      <section class="bg-white rounded-lg shadow p-6 space-y-5">
        <h3 class="text-lg font-semibold text-dark">Details</h3>
        <div>
          <label for="d-category" class="block text-sm font-medium text-dark mb-1">Category <span class="text-red-600">*</span></label>
          <select v-if="!customCategory" id="d-category" v-model="form.category" :class="inputClass('category')" @change="onCategoryChange">
            <option value="" disabled>Choose a category</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ label(cat) }}</option>
            <option value="__new">+ New category…</option>
          </select>
          <div v-else class="flex gap-2">
            <input id="d-category" v-model="form.category" type="text" maxlength="100" placeholder="New category name" :class="inputClass('category')" />
            <button type="button" class="text-sm text-gray-600 hover:text-dark" @click="customCategory = false; form.category = ''">Cancel</button>
          </div>
          <p v-if="form.errors.category" class="text-red-600 text-sm mt-1">{{ form.errors.category }}</p>
        </div>
        <div>
          <label for="d-title" class="block text-sm font-medium text-dark mb-1">Title <span class="text-red-600">*</span></label>
          <input id="d-title" v-model="form.title" type="text" maxlength="200" placeholder="e.g. The Lagos Promise — Pillar 3: Opportunity" :class="inputClass('title')" />
          <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
          <p v-else-if="form.category === 'manifesto'" class="text-xs mt-1" :class="placement.tone">{{ placement.text }}</p>
        </div>
        <div>
          <div class="flex justify-between items-baseline mb-1">
            <label for="d-description" class="block text-sm font-medium text-dark">Description <span class="text-red-600">*</span></label>
            <span class="text-xs text-gray-400">{{ form.description.length }}/500</span>
          </div>
          <textarea id="d-description" v-model="form.description" rows="3" maxlength="500" placeholder="One sentence on what the document covers" :class="inputClass('description')" />
          <p v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</p>
        </div>
        <label class="flex items-start gap-3 cursor-pointer">
          <input v-model="form.is_active" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
          <span>
            <span class="block text-sm font-medium text-dark">Show on the website</span>
            <span class="block text-xs text-gray-500">Hidden documents can't be downloaded by visitors.</span>
          </span>
        </label>
      </section>
    </div>

    <div class="lg:col-span-2 space-y-6">
      <!-- File -->
      <section class="bg-white rounded-lg shadow p-6 space-y-3">
        <h3 class="text-lg font-semibold text-dark">File <span v-if="!material" class="text-red-600">*</span></h3>
        <div v-if="material && !form.file" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
          <span class="text-xs font-bold px-2 py-1 rounded bg-white border uppercase">{{ material.file_type }}</span>
          <div class="min-w-0 flex-1">
            <p class="text-sm text-dark truncate">{{ material.file_name }}</p>
            <p class="text-xs text-gray-500">{{ formatSize(material.file_size) }} · {{ material.download_count }} downloads</p>
          </div>
          <a :href="`/admin/materials/${material.id}/download`" class="text-sm text-primary hover:text-primary-dark font-medium">Download</a>
        </div>
        <div
          class="border-2 border-dashed rounded-lg p-5 text-center transition-colors"
          :class="isDragging ? 'border-primary bg-blue-50' : (form.errors.file ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50')"
          @drop.prevent="onDrop"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
        >
          <p v-if="form.file" class="text-sm font-medium text-dark break-all mb-2">
            {{ form.file.name }} <span class="text-gray-500 font-normal">({{ formatSize(form.file.size) }})</span>
          </p>
          <label class="inline-block px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium cursor-pointer">
            {{ form.file ? 'Choose a different file' : (material ? 'Replace file' : 'Choose file') }}
            <input type="file" :accept="accept" class="sr-only" @change="onSelect" />
          </label>
          <p class="text-xs text-gray-500 mt-2">or drag it here. PDF, Word, PowerPoint, Excel or ZIP, up to {{ formatSize(maxUploadBytes) }}.</p>
          <p v-if="material && !form.file" class="text-xs text-gray-500 mt-1">Leave empty to keep the current file.</p>
        </div>
        <p v-if="form.errors.file" class="text-red-600 text-sm">{{ form.errors.file }}</p>
        <p v-if="willFeedAssistant" class="text-xs text-blue-700 bg-blue-50 rounded-lg px-3 py-2">
          The Manifesto Assistant reads this PDF. Its text is extracted automatically after you save.
        </p>
      </section>

      <!-- Thumbnail -->
      <section class="bg-white rounded-lg shadow p-6 space-y-3">
        <h3 class="text-lg font-semibold text-dark">Cover image</h3>
        <div class="aspect-[4/3] rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center">
          <img v-if="thumbPreview" :src="thumbPreview" alt="" class="w-full h-full object-cover" />
          <span v-else class="text-xs text-gray-400">No cover image</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <label class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-dark hover:border-primary hover:text-primary cursor-pointer">
            {{ thumbPreview ? 'Replace' : 'Choose image' }}
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" @change="onThumb" />
          </label>
          <button v-if="thumbPreview" type="button" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg" @click="removeThumb">Remove</button>
        </div>
        <p class="text-xs text-gray-500">Optional. JPG, PNG, GIF or WebP, up to 2 MB.</p>
        <p v-if="form.errors.thumbnail" class="text-red-600 text-sm">{{ form.errors.thumbnail }}</p>
      </section>

      <div class="space-y-2">
        <div v-if="form.progress" class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full bg-primary transition-all" :style="{ width: form.progress.percentage + '%' }" />
        </div>
        <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
          <Link href="/admin/materials" class="px-6 py-2 text-center bg-gray-100 hover:bg-gray-200 text-dark rounded-lg font-medium transition-colors">Cancel</Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
          >
            {{ form.processing ? (form.progress ? `Uploading… ${form.progress.percentage}%` : 'Saving…') : (material ? 'Save changes' : 'Upload document') }}
          </button>
        </div>
      </div>
    </div>
  </form>
</template>

<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  material: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
  maxUploadBytes: { type: Number, default: 8 * 1024 * 1024 },
})

const EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip']
const accept = EXTENSIONS.map(e => `.${e}`).join(',')

const m = props.material || {}
const form = useForm({
  title: m.title || '',
  description: m.description || '',
  category: m.category || '',
  file: null,
  thumbnail: null,
  remove_thumbnail: false,
  is_active: props.material ? !!m.is_active : true,
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

const customCategory = ref(false)
const onCategoryChange = () => {
  if (form.category === '__new') {
    form.category = ''
    customCategory.value = true
  }
}
const label = (cat) => (cat === 'faq' ? 'FAQ' : cat.charAt(0).toUpperCase() + cat.slice(1))

// Mirrors how Pages/Materials.vue places manifesto documents
const placement = computed(() => {
  const pillar = form.title.match(/pillar\s*(\d)/i)
  if (pillar) {
    const n = Number(pillar[1])
    return n >= 1 && n <= 7
      ? { text: `Shown on the Manifesto page as the Pillar ${n} document.`, tone: 'text-gray-500' }
      : { text: `There is no Pillar ${n}; the page only has pillars 1–7, so this won't be shown.`, tone: 'text-amber-700' }
  }
  return { text: 'No "Pillar N" in the title, so this is treated as the full manifesto (the main download).', tone: 'text-gray-500' }
})

const fileType = computed(() => (form.file?.name.split('.').pop() || m.file_type || '').toLowerCase())
const willFeedAssistant = computed(() =>
  form.category === 'manifesto' && fileType.value === 'pdf' && !form.title.startsWith('Full Campaign Manifesto')
)

const formatSize = (bytes) => {
  if (!bytes) return '0 KB'
  const kb = bytes / 1024
  return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`
}

// Check against the server's real limit so big files fail here with a clear message,
// instead of PHP silently dropping the whole request
const setFile = (file) => {
  form.clearErrors('file')
  if (!file) return
  const ext = file.name.split('.').pop().toLowerCase()
  if (!EXTENSIONS.includes(ext)) {
    form.setError('file', 'Upload a PDF, Word, PowerPoint, Excel or ZIP file.')
    return
  }
  if (file.size > props.maxUploadBytes) {
    form.setError('file', `This file is ${formatSize(file.size)}; the server accepts up to ${formatSize(props.maxUploadBytes)}. Compress the PDF or ask your host to raise PHP's upload_max_filesize and post_max_size.`)
    return
  }
  form.file = file
}

const isDragging = ref(false)
const onSelect = (e) => { setFile(e.target.files[0]); e.target.value = '' }
const onDrop = (e) => { isDragging.value = false; setFile(e.dataTransfer.files[0]) }

const localThumb = ref(null)
const thumbPreview = computed(() => localThumb.value || (form.remove_thumbnail ? null : m.thumbnail_url) || null)

const onThumb = (e) => {
  const file = e.target.files[0]
  e.target.value = ''
  form.clearErrors('thumbnail')
  if (!file) return
  if (file.size > 2 * 1024 * 1024) {
    form.setError('thumbnail', `Image is ${formatSize(file.size)}; the limit is 2 MB.`)
    return
  }
  form.thumbnail = file
  form.remove_thumbnail = false
  if (localThumb.value) URL.revokeObjectURL(localThumb.value)
  localThumb.value = URL.createObjectURL(file)
}

const removeThumb = () => {
  if (localThumb.value) URL.revokeObjectURL(localThumb.value)
  localThumb.value = null
  form.thumbnail = null
  form.remove_thumbnail = true
}

onBeforeUnmount(() => { if (localThumb.value) URL.revokeObjectURL(localThumb.value) })

const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const submit = () => {
  if (!props.material && !form.file) {
    form.setError('file', 'Choose a file to upload.')
    return
  }
  // File uploads must be POST with a spoofed method, since PHP can't parse multipart PUT bodies
  form
    .transform((data) => ({ ...data, ...(props.material ? { _method: 'put' } : {}) }))
    .post(props.material ? `/admin/materials/${props.material.id}` : '/admin/materials', {
      forceFormData: true,
      preserveScroll: true,
      onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    })
}
</script>
