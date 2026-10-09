<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Filters -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              type="button"
              class="px-4 py-2 rounded-full font-medium text-sm whitespace-nowrap transition-colors"
              :class="(filters.status || 'all') === tab.key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              @click="visit({ status: tab.key === 'all' ? undefined : tab.key })"
            >
              {{ tab.label }} <span class="ml-1 font-semibold">{{ counts[tab.key] || 0 }}</span>
            </button>
          </div>
          <Link
            href="/admin/news/create"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New article
          </Link>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <label for="news-search" class="sr-only">Search</label>
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              id="news-search"
              v-model="searchQuery"
              type="search"
              placeholder="Search titles and summaries..."
              class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>
          <label for="news-category" class="sr-only">Category</label>
          <select
            id="news-category"
            :value="filters.category || ''"
            class="sm:w-56 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            @change="visit({ category: $event.target.value || undefined })"
          >
            <option value="">All categories</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ categoryLabel(cat) }}</option>
          </select>
        </div>
      </div>

      <!-- Articles -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="divide-y">
          <div v-for="article in articles.data" :key="article.id" class="flex flex-col sm:flex-row gap-4 px-4 sm:px-6 py-4">
            <Link :href="`/admin/news/${article.id}/edit`" class="w-full sm:w-32 aspect-video rounded bg-gray-100 overflow-hidden flex-shrink-0 flex items-center justify-center">
              <img v-if="article.image_url" :src="article.image_url" alt="" class="w-full h-full object-cover" loading="lazy" />
              <svg v-else class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v11l4-4h-4v4" />
              </svg>
            </Link>

            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="statusStyle(article.status).class">{{ statusStyle(article.status).label }}</span>
                <span v-if="article.is_featured" class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">★ Featured</span>
                <span class="text-xs text-gray-500">{{ categoryLabel(article.category) }}</span>
              </div>
              <Link :href="`/admin/news/${article.id}/edit`" class="block mt-1 font-semibold text-dark hover:text-primary break-words">
                {{ article.title }}
              </Link>
              <p class="text-sm text-gray-500 line-clamp-1">{{ article.excerpt }}</p>
              <p class="text-xs text-gray-400 mt-1">
                {{ dateLine(article) }} · {{ article.author_name }}
              </p>
            </div>

            <div class="flex sm:flex-col gap-2 sm:items-end flex-shrink-0">
              <div class="flex gap-2">
                <Link
                  :href="`/admin/news/${article.id}/edit`"
                  class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
                >
                  Edit
                </Link>
                <a
                  v-if="article.status === 'published'"
                  :href="`/news/${article.slug}`"
                  target="_blank"
                  rel="noopener"
                  class="px-3 py-1.5 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
                >
                  View ↗
                </a>
              </div>
              <div class="flex gap-2">
                <button
                  type="button"
                  :disabled="busyId === article.id"
                  class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                  :class="article.status === 'draft' ? 'text-green-700 hover:bg-green-50' : 'text-gray-600 hover:bg-gray-100'"
                  @click="togglePublish(article)"
                >
                  {{ { draft: 'Publish now', scheduled: 'Unschedule', published: 'Unpublish' }[article.status] }}
                </button>
                <button
                  v-if="canDelete"
                  type="button"
                  class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg font-medium text-sm transition-colors"
                  @click="deleteArticle(article)"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!articles.data.length" class="text-center py-12 px-4">
          <p class="text-gray-500">{{ isFiltered ? 'No articles match these filters' : 'No articles yet' }}</p>
          <button v-if="isFiltered" type="button" class="mt-3 text-sm text-primary hover:text-primary-dark font-medium" @click="clearFilters">
            Clear filters
          </button>
          <Link v-else href="/admin/news/create" class="inline-block mt-3 text-sm text-primary hover:text-primary-dark font-medium">
            Write the first article →
          </Link>
        </div>
      </div>

      <div v-if="articles.data.length" class="space-y-2">
        <Pagination v-if="articles.last_page > 1" :links="articles.links" />
        <p class="text-center text-sm text-gray-600">
          Showing {{ articles.from }} to {{ articles.to }} of {{ articles.total }} articles
        </p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  articles: { type: Object, required: true },
  counts: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const confirmDialog = ref(null)
const busyId = ref(null)
const canDelete = computed(() => page.props.auth?.user?.role === 'admin')

const tabs = [
  { key: 'all', label: 'All' },
  { key: 'published', label: 'Published' },
  { key: 'scheduled', label: 'Scheduled' },
  { key: 'draft', label: 'Drafts' },
]

const searchQuery = ref(props.filters.search || '')
const isFiltered = computed(() => !!(props.filters.status || props.filters.search || props.filters.category))

const visit = (params) => {
  const query = {
    status: props.filters.status || undefined,
    category: props.filters.category || undefined,
    search: searchQuery.value.trim() || undefined,
    ...params,
  }
  router.get('/admin/news', query, { preserveState: true, preserveScroll: true, replace: true })
}

let searchTimer
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => visit({}), 300)
})

const clearFilters = () => {
  searchQuery.value = ''
  clearTimeout(searchTimer)
  router.get('/admin/news', {}, { preserveState: true, replace: true })
}

const categoryLabel = (cat) => (cat || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())

const statusStyle = (status) => ({
  draft: { label: 'Draft', class: 'bg-gray-100 text-gray-700' },
  scheduled: { label: 'Scheduled', class: 'bg-blue-100 text-blue-800' },
  published: { label: 'Published', class: 'bg-green-100 text-green-800' },
}[status])

const fmt = (iso) => new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
const fmtTime = (iso) => new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' })

const dateLine = (article) => {
  if (article.status === 'scheduled') return `Goes live ${fmtTime(article.published_at)}`
  if (article.status === 'published' && article.published_at) return `Published ${fmt(article.published_at)}`
  return `Last edited ${fmt(article.updated_at)}`
}

const togglePublish = async (article) => {
  if (article.status !== 'draft') {
    const scheduled = article.status === 'scheduled'
    const ok = await confirmDialog.value.open(
      scheduled ? 'Unschedule article' : 'Unpublish article',
      scheduled
        ? `Cancel the scheduled publish of "${article.title}"? It will be kept as a draft.`
        : `Take "${article.title}" off the website? It will be kept as a draft.`,
      { confirmText: scheduled ? 'Unschedule' : 'Unpublish' }
    )
    if (!ok) return
  }
  busyId.value = article.id
  router.post(`/admin/news/${article.id}/publish`, {}, {
    preserveScroll: true,
    onFinish: () => { busyId.value = null },
  })
}

const deleteArticle = async (article) => {
  const ok = await confirmDialog.value.open(
    'Delete article',
    `Permanently delete "${article.title}"? To take it off the site but keep it, unpublish it instead.`,
    { confirmText: 'Delete', isDangerous: true }
  )
  if (ok) router.delete(`/admin/news/${article.id}`, { preserveScroll: true })
}
</script>
