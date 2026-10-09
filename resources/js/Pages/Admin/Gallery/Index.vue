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
          <template v-if="featuredCount > homepageSlots">Only the first {{ homepageSlots }} featured photos appear on the homepage.</template>
          <template v-else-if="featuredCount === 0">Star photos to show them in the homepage gallery.</template>
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

      <!-- Category tabs -->
      <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
        <Link
          href="/admin/gallery"
          class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
          :class="!selectedCategory ? 'bg-primary text-white' : 'bg-white text-gray-600 hover:bg-gray-100 shadow-sm'"
        >
          All categories
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
          Uncategorized <span class="ml-1 opacity-75">{{ uncategorizedCount }}</span>
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
            <p class="font-semibold text-sm text-amber-800">Uncategorized</p>
            <p class="text-xs text-amber-700">{{ uncategorizedCount }} photo{{ uncategorizedCount !== 1 ? 's' : '' }} need a category</p>
          </Link>

          <!-- New category -->
          <div v-if="canManage" class="border-2 border-dashed border-gray-300 rounded-lg p-4 flex flex-col items-center justify-center text-center min-h-[10rem]">
            <template v-if="!creatingCategory">
              <button type="button" class="text-sm text-primary hover:text-primary-dark font-medium" @click="creatingCategory = true">
                + New category
              </button>
            </template>
            <form v-else class="w-full space-y-2" @submit.prevent="createCategory">
              <input
                v-model="newCategoryName"
                type="text"
                maxlength="100"
                placeholder="Category name"
                class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                autofocus
              />
              <p v-if="categoryError" class="text-red-600 text-xs">{{ categoryError }}</p>
              <div class="flex gap-2 justify-center">
                <button type="submit" :disabled="!newCategoryName.trim() || savingCategory" class="px-3 py-1.5 bg-primary text-white rounded text-xs font-medium disabled:opacity-50">
                  {{ savingCategory ? 'Adding…' : 'Add' }}
                </button>
                <button type="button" class="px-3 py-1.5 bg-gray-200 rounded text-xs font-medium" @click="creatingCategory = false; newCategoryName = ''; categoryError = ''">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="!categories.length && !uncategorizedCount" class="text-center py-6">
          <p class="text-gray-500">No photos yet. Create a category, then upload photos into it.</p>
        </div>
      </template>

      <!-- ===== Category view ===== -->
      <template v-else>
        <div class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-5">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
              <form v-if="renaming" class="flex flex-wrap items-center gap-2" @submit.prevent="renameCategory">
                <input
                  v-model="renameValue"
                  type="text"
                  maxlength="100"
                  class="px-3 py-1.5 border rounded-lg text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                  autofocus
                />
                <button type="submit" class="px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium">Save</button>
                <button type="button" class="px-3 py-1.5 text-sm text-gray-600" @click="renaming = false">Cancel</button>
                <p v-if="renameError" class="w-full text-red-600 text-xs">{{ renameError }}</p>
              </form>
              <h2 v-else class="text-xl font-bold text-dark truncate">{{ currentCategoryName }}</h2>
              <p class="text-sm text-gray-500">
                {{ images.total }} photo{{ images.total !== 1 ? 's' : '' }}
                <template v-if="needsDescriptionCount"> · <span class="text-amber-700">{{ needsDescriptionCount }} on this page need a description</span></template>
              </p>
            </div>
            <div v-if="currentCategory && canManage && !renaming" class="flex gap-2">
              <button type="button" class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary rounded-lg text-sm font-medium" @click="startRename">
                Rename
              </button>
              <button v-if="canDelete" type="button" class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium" @click="deleteCategory">
                Delete category
              </button>
            </div>
          </div>

          <GalleryUploader
            v-if="currentCategory && canManage"
            :category-id="currentCategory.id"
            :max-upload-bytes="maxUploadBytes"
            :max-upload-files="maxUploadFiles"
            compact
          />
          <p v-else-if="selectedCategory === 'uncategorized'" class="text-sm text-amber-700">
            These photos lost their category. Open each one and choose a new category.
          </p>
        </div>

        <!-- Images -->
        <div v-if="images.data.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
          <div v-for="img in images.data" :key="img.id" class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
            <Link :href="`/admin/gallery/${img.id}/edit`" class="relative block aspect-square bg-gray-100">
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
        <div v-else class="text-center py-10 text-gray-500">No photos in this category yet.</div>

        <Pagination v-if="images.last_page > 1" :links="images.links" />
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
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
const currentCategoryName = computed(() => currentCategory.value?.name || 'Uncategorized')
const needsDescriptionCount = computed(() => (props.images?.data || []).filter(i => i.needs_description).length)

const uploadHref = computed(() =>
  currentCategory.value ? `/admin/gallery/create?category_id=${currentCategory.value.id}` : '/admin/gallery/create'
)

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

// --- Categories ---
const creatingCategory = ref(false)
const newCategoryName = ref('')
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
      body: JSON.stringify({ name }),
    })
    const data = await response.json()
    if (!response.ok) {
      categoryError.value = data.errors?.name?.[0] ?? data.message ?? 'Could not add category.'
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
const renameValue = ref('')
const renameError = ref('')

const startRename = () => {
  renameValue.value = currentCategory.value.name
  renameError.value = ''
  renaming.value = true
}

const renameCategory = () => {
  router.patch(`/admin/gallery-categories/${currentCategory.value.id}`, { name: renameValue.value.trim() }, {
    preserveScroll: true,
    onSuccess: () => { renaming.value = false },
    onError: (errors) => { renameError.value = errors.name || 'Could not rename.' },
  })
}

const deleteCategory = async () => {
  const count = currentCategory.value.gallery_images_count
  const message = count
    ? `Delete "${currentCategory.value.name}"? Its ${count} photo${count !== 1 ? 's' : ''} will be kept but moved to Uncategorized.`
    : `Delete the empty category "${currentCategory.value.name}"?`
  const ok = await confirmDialog.value.open('Delete category', message, { confirmText: 'Delete category', isDangerous: true })
  if (ok) router.delete(`/admin/gallery-categories/${currentCategory.value.id}`)
}
</script>
