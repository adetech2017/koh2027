<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-600">
          <span class="font-semibold text-dark">{{ activeCount }}</span> of {{ ordered.length }} slides showing on the homepage.
          <span v-if="ordered.length > 1" class="hidden sm:inline">Drag or use the arrows to change the order.</span>
        </p>
        <Link
          href="/admin/hero-slides/create"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New slide
        </Link>
      </div>

      <div v-if="activeCount === 0 && ordered.length" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
        No slides are showing, so the homepage hero is empty. Turn at least one slide on.
      </div>

      <!-- Slides -->
      <ol v-if="ordered.length" class="space-y-3">
        <li
          v-for="(slide, index) in ordered"
          :key="slide.id"
          class="bg-white rounded-lg shadow flex flex-col sm:flex-row gap-4 p-3 sm:p-4 transition-opacity"
          :class="{ 'opacity-50': draggingId === slide.id, 'ring-2 ring-primary': dragOverId === slide.id && draggingId !== slide.id }"
          draggable="true"
          @dragstart="onDragStart(slide.id, $event)"
          @dragover.prevent="dragOverId = slide.id"
          @dragleave="dragOverId = null"
          @drop.prevent="onDrop(slide.id)"
          @dragend="draggingId = dragOverId = null"
        >
          <!-- Position & reorder -->
          <div class="flex sm:flex-col items-center justify-between sm:justify-center gap-1 sm:w-10 flex-shrink-0">
            <span class="text-sm font-bold text-gray-400 cursor-grab" title="Drag to reorder">
              <svg class="w-4 h-4 hidden sm:block mx-auto mb-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zm0 6a1 1 0 11-2 0 1 1 0 012 0zm0 6a1 1 0 11-2 0 1 1 0 012 0zm8-12a1 1 0 11-2 0 1 1 0 012 0zm0 6a1 1 0 11-2 0 1 1 0 012 0zm0 6a1 1 0 11-2 0 1 1 0 012 0z" />
              </svg>
              {{ index + 1 }}
            </span>
            <div class="flex sm:flex-col gap-1">
              <button
                type="button"
                class="p-1 rounded text-gray-500 hover:text-primary hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent"
                :disabled="index === 0 || saving"
                :aria-label="`Move slide ${index + 1} up`"
                @click="move(index, -1)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
              </button>
              <button
                type="button"
                class="p-1 rounded text-gray-500 hover:text-primary hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent"
                :disabled="index === ordered.length - 1 || saving"
                :aria-label="`Move slide ${index + 1} down`"
                @click="move(index, 1)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
              </button>
            </div>
          </div>

          <!-- Thumbnail -->
          <Link :href="`/admin/hero-slides/${slide.id}/edit`" class="relative w-full sm:w-56 aspect-video rounded overflow-hidden bg-gray-200 flex-shrink-0" draggable="false">
            <img v-if="slide.image_path" :src="slide.image_url" :alt="slide.image_alt || ''" class="w-full h-full object-cover" draggable="false" />
            <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent" />
            <p class="absolute left-2 bottom-2 right-8 text-white text-xs font-semibold leading-tight line-clamp-2">{{ slide.headline }}</p>
          </Link>

          <!-- Text -->
          <div class="flex-1 min-w-0">
            <p v-if="slide.tagline" class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ slide.tagline }}</p>
            <Link :href="`/admin/hero-slides/${slide.id}/edit`" class="block font-semibold text-dark hover:text-primary break-words" draggable="false">
              {{ slide.headline }}
            </Link>
            <p class="text-sm text-gray-600 line-clamp-2 mt-0.5">{{ slide.subtitle }}</p>
            <p class="text-xs text-gray-500 mt-2">
              <template v-if="slide.cta_text && slide.cta_url">
                Button: <span class="font-medium text-dark">{{ slide.cta_text }}</span> → {{ slide.cta_url }}
              </template>
              <template v-else>No button</template>
            </p>
          </div>

          <!-- Actions -->
          <div class="flex sm:flex-col items-center sm:items-end justify-between gap-3 flex-shrink-0">
            <button
              type="button"
              role="switch"
              :aria-checked="slide.is_active"
              :disabled="togglingId === slide.id"
              class="inline-flex items-center gap-2 text-sm"
              @click="toggle(slide)"
            >
              <span class="relative w-9 h-5 rounded-full transition-colors" :class="slide.is_active ? 'bg-green-500' : 'bg-gray-300'">
                <span class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="{ 'translate-x-4': slide.is_active }" />
              </span>
              <span :class="slide.is_active ? 'text-green-700' : 'text-gray-500'">{{ slide.is_active ? 'Showing' : 'Hidden' }}</span>
            </button>
            <div class="flex gap-2">
              <Link
                :href="`/admin/hero-slides/${slide.id}/edit`"
                class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
                draggable="false"
              >
                Edit
              </Link>
              <button
                v-if="canDelete"
                type="button"
                class="px-3 py-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg font-medium text-sm transition-colors"
                @click="deleteSlide(slide)"
              >
                Delete
              </button>
            </div>
          </div>
        </li>
      </ol>

      <div v-else class="bg-white rounded-lg shadow text-center py-12 px-4">
        <p class="text-gray-500">No hero slides yet. The homepage hero will be empty until you add one.</p>
        <Link href="/admin/hero-slides/create" class="inline-block mt-3 text-sm text-primary hover:text-primary-dark font-medium">
          Create the first slide →
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()
const confirmDialog = ref(null)

const canDelete = computed(() => page.props.auth?.user?.role === 'admin')

// Local copy so reordering feels instant; resynced whenever the server sends fresh props
const ordered = ref([])
watch(() => page.props.slides, (slides) => { ordered.value = [...(slides || [])] }, { immediate: true })

const activeCount = computed(() => ordered.value.filter(s => s.is_active).length)

const saving = ref(false)
const togglingId = ref(null)
const draggingId = ref(null)
const dragOverId = ref(null)

const saveOrder = () => {
  saving.value = true
  router.post('/admin/hero-slides/reorder', { ids: ordered.value.map(s => s.id) }, {
    preserveScroll: true,
    onFinish: () => { saving.value = false },
  })
}

const move = (index, delta) => {
  const target = index + delta
  if (target < 0 || target >= ordered.value.length) return
  const list = [...ordered.value]
  ;[list[index], list[target]] = [list[target], list[index]]
  ordered.value = list
  saveOrder()
}

const onDragStart = (id, event) => {
  draggingId.value = id
  event.dataTransfer.effectAllowed = 'move'
}

const onDrop = (targetId) => {
  const fromId = draggingId.value
  draggingId.value = dragOverId.value = null
  if (!fromId || fromId === targetId) return

  const list = [...ordered.value]
  const from = list.findIndex(s => s.id === fromId)
  const to = list.findIndex(s => s.id === targetId)
  const [moved] = list.splice(from, 1)
  list.splice(to, 0, moved)
  ordered.value = list
  saveOrder()
}

const toggle = async (slide) => {
  if (slide.is_active && activeCount.value === 1) {
    const ok = await confirmDialog.value.open(
      'Hide last slide',
      'This is the only slide showing. Hiding it leaves the homepage hero empty.',
      { confirmText: 'Hide anyway', isDangerous: true }
    )
    if (!ok) return
  }
  togglingId.value = slide.id
  router.patch(`/admin/hero-slides/${slide.id}/toggle`, {}, {
    preserveScroll: true,
    onFinish: () => { togglingId.value = null },
  })
}

const deleteSlide = async (slide) => {
  const confirmed = await confirmDialog.value.open(
    'Delete slide',
    `Delete "${slide.headline}"? This cannot be undone. To take it off the homepage but keep it, hide it instead.`,
    { confirmText: 'Delete', isDangerous: true }
  )
  if (confirmed) {
    router.delete(`/admin/hero-slides/${slide.id}`, { preserveScroll: true })
  }
}
</script>
