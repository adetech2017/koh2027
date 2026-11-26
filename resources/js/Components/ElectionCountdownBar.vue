<template>
  <aside v-if="elections.length" class="ticker bg-primary-dark text-white overflow-hidden" aria-label="Election day countdown">
    <!-- Screen readers get one plain sentence per election instead of the moving copies -->
    <ul class="sr-only">
      <li v-for="election in elections" :key="election.key">
        {{ election.label }}: {{ election.isToday ? 'today, go vote!' : `in ${election.days} days, on ${election.date}` }}
      </li>
    </ul>

    <!-- Two identical halves; sliding left by one half loops seamlessly -->
    <div class="ticker-track flex w-max py-2 text-sm" aria-hidden="true">
      <div v-for="half in 2" :key="half" class="flex">
        <span v-for="(item, i) in items" :key="i" class="flex items-center gap-2 px-6 whitespace-nowrap">
          <span class="font-bold uppercase tracking-wide text-gold">{{ item.label }}</span>
          <span v-if="item.isToday" class="font-extrabold">is today, go vote!</span>
          <span v-else class="font-extrabold tabular-nums">
            {{ item.days }}d {{ pad(item.hours) }}h {{ pad(item.minutes) }}m {{ pad(item.seconds) }}s
          </span>
          <span class="ml-10 text-gold/60">★</span>
        </span>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useElectionCountdown, pad } from '@/Composables/useElectionCountdown'

const { elections } = useElectionCountdown()

// Repeat the elections so each half is wider than even a large screen
const items = computed(() => Array.from({ length: 4 }, () => elections.value).flat())
</script>

<style scoped>
.ticker-track {
  animation: ticker 40s linear infinite;
}
.ticker:hover .ticker-track {
  animation-play-state: paused;
}
@keyframes ticker {
  to { transform: translateX(-50%); }
}
@media (prefers-reduced-motion: reduce) {
  .ticker-track {
    animation: none;
  }
}
</style>
