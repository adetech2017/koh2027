<template>
  <AppLayout>
    <Head title="Contact" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Get in Touch</h1>
          <p class="text-xl text-gray-200">Questions, ideas or concerns? We read every message.</p>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
          <!-- Details -->
          <div class="lg:col-span-2 space-y-8">
            <div>
              <h2 class="text-2xl font-bold text-dark mb-6">Contact information</h2>
              <dl class="space-y-5">
                <div class="flex gap-3">
                  <MapPinIcon class="w-6 h-6 text-primary flex-shrink-0" aria-hidden="true" />
                  <div>
                    <dt class="font-semibold text-dark">Address</dt>
                    <dd class="text-body">Lagos State, Nigeria</dd>
                  </div>
                </div>
                <div v-if="campaign.email" class="flex gap-3">
                  <EnvelopeIcon class="w-6 h-6 text-primary flex-shrink-0" aria-hidden="true" />
                  <div>
                    <dt class="font-semibold text-dark">Email</dt>
                    <dd><a :href="`mailto:${campaign.email}`" class="text-primary hover:underline break-all">{{ campaign.email }}</a></dd>
                  </div>
                </div>
                <div v-if="campaign.phone" class="flex gap-3">
                  <PhoneIcon class="w-6 h-6 text-primary flex-shrink-0" aria-hidden="true" />
                  <div>
                    <dt class="font-semibold text-dark">Phone</dt>
                    <dd><a :href="`tel:${campaign.phone.replace(/[^+\d]/g, '')}`" class="text-primary hover:underline">{{ campaign.phone }}</a></dd>
                  </div>
                </div>
              </dl>
            </div>
            <div>
              <h2 class="font-semibold text-dark mb-3">Follow us</h2>
              <ul class="flex flex-wrap gap-3">
                <li v-for="s in socials" :key="s.name">
                  <a :href="s.url" target="_blank" rel="noopener" class="inline-block px-4 py-2 border border-gray-300 rounded-full text-sm text-dark hover:border-primary hover:text-primary transition-colors">{{ s.name }}</a>
                </li>
              </ul>
            </div>
            <div class="bg-light-gray rounded-lg p-5 text-sm text-body">
              Want to help? <a :href="campaign.volunteerUrl" target="_blank" rel="noopener" class="font-semibold text-primary hover:underline">Sign up to volunteer</a>.
            </div>
          </div>

          <!-- Form -->
          <div class="lg:col-span-3">
            <div v-if="sent" class="bg-green-50 border border-green-200 rounded-lg p-8" role="status">
              <CheckCircleIcon class="w-10 h-10 text-success mb-3" aria-hidden="true" />
              <h2 class="text-2xl font-bold text-dark mb-2">Message sent</h2>
              <p class="text-body">{{ sent }}</p>
              <button type="button" class="mt-5 text-sm font-semibold text-primary hover:underline" @click="sent = ''">Send another message</button>
            </div>
            <form v-else class="bg-light-gray rounded-lg p-6 md:p-8 space-y-5" novalidate @submit.prevent="submitForm">
              <h2 class="text-2xl font-bold text-dark">Send us a message</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div v-for="field in fields.slice(0, 4)" :key="field.key">
                  <label :for="`contact-${field.key}`" class="block text-dark font-medium mb-1">
                    {{ field.label }} <span v-if="field.optional" class="text-gray-500 font-normal">(optional)</span>
                  </label>
                  <input
                    :id="`contact-${field.key}`"
                    v-model="form[field.key]"
                    :type="field.type"
                    :autocomplete="field.autocomplete"
                    :aria-invalid="!!form.errors[field.key]"
                    :class="inputClass(field.key)"
                  />
                  <p v-if="form.errors[field.key]" class="text-sm text-red-600 mt-1">{{ friendly(field.key) }}</p>
                </div>
              </div>
              <div>
                <div class="flex justify-between items-baseline mb-1">
                  <label for="contact-message" class="block text-dark font-medium">Message</label>
                  <span class="text-xs" :class="form.message.length > 2000 ? 'text-red-600' : 'text-gray-500'">{{ form.message.length }}/2000</span>
                </div>
                <textarea
                  id="contact-message"
                  v-model="form.message"
                  rows="7"
                  :aria-invalid="!!form.errors.message"
                  :class="inputClass('message')"
                />
                <p v-if="form.errors.message" class="text-sm text-red-600 mt-1">{{ friendly('message') }}</p>
              </div>
              <button type="submit" class="btn-primary w-full sm:w-auto" :disabled="form.processing">
                {{ form.processing ? 'Sending…' : 'Send message' }}
              </button>
              <p class="text-xs text-gray-500">We'll only use your details to reply. <Link href="/privacy" class="underline">Privacy policy</Link></p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { CheckCircleIcon, EnvelopeIcon, MapPinIcon, PhoneIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'

const page = usePage()
const campaign = computed(() => page.props.campaign || {})

const socials = [
  { name: 'Facebook', url: 'https://facebook.com/KadriObafemiHamzat' },
  { name: 'X', url: 'https://x.com/OfficialKOH2027' },
  { name: 'Instagram', url: 'https://instagram.com/OfficialKOH2027' },
  { name: 'YouTube', url: 'https://youtube.com/@KadriObafemiHamzatOfficial' },
]

// Mirrors ContactFormRequest
const fields = [
  { key: 'name', label: 'Full name', type: 'text', autocomplete: 'name' },
  { key: 'email', label: 'Email', type: 'email', autocomplete: 'email' },
  { key: 'phone', label: 'Phone', type: 'tel', autocomplete: 'tel', optional: true },
  { key: 'subject', label: 'Subject', type: 'text', autocomplete: 'off' },
]

// Other pages can pre-fill the subject, e.g. /contact?subject=Merchandise%20enquiry
const presetSubject = new URLSearchParams(page.url.split('?')[1] || '').get('subject') || ''
const form = useForm({ name: '', email: '', phone: '', subject: presetSubject.slice(0, 150), message: '' })
const sent = ref('')

const inputClass = (key) => [
  'w-full px-4 py-3 bg-white text-dark border-2 rounded focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary',
  form.errors[key] ? 'border-red-400' : 'border-gray-300',
]

const friendly = (key) => {
  const error = form.errors[key] || ''
  if (key === 'email' && /valid email/i.test(error)) return 'Please check your email address.'
  if (key === 'phone' && /format/i.test(error)) return 'Please enter a valid phone number, e.g. 0803 123 4567.'
  if (key === 'message' && /at least/i.test(error)) return 'Please write a little more (at least 10 characters).'
  return error
}

const submitForm = () => {
  form.post('/contact', {
    preserveScroll: true,
    onSuccess: () => {
      if (Object.keys(form.errors).length) return
      sent.value = page.props.flash?.success || "Thanks, we've received your message and will reply within 48 hours."
      form.reset()
    },
  })
}
</script>
