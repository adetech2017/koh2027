<template>
  <AppLayout>
    <Head :title="safeMeta.title">
      <meta name="description" :content="safeMeta.description" />
      <meta property="og:title" :content="safeMeta.title" />
      <meta property="og:description" :content="safeMeta.description" />
      <meta v-if="safeMeta.image" property="og:image" :content="safeMeta.image" />
      <meta property="og:type" content="article" />
      <meta property="og:url" :content="safeMeta.url" />
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" :content="safeMeta.title" />
      <meta name="twitter:description" :content="safeMeta.description" />
      <meta v-if="safeMeta.image" name="twitter:image" :content="safeMeta.image" />
    </Head>
    <div class="min-h-screen bg-white">
      <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <header class="mb-8">
          <div class="flex items-center space-x-4 mb-4">
            <span class="bg-primary text-white px-3 py-1 rounded-full text-sm font-semibold capitalize">{{ article.category }}</span>
            <span class="text-body text-sm">{{ formatDate(article.published_at) }}</span>
          </div>
          <h1 class="text-5xl font-bold text-dark mb-4">{{ article.title }}</h1>
          <p class="text-xl text-body">{{ article.excerpt }}</p>
          <p class="text-body mt-4">By {{ article.author_name }}</p>
        </header>
        <figure v-if="article.image_url" class="mb-8">
          <img :src="article.image_url" :alt="article.image_alt" class="w-full rounded-lg" />
        </figure>
        <div class="prose max-w-none mb-12">
          <p v-html="article.body" class="text-body leading-relaxed"></p>
        </div>
        <div class="border-t border-light-gray pt-8 mb-12">
          <ShareButtons :url="safeMeta.url" :title="safeMeta.title" />
        </div>
        <div v-if="related.length" class="border-t border-light-gray pt-12">
          <h2 class="text-2xl font-bold text-dark mb-8">Related Articles</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <NewsCard v-for="relArticle in related" :key="relArticle.id" :article="relArticle" />
          </div>
        </div>
      </article>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import NewsCard from '@/Components/NewsCard.vue'
import ShareButtons from '@/Components/ShareButtons.vue'

const page = usePage()

const props = defineProps({
  article: Object,
  related: Array,
  meta: Object,
})

const safeMeta = computed(() => ({
  title: props.meta?.title || props.article?.title || 'Article',
  description: props.meta?.description || props.article?.excerpt || '',
  image: props.meta?.image || props.article?.image_url || '',
  url: props.meta?.url || (typeof window !== 'undefined' ? window.location.href : ''),
}))

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
}
</script>
