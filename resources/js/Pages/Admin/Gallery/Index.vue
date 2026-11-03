<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Homepage summary -->
      <div
        class="rounded-lg p-4 text-sm flex flex-wrap items-center justify-between gap-3"
        :class="featuredCount > homepageSlots ? 'bg-amber-50 border border-amber-200 text-amber-800' : 'bg-white shadow text-gray-700'"
      >
        <p>
          <span class="font-semibold">★ {{ featuredCount }} of {{ homepageSlots }}</span> homepage slots used.
          <template v-if="featuredCount > homepageSlots">Only the {{ homepageSlots }} newest featured photos appear on the homepage.</template>
          <template v-else-if="featuredCount < homepageSlots">Free slots show the latest uploads. Star photos to keep them on the homepage.</template>
        </p>
        <Link
          :href="uploadHref"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Upload photos
        </Link>
      </div>

      <!-- Album tabs -->
      <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
        <Link
          href="/admin/gallery"
          class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
          :class="!selectedCategory ? 'bg-primary text-white' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm'"
        >
          All albums
        </Link>
        <Link
          v-for="cat in categories"
          :key="cat.id"
          :href="`/admin/gallery?category=${cat.id}`"
          class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
          :class="String(selectedCategory) === String(cat.id) ? 'bg-primary text-white' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm'"
        >
          {{ cat.name }} <span class="ml-1 opacity-75">{{ cat.gallery_images_count }}</span>
        </Link>
        <Link
          v-if="uncategorizedCount"
          href="/admin/gallery?category=uncategorized"
          class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
          :class="selectedCategory === 'uncategorized' ? 'bg-primary text-white' : 'bg-white text-amber-700 hover:bg-gray-100 shadow-sm'"
        >
          No album <span class="ml-1 opacity-75">{{ uncategorizedCount }}</span>
        </Link>
      </div>

      <!-- ===== Overview ===== -->
      <template v-if="!selectedCategory">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <Link
            v-for="cat in categories"
            :key="cat.id"
            :href="`/admin/gallery?category=${cat.id}`"
            class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow"
          >
            <div class="w-full aspect-[4/3] bg-gray-100 overflow-hidden flex items-center justify-center">
              <img v-if="cat.cover_url" :src="cat.cover_url" :alt="''" class="w-full h-full object-cover" loading="lazy" />
              <span v-else class="text-gray-400 text-xs">No photos yet</span>
            </div>
            <div class="p-3">
              <h3 class="font-semibold text-sm text-dark truncate">{{ cat.name }}</h3>
              <p v-if="cat.event_date" class="text-xs text-gray-500">{{ formatDate(cat.event_date) }}</p>
              <p class="text-xs text-gray-500">
                {{ cat.gallery_images_count }} photo{{ cat.gallery_images_count !== 1 ? 's' : '' }}
                <template v-if="cat.featured_count"> · ★ {{ cat.featured_count }}</template>
              </p>
            </div>
          </Link>

          <Link
            v-if="uncategorizedCount"
            href="/admin/gallery?category=uncategorized"
            class="bg-amber-50 border border-amber-200 rounded-lg overflow-hidden flex flex-col items-center justify-center p-4 text-center aspect-[4/3] sm:aspect-auto"
          >
            <p class="font-semibold text-sm text-amber-800">No album</p>
            <p class="text-xs text-amber-700">{{ uncategorizedCount }} photo{{ uncategorizedCount !== 1 ? 's' : '' }} need an album</p>
          </Link>

          <!-- New album -->
          <div v-if="canManage" class="border-2 border-dashed border-gray-300 rounded-lg p-4 flex flex-col items-center justify-center text-center min-h-[10rem]">
            <template v-if="!creatingCategory">
              <button type="button" class="text-sm text-primary hover:text-primary-dark font-medium" @click="creatingCategory = true">
                + New album
              </button>
            </template>
            <form v-else class="w-full space-y-2" @submit.prevent="createCategory">
              <input
                v-model="newCategoryName"
                type="text"
                maxlength="100"
                placeholder="e.g. Manifesto Unveiling"
                class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                autofocus
              />
              <label class="block text-left text-xs text-gray-500">
                Event date (optional)
                <input
                  v-model="newCategoryDate"
                  type="date"
                  class="mt-1 w-full px-3 py-2 border rounded-lg text-sm text-dark focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </label>
              <p v-if="categoryError" class="text-red-600 text-xs">{{ categoryError }}</p>
              <div class="flex gap-2 justify-center">
                <button type="submit" :disabled="!newCategoryName.trim() || savingCategory" class="px-3 py-1.5 bg-primary text-white rounded text-xs font-medium disabled:opacity-50">
                  {{ savingCategory ? 'Adding…' : 'Add' }}
                </button>
                <button type="button" class="px-3 py-1.5 bg-gray-200 rounded text-xs font-medium" @click="creatingCategory = false; newCategoryName = ''; newCategoryDate = ''; categoryError = ''">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="!categories.length && !uncategorizedCount" class="text-center py-6">
          <p class="text-gray-500">No photos yet. Create an album for an event, then upload photos into it.</p>
        </div>
      </template>

      <!-- ===== Album view ===== -->
      <template v-else>
        <div class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-5">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
              <h2 class="text-xl font-bold text-dark truncate">{{ currentCategoryName }}</h2>
              <p class="text-sm text-gray-500">
                <template v-if="currentCategory?.event_date">{{ formatDate(currentCategory.event_date) }} · </template>
                {{ images.total }} photo{{ images.total !== 1 ? 's' : '' }}
                <template v-if="needsDescriptionCount"> · <span class="text-amber-700">{{ needsDescriptionCount }} on this page need a description</span></template>
              </p>
            </div>
            <div v-if="currentCategory && !renaming" class="flex flex-wrap gap-2">
              <a
                v-if="currentCategory.slug && images.total"
                :href="`/gallery/${currentCategory.slug}`"
                target="_blank"
                rel="noopener"
                class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary rounded-lg text-sm font-medium"
              >View on site ↗</a>
              <button v-if="canManage" type="button" class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary rounded-lg text-sm font-medium" @click="startRename">
                Edit album
              </button>
              <button v-if="canDelete" type="button" class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium" @click="deleteCategory">
                Delete album
              </button>
            </div>
          </div>

          <form v-if="renaming" class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t pt-5" @submit.prevent="renameCategory">
            <div class="sm:col-span-2">
              <label for="album-name" class="block text-sm font-medium text-dark mb-1">Album name</label>
              <input
                id="album-name"
                v-model="albumForm.name"
                type="text"
                maxlength="100"
                required
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                autofocus
              />
              <p v-if="albumForm.errors.name" class="text-red-600 text-xs mt-1">{{ albumForm.errors.name }}</p>
            </div>
            <div>
              <label for="album-date" class="block text-sm font-medium text-dark mb-1">Event date</label>
              <input id="album-date" v-model="albumForm.event_date" type="date" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" />
              <p v-if="albumForm.errors.event_date" class="text-red-600 text-xs mt-1">{{ albumForm.errors.event_date }}</p>
            </div>
            <div class="sm:col-span-3">
              <label for="album-description" class="block text-sm font-medium text-dark mb-1">Description</label>
              <textarea
                id="album-description"
                v-model="albumForm.description"
                rows="3"
                maxlength="1000"
                placeholder="A sentence or two about the event, shown at the top of the album page"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              />
              <p v-if="albumForm.errors.description" class="text-red-600 text-xs mt-1">{{ albumForm.errors.description }}</p>
            </div>
            <div class="sm:col-span-3 flex gap-2 justify-end">
              <button type="button" class="px-3 py-1.5 text-sm text-gray-600" @click="renaming = false">Cancel</button>
              <button type="submit" :disabled="albumForm.processing" class="px-4 py-1.5 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-50">
                {{ albumForm.processing ? 'Saving…' : 'Save album' }}
              </button>
            </div>
          </form>

          <GalleryUploader
            v-if="currentCategory && canManage"
            :category-id="currentCategory.id"
            :max-upload-bytes="maxUploadBytes"
            :max-upload-files="maxUploadFiles"
            compact
          />
          <p v-else-if="selectedCategory === 'uncategorized'" class="text-sm text-amber-700">
            These photos aren't in an album. Open each one and choose an album.
          </p>
        </div>

        <!-- Bulk move -->
        <div v-if="canManage && images.data.length" class="flex flex-wrap items-center gap-2">
          <button
            v-if="!selecting"
            type="button"
            class="px-3 py-1.5 border border-gray-300 bg-white hover:border-primary hover:text-primary rounded-lg text-sm font-medium"
            @click="selecting = true"
          >
            Select photos to move
          </button>
          <template v-else>
            <span class="text-sm text-gray-600">Click photos to select them.</span>
            <button type="button" class="px-3 py-1.5 border border-gray-300 bg-white hover:border-primary hover:text-primary rounded-lg text-sm font-medium" @click="selectAllOnPage">
              {{ allOnPageSelected ? 'Unselect' : 'Select' }} all on this page ({{ images.data.length }})
            </button>
          </template>
        </div>

        <!-- Images -->
        <div v-if="images.data.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4" :class="{ 'pb-24': selecting }">
          <div
            v-for="img in images.data"
            :key="img.id"
            class="bg-white rounded-lg shadow overflow-hidden flex flex-col"
            :class="{ 'ring-4 ring-primary': selected.has(img.id) }"
          >
            <button
              v-if="selecting"
              type="button"
              class="relative block aspect-square bg-gray-100 w-full"
              :aria-pressed="selected.has(img.id)"
              :aria-label="`${selected.has(img.id) ? 'Unselect' : 'Select'} photo${img.alt_text ? ': ' + img.alt_text : ''}`"
              @click="toggleSelected(img.id)"
            >
              <img v-if="!failedImages.has(img.id)" :src="img.image_url" alt="" class="w-full h-full object-cover" loading="lazy" @error="failedImages.add(img.id)" />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">Image file missing</div>
              <span
                class="absolute top-2 right-2 w-6 h-6 rounded border-2 flex items-center justify-center text-sm font-bold"
                :class="selected.has(img.id) ? 'bg-primary border-primary text-white' : 'bg-white/90 border-gray-400'"
                aria-hidden="true"
              >{{ selected.has(img.id) ? '✓' : '' }}</span>
            </button>
            <Link v-else :href="`/admin/gallery/${img.id}/edit`" class="relative block aspect-square bg-gray-100">
              <img
                v-if="!failedImages.has(img.id)"
                :src="img.image_url"
                :alt="img.alt_text || ''"
                class="w-full h-full object-cover"
                :class="{ 'opacity-50': !img.is_active }"
                loading="lazy"
                @error="failedImages.add(img.id)"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">Image file missing</div>
              <div class="absolute top-2 left-2 flex flex-col items-start gap-1">
                <span v-if="img.is_featured" class="text-[11px] font-semibold bg-amber-400 text-dark px-1.5 py-0.5 rounded">★ Homepage</span>
                <span v-if="!img.is_active" class="text-[11px] font-semibold bg-gray-800/80 text-white px-1.5 py-0.5 rounded">Hidden</span>
              </div>
            </Link>
            <div class="p-2 flex-1 flex flex-col gap-2">
              <p class="text-xs truncate" :class="img.needs_description ? 'text-amber-700' : 'text-gray-600'">
                {{ img.needs_description ? 'Needs a description' : (img.title || img.alt_text) }}
              </p>
              <div v-if="canManage" class="flex items-center justify-between gap-1 mt-auto">
                <button
                  type="button"
                  class="p-1.5 rounded hover:bg-gray-100"
                  :class="img.is_featured ? 'text-amber-500' : 'text-gray-400 hover:text-amber-500'"
                  :aria-label="img.is_featured ? 'Remove from homepage' : 'Feature on homepage'"
                  :title="img.is_featured ? 'Remove from homepage' : 'Feature on homepage'"
                  :disabled="busyId === img.id"
                  @click="act(img, 'toggle-featured')"
                >
                  <svg class="w-5 h-5" :fill="img.is_featured ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                </button>
                <button
                  type="button"
                  class="p-1.5 rounded text-gray-400 hover:text-dark hover:bg-gray-100"
                  :aria-label="img.is_active ? 'Hide from public gallery' : 'Show in public gallery'"
                  :title="img.is_active ? 'Hide from public gallery' : 'Show in public gallery'"
                  :disabled="busyId === img.id"
                  @click="act(img, 'toggle-active')"
                >
                  <svg v-if="img.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                </button>
                <Link
                  :href="`/admin/gallery/${img.id}/edit`"
                  class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-gray-100"
                  aria-label="Edit details"
                  title="Edit details"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </Link>
                <button
                  v-if="canDelete"
                  type="button"
                  class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50"
                  aria-label="Delete"
                  title="Delete"
                  @click="deleteImage(img)"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-10 text-gray-500">No photos in this album yet.</div>

        <Pagination v-if="images.last_page > 1" :links="images.links" />

        <!-- Move toolbar, pinned to the bottom while selecting -->
        <div v-if="selecting" class="fixed bottom-0 inset-x-0 z-40 bg-white border-t shadow-lg">
          <form class="max-w-5xl mx-auto px-4 py-3 flex flex-wrap items-center gap-3" @submit.prevent="moveSelected">
            <span class="text-sm font-semibold text-dark">{{ selected.size }} selected</span>
            <label for="move-target" class="sr-only">Move to album</label>
            <select
              id="move-target"
              v-model="moveTarget"
              class="flex-1 min-w-[12rem] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option :value="null" disabled>Move to album…</option>
              <option v-for="cat in moveTargets" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <button
              type="submit"
              :disabled="!selected.size || !moveTarget || moving"
              class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium disabled:opacity-50"
            >
              {{ moving ? 'Moving…' : 'Move' }}
            </button>
            <button type="button" class="px-3 py-2 text-sm text-gray-600 hover:text-dark" @click="cancelSelecting">Cancel</button>
            <p v-if="moveError" class="w-full text-red-600 text-xs">{{ moveError }}</p>
          </form>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import Pagination from '@/Components/Pagination.vue'
import GalleryUploader from '@/Components/GalleryUploader.vue'

const props = defineProps({
  categories: { type: Array, default: () => [] },
  uncategorizedCount: { type: Number, default: 0 },
  featuredCount: { type: Number, default: 0 },
  homepageSlots: { type: Number, default: 8 },
  selectedCategory: { type: [Number, String], default: null },
  images: { type: Object, default: null },
  maxUploadBytes: { type: Number, default: 8 * 1024 * 1024 },
  maxUploadFiles: { type: Number, default: 20 },
})

const page = usePage()
const confirmDialog = ref(null)
const failedImages = ref(new Set())
const busyId = ref(null)

const role = computed(() => page.props.auth?.user?.role)
const canManage = computed(() => ['admin', 'editor'].includes(role.value))
const canDelete = computed(() => role.value === 'admin')

const currentCategory = computed(() => props.categories.find(c => String(c.id) === String(props.selectedCategory)) || null)
const currentCategoryName = computed(() => currentCategory.value?.name || 'No album')
const needsDescriptionCount = computed(() => (props.images?.data || []).filter(i => i.needs_description).length)

const uploadHref = computed(() =>
  currentCategory.value ? `/admin/gallery/create?category_id=${currentCategory.value.id}` : '/admin/gallery/create'
)

// --- Bulk move ---
const selecting = ref(false)
const selected = ref(new Set())
const moveTarget = ref(null)
const moving = ref(false)
const moveError = ref('')

const moveTargets = computed(() => props.categories.filter((c) => String(c.id) !== String(props.selectedCategory)))
const allOnPageSelected = computed(() => !!props.images?.data.length && props.images.data.every((i) => selected.value.has(i.id)))

const toggleSelected = (id) => {
  const next = new Set(selected.value)
  next.has(id) ? next.delete(id) : next.add(id)
  selected.value = next
}

const selectAllOnPage = () => {
  const next = new Set(selected.value)
  const ids = props.images.data.map((i) => i.id)
  if (allOnPageSelected.value) ids.forEach((id) => next.delete(id))
  else ids.forEach((id) => next.add(id))
  selected.value = next
}

const cancelSelecting = () => {
  selecting.value = false
  selected.value = new Set()
  moveTarget.value = null
  moveError.value = ''
}

const moveSelected = () => {
  if (!selected.value.size || !moveTarget.value) return
  moving.value = true
  moveError.value = ''
  router.post('/admin/gallery/bulk-move', { ids: [...selected.value], category_id: moveTarget.value }, {
    preserveScroll: true,
    onSuccess: cancelSelecting,
    onError: (errors) => { moveError.value = Object.values(errors)[0] || 'Could not move the photos.' },
    onFinish: () => { moving.value = false },
  })
}

const act = (img, action) => {
  busyId.value = img.id
  router.patch(`/admin/gallery/${img.id}/${action}`, {}, {
    preserveScroll: true,
    onFinish: () => { busyId.value = null },
  })
}

const deleteImage = async (img) => {
  const featuredNote = img.is_featured ? ' It will also disappear from the homepage.' : ''
  const ok = await confirmDialog.value.open('Delete photo', `Permanently delete this photo?${featuredNote} To keep it but stop showing it, hide it instead.`, {
    confirmText: 'Delete',
    isDangerous: true,
  })
  if (ok) router.delete(`/admin/gallery/${img.id}`, { preserveScroll: true })
}

// --- Albums ---
const formatDate = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })

const creatingCategory = ref(false)
const newCategoryName = ref('')
const newCategoryDate = ref('')
const savingCategory = ref(false)
const categoryError = ref('')

// The store endpoint returns JSON (it is also used by the category picker), so call it with fetch
const createCategory = async () => {
  const name = newCategoryName.value.trim()
  if (!name) return
  savingCategory.value = true
  categoryError.value = ''
  try {
    const response = await fetch('/admin/gallery-categories', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ name, event_date: newCategoryDate.value || null }),
    })
    const data = await response.json()
    if (!response.ok) {
      categoryError.value = data.errors?.name?.[0] ?? data.message ?? 'Could not add album.'
      return
    }
    router.visit(`/admin/gallery?category=${data.id}`)
  } catch {
    categoryError.value = 'Could not reach the server. Please try again.'
  } finally {
    savingCategory.value = false
  }
}

const renaming = ref(false)
const albumForm = useForm({ name: '', event_date: '', description: '' })

const startRename = () => {
  albumForm.defaults({
    name: currentCategory.value.name,
    event_date: currentCategory.value.event_date || '',
    description: currentCategory.value.description || '',
  })
  albumForm.reset()
  albumForm.clearErrors()
  renaming.value = true
}

const renameCategory = () => {
  albumForm
    .transform((data) => ({ ...data, name: data.name.trim(), event_date: data.event_date || null }))
    .patch(`/admin/gallery-categories/${currentCategory.value.id}`, {
      preserveScroll: true,
      onSuccess: () => { renaming.value = false },
    })
}

const deleteCategory = async () => {
  const count = currentCategory.value.gallery_images_count
  const message = count
    ? `Delete the album "${currentCategory.value.name}"? Its ${count} photo${count !== 1 ? 's' : ''} will be kept but moved to "No album".`
    : `Delete the empty album "${currentCategory.value.name}"?`
  const ok = await confirmDialog.value.open('Delete album', message, { confirmText: 'Delete album', isDangerous: true })
  if (ok) router.delete(`/admin/gallery-categories/${currentCategory.value.id}`)
}
</script>
