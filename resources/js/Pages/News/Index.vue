<template>
  <AppLayout>
    <Head title="Campaign News" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign News</h1>
          <p class="text-xl text-gray-200">Latest updates and press releases</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-light-gray rounded-lg p-4 sm:p-6 mb-8 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div class="md:col-span-2">
            <label for="news-search" class="block text-dark font-medium mb-2">Search</label>
            <input
              id="news-search"
              v-model="searchInput"
              type="search"
              placeholder="Search articles…"
              class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>
          <div>
            <label for="news-category" class="block text-dark font-medium mb-2">Category</label>
            <select
              id="news-category"
              :value="filters.category || ''"
              class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary"
              @change="visit({ category: $event.target.value || undefined })"
            >
              <option value="">All categories</option>
              <option v-for="cat in categories" :key="cat" :value="cat">{{ categoryLabel(cat) }}</option>
            </select>
          </div>
        </div>

        <div v-if="articles.data.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
          <NewsCard v-for="article in articles.data" :key="article.id" :article="article" />
        </div>
        <div v-else class="text-center py-16">
          <p class="text-lg font-semibold text-dark mb-2">
            {{ isFiltered ? 'No articles match your search.' : 'No news yet. Check back soon.' }}
          </p>
          <button v-if="isFiltered" type="button" class="text-primary font-semibold hover:underline" @click="clearFilters">Show all news</button>
        </div>

        <Pagination v-if="articles.last_page > 1" :links="articles.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import NewsCard from '@/Components/NewsCard.vue'
import Pagination from '@/Components/Pagination.vue'
import { categoryLabel } from '@/Utils/format'

const props = defineProps({
  articles: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
})

// Keeps what was typed after the page reloads
const searchInput = ref(props.filters.search || '')
const isFiltered = computed(() => !!(props.filters.search || props.filters.category))

// Previously referenced undefined `filters` and `route` here, so search and category threw errors
const visit = (changes) => {
  const query = {
    category: props.filters.category || undefined,
    search: searchInput.value.trim() || undefined,
    ...changes,
  }
  router.get('/news', query, { preserveScroll: true, preserveState: true, replace: true })
}

let timer
watch(searchInput, () => {
  clearTimeout(timer)
  timer = setTimeout(() => visit({}), 400)
})

const clearFilters = () => {
  searchInput.value = ''
  clearTimeout(timer)
  router.get('/news', {}, { preserveScroll: true, replace: true })
}
</script>
