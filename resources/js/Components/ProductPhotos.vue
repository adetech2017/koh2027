<template>
  <section class="bg-white rounded-lg shadow p-6 space-y-4">
    <div class="flex items-baseline justify-between gap-2">
      <h3 class="text-lg font-semibold text-dark">Photos</h3>
      <span class="text-xs text-gray-500">{{ ordered.length }} photo{{ ordered.length !== 1 ? 's' : '' }}</span>
    </div>

    <p v-if="!ordered.length" class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
      No photos yet. The website shows a grey placeholder until you add one.
    </p>

    <!-- Photos -->
    <ul v-if="ordered.length" class="space-y-3">
      <li v-for="(photo, index) in ordered" :key="photo.id" class="flex gap-3 items-start">
        <div class="relative w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
          <img :src="photo.image_url" :alt="photo.image_alt" class="w-full h-full object-cover" />
          <span v-if="photo.is_primary" class="absolute bottom-0 inset-x-0 bg-primary text-white text-[10px] font-semibold text-center py-0.5">Thumbnail</span>
        </div>
        <div class="flex-1 min-w-0 space-y-1.5">
          <form class="flex gap-1" @submit.prevent="saveAlt(photo)">
            <label :for="`alt-${photo.id}`" class="sr-only">Photo description</label>
            <input
              :id="`alt-${photo.id}`"
              v-model="altDrafts[photo.id]"
              type="text"
              maxlength="200"
              placeholder="Describe the photo"
              class="flex-1 min-w-0 px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary"
            />
            <button
              v-if="altDrafts[photo.id] !== photo.image_alt"
              type="submit"
              :disabled="!altDrafts[photo.id]?.trim()"
              class="px-2 py-1 bg-primary text-white rounded text-xs font-medium disabled:opacity-50"
            >Save</button>
          </form>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
            <button v-if="!photo.is_primary" type="button" class="text-primary hover:text-primary-dark font-medium" @click="setPrimary(photo)">Use as thumbnail</button>
            <button type="button" class="text-gray-500 hover:text-dark disabled:opacity-30" :disabled="index === 0" :aria-label="'Move photo up'" @click="move(index, -1)">↑</button>
            <button type="button" class="text-gray-500 hover:text-dark disabled:opacity-30" :disabled="index === ordered.length - 1" :aria-label="'Move photo down'" @click="move(index, 1)">↓</button>
            <button type="button" class="text-red-600 hover:text-red-800 ml-auto" @click="remove(photo)">Remove</button>
          </div>
        </div>
      </li>
    </ul>

    <!-- Upload -->
    <div
      class="border-2 border-dashed rounded-lg p-4 text-center transition-colors"
      :class="isDragging ? 'border-primary bg-blue-50' : 'border-gray-300 bg-gray-50'"
      @drop.prevent="onDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
    >
      <label class="inline-block px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium cursor-pointer" :class="{ 'opacity-50 pointer-events-none': uploading }">
        {{ uploading ? `Uploading… ${progress}%` : 'Add photos' }}
        <input type="file" multiple accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" :disabled="uploading" @change="onSelect" />
      </label>
      <p class="text-xs text-gray-500 mt-2">or drag them here. JPG, PNG, GIF or WebP, up to 2 MB each, 10 at a time.</p>
      <p v-for="msg in uploadErrors" :key="msg" class="text-xs text-red-600 mt-1">{{ msg }}</p>
    </div>
    <ConfirmDialog ref="confirmDialog" />
  </section>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
  productId: { type: Number, required: true },
  photos: { type: Array, default: () => [] },
})

const MAX_BYTES = 2 * 1024 * 1024
const ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']

const confirmDialog = ref(null)
const ordered = ref([])
const altDrafts = reactive({})
watch(() => props.photos, (photos) => {
  ordered.value = [...photos]
  for (const photo of photos) altDrafts[photo.id] = photo.image_alt
}, { immediate: true })

const isDragging = ref(false)
const uploading = ref(false)
const progress = ref(0)
const uploadErrors = ref([])

const upload = (files) => {
  uploadErrors.value = []
  const valid = []
  for (const file of files) {
    if (!ALLOWED.includes(file.type)) uploadErrors.value.push(`${file.name}: not a JPG, PNG, GIF or WebP.`)
    else if (file.size > MAX_BYTES) uploadErrors.value.push(`${file.name}: ${(file.size / 1048576).toFixed(1)} MB, over the 2 MB limit.`)
    else valid.push(file)
  }
  if (valid.length > 10) {
    uploadErrors.value.push(`Only the first 10 of ${valid.length} photos were uploaded. Add the rest in another batch.`)
    valid.length = 10
  }
  if (!valid.length) return

  uploading.value = true
  router.post(`/admin/merchandise/${props.productId}/images`, { images: valid }, {
    forceFormData: true,
    preserveScroll: true,
    onProgress: (e) => { progress.value = e?.percentage || 0 },
    onError: (errors) => { uploadErrors.value.push(...Object.values(errors)) },
    onFinish: () => { uploading.value = false; progress.value = 0 },
  })
}

const onSelect = (e) => {
  upload(Array.from(e.target.files || []))
  e.target.value = ''
}

const onDrop = (e) => {
  isDragging.value = false
  if (!uploading.value) upload(Array.from(e.dataTransfer.files || []))
}

const saveAlt = (photo) => {
  router.patch(`/admin/merchandise/images/${photo.id}`, { image_alt: altDrafts[photo.id].trim() }, { preserveScroll: true })
}

const setPrimary = (photo) => {
  router.post(`/admin/merchandise/images/${photo.id}/set-primary`, {}, { preserveScroll: true })
}

const move = (index, delta) => {
  const list = [...ordered.value]
  const target = index + delta
  ;[list[index], list[target]] = [list[target], list[index]]
  ordered.value = list
  router.post(`/admin/merchandise/${props.productId}/images/reorder`, { ids: list.map(p => p.id) }, { preserveScroll: true })
}

const remove = async (photo) => {
  const note = photo.is_primary && ordered.value.length > 1 ? ' The next photo will become the thumbnail.' : ''
  const ok = await confirmDialog.value.open('Remove photo', `Remove this photo?${note}`, { confirmText: 'Remove', isDangerous: true })
  if (ok) router.delete(`/admin/merchandise/images/${photo.id}`, { preserveScroll: true })
}
</script>
