<template>
  <AppLayout>
    <Head title="Our Platform" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">The Lagos Promise</h1>
          <p class="text-xl text-gray-200">{{ pillarWord }} pillars for a progressive Lagos</p>
        </div>
      </div>

      <!-- Jump-to menu: sticks under the site header while reading -->
      <nav v-if="pillars.length > 1" class="sticky top-16 md:top-20 z-30 bg-white/95 backdrop-blur border-b border-light-gray" aria-label="Pillars">
        <ul class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex gap-2 overflow-x-auto py-3 scrollbar-none">
          <li v-for="(pillar, idx) in pillars" :key="pillar.id" class="flex-shrink-0">
            <a
              :href="`#${pillar.slug}`"
              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm border transition-colors"
              :class="activeSlug === pillar.slug ? 'text-white border-transparent' : 'border-gray-200 text-body hover:border-gray-400'"
              :style="activeSlug === pillar.slug ? { backgroundColor: pillar.color || '#003D82' } : {}"
              :aria-current="activeSlug === pillar.slug ? 'true' : undefined"
            >
              <span class="font-semibold">{{ idx + 1 }}</span> {{ pillar.title }}
            </a>
          </li>
        </ul>
      </nav>

      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="space-y-14">
          <section
            v-for="(pillar, idx) in pillars"
            :id="pillar.slug"
            :key="pillar.id"
            ref="sections"
            class="border-b pb-14 last:border-b-0 scroll-mt-40"
          >
            <div class="flex items-start gap-4 mb-6">
              <div class="w-16 h-16 rounded-lg flex items-center justify-center flex-shrink-0" :style="{ backgroundColor: (pillar.color || '#003D82') + '1A' }">
                <component :is="getIcon(pillar.icon)" :style="{ color: pillar.color || '#003D82' }" class="w-8 h-8" aria-hidden="true" />
              </div>
              <div>
                <p class="text-sm font-semibold uppercase tracking-wide" :style="{ color: pillar.color || '#003D82' }">Pillar {{ idx + 1 }}</p>
                <h2 class="text-2xl md:text-3xl font-bold text-dark">{{ pillar.title }}</h2>
                <p class="text-body mt-1">{{ pillar.summary }}</p>
              </div>
            </div>
            <p class="text-body leading-relaxed whitespace-pre-line">{{ pillar.body }}</p>
            <a
              v-if="pillar.document"
              :href="`/materials/${pillar.document.id}/download`"
              class="inline-flex items-center gap-2 mt-6 font-semibold hover:underline"
              :style="{ color: pillar.color || '#003D82' }"
            >
              <ArrowDownTrayIcon class="w-5 h-5" aria-hidden="true" />
              Download the full Pillar {{ idx + 1 }} document ({{ fileSize(pillar.document.file_size) }})
            </a>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import { getPlatformIcon as getIcon } from '@/Utils/platformIcons'
import { fileSize } from '@/Utils/format'

const props = defineProps({
  pillars: { type: Array, default: () => [] },
})

const WORDS = ['No', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten']
const pillarWord = computed(() => WORDS[props.pillars.length] || props.pillars.length)

// Highlight the pillar being read in the jump-to menu
const sections = ref([])
const activeSlug = ref(props.pillars[0]?.slug || null)
let observer
onMounted(() => {
  if (!('IntersectionObserver' in window)) return
  observer = new IntersectionObserver((entries) => {
    const visible = entries.filter(e => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)
    if (visible[0]) activeSlug.value = visible[0].target.id
  }, { rootMargin: '-30% 0px -60% 0px' })
  sections.value.forEach(el => observer.observe(el))
})
onBeforeUnmount(() => observer?.disconnect())
</script>

<style scoped>
.scrollbar-none { scrollbar-width: none; }
.scrollbar-none::-webkit-scrollbar { display: none; }
</style>
