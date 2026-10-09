<template>
  <div class="flex h-screen bg-light-gray">
    <!-- Mobile backdrop -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-30 bg-black/50 md:hidden"
      aria-hidden="true"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      :class="[
        'w-64 bg-primary text-white flex flex-col fixed inset-y-0 left-0 z-40 overflow-y-auto transition-transform duration-200 md:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
    >
      <div class="p-6 border-b border-primary-dark flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold">KOH 2027</h1>
          <p class="text-sm text-gray-300">Admin Panel</p>
        </div>
        <button
          type="button"
          class="md:hidden p-1 -mr-2 rounded hover:bg-primary-dark"
          aria-label="Close menu"
          @click="sidebarOpen = false"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div v-if="!auth || !auth.user" class="p-6 text-center text-gray-300">
        <p class="text-sm">Loading...</p>
      </div>

      <nav v-else class="flex-1 px-4 py-6 space-y-2">
        <div v-for="section in visibleSections" :key="section.label || 'main'" :class="{ 'pt-4': section.label }">
          <p v-if="section.label" class="px-4 text-xs font-semibold text-gray-300 uppercase">{{ section.label }}</p>
          <Link
            v-for="item in section.items"
            :key="item.href"
            :href="item.href"
            :class="['flex items-center px-4 py-3 rounded transition-colors', isActive(item.href) ? 'bg-primary-dark text-white font-semibold' : 'text-gray-100 hover:bg-primary-dark']"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
            </svg>
            {{ item.label }}
          </Link>
        </div>
      </nav>

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
    </aside>

    <!-- Main Content -->
    <div class="flex-1 min-w-0 md:ml-64 flex flex-col overflow-hidden">
      <!-- Top Bar -->
      <div class="bg-white border-b border-light-gray px-4 sm:px-6 py-4 flex items-center gap-3">
        <button
          type="button"
          class="md:hidden p-2 -ml-2 rounded text-dark hover:bg-light-gray"
          aria-label="Open menu"
          @click="sidebarOpen = true"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <h2 class="text-xl sm:text-2xl font-bold text-dark truncate">{{ pageTitle }}</h2>
      </div>

      <!-- Flash Messages -->
      <FlashMessage v-if="flash.success || flash.error" />

      <!-- Page Content -->
      <div class="flex-1 overflow-auto p-4 sm:p-6">
        <slot />
      </div>
    </div>

    <!-- Dialog Container (outside scrollable area) -->
    <slot name="dialogs" />
  </div>
</template>

<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import FlashMessage from '@/Components/FlashMessage.vue'

const page = usePage()
const auth = computed(() => page.props.auth)
const flash = computed(() => page.props.flash)
const csrf_token = computed(() => document.querySelector('meta[name="csrf-token"]')?.content || '')

const sidebarOpen = ref(false)

const icons = {
  home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  chat: 'M3 8a6 6 0 016-6h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z',
  userGroup: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
  mail: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  film: 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z',
  photo: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
  news: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v11l4-4h-4v4',
  collection: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
  bag: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
  document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
  bolt: 'M13 10V3L4 14h7v7l9-11h-7z',
  shield: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
}

// Matches the server's admin.role:admin,editor middleware on content routes
const CONTENT_ROLES = ['admin', 'editor']

const sections = [
  { items: [{ href: '/admin', label: 'Dashboard', title: 'Dashboard', icon: icons.home }] },
  {
    label: 'CRM',
    items: [
      { href: '/admin/contacts', label: 'Contacts', icon: icons.chat },
      { href: '/admin/volunteers', label: 'Volunteers', icon: icons.userGroup },
      { href: '/admin/newsletter', label: 'Newsletter', title: 'Newsletter Subscribers', icon: icons.mail },
      { href: '/admin/events', label: 'Events', icon: icons.calendar, roles: CONTENT_ROLES },
    ],
  },
  {
    label: 'Content',
    roles: CONTENT_ROLES,
    items: [
      { href: '/admin/hero-slides', label: 'Hero Slider', icon: icons.film },
      { href: '/admin/gallery', label: 'Gallery', icon: icons.photo },
      { href: '/admin/news', label: 'News', title: 'News Articles', icon: icons.news },
      { href: '/admin/platform-pillars', label: 'Platform', title: 'Platform Pillars', icon: icons.collection },
      { href: '/admin/merchandise', label: 'Merchandise Designs', icon: icons.bag },
      { href: '/admin/materials', label: 'Manifesto', icon: icons.document },
    ],
  },
  {
    label: 'Tools',
    items: [
      { href: '/admin/analytics', label: 'Analytics', icon: icons.chart },
      { href: '/admin/bulk-email', label: 'Bulk Email', title: 'Bulk Email Campaign', icon: icons.bolt, adminOnly: true },
    ],
  },
  {
    label: 'Admin',
    adminOnly: true,
    items: [{ href: '/admin/users', label: 'Users', title: 'User Management', icon: icons.shield }],
  },
]

// Hide sections and links the user's role can't open (the server enforces this too)
const visibleSections = computed(() => {
  const role = auth.value?.user?.role
  const allowed = (entry) => (!entry.adminOnly || role === 'admin') && (!entry.roles || entry.roles.includes(role))
  return sections
    .filter(allowed)
    .map(section => ({ ...section, items: section.items.filter(allowed) }))
    .filter(section => section.items.length)
})

// page.url is reactive across Inertia visits; strip query string and trailing slash
const currentPath = computed(() => (page.url || '').split('?')[0].replace(/\/+$/, '') || '/')

const isActive = (href) => {
  if (href === '/admin') return currentPath.value === '/admin'
  return currentPath.value === href || currentPath.value.startsWith(href + '/')
}

const pageTitle = computed(() => {
  const item = sections.flatMap(section => section.items).find(item => isActive(item.href))
  return item ? (item.title || item.label) : 'Admin'
})

// Close the mobile drawer after navigating
watch(() => page.url, () => { sidebarOpen.value = false })
</script>
