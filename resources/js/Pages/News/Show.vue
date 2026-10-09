<template>
  <AppLayout>
    <Head :title="article.title" />
    <div class="min-h-screen bg-white">
      <article class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <Link href="/news" class="inline-flex items-center gap-1 text-sm text-primary hover:underline mb-6">← All news</Link>
        <header class="mb-8">
          <div class="flex flex-wrap items-center gap-3 mb-4">
            <Link :href="`/news?category=${encodeURIComponent(article.category)}`" class="bg-primary text-white px-3 py-1 rounded-full text-sm font-semibold hover:bg-primary-dark">
              {{ categoryLabel(article.category) }}
            </Link>
            <time :datetime="article.published_at" class="text-body text-sm">{{ longDate(article.published_at) }}</time>
            <span class="text-body text-sm">· {{ readMinutes }} min read</span>
          </div>
          <h1 class="text-3xl md:text-5xl font-bold text-dark mb-4 leading-tight">{{ article.title }}</h1>
          <p class="text-xl text-body leading-relaxed">{{ article.excerpt }}</p>
          <p class="text-body mt-4">By {{ article.author_name }}</p>
        </header>
        <figure v-if="article.image_url && !imageFailed" class="mb-8">
          <img :src="article.image_url" :alt="article.image_alt || ''" class="w-full rounded-lg" @error="imageFailed = true" />
        </figure>

        <!-- A <div>, not a <p>: the editor's headings and lists aren't allowed inside a paragraph -->
        <div class="article-body text-body leading-relaxed mb-12" v-html="safeBody" />

        <div class="border-t border-light-gray pt-8 mb-12">
          <ShareButtons :url="shareUrl" :title="article.title" />
        </div>
      </article>

      <section v-if="related.length" class="bg-light-gray py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-2xl font-bold text-dark mb-8">Related articles</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <NewsCard v-for="relArticle in related" :key="relArticle.id" :article="relArticle" />
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import DOMPurify from 'dompurify'
import AppLayout from '@/Layouts/AppLayout.vue'
import NewsCard from '@/Components/NewsCard.vue'
import ShareButtons from '@/Components/ShareButtons.vue'
import { categoryLabel, longDate } from '@/Utils/format'

const props = defineProps({
  article: { type: Object, required: true },
  related: { type: Array, default: () => [] },
  shareUrl: { type: String, default: '' },
})

const imageFailed = ref(false)

// The body is HTML from the admin editor; strip anything that could run script
const safeBody = computed(() => DOMPurify.sanitize(props.article.body || '', { USE_PROFILES: { html: true } }))

const readMinutes = computed(() => {
  const words = (props.article.body || '').replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.round(words / 200))
})
</script>

<style scoped>
/* Readable styles for the editor's HTML (the Tailwind typography plugin isn't installed) */
.article-body :deep(p) { margin-bottom: 1.1em; font-size: 1.0625rem; }
.article-body :deep(h1), .article-body :deep(h2) { font-size: 1.5rem; font-weight: 700; color: var(--color-dark); margin: 1.6em 0 0.6em; }
.article-body :deep(h3), .article-body :deep(h4) { font-size: 1.25rem; font-weight: 700; color: var(--color-dark); margin: 1.4em 0 0.5em; }
.article-body :deep(ul) { list-style: disc; padding-left: 1.5em; margin-bottom: 1.1em; }
.article-body :deep(ol) { list-style: decimal; padding-left: 1.5em; margin-bottom: 1.1em; }
.article-body :deep(li) { margin-bottom: 0.35em; }
.article-body :deep(a) { color: var(--color-primary); text-decoration: underline; }
.article-body :deep(blockquote) { border-left: 4px solid var(--color-gold); padding-left: 1em; font-style: italic; margin: 1.4em 0; }
.article-body :deep(strong) { color: var(--color-dark); }
.article-body :deep(img) { border-radius: 0.5rem; margin: 1.4em 0; }
</style>
