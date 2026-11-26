<template>
  <section v-if="elections.length" class="bg-primary py-14 md:py-20" aria-labelledby="countdown-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-10">
        <span class="inline-block text-gold text-xs md:text-sm font-semibold uppercase tracking-widest border-b-2 border-gold pb-2 mb-4">Election Day 2027</span>
        <h2 id="countdown-heading" class="text-3xl md:text-5xl font-extrabold text-white">The Countdown Is On</h2>
      </div>

      <div class="grid grid-cols-1 gap-6 md:gap-8" :class="{ 'lg:grid-cols-2': elections.length > 1 }">
        <div
          v-for="election in elections"
          :key="election.key"
          class="rounded-xl bg-white/5 border border-white/15 border-t-4 border-t-gold p-6 md:p-8 text-center"
        >
          <h3 class="text-xl md:text-2xl font-extrabold uppercase tracking-wide text-gold">{{ election.label }}</h3>
          <p class="text-gray-200 mt-1 mb-6">{{ election.date }}</p>

          <p v-if="election.isToday" class="text-3xl md:text-5xl font-extrabold text-white py-4">
            Polls are open. Go vote!
          </p>
          <template v-else>
            <div class="grid grid-cols-4 gap-2 sm:gap-4" aria-hidden="true">
              <div v-for="unit in units(election)" :key="unit.label" class="rounded-lg bg-white py-3 sm:py-5">
                <span class="block text-4xl sm:text-5xl md:text-6xl font-black text-primary tabular-nums leading-none">{{ unit.value }}</span>
                <span class="block mt-2 text-[0.65rem] sm:text-xs font-bold uppercase tracking-widest text-body">{{ unit.label }}</span>
              </div>
            </div>
            <p class="sr-only">{{ election.days }} days to go until the {{ election.label }} on {{ election.date }}.</p>
          </template>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useElectionCountdown, pad } from '@/Composables/useElectionCountdown'

const { elections } = useElectionCountdown()

const units = (election) => [
  { label: 'Days', value: election.days },
  { label: 'Hours', value: pad(election.hours) },
  { label: 'Minutes', value: pad(election.minutes) },
  { label: 'Seconds', value: pad(election.seconds) },
]
</script>
