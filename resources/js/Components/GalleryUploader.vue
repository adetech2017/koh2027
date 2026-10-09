<template>
  <div class="space-y-4">
    <!-- Dropzone -->
    <div
      @drop.prevent="onDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      :class="['border-2 border-dashed rounded-lg text-center transition-colors', compact ? 'p-6' : 'p-10', isDragging ? 'border-primary bg-blue-50' : 'border-gray-300 bg-gray-50']"
    >
      <svg class="mx-auto h-10 w-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3v-6" />
      </svg>
      <p class="text-sm font-medium text-dark">Drag photos here, or</p>
      <label class="inline-block mt-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium text-sm cursor-pointer transition-colors">
        Choose photos
        <input type="file" multiple accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" :disabled="uploading" @change="onSelect" />
      </label>
      <p class="text-xs text-gray-500 mt-2">JPG, PNG, GIF or WebP, up to 2 MB each. You can add many at once.</p>
    </div>

    <!-- Queue -->
    <div v-if="items.length" class="space-y-3">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-dark">
          <span class="font-semibold">{{ validItems.length }}</span> ready to upload
          <span v-if="invalidCount" class="text-red-600"> · {{ invalidCount }} can't be uploaded</span>
        </p>
        <div class="flex gap-2">
          <button
            v-if="invalidCount && !uploading"
            type="button"
            class="text-sm text-gray-600 hover:text-dark"
            @click="removeInvalid"
          >
            Remove problem files
          </button>
          <button v-if="!uploading" type="button" class="text-sm text-gray-600 hover:text-dark" @click="clear">Clear all</button>
        </div>
      </div>

      <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
        <div v-for="item in items" :key="item.key" class="relative">
          <img
            :src="item.url"
            :alt="item.file.name"
            class="w-full aspect-square object-cover rounded-lg"
            :class="{ 'opacity-40': item.error }"
          />
          <div v-if="item.error" class="absolute inset-x-1 bottom-1 bg-red-600 text-white text-[10px] leading-tight rounded px-1.5 py-1">
            {{ item.error }}
          </div>
          <div v-else-if="item.done" class="absolute top-1 left-1 bg-green-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">✓</div>
          <button
            v-if="!uploading"
            type="button"
            class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 hover:bg-black/80 text-white text-sm flex items-center justify-center"
            :aria-label="`Remove ${item.file.name}`"
            @click="remove(item.key)"
          >
            ×
          </button>
        </div>
      </div>

      <div v-if="uploading" class="space-y-1">
        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full bg-primary transition-all" :style="{ width: overallProgress + '%' }" />
        </div>
        <p class="text-xs text-gray-500">
          Uploading{{ batchCount > 1 ? ` batch ${batchIndex + 1} of ${batchCount}` : '' }}… {{ overallProgress }}%
        </p>
      </div>
      <p v-if="uploadError" class="text-sm text-red-600">{{ uploadError }}</p>

      <div class="flex justify-end">
        <button
          type="button"
          :disabled="!canUpload"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          @click="upload"
        >
          {{ uploading ? 'Uploading…' : `Upload ${validItems.length} photo${validItems.length !== 1 ? 's' : ''}` }}
        </button>
      </div>
      <p v-if="!categoryId && validItems.length" class="text-sm text-amber-700 text-right">Choose a category first.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  categoryId: { type: [Number, String], default: null },
  maxUploadBytes: { type: Number, default: 8 * 1024 * 1024 },
  maxUploadFiles: { type: Number, default: 20 },
  compact: { type: Boolean, default: false },
})

const MAX_FILE_BYTES = 2 * 1024 * 1024
const ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']

const items = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const uploadError = ref('')
const batchIndex = ref(0)
const batchCount = ref(0)
const batchProgress = ref(0)
let nextKey = 0

const validItems = computed(() => items.value.filter(i => !i.error && !i.done))
const invalidCount = computed(() => items.value.filter(i => i.error).length)
const canUpload = computed(() => !uploading.value && validItems.value.length > 0 && !!props.categoryId)

const overallProgress = computed(() =>
  batchCount.value ? Math.round(((batchIndex.value + batchProgress.value / 100) / batchCount.value) * 100) : 0
)

const formatSize = (bytes) => `${(bytes / 1024 / 1024).toFixed(1)} MB`

// Each item keeps its own file + preview, so removing one can never drop a different file
const addFiles = (files) => {
  uploadError.value = ''
  for (const file of files) {
    let error = ''
    if (!ALLOWED.includes(file.type)) error = 'Not a JPG, PNG, GIF or WebP'
    else if (file.size > MAX_FILE_BYTES) error = `Too large (${formatSize(file.size)}, max 2 MB)`
    items.value.push({ key: nextKey++, file, url: URL.createObjectURL(file), error, done: false })
  }
}

const onSelect = (e) => {
  addFiles(Array.from(e.target.files || []))
  e.target.value = ''
}

const onDrop = (e) => {
  isDragging.value = false
  if (!uploading.value) addFiles(Array.from(e.dataTransfer.files || []))
}

const remove = (key) => {
  const item = items.value.find(i => i.key === key)
  if (item) URL.revokeObjectURL(item.url)
  items.value = items.value.filter(i => i.key !== key)
}

const removeInvalid = () => items.value.filter(i => i.error).forEach(i => remove(i.key))

const clear = () => {
  items.value.forEach(i => URL.revokeObjectURL(i.url))
  items.value = []
  uploadError.value = ''
}

onBeforeUnmount(clear)

// Keep each request under PHP's post_max_size and max_file_uploads, which otherwise fail silently
const makeBatches = (list) => {
  const budget = props.maxUploadBytes * 0.9
  const batches = []
  let current = []
  let size = 0
  for (const item of list) {
    if (current.length && (size + item.file.size > budget || current.length >= props.maxUploadFiles)) {
      batches.push(current)
      current = []
      size = 0
    }
    current.push(item)
    size += item.file.size
  }
  if (current.length) batches.push(current)
  return batches
}

const postBatch = (batch, isLast) => new Promise((resolve, reject) => {
  router.post('/admin/gallery', {
    category_id: props.categoryId,
    images: batch.map(i => i.file),
    ...(isLast ? {} : { stay: 1 }),
  }, {
    forceFormData: true,
    preserveScroll: true,
    preserveState: !isLast,
    onProgress: (event) => { batchProgress.value = event?.percentage || 0 },
    onSuccess: (page) => {
      const errors = page.props.errors || {}
      if (Object.keys(errors).length) reject(errors)
      else resolve()
    },
    onError: (errors) => reject(errors),
  })
})

const upload = async () => {
  if (!canUpload.value) return
  const batches = makeBatches(validItems.value)
  uploading.value = true
  uploadError.value = ''
  batchCount.value = batches.length

  try {
    for (const [index, batch] of batches.entries()) {
      batchIndex.value = index
      batchProgress.value = 0
      await postBatch(batch, index === batches.length - 1)
      batch.forEach(item => { item.done = true })
    }
  } catch (errors) {
    const first = Object.values(errors || {})[0]
    uploadError.value = `Upload stopped: ${first || 'something went wrong'}. Photos marked ✓ were saved; try the rest again.`
  } finally {
    uploading.value = false
  }
}
</script>
