<template>
  <AppLayout>
    <Head :title="product.name" />
    <div class="min-h-screen bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <Link href="/merchandise" class="inline-flex items-center gap-1 text-sm text-primary hover:underline mb-6">← All designs</Link>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 lg:gap-16">
          <!-- Photos -->
          <div>
            <div class="bg-light-gray rounded-lg aspect-square flex items-center justify-center mb-3 overflow-hidden">
              <img v-if="activeImage" :src="activeImage.url" :alt="activeImage.alt || product.name" class="w-full h-full object-cover" />
              <div v-else class="flex flex-col items-center gap-2 text-gray-400" aria-hidden="true">
                <PhotoIcon class="w-16 h-16" />
                <span class="text-sm">Design preview coming soon</span>
              </div>
            </div>
            <ul v-if="images.length > 1" class="grid grid-cols-4 sm:grid-cols-5 gap-2">
              <li v-for="(img, idx) in images" :key="img.url">
                <button
                  type="button"
                  class="block w-full aspect-square rounded overflow-hidden border-2 transition-colors"
                  :class="idx === activeIndex ? 'border-primary' : 'border-transparent hover:border-gray-300'"
                  :aria-label="`Show photo ${idx + 1}`"
                  :aria-pressed="idx === activeIndex"
                  @click="activeIndex = idx"
                >
                  <img :src="img.url" :alt="''" class="w-full h-full object-cover" />
                </button>
              </li>
            </ul>
          </div>

          <!-- Details -->
          <div>
            <p class="text-primary text-sm uppercase font-semibold tracking-wide mb-2">{{ categoryLabel(product.category) }}</p>
            <h1 class="text-3xl md:text-4xl font-bold text-dark mb-6">{{ product.name }}</h1>
            <p class="text-body leading-relaxed mb-8 whitespace-pre-line">{{ product.description }}</p>

            <!-- Design specifications (information, not choices) -->
            <dl class="space-y-5 mb-8">
              <div v-if="sizes.length">
                <dt class="font-semibold text-dark mb-2">Sizes</dt>
                <dd class="flex flex-wrap gap-2">
                  <span v-for="size in sizes" :key="size" class="px-3 py-1.5 bg-light-gray rounded text-sm text-dark">{{ size }}</span>
                </dd>
              </div>
              <div v-if="colors.length">
                <dt class="font-semibold text-dark mb-2">Approved colours</dt>
                <dd class="flex flex-wrap gap-2">
                  <span v-for="color in colors" :key="color" class="px-3 py-1.5 bg-light-gray rounded text-sm text-dark">{{ color }}</span>
                </dd>
              </div>
            </dl>

            <div class="bg-light-blue rounded-lg p-5">
              <p class="font-semibold text-dark mb-1">Producing this for your support group?</p>
              <p class="text-sm text-body mb-4">Request the print-ready design files and brand guidelines from the campaign office.</p>
              <Link :href="requestHref" class="btn-primary inline-block">Request design files &amp; guidelines</Link>
            </div>
          </div>
        </div>

        <div v-if="related.length" class="mt-20">
          <h2 class="text-2xl md:text-3xl font-bold text-dark mb-8">More designs</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            <MerchandiseCard v-for="prod in related" :key="prod.id" :product="prod" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { PhotoIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import MerchandiseCard from '@/Components/MerchandiseCard.vue'
import { categoryLabel } from '@/Utils/format'

const props = defineProps({
  product: { type: Object, required: true },
  related: { type: Array, default: () => [] },
})

const images = computed(() => props.product.images_urls || [])
const activeIndex = ref(0)
const activeImage = computed(() => images.value[activeIndex.value] || null)

const toList = (value) => (Array.isArray(value) ? value : [])
const sizes = computed(() => toList(props.product.sizes))
const colors = computed(() => toList(props.product.colors))

// Opens the contact form with the subject filled in
const requestHref = computed(() => `/contact?subject=${encodeURIComponent(`Merchandise: ${props.product.name}`)}`)
</script>
