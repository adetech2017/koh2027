<template>
  <AdminLayout>
    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-dark">News Articles</h2>
        <button
          @click="createNew"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors"
        >
          Create Article
        </button>
      </div>

      <!-- News Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Title</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Author</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="article in articles" :key="article.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <p class="font-medium text-dark">{{ article.title }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ article.author }}</td>
              <td class="px-6 py-4">
                <span :class="['text-xs font-medium px-3 py-1 rounded-full', article.is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800']">
                  {{ article.is_published ? 'Published' : 'Draft' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(article.created_at) }}</td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-3">
                  <button
                    @click="editArticle(article.id)"
                    class="text-primary hover:text-primary-dark font-medium text-sm"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteArticle(article.id)"
                    class="text-red-600 hover:text-red-800 font-medium text-sm"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="articles.length === 0" class="text-center py-12">
          <p class="text-gray-500">No news articles yet.</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()
const confirmDialog = ref(null)

const articles = computed(() => {
  const data = page.props.articles
  if (!data) return []
  return Array.isArray(data) ? data : (data.data || [])
})

const createNew = () => {
  router.visit('/admin/news/create')
}

const editArticle = (id) => {
  router.visit(`/admin/news/${id}/edit`)
}

const deleteArticle = async (id) => {
  const confirmed = await confirmDialog.value.open(
    'Delete Article',
    'Are you sure you want to delete this news article?',
    { confirmText: 'Delete', isDangerous: true }
  )
  if (confirmed) {
    router.delete(`/admin/news/${id}`)
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>
