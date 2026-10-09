<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Delivery problems come first: there's no point composing if nothing will be delivered -->
      <div v-if="deliveryWarnings.length" class="space-y-2">
        <div v-for="w in deliveryWarnings" :key="w.title" class="rounded-lg border p-4 text-sm" :class="w.tone">
          <p class="font-semibold">{{ w.title }}</p>
          <p class="mt-0.5">{{ w.text }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <!-- Compose -->
        <form class="xl:col-span-3 space-y-6" @submit.prevent="review">
          <div v-if="Object.keys(form.errors).length" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
            <p class="font-semibold">Please fix the highlighted fields.</p>
          </div>

          <!-- Audience -->
          <section class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-dark mb-3">Who should get it?</h3>
            <fieldset class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <legend class="sr-only">Audience</legend>
              <label
                v-for="option in audienceOptions"
                :key="option.value"
                class="flex flex-col gap-1 p-4 border rounded-lg cursor-pointer transition-colors"
                :class="form.audience === option.value ? 'border-primary ring-1 ring-primary bg-blue-50/50' : 'border-gray-200 hover:border-gray-400'"
              >
                <span class="flex items-center gap-2">
                  <input v-model="form.audience" type="radio" :value="option.value" class="text-primary focus:ring-primary" />
                  <span class="font-medium text-dark">{{ option.label }}</span>
                </span>
                <span class="text-2xl font-bold text-dark tabular-nums pl-6">{{ audienceCounts[option.value] || 0 }}</span>
                <span class="text-xs text-gray-500 pl-6">{{ option.help }}</span>
              </label>
            </fieldset>
            <p v-if="form.errors.audience" class="text-red-600 text-sm mt-2">{{ form.errors.audience }}</p>
          </section>

          <!-- Message -->
          <section class="bg-white rounded-lg shadow p-6 space-y-5">
            <h3 class="text-lg font-semibold text-dark">Message</h3>
            <div>
              <div class="flex justify-between items-baseline mb-1">
                <label for="be-subject" class="block text-sm font-medium text-dark">Subject <span class="text-red-600">*</span></label>
                <span class="text-xs" :class="form.subject.length > 150 ? 'text-red-600' : 'text-gray-400'">{{ form.subject.length }}/150</span>
              </div>
              <input id="be-subject" ref="subjectEl" v-model="form.subject" type="text" placeholder="e.g. Join us at the Ikeja town hall this Saturday" :class="inputClass('subject')" />
              <p v-if="form.errors.subject" class="text-red-600 text-sm mt-1">{{ form.errors.subject }}</p>
              <p v-else-if="form.subject.length > 60" class="text-xs text-gray-500 mt-1">Phones show about 40–60 characters, so put the key point first.</p>
            </div>

            <div>
              <div class="flex flex-wrap justify-between items-center gap-2 mb-1">
                <label for="be-body" class="block text-sm font-medium text-dark">Message <span class="text-red-600">*</span></label>
                <div class="flex gap-1">
                  <button type="button" class="px-2 py-1 text-xs border border-gray-300 rounded hover:border-primary hover:text-primary font-bold" title="Bold" @click="wrap('**', '**')">B</button>
                  <button type="button" class="px-2 py-1 text-xs border border-gray-300 rounded hover:border-primary hover:text-primary" title="Insert a link" @click="wrap('[', '](https://)')">Link</button>
                  <button type="button" class="px-2 py-1 text-xs border border-gray-300 rounded hover:border-primary hover:text-primary" title="Insert the recipient's first name" @click="insert('{first_name}')">+ First name</button>
                </div>
              </div>
              <textarea
                id="be-body"
                ref="bodyEl"
                v-model="form.body"
                rows="12"
                placeholder="Write your message. Leave a blank line between paragraphs."
                :class="inputClass('body')"
              />
              <p v-if="form.errors.body" class="text-red-600 text-sm mt-1">{{ form.errors.body }}</p>
              <p v-else class="text-xs text-gray-500 mt-1">
                Each email starts with "Hi [first name]," automatically. Use **bold** and [link text](https://…) for formatting.
                <span v-if="draftSavedAt" class="text-gray-400"> · Draft saved on this device.</span>
              </p>
            </div>

            <div>
              <p class="block text-sm font-medium text-dark mb-1">Button <span class="text-gray-400 font-normal">(optional)</span></p>
              <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <div class="sm:col-span-2">
                  <label for="be-cta-text" class="sr-only">Button text</label>
                  <input id="be-cta-text" v-model="form.cta_text" type="text" maxlength="50" placeholder="Button text, e.g. RSVP now" :class="inputClass('cta_text')" />
                  <p v-if="form.errors.cta_text" class="text-red-600 text-sm mt-1">{{ form.errors.cta_text }}</p>
                </div>
                <div class="sm:col-span-3">
                  <label for="be-cta-url" class="sr-only">Button link</label>
                  <input id="be-cta-url" v-model="form.cta_url" type="url" placeholder="https://…" :class="inputClass('cta_url')" />
                  <p v-if="form.errors.cta_url" class="text-red-600 text-sm mt-1">{{ form.errors.cta_url }}</p>
                </div>
              </div>
            </div>
          </section>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-3 sm:justify-between sm:items-center">
            <button v-if="hasDraft" type="button" class="text-sm text-gray-500 hover:text-red-600 self-start" @click="discardDraft">Discard draft</button>
            <span v-else />
            <div class="flex flex-col-reverse sm:flex-row gap-3">
              <button
                type="button"
                :disabled="sendingTest || !canSubmit"
                class="px-5 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium transition-colors disabled:opacity-50"
                @click="sendTest"
              >
                {{ sendingTest ? 'Sending test…' : `Send test to ${senderEmail}` }}
              </button>
              <button
                type="submit"
                :disabled="!canSubmit || !recipientCount"
                class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-semibold transition-colors disabled:opacity-50"
              >
                Review & send to {{ recipientCount }}
              </button>
            </div>
          </div>
        </form>

        <!-- Preview & history -->
        <div class="xl:col-span-2 space-y-6">
          <section class="xl:sticky xl:top-6 space-y-2">
            <p class="text-sm font-medium text-gray-600">Preview</p>
            <div class="bg-gray-100 rounded-lg p-3 sm:p-4">
              <div class="bg-white rounded border text-sm">
                <div class="px-4 py-3 border-b text-xs text-gray-500 space-y-0.5">
                  <p><span class="text-gray-400">From:</span> {{ delivery.fromName }} &lt;{{ delivery.fromAddress }}&gt;</p>
                  <p class="text-dark font-semibold text-sm break-words">{{ previewText(form.subject) || '(No subject)' }}</p>
                </div>
                <div class="px-4 py-4 space-y-3 text-gray-700">
                  <p>Hi {{ sampleName }},</p>
                  <div class="space-y-3 break-words" v-html="previewHtml" />
                  <p v-if="form.cta_text && form.cta_url" class="text-center py-1">
                    <span class="inline-block px-4 py-2 rounded bg-gray-800 text-white text-xs font-semibold">{{ form.cta_text }}</span>
                  </p>
                  <p>Thanks,<br />{{ delivery.fromName }}</p>
                  <p class="pt-3 border-t text-[11px] text-gray-400">
                    {{ form.audience === 'volunteers' ? "You're receiving this because you volunteered with the campaign." : "You're receiving this because you subscribed to campaign updates." }}
                    <span v-if="form.audience !== 'volunteers'" class="underline">Unsubscribe</span>
                  </p>
                </div>
              </div>
            </div>
            <p class="text-xs text-gray-500">Shown with the sample name "{{ sampleName }}". Recipients without a name get "Hello,".</p>
          </section>

          <section class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-dark mb-3">Sent campaigns</h3>
            <p v-if="!recentCampaigns.length" class="text-sm text-gray-500">Nothing sent yet.</p>
            <ul v-else class="divide-y">
              <li v-for="c in recentCampaigns" :key="c.id" class="py-3">
                <button type="button" class="text-left w-full group" @click="reuse(c)">
                  <p class="text-sm font-medium text-dark group-hover:text-primary break-words">{{ c.subject }}</p>
                  <p class="text-xs text-gray-500">
                    {{ audienceLabel(c.audience) }} · {{ c.recipients_count }} recipient{{ c.recipients_count !== 1 ? 's' : '' }} · {{ formatDate(c.created_at) }}<template v-if="c.sender"> · {{ c.sender.name }}</template>
                  </p>
                  <p class="text-xs text-primary opacity-0 group-hover:opacity-100">Use as a starting point</p>
                </button>
              </li>
            </ul>
          </section>
        </div>
      </div>
    </div>

    <!-- Confirm -->
    <template #dialogs>
      <div v-if="confirming" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-title" @keydown.esc="confirming = false">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
          <h3 id="confirm-title" class="text-xl font-bold text-dark mb-4">Send this email?</h3>
          <dl class="text-sm space-y-2 mb-5 bg-gray-50 rounded-lg p-4">
            <div class="flex gap-2"><dt class="text-gray-500 w-20 flex-shrink-0">To</dt><dd class="font-semibold text-dark">{{ recipientCount }} {{ audienceLabel(form.audience).toLowerCase() }}</dd></div>
            <div class="flex gap-2"><dt class="text-gray-500 w-20 flex-shrink-0">Subject</dt><dd class="text-dark break-words">{{ form.subject }}</dd></div>
            <div v-if="form.cta_text" class="flex gap-2"><dt class="text-gray-500 w-20 flex-shrink-0">Button</dt><dd class="text-dark break-all">{{ form.cta_text }} → {{ form.cta_url }}</dd></div>
          </dl>
          <p class="text-sm text-gray-600 mb-5">Emails can't be recalled once sent. {{ testSent ? '' : 'Tip: send yourself a test first.' }}</p>
          <div class="flex gap-3">
            <button
              ref="confirmBtn"
              type="button"
              :disabled="form.processing"
              class="flex-1 bg-primary hover:bg-primary-dark text-white font-semibold py-2 rounded-lg transition-colors disabled:opacity-50"
              @click="send"
            >
              {{ form.processing ? 'Queuing…' : `Send to ${recipientCount}` }}
            </button>
            <button
              type="button"
              :disabled="form.processing"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-dark font-semibold py-2 rounded-lg transition-colors disabled:opacity-50"
              @click="confirming = false"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </template>
  </AdminLayout>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  audienceCounts: { type: Object, default: () => ({}) },
  recentCampaigns: { type: Array, default: () => [] },
  delivery: { type: Object, default: () => ({}) },
  senderEmail: { type: String, default: '' },
})

const page = usePage()

const audienceOptions = [
  { value: 'subscribers', label: 'Newsletter', help: 'Confirmed subscribers' },
  { value: 'volunteers', label: 'Volunteers', help: 'Approved and active' },
  { value: 'everyone', label: 'Everyone', help: 'Both, one copy each' },
]
const audienceLabel = (value) => ({ subscribers: 'Newsletter subscribers', volunteers: 'Volunteers', everyone: 'Everyone' }[value] || value)

const DRAFT_KEY = 'koh-bulk-email-draft'
const loadDraft = () => {
  try { return JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null') } catch { return null }
}
const draft = loadDraft()

const form = useForm({
  audience: draft?.audience || 'subscribers',
  subject: draft?.subject || '',
  body: draft?.body || '',
  cta_text: draft?.cta_text || '',
  cta_url: draft?.cta_url || '',
})

const recipientCount = computed(() => props.audienceCounts[form.audience] || 0)
const canSubmit = computed(() => form.subject.trim() && form.body.trim() && !form.processing)
const sampleName = computed(() => (page.props.auth?.user?.name || 'Ada').split(' ')[0])

// --- Draft autosave (this device only) ---
const draftSavedAt = ref(null)
const hasDraft = computed(() => !!(form.subject || form.body || form.cta_text || form.cta_url))
let draftTimer
watch(() => form.data(), (data) => {
  clearTimeout(draftTimer)
  draftTimer = setTimeout(() => {
    try {
      if (data.subject || data.body) {
        localStorage.setItem(DRAFT_KEY, JSON.stringify(data))
        draftSavedAt.value = Date.now()
      } else {
        localStorage.removeItem(DRAFT_KEY)
        draftSavedAt.value = null
      }
    } catch {
      // Storage unavailable (private mode); drafts just aren't kept
    }
  }, 500)
}, { deep: true })

const clearDraft = () => {
  try { localStorage.removeItem(DRAFT_KEY) } catch {}
  draftSavedAt.value = null
}
// form.reset() would go back to the restored draft, so clear the fields explicitly
const clearForm = () => {
  form.subject = ''
  form.body = ''
  form.cta_text = ''
  form.cta_url = ''
  form.clearErrors()
  clearDraft()
}
const discardDraft = clearForm

// --- Formatting helpers ---
const bodyEl = ref(null)
const subjectEl = ref(null)
const wrap = (before, after) => {
  const el = bodyEl.value
  const start = el.selectionStart
  const end = el.selectionEnd
  const selected = form.body.slice(start, end) || (before === '**' ? 'bold text' : 'link text')
  form.body = form.body.slice(0, start) + before + selected + after + form.body.slice(end)
  nextTick(() => {
    el.focus()
    el.setSelectionRange(start + before.length, start + before.length + selected.length)
  })
}
const insert = (text) => {
  const el = bodyEl.value
  const pos = el.selectionStart ?? form.body.length
  form.body = form.body.slice(0, pos) + text + form.body.slice(pos)
  nextTick(() => { el.focus(); el.setSelectionRange(pos + text.length, pos + text.length) })
}

// --- Preview: same rules as the email (escaped text, **bold**, [links](…), line breaks) ---
const escapeHtml = (s) => s.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]))
const previewText = (text) => (text || '').replaceAll('{first_name}', sampleName.value)
const previewHtml = computed(() => {
  const body = previewText(form.body).trim()
  if (!body) return '<p class="text-gray-400">Your message will appear here.</p>'
  return body.split(/\n{2,}/).map(paragraph => {
    const html = escapeHtml(paragraph)
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g, '<a class="text-primary underline">$1</a>')
      .replace(/\n/g, '<br>')
    return `<p>${html}</p>`
  }).join('')
})

// --- Delivery health ---
const deliveryWarnings = computed(() => {
  const d = props.delivery
  const warnings = []
  if (d.mailer === 'log' || d.mailer === 'array') {
    warnings.push({ tone: 'bg-amber-50 border-amber-200 text-amber-900', title: 'Emails are not actually being sent', text: `The mail setting is "${d.mailer}", so emails are written to the server log instead of delivered. Fine for testing; set MAIL_MAILER to your email provider before a real campaign.` })
  }
  if (d.workerStalled) {
    warnings.push({ tone: 'bg-red-50 border-red-200 text-red-900', title: 'Emails are stuck in the queue', text: `${d.pendingJobs} queued job${d.pendingJobs !== 1 ? 's are' : ' is'} waiting and nothing is processing them. Ask your developer or host to run the queue worker (php artisan queue:work).` })
  } else if (d.pendingJobs) {
    warnings.push({ tone: 'bg-blue-50 border-blue-200 text-blue-900', title: 'Sending in progress', text: `${d.pendingJobs} email${d.pendingJobs !== 1 ? 's are' : ' is'} still in the queue.` })
  }
  if (d.failedJobs) {
    warnings.push({ tone: 'bg-red-50 border-red-200 text-red-900', title: `${d.failedJobs} email${d.failedJobs !== 1 ? 's' : ''} failed to send`, text: 'Check the mail settings with your developer; failed emails can be retried with php artisan queue:retry all.' })
  }
  return warnings
})

// --- Actions ---
const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const sendingTest = ref(false)
const testSent = ref(false)
const sendTest = () => {
  sendingTest.value = true
  form.post('/admin/bulk-email/test', {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => { testSent.value = true },
    onFinish: () => { sendingTest.value = false },
  })
}

const confirming = ref(false)
const confirmBtn = ref(null)
const review = () => {
  form.clearErrors()
  if (!form.subject.trim()) form.setError('subject', 'Add a subject.')
  if (!form.body.trim()) form.setError('body', 'Write a message.')
  if (Boolean(form.cta_text) !== Boolean(form.cta_url)) form.setError(form.cta_text ? 'cta_url' : 'cta_text', 'Fill in both the button text and its link, or neither.')
  if (Object.keys(form.errors).length) return
  confirming.value = true
  nextTick(() => confirmBtn.value?.focus())
}

const send = () => {
  form.post('/admin/bulk-email', {
    preserveScroll: true,
    onSuccess: () => {
      // Only clear when the server accepted it (validation errors keep the form)
      if (!Object.keys(form.errors).length) {
        clearForm()
        testSent.value = false
      }
    },
    onFinish: () => { confirming.value = false },
  })
}

const reuse = (campaign) => {
  form.audience = campaign.audience
  form.subject = campaign.subject
  form.body = campaign.body
  form.cta_text = campaign.cta_text || ''
  form.cta_url = campaign.cta_url || ''
  form.clearErrors()
  window.scrollTo({ top: 0, behavior: 'smooth' })
  nextTick(() => subjectEl.value?.focus())
}

const formatDate = (iso) => new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' })
</script>
