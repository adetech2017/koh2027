<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Range filter: one row, above everything it controls -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white shadow-sm">
            <span class="relative flex w-2 h-2">
              <span v-if="trafficSummary.liveNow" class="absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75 animate-ping" />
              <span class="relative inline-flex rounded-full w-2 h-2" :class="trafficSummary.liveNow ? 'bg-green-500' : 'bg-gray-300'" />
            </span>
            <span><span class="font-semibold text-dark">{{ trafficSummary.liveNow }}</span> on the site now</span>
          </span>
          <span>{{ rangeLabel }}, compared with the {{ days }} days before.</span>
        </div>
        <div class="inline-flex rounded-lg border border-gray-300 bg-white p-0.5" role="group" aria-label="Time range">
          <button
            v-for="r in ranges"
            :key="r"
            type="button"
            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
            :class="r === days ? 'bg-primary text-white' : 'text-gray-600 hover:text-dark'"
            :aria-pressed="r === days"
            @click="setRange(r)"
          >
            {{ r }} days
          </button>
        </div>
      </div>

      <div v-if="!trackingSince" class="bg-blue-50 border border-blue-200 text-blue-900 rounded-lg p-4 text-sm">
        Website tracking is switched on. Visitor numbers will appear here as people use the public site.
        Visits by signed-in staff aren't counted.
      </div>

      <!-- ===== Website traffic ===== -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="tile in trafficTiles" :key="tile.key" class="bg-white rounded-lg shadow p-4 sm:p-5">
          <p class="text-sm text-gray-500 flex items-center gap-1">
            {{ tile.label }}
            <span v-if="tile.help" class="text-gray-400 cursor-help" :title="tile.help" aria-hidden="true">ⓘ</span>
          </p>
          <p class="text-3xl font-bold text-dark mt-1 tabular-nums">{{ tile.value }}</p>
          <p class="text-xs mt-1" :class="tile.delta.class">{{ tile.delta.text }}</p>
          <p v-if="tile.help" class="sr-only">{{ tile.help }}</p>
        </div>
      </div>

      <section class="bg-white rounded-lg shadow p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-dark mb-1">Visitors and page views</h3>
        <p class="text-xs text-gray-500 mb-4">Hover the chart to see each day.</p>
        <TrendChart :data="trafficDaily" :series="trafficSeries" empty-text="No visits in this period yet" />
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top pages -->
        <section class="bg-white rounded-lg shadow p-4 sm:p-6">
          <div class="flex items-baseline justify-between mb-4">
            <h3 class="text-lg font-semibold text-dark">Top pages</h3>
            <span class="text-xs text-gray-500">Page views</span>
          </div>
          <RankedBars
            :rows="topPages.map(p => ({ label: p.name, sub: p.path !== '/' && p.name !== p.path ? p.path : null, value: p.views, extra: `${p.visitors} visitor${p.visitors !== 1 ? 's' : ''}`, href: p.path }))"
            external
            empty="No page views yet"
          />
        </section>

        <!-- Sources -->
        <section class="bg-white rounded-lg shadow p-4 sm:p-6">
          <div class="flex items-baseline justify-between mb-4">
            <h3 class="text-lg font-semibold text-dark">Where visitors come from</h3>
            <span class="text-xs text-gray-500">Visitors</span>
          </div>
          <RankedBars
            :rows="sourceRows"
            empty="No referrals yet. Visitors who type the address or use a bookmark count as Direct."
          />
          <p v-if="directVisitors > 0" class="text-xs text-gray-500 mt-3">
            Plus {{ directVisitors }} direct visit{{ directVisitors !== 1 ? 's' : '' }} (typed the address, a bookmark, or an app that hides where it came from, such as WhatsApp).
          </p>
          <div v-if="campaigns.length" class="mt-5 pt-4 border-t">
            <p class="text-sm font-medium text-dark mb-2">Tagged campaigns</p>
            <ul class="space-y-1 text-sm">
              <li v-for="c in campaigns" :key="c.campaign" class="flex justify-between">
                <span class="text-gray-700 truncate pr-2">{{ c.campaign }}</span>
                <span class="font-semibold text-dark tabular-nums">{{ c.visitors }}</span>
              </li>
            </ul>
          </div>
        </section>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Clicks -->
        <section class="lg:col-span-2 bg-white rounded-lg shadow p-4 sm:p-6">
          <h3 class="text-lg font-semibold text-dark mb-4">Clicks</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
            <div v-for="type in clickTypes" :key="type.name" class="rounded-lg border border-gray-200 p-3">
              <p class="text-xs text-gray-500">{{ type.label }}</p>
              <p class="text-xl font-bold text-dark tabular-nums">{{ type.clicks }}</p>
              <p class="text-[11px]" :class="type.delta.class">{{ type.delta.text }}</p>
            </div>
          </div>
          <p class="text-sm font-medium text-dark mb-2">Most clicked</p>
          <RankedBars
            :rows="clicks.top.map(c => ({ label: c.label || clickLabel(c.name), sub: clickLabel(c.name), value: c.clicks, extra: `${c.visitors} visitor${c.visitors !== 1 ? 's' : ''}` }))"
            empty="No clicks recorded yet"
          />
        </section>

        <!-- Devices -->
        <section class="bg-white rounded-lg shadow p-4 sm:p-6">
          <h3 class="text-lg font-semibold text-dark mb-4">Devices</h3>
          <BreakdownBar :segments="deviceSegments" :total="deviceTotal" empty="No visits yet" />
        </section>
      </div>

      <!-- Tracked link builder -->
      <section class="bg-white rounded-lg shadow p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-dark mb-1">Make a trackable link</h3>
        <p class="text-sm text-gray-500 mb-4">Share this link instead of the plain address, and visits from it will show up under "Where visitors come from" and "Tagged campaigns".</p>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <div>
            <label for="lb-page" class="block text-xs font-medium text-gray-600 mb-1">Page</label>
            <select id="lb-page" v-model="link.page" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
              <option v-for="(name, path) in linkPages" :key="path" :value="path">{{ name }}</option>
            </select>
          </div>
          <div>
            <label for="lb-source" class="block text-xs font-medium text-gray-600 mb-1">Where you'll share it</label>
            <select id="lb-source" v-model="link.source" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
              <option v-for="s in linkSources" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label for="lb-campaign" class="block text-xs font-medium text-gray-600 mb-1">Campaign name</label>
            <input id="lb-campaign" v-model="link.campaign" type="text" placeholder="e.g. ikeja-town-hall" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
          </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 mt-3">
          <input :value="trackedLink" readonly class="flex-1 px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm font-mono text-gray-700" aria-label="Trackable link" @focus="$event.target.select()" />
          <button type="button" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium" @click="copyLink">
            {{ copied ? 'Copied' : 'Copy link' }}
          </button>
        </div>
      </section>

      <!-- ===== Campaign outcomes ===== -->
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 pt-2">Supporters</h2>

      <section class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
          <h3 class="text-lg font-semibold text-dark">Sign-ups from the website</h3>
          <p v-if="trafficSummary.visitors" class="text-sm text-gray-600">
            <span class="font-semibold text-dark">{{ conversionRate }}%</span> of visitors signed up for something
          </p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <Link v-for="tile in signupTiles" :key="tile.key" :href="tile.href" class="rounded-lg border border-gray-200 p-4 hover:border-primary transition-colors">
            <p class="text-sm text-gray-500">{{ tile.label }}</p>
            <p class="text-2xl font-bold text-dark mt-1 tabular-nums">{{ tile.current }}</p>
            <p class="text-xs mt-1" :class="tile.delta.class">{{ tile.delta.text }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ tile.context }}</p>
          </Link>
        </div>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section class="bg-white rounded-lg shadow p-4 sm:p-6">
          <div class="flex items-baseline justify-between mb-4">
            <h3 class="text-lg font-semibold text-dark">Volunteer pipeline</h3>
            <span class="text-sm text-gray-500">{{ overviewStats.volunteersTotal || 0 }} total</span>
          </div>
          <BreakdownBar :segments="volunteerSegments" :total="overviewStats.volunteersTotal || 0" empty="No volunteers yet" />
        </section>

        <section class="bg-white rounded-lg shadow p-4 sm:p-6">
          <div class="flex items-baseline justify-between mb-4">
            <h3 class="text-lg font-semibold text-dark">Newsletter sign-ups</h3>
            <span class="text-sm text-gray-500">{{ newsletterTotal }} total</span>
          </div>
          <BreakdownBar :segments="newsletterSegments" :total="newsletterTotal" empty="No sign-ups yet" />
          <p v-if="newsletterTotal" class="text-sm text-gray-600 mt-4">
            <span class="font-semibold text-dark">{{ pct(newsletterFunnel.confirmed, newsletterTotal) }}%</span> of sign-ups confirmed their email.
          </p>
        </section>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section class="bg-white rounded-lg shadow p-4 sm:p-6">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h3 class="text-lg font-semibold text-dark">Supporters by LGA</h3>
            <div class="inline-flex rounded-lg border border-gray-300 p-0.5 text-xs" role="group" aria-label="Measure">
              <button
                v-for="opt in lgaOptions"
                :key="opt.key"
                type="button"
                class="px-2.5 py-1 rounded-md font-medium"
                :class="lgaMode === opt.key ? 'bg-gray-800 text-white' : 'text-gray-600 hover:text-dark'"
                :aria-pressed="lgaMode === opt.key"
                @click="lgaMode = opt.key; showAllLga = false"
              >{{ opt.label }}</button>
            </div>
          </div>
          <RankedBars :rows="lgaRows" :limit="showAllLga ? null : 10" :total="lgaTotal" empty="No data yet" />
          <button
            v-if="lgaRows.length > 10"
            type="button"
            class="mt-3 text-sm text-primary hover:text-primary-dark font-medium"
            @click="showAllLga = !showAllLga"
          >{{ showAllLga ? 'Show top 10' : `Show all ${lgaRows.length} LGAs` }}</button>
        </section>

        <section class="bg-white rounded-lg shadow p-4 sm:p-6">
          <div class="flex items-baseline justify-between mb-4">
            <h3 class="text-lg font-semibold text-dark">Volunteer skills</h3>
            <span class="text-xs text-gray-500">Volunteers can pick several</span>
          </div>
          <RankedBars :rows="skillRows" :total="overviewStats.volunteersTotal || 0" percent-label="of volunteers" empty="No skills recorded yet" />
        </section>
      </div>

      <section class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="flex items-baseline justify-between mb-4">
          <h3 class="text-lg font-semibold text-dark">Event RSVPs</h3>
          <Link href="/admin/events" class="text-sm text-primary hover:text-primary-dark font-medium">All events →</Link>
        </div>
        <p v-if="!eventAttendance.length" class="text-sm text-gray-500 py-6 text-center">No published events yet.</p>
        <ul v-else class="divide-y">
          <li v-for="event in eventAttendance" :key="event.id" class="py-3">
            <Link :href="`/admin/events/${event.id}/rsvps`" class="block group">
              <div class="flex flex-wrap items-baseline justify-between gap-2 mb-1.5">
                <p class="text-sm font-medium text-dark group-hover:text-primary">
                  {{ event.title }}
                  <span class="text-xs font-normal text-gray-500 ml-1">{{ eventDate(event.starts_at) }}<template v-if="event.is_past"> · past</template></span>
                </p>
                <p class="text-sm text-gray-600 tabular-nums">
                  <span class="font-semibold text-dark">{{ event.confirmed }}</span>
                  <template v-if="event.capacity"> / {{ event.capacity }} · {{ Math.round(event.fillRate) }}% full</template>
                  <template v-else> confirmed · no limit</template>
                </p>
              </div>
              <div v-if="event.capacity" class="w-full h-2 bg-gray-100 rounded overflow-hidden">
                <div
                  class="h-full rounded"
                  :class="event.fillRate >= 100 ? 'bg-red-500' : event.fillRate >= 80 ? 'bg-amber-500' : 'bg-blue-600'"
                  :style="{ width: Math.min(event.fillRate, 100) + '%' }"
                />
              </div>
            </Link>
          </li>
        </ul>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, reactive, ref, h } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TrendChart from '@/Components/TrendChart.vue'

const props = defineProps({
  days: { type: Number, default: 30 },
  ranges: { type: Array, default: () => [7, 30, 90] },
  trafficSummary: { type: Object, default: () => ({ previous: {} }) },
  trafficDaily: { type: Array, default: () => [] },
  topPages: { type: Array, default: () => [] },
  sources: { type: Array, default: () => [] },
  campaigns: { type: Array, default: () => [] },
  devices: { type: Array, default: () => [] },
  clicks: { type: Object, default: () => ({ byType: [], top: [] }) },
  trackingSince: { type: String, default: null },
  periodTotals: { type: Object, default: () => ({}) },
  overviewStats: { type: Object, default: () => ({}) },
  lgaBreakdown: { type: Object, default: () => ({ volunteers: [], rsvps: [] }) },
  skillsInventory: { type: [Object, Array], default: () => ({}) },
  newsletterFunnel: { type: Object, default: () => ({}) },
  eventAttendance: { type: Array, default: () => [] },
})

// Validated categorical palette (blue, violet): passes CVD and contrast checks on white
const trafficSeries = [
  { key: 'visitors', label: 'Visitors', color: '#2563eb' },
  { key: 'views', label: 'Page views', color: '#9333ea' },
]

const setRange = (days) => router.get('/admin/analytics', { days }, { preserveScroll: true, preserveState: false, replace: true })
const rangeLabel = computed(() => `Last ${props.days} days`)

const pct = (part, whole) => (whole ? Math.round(((part || 0) / whole) * 100) : 0)

// lowerIsBetter flips the colours, e.g. for bounce rate
const delta = (current, previous, { lowerIsBetter = false, unit = '' } = {}) => {
  if (!previous && !current) return { text: 'No data before', class: 'text-gray-400' }
  if (!previous) return { text: 'No data before', class: 'text-gray-400' }
  const change = Math.round(((current - previous) / previous) * 100)
  if (change === 0) return { text: `Same as before (${previous}${unit})`, class: 'text-gray-500' }
  const good = lowerIsBetter ? change < 0 : change > 0
  return {
    text: `${change > 0 ? '▲' : '▼'} ${Math.abs(change)}% vs ${previous}${unit} before`,
    class: good ? 'text-green-700' : 'text-red-700',
  }
}

const trafficTiles = computed(() => {
  const s = props.trafficSummary
  const p = s.previous || {}
  return [
    { key: 'visitors', label: 'Visitors', value: (s.visitors || 0).toLocaleString(), delta: delta(s.visitors, p.visitors), help: 'Each person is counted once per day they visit. No cookies are used.' },
    { key: 'views', label: 'Page views', value: (s.views || 0).toLocaleString(), delta: delta(s.views, p.views) },
    { key: 'ppv', label: 'Pages per visit', value: s.pagesPerVisit || 0, delta: delta(s.pagesPerVisit, p.pagesPerVisit) },
    { key: 'bounce', label: 'Left after one page', value: `${s.bounceRate || 0}%`, delta: delta(s.bounceRate, p.bounceRate, { lowerIsBetter: true, unit: '%' }), help: 'Visits that viewed only one page. Lower usually means people explore more.' },
  ]
})

const sourceRows = computed(() => props.sources.map(s => ({
  label: s.label,
  sub: s.tagged ? 'tagged link' : (s.label !== s.source ? s.source : null),
  value: s.visitors,
})))
const directVisitors = computed(() =>
  Math.max(0, (props.trafficSummary.visitors || 0) - props.sources.reduce((sum, s) => sum + s.visitors, 0))
)

const clickNames = {
  cta_click: 'Homepage buttons',
  volunteer_click: 'Volunteer links',
  share: 'Shares',
  download: 'Manifesto downloads',
  contact_click: 'Email & phone taps',
  outbound_click: 'Other outside links',
}
const clickLabel = (name) => clickNames[name] || name
const clickTypes = computed(() => (props.clicks.byType || []).map(t => ({
  ...t,
  label: clickLabel(t.name),
  delta: delta(t.clicks, t.previous),
})))

const deviceColors = { mobile: '#2563eb', desktop: '#16a34a', tablet: '#9333ea' }
const deviceSegments = computed(() => props.devices.map(d => ({
  key: d.device,
  label: d.device.charAt(0).toUpperCase() + d.device.slice(1),
  value: d.visitors,
  color: deviceColors[d.device],
})))
const deviceTotal = computed(() => props.devices.reduce((sum, d) => sum + d.visitors, 0))

// --- Link builder ---
const linkPages = { '/': 'Home', '/materials': 'Manifesto', '/events': 'Events', '/news': 'News', '/platforms': 'Platforms', '/about': 'About', '/contact': 'Contact' }
const linkSources = ['whatsapp', 'facebook', 'x', 'instagram', 'tiktok', 'sms', 'email', 'flyer']
const link = reactive({ page: '/', source: 'whatsapp', campaign: '' })
const copied = ref(false)
const trackedLink = computed(() => {
  const url = new URL(link.page, window.location.origin)
  url.searchParams.set('utm_source', link.source)
  const campaign = link.campaign.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
  if (campaign) url.searchParams.set('utm_campaign', campaign)
  return url.toString()
})
const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(trackedLink.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 1500)
  } catch {
    // Clipboard unavailable; the field is selectable
  }
}

// --- Supporters ---
const signupTiles = computed(() => {
  const t = props.periodTotals
  const s = props.overviewStats
  const tile = (key, label, href, context) => ({
    key, label, href, context,
    current: t[key]?.current ?? 0,
    delta: delta(t[key]?.current ?? 0, t[key]?.previous ?? 0),
  })
  return [
    tile('volunteers', 'Volunteers', '/admin/volunteers', `${s.volunteersPending || 0} awaiting approval`),
    tile('subscribers', 'Newsletter', '/admin/newsletter', `${s.subscribersConfirmed || 0} confirmed in total`),
    tile('contacts', 'Messages', '/admin/contacts', `${s.contactsNew || 0} unread`),
    tile('rsvps', 'Event RSVPs', '/admin/events', `${s.rsvpsConfirmed || 0} confirmed in total`),
  ]
})
const conversionRate = computed(() => {
  const signups = ['volunteers', 'subscribers', 'contacts', 'rsvps'].reduce((sum, k) => sum + (props.periodTotals[k]?.current || 0), 0)
  const visitors = props.trafficSummary.visitors || 0
  return visitors ? Math.min(100, Math.round((signups / visitors) * 1000) / 10) : 0
})

const volunteerSegments = computed(() => [
  { key: 'pending', label: 'Pending', value: props.overviewStats.volunteersPending || 0, color: '#f59e0b', href: '/admin/volunteers?status=pending' },
  { key: 'approved', label: 'Approved', value: props.overviewStats.volunteersApproved || 0, color: '#2563eb', href: '/admin/volunteers?status=approved' },
  { key: 'active', label: 'Active', value: props.overviewStats.volunteersActive || 0, color: '#16a34a', href: '/admin/volunteers?status=active' },
  { key: 'inactive', label: 'Inactive', value: props.overviewStats.volunteersInactive || 0, color: '#9ca3af', href: '/admin/volunteers?status=inactive' },
])

const newsletterTotal = computed(() =>
  (props.newsletterFunnel.pending || 0) + (props.newsletterFunnel.confirmed || 0) + (props.newsletterFunnel.unsubscribed || 0)
)
const newsletterSegments = computed(() => [
  { key: 'confirmed', label: 'Confirmed', value: props.newsletterFunnel.confirmed || 0, color: '#16a34a', href: '/admin/newsletter?status=confirmed' },
  { key: 'pending', label: 'Pending', value: props.newsletterFunnel.pending || 0, color: '#f59e0b', href: '/admin/newsletter?status=pending' },
  { key: 'unsubscribed', label: 'Unsubscribed', value: props.newsletterFunnel.unsubscribed || 0, color: '#9ca3af', href: '/admin/newsletter?status=unsubscribed' },
])

const lgaOptions = [
  { key: 'volunteers', label: 'Volunteers' },
  { key: 'rsvps', label: 'RSVPs' },
]
const lgaMode = ref('volunteers')
const showAllLga = ref(false)
const lgaRows = computed(() =>
  (props.lgaBreakdown[lgaMode.value] || []).map(r => ({
    label: r.lga,
    value: r.count,
    href: lgaMode.value === 'volunteers' && r.lga !== 'Not given' ? `/admin/volunteers?lga=${encodeURIComponent(r.lga)}` : null,
  }))
)
const lgaTotal = computed(() => lgaRows.value.reduce((sum, r) => sum + r.value, 0))

const skillLabel = (key) => key.replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase())
const skillRows = computed(() =>
  Object.entries(props.skillsInventory || {})
    .map(([key, count]) => ({ label: skillLabel(key), value: count }))
    .sort((a, b) => b.value - a.value)
)

const eventDate = (iso) => new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })

// --- Small local chart pieces ---

// Horizontal stacked bar with 2px surface gaps, plus a labelled legend (linked when href is given)
const BreakdownBar = (p) => {
  if (!p.total) return h('p', { class: 'text-sm text-gray-500 py-6 text-center' }, p.empty)
  const visible = p.segments.filter(s => s.value > 0)
  const legendItem = (s) => [
    h('span', { class: 'w-2.5 h-2.5 rounded-sm flex-shrink-0', style: { backgroundColor: s.color } }),
    h('span', { class: 'text-gray-600' }, s.label),
    h('span', { class: 'ml-auto font-semibold text-dark tabular-nums' }, `${s.value}`),
    h('span', { class: 'text-xs text-gray-400 w-10 text-right tabular-nums' }, `${pct(s.value, p.total)}%`),
  ]
  return h('div', [
    h('div', { class: 'flex h-4 w-full rounded overflow-hidden gap-[2px] bg-white', role: 'img', 'aria-label': p.segments.map(s => `${s.label} ${s.value}`).join(', ') },
      visible.map(s => h('div', { key: s.key, style: { width: `${(s.value / p.total) * 100}%`, backgroundColor: s.color }, title: `${s.label}: ${s.value}` }))),
    h('ul', { class: 'grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 mt-4 text-sm' },
      p.segments.map(s => h('li', { key: s.key },
        s.href
          ? h(Link, { href: s.href, class: 'flex items-center gap-2 hover:text-primary' }, () => legendItem(s))
          : h('div', { class: 'flex items-center gap-2' }, legendItem(s))))),
  ])
}
BreakdownBar.props = ['segments', 'total', 'empty']

// Ranked horizontal bars (single series: one hue; value, share and extra detail in text)
const RankedBars = (p) => {
  const rows = p.limit ? p.rows.slice(0, p.limit) : p.rows
  if (!rows.length) return h('p', { class: 'text-sm text-gray-500 py-6 text-center' }, p.empty)
  const max = Math.max(1, ...rows.map(r => r.value))
  return h('ul', { class: 'space-y-2.5' }, rows.map(r => {
    const inner = [
      h('div', { class: 'flex justify-between items-baseline gap-2 text-sm mb-1' }, [
        h('span', { class: 'min-w-0 truncate' }, [
          h('span', { class: 'text-dark' }, r.label),
          r.sub ? h('span', { class: 'text-xs text-gray-400 ml-1.5' }, r.sub) : null,
        ]),
        h('span', { class: 'text-gray-600 tabular-nums whitespace-nowrap' }, [
          h('span', { class: 'font-semibold text-dark' }, `${r.value}`),
          r.extra ? h('span', { class: 'text-xs text-gray-400 ml-1.5' }, r.extra) : null,
          p.total ? h('span', { class: 'text-xs text-gray-400 ml-1.5' }, `${pct(r.value, p.total)}%${p.percentLabel ? ` ${p.percentLabel}` : ''}`) : null,
        ]),
      ]),
      h('div', { class: 'w-full h-2 bg-gray-100 rounded overflow-hidden' },
        h('div', { class: 'h-full bg-blue-600 rounded', style: { width: `${(r.value / max) * 100}%` } })),
    ]
    if (!r.href) return h('li', { key: r.label + (r.sub || '') }, inner)
    // Public pages open in a new tab; admin links navigate in place
    return h('li', { key: r.label + (r.sub || '') }, p.external
      ? h('a', { href: r.href, target: '_blank', rel: 'noopener', class: 'block hover:opacity-80' }, inner)
      : h(Link, { href: r.href, class: 'block hover:opacity-80' }, () => inner))
  }))
}
RankedBars.props = ['rows', 'limit', 'total', 'percentLabel', 'empty', 'external']
</script>
