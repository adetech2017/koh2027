<template>
  <div>
    <!-- Legend (always shown for multiple series), with totals; doubles as the toggle for the table view -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
      <ul class="flex flex-wrap gap-x-5 gap-y-1 text-sm">
        <li v-for="s in series" :key="s.key" class="flex items-center gap-2">
          <span class="w-3 h-0.5 rounded" :style="{ backgroundColor: s.color, height: '2px' }" />
          <span class="text-gray-600">{{ s.label }}</span>
          <span class="font-semibold text-dark">{{ total(s.key) }}</span>
        </li>
      </ul>
      <button type="button" class="text-xs text-primary hover:text-primary-dark font-medium" @click="showTable = !showTable">
        {{ showTable ? 'Show chart' : 'Show as table' }}
      </button>
    </div>

    <!-- Table view -->
    <div v-if="showTable" class="max-h-72 overflow-auto border rounded-lg">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 sticky top-0">
          <tr>
            <th class="text-left px-3 py-2 font-medium text-gray-600">Date</th>
            <th v-for="s in series" :key="s.key" class="text-right px-3 py-2 font-medium text-gray-600">{{ s.label }}</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="row in [...data].reverse()" :key="row.date">
            <td class="px-3 py-1.5 text-gray-700">{{ longDate(row.date) }}</td>
            <td v-for="s in series" :key="s.key" class="px-3 py-1.5 text-right tabular-nums text-dark">{{ row[s.key] || 0 }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Chart -->
    <div v-else ref="wrap" class="relative select-none" :style="{ height: `${height}px` }" @mouseleave="hover = null">
      <svg v-if="width" :width="width" :height="height" class="block" role="img" :aria-label="ariaLabel">
        <!-- Recessive grid + y labels -->
        <g v-for="tick in yTicks" :key="tick">
          <line :x1="pad.left" :x2="width - pad.right" :y1="y(tick)" :y2="y(tick)" stroke="#e5e7eb" stroke-width="1" />
          <text :x="pad.left - 8" :y="y(tick)" text-anchor="end" dominant-baseline="middle" class="fill-gray-400 text-[11px]">{{ tick }}</text>
        </g>
        <!-- X labels: a handful, never all of them -->
        <text
          v-for="i in xLabelIndexes"
          :key="`x${i}`"
          :x="x(i)"
          :y="height - 6"
          :text-anchor="i === 0 ? 'start' : (i === data.length - 1 ? 'end' : 'middle')"
          class="fill-gray-400 text-[11px]"
        >{{ shortDate(data[i].date) }}</text>

        <!-- Crosshair -->
        <line v-if="hover !== null" :x1="x(hover)" :x2="x(hover)" :y1="pad.top" :y2="height - pad.bottom" stroke="#9ca3af" stroke-width="1" stroke-dasharray="3 3" />

        <!-- Lines -->
        <path v-for="s in series" :key="s.key" :d="linePath(s.key)" fill="none" :stroke="s.color" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

        <!-- Hover markers with a surface ring -->
        <template v-if="hover !== null">
          <circle v-for="s in series" :key="`m${s.key}`" :cx="x(hover)" :cy="y(data[hover][s.key] || 0)" r="4.5" :fill="s.color" stroke="#ffffff" stroke-width="2" />
        </template>

        <!-- Hit area: the whole plot, mapped to the nearest day -->
        <rect :x="pad.left" :y="pad.top" :width="plotWidth" :height="plotHeight" fill="transparent" @mousemove="onMove" @touchstart.passive="onTouch" @touchmove.passive="onTouch" />
      </svg>

      <!-- Tooltip -->
      <div
        v-if="hover !== null"
        class="absolute pointer-events-none bg-white border border-gray-200 shadow-lg rounded-lg px-3 py-2 text-xs z-10"
        :style="tooltipStyle"
      >
        <p class="font-semibold text-dark mb-1">{{ longDate(data[hover].date) }}</p>
        <p v-for="s in series" :key="s.key" class="flex items-center gap-2 text-gray-600">
          <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: s.color }" />
          {{ s.label }}
          <span class="ml-auto pl-3 font-semibold text-dark tabular-nums">{{ data[hover][s.key] || 0 }}</span>
        </p>
      </div>

      <p v-if="isEmpty" class="absolute inset-0 flex items-center justify-center text-sm text-gray-500">{{ emptyText }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch, nextTick } from 'vue'

const props = defineProps({
  data: { type: Array, required: true }, // [{ date: 'YYYY-MM-DD', [key]: number }]
  series: { type: Array, required: true }, // [{ key, label, color }]
  height: { type: Number, default: 260 },
  emptyText: { type: String, default: 'No data in this period' },
})

const pad = { top: 12, right: 12, bottom: 26, left: 36 }
const wrap = ref(null)
const width = ref(0)
const hover = ref(null)
const showTable = ref(false)

let observer
const observe = () => {
  observer?.disconnect()
  if (!wrap.value) return
  observer = new ResizeObserver(([entry]) => { width.value = Math.floor(entry.contentRect.width) })
  observer.observe(wrap.value)
}
onMounted(observe)
watch(showTable, async (table) => { if (!table) { await nextTick(); observe() } })
onBeforeUnmount(() => observer?.disconnect())

const plotWidth = computed(() => Math.max(0, width.value - pad.left - pad.right))
const plotHeight = computed(() => props.height - pad.top - pad.bottom)

const maxValue = computed(() => Math.max(1, ...props.data.flatMap(d => props.series.map(s => d[s.key] || 0))))
const isEmpty = computed(() => props.data.every(d => props.series.every(s => !d[s.key])))

// "Nice" integer ticks: 0 and up to 4 steps above
const yTicks = computed(() => {
  const raw = maxValue.value / 4
  const magnitude = 10 ** Math.floor(Math.log10(raw || 1))
  const step = Math.max(1, [1, 2, 5, 10].map(m => m * magnitude).find(s => s >= raw) || magnitude * 10)
  const ticks = []
  for (let v = 0; v <= maxValue.value + step - 1 && ticks.length < 6; v += step) ticks.push(v)
  return ticks
})
const yMax = computed(() => yTicks.value[yTicks.value.length - 1] || 1)

const x = (i) => pad.left + (props.data.length <= 1 ? plotWidth.value / 2 : (i / (props.data.length - 1)) * plotWidth.value)
const y = (v) => pad.top + plotHeight.value - (v / yMax.value) * plotHeight.value

const linePath = (key) => props.data.map((d, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(d[key] || 0).toFixed(1)}`).join('')

const xLabelIndexes = computed(() => {
  const n = props.data.length
  if (!n) return []
  const count = Math.min(n, Math.max(2, Math.floor(plotWidth.value / 80)))
  const idx = new Set()
  for (let k = 0; k < count; k++) idx.add(Math.round((k / (count - 1 || 1)) * (n - 1)))
  return [...idx]
})

const nearest = (clientX) => {
  const rect = wrap.value.getBoundingClientRect()
  const rel = (clientX - rect.left - pad.left) / (plotWidth.value || 1)
  return Math.min(props.data.length - 1, Math.max(0, Math.round(rel * (props.data.length - 1))))
}
const onMove = (e) => { hover.value = nearest(e.clientX) }
const onTouch = (e) => { if (e.touches[0]) hover.value = nearest(e.touches[0].clientX) }

const tooltipStyle = computed(() => {
  if (hover.value === null) return {}
  const left = x(hover.value)
  const flip = left > width.value / 2
  return { top: `${pad.top}px`, [flip ? 'right' : 'left']: `${flip ? width.value - left + 12 : left + 12}px` }
})

const total = (key) => props.data.reduce((sum, d) => sum + (d[key] || 0), 0)

// Dates are YYYY-MM-DD; parse as local to avoid a timezone shift
const parse = (date) => { const [yy, mm, dd] = date.split('-').map(Number); return new Date(yy, mm - 1, dd) }
const shortDate = (date) => parse(date).toLocaleDateString(undefined, { day: 'numeric', month: 'short' })
const longDate = (date) => parse(date).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })

const ariaLabel = computed(() =>
  `Daily figures from ${props.data[0] ? longDate(props.data[0].date) : ''} to ${props.data.at(-1) ? longDate(props.data.at(-1).date) : ''}: ` +
  props.series.map(s => `${s.label} ${total(s.key)}`).join(', ')
)
</script>
