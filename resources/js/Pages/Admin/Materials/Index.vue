<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
          <Link
            href="/admin/materials"
            class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
            :class="!filters.category ? 'bg-primary text-white' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm'"
          >All</Link>
          <Link
            v-for="cat in categories"
            :key="cat"
            :href="`/admin/materials?category=${encodeURIComponent(cat)}`"
            class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
            :class="filters.category === cat ? 'bg-primary text-white' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm'"
          >{{ label(cat) }}</Link>
        </div>
        <Link
          href="/admin/materials/create"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Upload document
        </Link>
      </div>

      <!-- Manifesto checklist -->
      <section v-if="!filters.category || filters.category === 'manifesto'" class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
          <h2 class="text-lg font-semibold text-dark">Manifesto page</h2>
          <p class="text-xs text-gray-500">The full manifesto plus one document per pillar.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
          <component
            :is="slot.material ? Link : 'div'"
            v-for="slot in manifestoSlots"
            :key="slot.key"
            :href="slot.material ? `/admin/materials/${slot.material.id}/edit` : undefined"
            class="rounded-lg border p-2 text-center text-xs"
            :class="slotClass(slot)"
          >
            <p class="font-semibold">{{ slot.label }}</p>
            <p>{{ slotStatus(slot) }}</p>
          </component>
        </div>
        <p v-if="missingText.length" class="text-xs text-amber-700 mt-3">
          The Manifesto Assistant can't read {{ missingText.length }} document{{ missingText.length !== 1 ? 's' : '' }} yet. Use "Extract text" below.
        </p>
      </section>

      <!-- Documents by category -->
      <section v-for="(items, cat) in grouped" :key="cat" class="bg-white rounded-lg shadow overflow-hidden">
        <h2 class="px-4 sm:px-6 py-3 bg-gray-50 border-b text-sm font-semibold text-gray-700">{{ label(cat) }} <span class="text-gray-400 font-normal">· {{ items.length }}</span></h2>
        <div class="divide-y">
          <div v-for="doc in items" :key="doc.id" class="flex flex-col md:flex-row md:items-center gap-4 px-4 sm:px-6 py-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div class="w-12 h-14 rounded bg-gray-100 overflow-hidden flex-shrink-0 flex items-center justify-center">
                <img v-if="doc.thumbnail_url" :src="doc.thumbnail_url" alt="" class="w-full h-full object-cover" />
                <span v-else class="text-[10px] font-bold uppercase text-gray-500">{{ doc.file_type }}</span>
              </div>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5">
                  <Link :href="`/admin/materials/${doc.id}/edit`" class="font-semibold text-dark hover:text-primary break-words">{{ doc.title }}</Link>
                  <span v-if="!doc.is_active" class="text-[11px] font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-700">Hidden</span>
                  <span v-if="doc.file_missing" class="text-[11px] font-medium px-1.5 py-0.5 rounded bg-red-100 text-red-700">File missing</span>
                </div>
                <p class="text-sm text-gray-500 line-clamp-1">{{ doc.description }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                  {{ doc.file_type?.toUpperCase() }} · {{ formatSize(doc.file_size) }} · {{ doc.download_count }} download{{ doc.download_count !== 1 ? 's' : '' }}
                </p>
              </div>
            </div>

            <!-- Assistant status -->
            <div class="md:w-48 flex-shrink-0 text-xs">
              <template v-if="doc.assistant === 'ready'">
                <span class="inline-flex items-center gap-1 text-green-700 font-medium">● Assistant uses this</span>
                <p class="text-gray-400">Read {{ formatDate(doc.extracted_at) }}</p>
              </template>
              <template v-else-if="doc.assistant === 'missing'">
                <span class="inline-flex items-center gap-1 text-amber-700 font-medium">● Assistant can't read it yet</span>
                <button
                  v-if="canManage"
                  type="button"
                  :disabled="extractingId === doc.id"
                  class="block mt-1 text-primary hover:text-primary-dark font-medium disabled:opacity-50"
                  @click="extract(doc)"
                >{{ extractingId === doc.id ? 'Extracting…' : 'Extract text' }}</button>
              </template>
              <span v-else-if="doc.assistant === 'skipped'" class="text-gray-400" title="The full manifesto repeats the pillar documents, so the assistant reads those instead">Not read by assistant (covered by the pillar documents)</span>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 flex-shrink-0">
              <button
                type="button"
                role="switch"
                :aria-checked="doc.is_active"
                :disabled="togglingId === doc.id"
                class="inline-flex items-center gap-2 text-sm"
                :title="doc.is_active ? 'Downloadable on the site' : 'Hidden from the site'"
                @click="toggle(doc)"
              >
                <span class="relative w-9 h-5 rounded-full transition-colors" :class="doc.is_active ? 'bg-green-500' : 'bg-gray-300'">
                  <span class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="{ 'translate-x-4': doc.is_active }" />
                </span>
              </button>
              <a :href="`/admin/materials/${doc.id}/download`" class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary rounded-lg text-sm font-medium" title="Download (not counted)">Download</a>
              <Link :href="`/admin/materials/${doc.id}/edit`" class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary rounded-lg text-sm font-medium">Edit</Link>
              <button v-if="canDelete" type="button" class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium" @click="deleteDoc(doc)">Delete</button>
            </div>
          </div>
        </div>
      </section>

      <div v-if="!materials.length" class="bg-white rounded-lg shadow text-center py-12 px-4">
        <p class="text-gray-500">No documents{{ filters.category ? ` in ${label(filters.category)}` : '' }} yet.</p>
        <Link href="/admin/materials/create" class="inline-block mt-3 text-sm text-primary hover:text-primary-dark font-medium">Upload one →</Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
  materials: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const confirmDialog = ref(null)
const role = computed(() => page.props.auth?.user?.role)
const canManage = computed(() => ['admin', 'editor'].includes(role.value))
const canDelete = computed(() => role.value === 'admin')

const label = (cat) => (cat === 'faq' ? 'FAQ' : (cat || '').charAt(0).toUpperCase() + (cat || '').slice(1))

const grouped = computed(() => {
  const groups = {}
  for (const doc of props.materials) (groups[doc.category] ||= []).push(doc)
  return groups
})

// The public Manifesto page expects one full document plus pillars 1–7
const manifestoSlots = computed(() => {
  const docs = props.materials.filter(d => d.category === 'manifesto')
  const full = docs.find(d => d.public_role === 'full')
  return [
    { key: 'full', label: 'Full', material: full },
    ...Array.from({ length: 7 }, (_, i) => ({
      key: `p${i + 1}`,
      label: `Pillar ${i + 1}`,
      material: docs.find(d => d.pillar_number === i + 1),
    })),
  ]
})

const missingText = computed(() => props.materials.filter(d => d.assistant === 'missing'))

const slotClass = (slot) => {
  if (!slot.material) return 'border-dashed border-gray-300 text-gray-400'
  if (!slot.material.is_active || slot.material.file_missing) return 'border-amber-300 bg-amber-50 text-amber-800 hover:border-amber-500'
  return 'border-green-200 bg-green-50 text-green-800 hover:border-green-400'
}

const slotStatus = (slot) => {
  if (!slot.material) return 'Missing'
  if (slot.material.file_missing) return 'File missing'
  if (!slot.material.is_active) return 'Hidden'
  return 'Live'
}

const formatSize = (bytes) => {
  if (!bytes) return '0 KB'
  const kb = bytes / 1024
  return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`
}

const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : '')

const togglingId = ref(null)
const toggle = (doc) => {
  togglingId.value = doc.id
  router.patch(`/admin/materials/${doc.id}/toggle`, {}, { preserveScroll: true, onFinish: () => { togglingId.value = null } })
}

const extractingId = ref(null)
const extract = (doc) => {
  extractingId.value = doc.id
  router.post(`/admin/materials/${doc.id}/extract`, {}, { preserveScroll: true, onFinish: () => { extractingId.value = null } })
}

const deleteDoc = async (doc) => {
  const assistantNote = doc.assistant === 'ready' ? ' The Manifesto Assistant will also stop using it.' : ''
  const ok = await confirmDialog.value.open(
    'Delete document',
    `Delete "${doc.title}" and its file? This cannot be undone.${assistantNote} To take it off the site but keep it, hide it instead.`,
    { confirmText: 'Delete', isDangerous: true }
  )
  if (ok) router.delete(`/admin/materials/${doc.id}`, { preserveScroll: true })
}
</script>
