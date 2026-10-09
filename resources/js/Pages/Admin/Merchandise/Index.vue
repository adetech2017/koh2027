<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <div v-if="counts.no_photo" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
        {{ counts.no_photo }} design{{ counts.no_photo !== 1 ? 's have' : ' has' }} no photo, so the website shows a grey placeholder. Open a design to add photos.
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              type="button"
              class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
              :class="(filters.filter || 'all') === tab.key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              @click="visit({ filter: tab.key === 'all' ? undefined : tab.key })"
            >
              {{ tab.label }}
              <span class="ml-1 font-semibold">{{ counts[tab.key] || 0 }}</span>
            </button>
          </div>
          <Link
            href="/admin/merchandise/create"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New design
          </Link>
        </div>
        <div class="relative">
          <label for="merch-search" class="sr-only">Search</label>
          <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            id="merch-search"
            v-model="searchQuery"
            type="search"
            placeholder="Search name or category..."
            class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>
        <p v-if="canReorder && ordered.length > 1" class="text-xs text-gray-500 hidden sm:block">Drag rows to change the order designs appear on the website.</p>
      </div>

      <!-- Designs -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b text-xs font-semibold text-gray-600 uppercase">
          <div class="col-span-8">Design</div>
          <div class="col-span-4 text-right">Actions</div>
        </div>
        <div class="divide-y">
          <div
            v-for="item in ordered"
            :key="item.id"
            class="md:grid md:grid-cols-12 md:gap-4 md:items-center px-4 sm:px-6 py-4 transition-opacity"
            :class="{ 'opacity-50': draggingId === item.id, 'ring-2 ring-inset ring-primary': dragOverId === item.id && draggingId !== item.id }"
            :draggable="canReorder"
            @dragstart="onDragStart(item.id, $event)"
            @dragover.prevent="canReorder && (dragOverId = item.id)"
            @dragleave="dragOverId = null"
            @drop.prevent="onDrop(item.id)"
            @dragend="draggingId = dragOverId = null"
          >
            <div class="md:col-span-8 flex items-center gap-3 min-w-0">
              <Link :href="`/admin/merchandise/${item.id}/edit`" class="w-14 h-14 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0 flex items-center justify-center" draggable="false">
                <img v-if="item.image_url" :src="item.image_url" alt="" class="w-full h-full object-cover" draggable="false" />
                <span v-else class="text-[10px] text-amber-700 text-center leading-tight px-1">No photo</span>
              </Link>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5">
                  <Link :href="`/admin/merchandise/${item.id}/edit`" class="font-semibold text-dark hover:text-primary truncate" draggable="false">{{ item.name }}</Link>
                  <span v-if="item.is_featured" class="text-[11px] font-medium px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">★ Featured</span>
                </div>
                <p class="text-xs text-gray-500 capitalize">
                  {{ item.category }}<template v-if="item.images_count"> · {{ item.images_count }} photo{{ item.images_count !== 1 ? 's' : '' }}</template>
                </p>
              </div>
            </div>
            <div class="md:col-span-4 mt-3 md:mt-0 flex items-center md:justify-end gap-3">
              <button
                type="button"
                role="switch"
                :aria-checked="item.is_active"
                :disabled="togglingId === item.id"
                class="inline-flex items-center gap-2 text-sm"
                :title="item.is_active ? 'Showing on the website' : 'Hidden from the website'"
                @click="toggle(item)"
              >
                <span class="relative w-9 h-5 rounded-full transition-colors" :class="item.is_active ? 'bg-green-500' : 'bg-gray-300'">
                  <span class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="{ 'translate-x-4': item.is_active }" />
                </span>
                <span class="md:hidden lg:inline" :class="item.is_active ? 'text-green-700' : 'text-gray-500'">{{ item.is_active ? 'Showing' : 'Hidden' }}</span>
              </button>
              <Link
                :href="`/admin/merchandise/${item.id}/edit`"
                class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
                draggable="false"
              >
                Edit
              </Link>
              <button
                v-if="canDelete"
                type="button"
                class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg font-medium text-sm transition-colors"
                @click="deleteItem(item)"
              >
                Delete
              </button>
            </div>
          </div>
        </div>

        <div v-if="!ordered.length" class="text-center py-12 px-4">
          <p class="text-gray-500">{{ isFiltered ? 'No designs match these filters' : 'No designs yet' }}</p>
          <button v-if="isFiltered" type="button" class="mt-3 text-sm text-primary hover:text-primary-dark font-medium" @click="clearFilters">Clear filters</button>
          <Link v-else href="/admin/merchandise/create" class="inline-block mt-3 text-sm text-primary hover:text-primary-dark font-medium">Add the first design →</Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
  products: { type: Array, default: () => [] },
  counts: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const confirmDialog = ref(null)
const canDelete = computed(() => page.props.auth?.user?.role === 'admin')

const tabs = [
  { key: 'all', label: 'All' },
  { key: 'live', label: 'Showing' },
  { key: 'hidden', label: 'Hidden' },
]

const ordered = ref([])
watch(() => props.products, (products) => { ordered.value = [...products] }, { immediate: true })

const searchQuery = ref(props.filters.search || '')
const isFiltered = computed(() => !!(props.filters.filter || props.filters.search))
// Reordering a filtered subset would scramble the hidden rows' positions
const canReorder = computed(() => !isFiltered.value)

const visit = (params) => {
  const query = {
    filter: props.filters.filter || undefined,
    search: searchQuery.value.trim() || undefined,
    ...params,
  }
  router.get('/admin/merchandise', query, { preserveState: true, preserveScroll: true, replace: true })
}

let searchTimer
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => visit({}), 300)
})

const clearFilters = () => {
  searchQuery.value = ''
  clearTimeout(searchTimer)
  router.get('/admin/merchandise', {}, { preserveState: true, replace: true })
}

// Reorder
const draggingId = ref(null)
const dragOverId = ref(null)

const onDragStart = (id, event) => {
  if (!canReorder.value) return
  draggingId.value = id
  event.dataTransfer.effectAllowed = 'move'
}

const onDrop = (targetId) => {
  const fromId = draggingId.value
  draggingId.value = dragOverId.value = null
  if (!canReorder.value || !fromId || fromId === targetId) return
  const list = [...ordered.value]
  const from = list.findIndex(p => p.id === fromId)
  const to = list.findIndex(p => p.id === targetId)
  const [moved] = list.splice(from, 1)
  list.splice(to, 0, moved)
  ordered.value = list
  router.post('/admin/merchandise/reorder', { ids: list.map(p => p.id) }, { preserveScroll: true })
}

const togglingId = ref(null)
const toggle = (item) => {
  togglingId.value = item.id
  router.patch(`/admin/merchandise/${item.id}/toggle`, {}, {
    preserveScroll: true,
    onFinish: () => { togglingId.value = null },
  })
}

const deleteItem = async (item) => {
  const ok = await confirmDialog.value.open(
    'Delete design',
    `Delete "${item.name}" and its ${item.images_count} photo${item.images_count !== 1 ? 's' : ''}? This cannot be undone. To take it off the website but keep it, hide it instead.`,
    { confirmText: 'Delete', isDangerous: true }
  )
  if (ok) router.delete(`/admin/merchandise/${item.id}`, { preserveScroll: true })
}
</script>
