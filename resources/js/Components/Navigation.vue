<template>
  <nav class="sticky top-0 z-50 bg-white border-b border-light-gray transition-shadow" :class="{ 'shadow-card': isScrolled }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16 md:h-20">
        <!-- Logo -->
        <Link href="/" class="flex items-center space-x-2 flex-shrink-0">
          <div class="w-10 h-10 bg-primary rounded flex items-center justify-center text-white font-bold text-lg">
            KOH
          </div>
          <span class="hidden sm:inline font-semibold text-dark text-sm">2027</span>
        </Link>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center space-x-8">
          <Link href="/" :class="isActive('/') ? 'text-primary font-semibold' : 'text-body hover:text-primary transition-colors'">Home</Link>
          <Link href="/about" :class="isActive('about') ? 'text-primary font-semibold' : 'text-body hover:text-primary transition-colors'">About</Link>

          <!-- Platforms dropdown -->
          <div class="relative" @mouseenter="platformsOpen = true" @mouseleave="platformsOpen = false">
            <button
              type="button"
              class="flex items-center gap-1 focus:outline-none"
              :class="isActive('platforms') || isActive('merchandise') ? 'text-primary font-semibold' : 'text-body hover:text-primary transition-colors'"
              @click="platformsOpen = !platformsOpen"
            >
              Platforms
              <ChevronDownIcon class="w-4 h-4 transition-transform" :class="{ 'rotate-180': platformsOpen }" />
            </button>
            <div v-show="platformsOpen" class="absolute left-0 top-full pt-2 w-52">
              <div class="bg-white rounded-lg shadow-card-hover border border-light-gray py-2">
                <Link href="/platforms" class="block px-4 py-2 text-sm text-body hover:bg-light-blue hover:text-primary transition-colors">Our Platforms</Link>
                <Link href="/merchandise" class="block px-4 py-2 text-sm text-body hover:bg-light-blue hover:text-primary transition-colors">Merchandise</Link>
              </div>
            </div>
          </div>

          <Link href="/materials" :class="isActive('materials') ? 'text-primary font-semibold' : 'text-body hover:text-primary transition-colors'">Manifesto</Link>
          <Link href="/events" :class="isActive('events') ? 'text-primary font-semibold' : 'text-body hover:text-primary transition-colors'">Events</Link>
          <Link href="/news" :class="isActive('news') ? 'text-primary font-semibold' : 'text-body hover:text-primary transition-colors'">News</Link>
          <Link href="/gallery" :class="isActive('gallery') ? 'text-primary font-semibold' : 'text-body hover:text-primary transition-colors'">Gallery</Link>
          <Link href="/contact" :class="isActive('contact') ? 'text-primary font-semibold' : 'text-body hover:text-primary transition-colors'">Contact</Link>
        </div>

        <!-- Right Actions -->
        <div class="hidden md:flex items-center space-x-4">
          <a href="https://hamzatforlagos.com/volunteer" target="_blank" rel="noopener noreferrer" class="btn-secondary text-sm">Volunteer</a>
          <a href="https://hamzatforlagos.com/register-voter" target="_blank" rel="noopener noreferrer" class="btn-primary text-sm">Voter Registration</a>
        </div>

        <!-- Mobile Menu Toggle -->
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-body hover:text-primary transition-colors">
          <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Mobile Menu -->
      <div v-if="mobileMenuOpen" class="md:hidden pb-4 space-y-2 border-t border-light-gray pt-4">
        <Link href="/" class="block px-4 py-2 text-body hover:bg-light-blue rounded transition-colors">Home</Link>
        <Link href="/about" class="block px-4 py-2 text-body hover:bg-light-blue rounded transition-colors">About</Link>

        <!-- Platforms expandable section -->
        <button
          type="button"
          class="w-full flex items-center justify-between px-4 py-2 text-body hover:bg-light-blue rounded transition-colors"
          @click="mobilePlatformsOpen = !mobilePlatformsOpen"
        >
          <span>Platforms</span>
          <ChevronDownIcon class="w-4 h-4 transition-transform" :class="{ 'rotate-180': mobilePlatformsOpen }" />
        </button>
        <div v-if="mobilePlatformsOpen" class="pl-4 space-y-1">
          <Link href="/platforms" class="block px-4 py-2 text-sm text-body hover:bg-light-blue rounded transition-colors">Our Platforms</Link>
          <Link href="/merchandise" class="block px-4 py-2 text-sm text-body hover:bg-light-blue rounded transition-colors">Merchandise</Link>
        </div>

        <Link href="/materials" class="block px-4 py-2 text-body hover:bg-light-blue rounded transition-colors">Manifesto</Link>
        <Link href="/events" class="block px-4 py-2 text-body hover:bg-light-blue rounded transition-colors">Events</Link>
        <Link href="/news" class="block px-4 py-2 text-body hover:bg-light-blue rounded transition-colors">News</Link>
        <Link href="/gallery" class="block px-4 py-2 text-body hover:bg-light-blue rounded transition-colors">Gallery</Link>
        <Link href="/contact" class="block px-4 py-2 text-body hover:bg-light-blue rounded transition-colors">Contact</Link>
        <div class="flex flex-col space-y-2 pt-2">
          <a href="https://hamzatforlagos.com/volunteer" target="_blank" rel="noopener noreferrer" class="btn-secondary w-full text-sm text-center">Volunteer</a>
          <a href="https://hamzatforlagos.com/register-voter" target="_blank" rel="noopener noreferrer" class="btn-primary w-full text-sm text-center">Voter Registration</a>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const page = usePage()

const isScrolled = ref(false)
const mobileMenuOpen = ref(false)
const platformsOpen = ref(false)
const mobilePlatformsOpen = ref(false)

const currentPath = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.pathname
  }
  return ''
})

const isActive = (path) => {
  const current = currentPath.value
  if (path === '/') {
    return current === '/'
  }
  return current.includes(`/${path}`)
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 0
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>
