<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-600">
          <span class="font-semibold text-dark">{{ activeCount }}</span> of {{ ordered.length }} pillars showing.
          The first {{ homepageCount }} showing pillars appear on the homepage.
          <span v-if="ordered.length > 1" class="hidden sm:inline">Drag or use the arrows to reorder.</span>
        </p>
        <Link
          href="/admin/platform-pillars/create"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New pillar
        </Link>
      </div>

      <ol v-if="ordered.length" class="space-y-3">
        <li
          v-for="(pillar, index) in ordered"
          :key="pillar.id"
          class="bg-white rounded-lg shadow flex flex-col sm:flex-row sm:items-center gap-4 p-4 border-l-4 transition-opacity"
          :class="{ 'opacity-50': draggingId === pillar.id, 'ring-2 ring-primary': dragOverId === pillar.id && draggingId !== pillar.id }"
          :style="{ borderLeftColor: pillar.color || '#003D82' }"
          draggable="true"
          @dragstart="onDragStart(pillar.id, $event)"
          @dragover.prevent="dragOverId = pillar.id"
          @dragleave="dragOverId = null"
          @drop.prevent="onDrop(pillar.id)"
          @dragend="draggingId = dragOverId = null"
        >
          <!-- Reorder -->
          <div class="flex sm:flex-col items-center gap-1 flex-shrink-0">
            <span class="text-sm font-bold text-gray-400 w-6 text-center cursor-grab" title="Drag to reorder">{{ index + 1 }}</span>
            <div class="flex sm:flex-col">
              <button type="button" class="p-1 rounded text-gray-500 hover:text-primary hover:bg-gray-100 disabled:opacity-30" :disabled="index === 0 || saving" :aria-label="`Move ${pillar.title} up`" @click="move(index, -1)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
              </button>
              <button type="button" class="p-1 rounded text-gray-500 hover:text-primary hover:bg-gray-100 disabled:opacity-30" :disabled="index === ordered.length - 1 || saving" :aria-label="`Move ${pillar.title} down`" @click="move(index, 1)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
              </button>
            </div>
          </div>

          <!-- Icon -->
          <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" :style="{ backgroundColor: (pillar.color || '#003D82') + '1A' }">
            <component :is="getPlatformIcon(pillar.icon)" class="w-6 h-6" :style="{ color: pillar.color || '#003D82' }" />
          </div>

          <!-- Text -->
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <Link :href="`/admin/platform-pillars/${pillar.id}/edit`" class="font-semibold text-dark hover:text-primary" draggable="false">{{ pillar.title }}</Link>
              <span v-if="homepageIds.has(pillar.id)" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">On homepage</span>
              <span v-if="!knownIcon(pillar.icon)" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Choose an icon</span>
            </div>
            <p class="text-sm text-gray-600 line-clamp-2">{{ pillar.summary }}</p>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between sm:justify-end gap-3 flex-shrink-0">
            <button
              type="button"
              role="switch"
              :aria-checked="pillar.is_active"
              :disabled="togglingId === pillar.id"
              class="inline-flex items-center gap-2 text-sm"
              @click="toggle(pillar)"
            >
              <span class="relative w-9 h-5 rounded-full transition-colors" :class="pillar.is_active ? 'bg-green-500' : 'bg-gray-300'">
                <span class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="{ 'translate-x-4': pillar.is_active }" />
              </span>
              <span :class="pillar.is_active ? 'text-green-700' : 'text-gray-500'">{{ pillar.is_active ? 'Showing' : 'Hidden' }}</span>
            </button>
            <Link
              :href="`/admin/platform-pillars/${pillar.id}/edit`"
              class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
              draggable="false"
            >
              Edit
            </Link>
            <button
              v-if="canDelete"
              type="button"
              class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg font-medium text-sm transition-colors"
              @click="deletePillar(pillar)"
            >
              Delete
            </button>
          </div>
        </li>
      </ol>

      <div v-else class="bg-white rounded-lg shadow text-center py-12 px-4">
        <p class="text-gray-500">No platform pillars yet.</p>
        <Link href="/admin/platform-pillars/create" class="inline-block mt-3 text-sm text-primary hover:text-primary-dark font-medium">
          Create the first pillar →
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
import { platformIcons, getPlatformIcon } from '@/Utils/platformIcons'

const props = defineProps({
  pillars: { type: Array, default: () => [] },
  homepageCount: { type: Number, default: 3 },
})

const page = usePage()
const confirmDialog = ref(null)
const canDelete = computed(() => page.props.auth?.user?.role === 'admin')

// Local copy so reordering feels instant; resynced when the server sends fresh props
const ordered = ref([])
watch(() => props.pillars, (pillars) => { ordered.value = [...pillars] }, { immediate: true })

const activeCount = computed(() => ordered.value.filter(p => p.is_active).length)
const homepageIds = computed(() => new Set(ordered.value.filter(p => p.is_active).slice(0, props.homepageCount).map(p => p.id)))
const knownIcon = (name) => !!platformIcons[name]

const saving = ref(false)
const togglingId = ref(null)
const draggingId = ref(null)
const dragOverId = ref(null)

const saveOrder = () => {
  saving.value = true
  router.post('/admin/platform-pillars/reorder', { ids: ordered.value.map(p => p.id) }, {
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
  const from = list.findIndex(p => p.id === fromId)
  const to = list.findIndex(p => p.id === targetId)
  const [moved] = list.splice(from, 1)
  list.splice(to, 0, moved)
  ordered.value = list
  saveOrder()
}

const toggle = (pillar) => {
  togglingId.value = pillar.id
  router.patch(`/admin/platform-pillars/${pillar.id}/toggle`, {}, {
    preserveScroll: true,
    onFinish: () => { togglingId.value = null },
  })
}

const deletePillar = async (pillar) => {
  const ok = await confirmDialog.value.open(
    'Delete pillar',
    `Delete "${pillar.title}"? This cannot be undone. To take it off the site but keep it, hide it instead.`,
    { confirmText: 'Delete', isDangerous: true }
  )
  if (ok) router.delete(`/admin/platform-pillars/${pillar.id}`, { preserveScroll: true })
}
</script>
