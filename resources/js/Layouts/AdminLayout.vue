<template>
  <div class="flex h-screen bg-light-gray">
    <!-- Sidebar -->
    <div class="hidden md:flex w-64 bg-primary text-white flex-col fixed h-screen overflow-y-auto">
      <div class="p-6 border-b border-primary-dark">
        <h1 class="text-2xl font-bold">KOH 2027</h1>
        <p class="text-sm text-gray-300">Admin Panel</p>
      </div>

      <div v-if="!auth || !auth.user" class="p-6 text-center text-gray-300">
        <p class="text-sm">Loading...</p>
      </div>
      <template v-else>

      <nav class="flex-1 px-4 py-6 space-y-2">
        <!-- Dashboard -->
        <Link href="/admin/" :class="['flex items-center px-4 py-3 rounded transition-colors', isActive('/admin/') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-4m0 0l4 4m-4-4V8" />
          </svg>
          Dashboard
        </Link>

        <!-- CRM -->
        <div class="pt-4">
          <p class="px-4 text-xs font-semibold text-gray-300 uppercase">CRM</p>
          <Link href="/admin/contacts" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/contacts') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8a6 6 0 016-6h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z" />
            </svg>
            Contacts
          </Link>
          <Link href="/admin/volunteers" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/volunteers') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m4 0a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Volunteers
          </Link>
          <Link href="/admin/newsletter" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/newsletter') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Newsletter
          </Link>

          <!-- Events -->
          <Link href="/admin/events" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/events') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Events
          </Link>
        </div>

        <!-- Content Management -->
        <div class="pt-4">
          <p class="px-4 text-xs font-semibold text-gray-300 uppercase">Content</p>
          <Link href="/admin/hero-slides" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/hero-slides') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Hero Slider
          </Link>
          <Link href="/admin/gallery" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/gallery') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Gallery
          </Link>
          <Link href="/admin/news" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/news') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v11l4-4h-4v4" />
            </svg>
            News
          </Link>
          <Link href="/admin/platform-pillars" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/platform-pillars') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Platform
          </Link>
          <Link href="/admin/merchandise" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/merchandise') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            Merchandise
          </Link>
          <Link href="/admin/materials" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/materials') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Materials
          </Link>
        </div>

        <!-- Analytics & Campaign -->
        <div class="pt-4">
          <p class="px-4 text-xs font-semibold text-gray-300 uppercase">Tools</p>
          <Link href="/admin/analytics" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/analytics') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Analytics
          </Link>
          <Link href="/admin/bulk-email" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/bulk-email') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Bulk Email
          </Link>
        </div>

        <!-- Admin Only -->
        <div v-if="auth.user.role === 'admin'" class="pt-4">
          <p class="px-4 text-xs font-semibold text-gray-300 uppercase">Admin</p>
          <Link href="/admin/users" :class="['flex items-center px-4 py-3 rounded flex-shrink-0 transition-colors', isActive('/admin/users') ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Users
          </Link>
        </div>
      </nav>
      </template>

      <!-- User Info & Logout -->
      <div v-if="auth && auth.user" class="p-4 border-t border-primary-dark">
        <div class="mb-4">
          <p class="text-sm font-semibold">{{ auth.user.name }}</p>
          <p class="text-xs text-gray-300 capitalize">{{ auth.user.role }}</p>
        </div>
        <form action="/admin/logout" method="POST" class="w-full">
          <input type="hidden" name="_token" :value="csrf_token">
          <button type="submit" class="w-full px-4 py-2 bg-primary-dark hover:bg-red-700 rounded text-sm font-semibold transition-colors">
            Logout
          </button>
        </form>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 md:ml-64 flex flex-col overflow-hidden">
      <!-- Top Bar -->
      <div class="bg-white border-b border-light-gray px-6 py-4 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-bold text-dark">{{ pageTitle }}</h2>
        </div>
        <div class="flex items-center space-x-4">
          <div v-if="auth && auth.user" class="text-right hidden sm:block">
            <p class="text-sm font-semibold text-dark">{{ auth.user.name }}</p>
            <p class="text-xs text-body capitalize">{{ auth.user.role }}</p>
          </div>
        </div>
      </div>

      <!-- Flash Messages -->
      <FlashMessage v-if="flash.success || flash.error" />

      <!-- Page Content -->
      <div class="flex-1 overflow-auto p-6">
        <slot />
      </div>
    </div>

    <!-- Dialog Container (outside scrollable area) -->
    <slot name="dialogs" />
  </div>
</template>

<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import FlashMessage from '@/Components/FlashMessage.vue'

const page = usePage()
const auth = computed(() => page.props.auth)
const flash = computed(() => page.props.flash)
const csrf_token = computed(() => document.querySelector('meta[name="csrf-token"]')?.content || '')

const currentPath = computed(() => window.location.pathname)

const isActive = (path) => {
  const current = currentPath.value
  if (!current || !path) return false
  if (path === '/admin/') {
    return current === '/admin/'
  }
  return current.startsWith(path)
}

const pageTitle = computed(() => {
  const path = currentPath.value
  const titles = {
    '/admin/': 'Dashboard',
    '/admin/contacts': 'Contacts',
    '/admin/volunteers': 'Volunteers',
    '/admin/newsletter': 'Newsletter Subscribers',
    '/admin/events': 'Events',
    '/admin/hero-slides': 'Hero Slider',
    '/admin/gallery': 'Gallery',
    '/admin/news': 'News Articles',
    '/admin/platform-pillars': 'Platform Pillars',
    '/admin/merchandise': 'Merchandise',
    '/admin/materials': 'Campaign Materials',
    '/admin/analytics': 'Analytics',
    '/admin/bulk-email': 'Bulk Email Campaign',
    '/admin/users': 'User Management',
  }
  return titles[path] || 'Admin'
})
</script>

