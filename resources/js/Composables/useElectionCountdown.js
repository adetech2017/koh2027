import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

// One clock shared by every countdown on the page, ticking once a second while any is mounted
const now = ref(Date.now())
let timer = null
let users = 0

const dateFormat = new Intl.DateTimeFormat('en-NG', {
  weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Africa/Lagos',
})

export function useElectionCountdown() {
  const page = usePage()

  onMounted(() => {
    now.value = Date.now()
    if (users++ === 0) timer = setInterval(() => { now.value = Date.now() }, 1000)
  })
  onBeforeUnmount(() => {
    if (--users === 0) clearInterval(timer)
  })

  // The server already drops elections whose day is over; this also drops them if the page stays open past midnight
  const elections = computed(() => (page.props.elections ?? [])
    .map((election) => {
      const startsAt = Date.parse(election.starts_at)
      const endsAt = Date.parse(election.ends_at)
      const left = Math.max(0, startsAt - now.value)
      return {
        ...election,
        date: dateFormat.format(startsAt),
        isToday: now.value >= startsAt,
        isOver: now.value > endsAt,
        days: Math.floor(left / 86400000),
        hours: Math.floor(left / 3600000) % 24,
        minutes: Math.floor(left / 60000) % 60,
        seconds: Math.floor(left / 1000) % 60,
      }
    })
    .filter((election) => !election.isOver))

  return { elections }
}

export const pad = (n) => String(n).padStart(2, '0')
