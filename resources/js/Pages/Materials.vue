<template>
  <AppLayout>
    <Head title="Materials & Resources" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Manifesto</h1>
          <p class="text-xl text-gray-200">Download brochures, policies, and campaign resources</p>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">
        <!-- The Lagos Promise: hero + seven pillars -->
        <section v-if="manifesto" class="space-y-10">
          <!-- Hero: the complete manifesto -->
          <a
            :href="`/materials/${manifesto.id}/download`"
            class="group relative overflow-hidden rounded-lg shadow-card-prominent bg-gradient-to-br from-primary to-primary-dark text-white flex flex-col md:flex-row items-stretch"
          >
            <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 85% 20%, white 0, transparent 45%);"></div>
            <div class="relative flex-grow p-8 md:p-12">
              <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider bg-white/15 rounded-full px-3 py-1 mb-5">
                <BookOpenIcon class="w-4 h-4" />
                The Complete Manifesto
              </span>
              <h2 class="text-2xl md:text-4xl font-bold mb-3 leading-tight">{{ manifesto.title }}</h2>
              <p class="text-gray-200 max-w-2xl mb-6">{{ manifesto.description }}</p>
              <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-2 px-6 py-3 bg-gold text-dark rounded font-semibold group-hover:bg-gold-dark transition-all group-hover:scale-105">
                  <ArrowDownTrayIcon class="w-5 h-5" />
                  Download Full Manifesto
                </span>
                <span class="text-sm text-gray-300">{{ formatFileSize(manifesto.file_size) }} &middot; {{ manifesto.file_type?.toUpperCase() }}</span>
              </div>
            </div>
          </a>

          <!-- Seven pillars -->
          <div v-if="pillarMaterials.length">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-8">
              <div>
                <h3 class="text-2xl font-bold text-dark mb-1">The Seven Pillars of the Promise</h3>
                <p class="text-body">Download each pillar on its own &mdash; a lighter file for the section you care about most.</p>
              </div>
              <div class="flex gap-1.5 text-2xl font-extrabold tracking-widest select-none">
                <span v-for="p in PILLARS" :key="p.letter" :style="{ color: p.color }">{{ p.letter }}</span>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <a
                v-for="item in pillarMaterials"
                :key="item.material.id"
                :href="`/materials/${item.material.id}/download`"
                class="group relative bg-white border border-light-gray rounded-lg overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 transition-all"
              >
                <div class="h-1.5" :style="{ backgroundColor: item.pillar.color }"></div>
                <div class="p-6">
                  <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center" :style="{ backgroundColor: item.pillar.color + '1A' }">
                      <component :is="item.pillar.icon" class="w-6 h-6" :style="{ color: item.pillar.color }" />
                    </div>
                    <span class="text-4xl font-extrabold leading-none opacity-10 group-hover:opacity-20 transition-opacity" :style="{ color: item.pillar.color }">
                      {{ item.pillar.letter }}
                    </span>
                  </div>
                  <p class="text-xs font-semibold uppercase tracking-wide mb-1" :style="{ color: item.pillar.color }">Pillar {{ item.pillar.number }}</p>
                  <h4 class="font-bold text-dark mb-2 group-hover:text-primary transition-colors">{{ item.pillar.name }}</h4>
                  <p class="text-sm text-body mb-5 leading-relaxed">{{ item.pillar.summary }}</p>
                  <div class="flex items-center justify-between pt-4 border-t border-light-gray">
                    <span class="text-xs text-gray-400">{{ formatFileSize(item.material.file_size) }} &middot; {{ item.material.file_type?.toUpperCase() }}</span>
                    <span class="inline-flex items-center gap-1 text-sm font-semibold group-hover:gap-2 transition-all" :style="{ color: item.pillar.color }">
                      Download
                      <ArrowDownTrayIcon class="w-4 h-4" />
                    </span>
                  </div>
                </div>
              </a>
            </div>
          </div>
        </section>

        <!-- Other resource categories -->
        <section v-if="otherCategories.length">
          <div class="mb-8 flex gap-2 flex-wrap border-b border-light-gray pb-6">
            <button v-for="cat in otherCategories" :key="cat" @click="activeCategory = cat" :class="['px-4 py-2 rounded transition-colors capitalize cursor-pointer font-medium', activeCategory === cat ? 'btn-primary' : 'text-body border border-light-gray hover:border-primary hover:text-primary']">{{ cat }}</button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a v-for="material in materialsByCategory" :key="material.id" :href="`/materials/${material.id}/download`" class="bg-white border border-light-gray rounded-lg p-6 hover:shadow-card transition-all group">
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 bg-light-blue rounded-lg flex items-center justify-center flex-shrink-0">
                  <DocumentTextIcon class="w-6 h-6 text-primary" />
                </div>
                <div class="flex-grow">
                  <h3 class="font-bold text-dark group-hover:text-primary transition-colors">{{ material.title }}</h3>
                  <p class="text-sm text-body">{{ material.description }}</p>
                  <div class="flex items-center justify-between mt-4">
                    <span class="text-xs text-gray-400">{{ formatFileSize(material.file_size) }} &middot; {{ material.file_type.toUpperCase() }}</span>
                    <span class="text-xs text-primary font-semibold">{{ material.download_count }} downloads</span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  BookOpenIcon,
  DocumentTextIcon,
  ArrowDownTrayIcon,
  HeartIcon,
  BoltIcon,
  BriefcaseIcon,
  BuildingLibraryIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  HomeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  materials: Object,
  categories: Array,
})

const PILLARS = [
  { number: 1, letter: 'P', name: 'People First', summary: 'Health, education, safety and service built around the dignity of every Lagosian.', icon: HeartIcon, color: '#27AE60' },
  { number: 2, letter: 'R', name: 'Renewal', summary: 'Building the roads, power, water and drainage that let Lagos work.', icon: BoltIcon, color: '#FFB81C' },
  { number: 3, letter: 'O', name: 'Opportunity', summary: 'Widening enterprise, industry, trade and the Lagos digital economy.', icon: BriefcaseIcon, color: '#003D82' },
  { number: 4, letter: 'M', name: 'Machinery of Government', summary: 'A government that works through the council, the courts and the citizen interface.', icon: BuildingLibraryIcon, color: '#6B21A8' },
  { number: 5, letter: 'I', name: 'Inclusion', summary: 'Equity for every Lagos woman, young person, and every division of the state.', icon: UserGroupIcon, color: '#DB2777' },
  { number: 6, letter: 'S', name: 'Security and Resilience', summary: 'A safe, climate-resilient and intelligently governed megacity.', icon: ShieldCheckIcon, color: '#B91C1C' },
  { number: 7, letter: 'E', name: 'Economic Dignity', summary: 'Affordable housing, mobility and a lower cost of living for every household.', icon: HomeIcon, color: '#0D9488' },
]

const manifestoGroup = computed(() => props.materials?.manifesto || [])

const manifesto = computed(() => {
  return manifestoGroup.value.find((m) => !/pillar\s*\d/i.test(m.title)) || null
})

const pillarMaterials = computed(() => {
  return manifestoGroup.value
    .map((m) => {
      const match = m.title.match(/pillar\s*(\d)/i)
      if (!match) return null
      const pillar = PILLARS[Number(match[1]) - 1]
      if (!pillar) return null
      return { material: m, pillar }
    })
    .filter(Boolean)
    .sort((a, b) => a.pillar.number - b.pillar.number)
})

const otherCategories = computed(() => (props.categories || []).filter((c) => c !== 'manifesto'))
const activeCategory = ref(otherCategories.value?.[0] || '')

const materialsByCategory = computed(() => {
  return props.materials[activeCategory.value] || []
})

const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}
</script>
