<template>
  <Link :href="`/platforms#${pillar.slug}`" class="group block bg-white rounded-lg overflow-hidden border border-light-gray shadow-card hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
    <div class="h-1.5" :style="{ backgroundColor: pillar.color || '#003D82' }"></div>
    <div class="p-6">
      <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" :style="{ backgroundColor: (pillar.color || '#003D82') + '1A' }">
        <component :is="getIcon(pillar.icon)" :style="{ color: pillar.color || '#003D82' }" class="w-6 h-6" />
      </div>
      <h3 class="text-xl font-bold text-dark mb-3 group-hover:text-primary transition-colors">{{ pillar.title }}</h3>
      <p class="text-body text-sm leading-relaxed mb-4">{{ pillar.summary }}</p>
      <span class="inline-flex items-center gap-1 font-semibold text-sm group-hover:gap-2 transition-all" :style="{ color: pillar.color || '#003D82' }">
        Learn more
        <ArrowRightIcon class="w-4 h-4" />
      </span>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import {
  BriefcaseIcon,
  BookOpenIcon,
  HeartIcon,
  BoltIcon,
  BuildingLibraryIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  HomeIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const iconMap = {
  briefcase: BriefcaseIcon,
  'book-open': BookOpenIcon,
  heart: HeartIcon,
  bolt: BoltIcon,
  'building-library': BuildingLibraryIcon,
  'user-group': UserGroupIcon,
  'shield-check': ShieldCheckIcon,
  home: HomeIcon,
}

const getIcon = (iconName) => iconMap[iconName] || BriefcaseIcon

defineProps({
  pillar: {
    type: Object,
    required: true,
  },
})
</script>
