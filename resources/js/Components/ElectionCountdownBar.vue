<template>
  <aside v-if="elections.length" class="bg-primary-dark text-white" aria-label="Election day countdown">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-center gap-x-8 gap-y-1 text-sm">
      <p v-for="election in elections" :key="election.key" class="flex items-center justify-center gap-2">
        <span class="font-bold uppercase tracking-wide text-gold">{{ election.label }}</span>
        <span v-if="election.isToday" class="font-extrabold">is today, go vote!</span>
        <template v-else>
          <span class="font-extrabold tabular-nums" aria-hidden="true">
            {{ election.days }}d {{ pad(election.hours) }}h {{ pad(election.minutes) }}m {{ pad(election.seconds) }}s
          </span>
          <span class="sr-only">in {{ election.days }} days, on {{ election.date }}</span>
        </template>
      </p>
    </div>
  </aside>
</template>

<script setup>
import { useElectionCountdown, pad } from '@/Composables/useElectionCountdown'

const { elections } = useElectionCountdown()
</script>
