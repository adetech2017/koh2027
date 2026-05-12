<template>
  <AppLayout>
    <Head title="Campaign News & Updates">
      <meta name="description" content="Latest news, press releases, and updates from the KOH 2027 campaign for Lagos State. Stay informed about our campaign initiatives and announcements." />
      <meta property="og:title" content="Campaign News & Updates - KOH 2027" />
      <meta property="og:description" content="Latest news, press releases, and updates from the KOH 2027 campaign for Lagos State." />
      <meta property="og:type" content="website" />
      <meta name="twitter:card" content="summary" />
      <meta name="twitter:title" content="Campaign News & Updates - KOH 2027" />
      <meta name="twitter:description" content="Latest news, press releases, and updates from the KOH 2027 campaign." />
    </Head>
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Campaign News</h1>
          <p class="text-xl text-gray-200">Latest updates and press releases</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-light-gray rounded-lg p-6 mb-8">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div class="md:col-span-2">
              <label class="block text-dark font-medium mb-2">Search</label>
              <input v-model="searchInput" @input="debounceSearch" type="text" placeholder="Search articles..." class="w-full px-4 py-2 border border-light-gray rounded focus:outline-none focus:ring-2 focus:ring-primary" />
            </div>
            <div>
              <label class="block text-dark font-medium mb-2">Category</label>
              <select :value="filters.category" @change="(e) => updateFilter('category', e.target.value)" class="w-full px-4 py-2 border border-light-gray rounded focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">All Categories</option>
                <option value="announcement">Announcement</option>
                <option value="press_release">Press Release</option>
                <option value="opinion">Opinion</option>
                <option value="update">Update</option>
              </select>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
          <NewsCard v-for="article in articles.data" :key="article.id" :article="article" />
        </div>
        <Pagination :links="articles.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import NewsCard from '@/Components/NewsCard.vue'
import Pagination from '@/Components/Pagination.vue'

defineProps({
  articles: Object,
  filters: Object,
})

const searchInput = ref('')
let searchTimeout

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    updateFilter('search', searchInput.value)
  }, 500)
}

const updateFilter = (key, value) => {
  const params = { ...filters, [key]: value || undefined }
  router.get(route('news.index'), params)
}
</script>
