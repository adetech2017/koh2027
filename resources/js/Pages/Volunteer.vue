<template>
  <AppLayout>
    <Head title="Volunteer" />
    <div class="min-h-screen bg-white">
      <div class="bg-primary text-white py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Volunteer</h1>
          <p class="text-xl text-gray-200">Give an hour, a skill or a ride. Every part of Lagos needs hands.</p>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
          <!-- Details -->
          <div class="lg:col-span-2 space-y-6">
            <h2 class="text-2xl font-bold text-dark">Join the team</h2>
            <p class="text-body">Tell us a little about yourself and where you are. The campaign team in your Local Government Area will contact you about:</p>
            <ul class="space-y-3">
              <li v-for="point in points" :key="point" class="flex gap-3 text-body">
                <CheckCircleIcon class="w-6 h-6 text-success flex-shrink-0" aria-hidden="true" />
                <span>{{ point }}</span>
              </li>
            </ul>
            <div class="bg-light-gray rounded-lg p-5 text-sm text-body">
              Questions first? <Link href="/contact" class="font-semibold text-primary hover:underline">Send us a message</Link>.
            </div>
          </div>

          <!-- Form -->
          <div class="lg:col-span-3">
            <div v-if="sent" class="bg-green-50 border border-green-200 rounded-lg p-8" role="status">
              <CheckCircleIcon class="w-10 h-10 text-success mb-3" aria-hidden="true" />
              <h2 class="text-2xl font-bold text-dark mb-2">Thank you for signing up</h2>
              <p class="text-body">{{ sent }}</p>
            </div>
            <form v-else class="bg-light-gray rounded-lg p-6 md:p-8 space-y-5" novalidate @submit.prevent="submitForm">
              <h2 class="text-2xl font-bold text-dark">Sign up to volunteer</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div v-for="field in fields" :key="field.key">
                  <label :for="`vol-${field.key}`" class="block text-dark font-medium mb-1">
                    {{ field.label }} <span v-if="field.optional" class="text-gray-500 font-normal">(optional)</span>
                  </label>
                  <input
                    :id="`vol-${field.key}`"
                    v-model="form[field.key]"
                    :type="field.type"
                    :autocomplete="field.autocomplete"
                    :aria-invalid="!!form.errors[field.key]"
                    :class="inputClass(field.key)"
                  />
                  <p v-if="form.errors[field.key]" class="text-sm text-red-600 mt-1">{{ friendly(field.key) }}</p>
                </div>
                <div>
                  <label for="vol-lga" class="block text-dark font-medium mb-1">Local Government Area</label>
                  <select id="vol-lga" v-model="form.lga" :aria-invalid="!!form.errors.lga" :class="inputClass('lga')">
                    <option value="" disabled>Choose your LGA</option>
                    <option v-for="lga in LAGOS_LGAS" :key="lga" :value="lga">{{ lga }}</option>
                  </select>
                  <p v-if="form.errors.lga" class="text-sm text-red-600 mt-1">{{ form.errors.lga }}</p>
                </div>
                <div>
                  <label for="vol-ward" class="block text-dark font-medium mb-1">Ward <span class="text-gray-500 font-normal">(optional)</span></label>
                  <input id="vol-ward" v-model="form.ward" type="text" maxlength="100" :class="inputClass('ward')" />
                  <p v-if="form.errors.ward" class="text-sm text-red-600 mt-1">{{ form.errors.ward }}</p>
                </div>
              </div>

              <fieldset>
                <legend class="block text-dark font-medium mb-2">How can you help? <span class="text-gray-500 font-normal">(optional)</span></legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <label v-for="(label, value) in SKILLS" :key="value" class="flex items-center gap-2 text-body cursor-pointer">
                    <input v-model="form.skills" type="checkbox" :value="value" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
                    {{ label }}
                  </label>
                </div>
              </fieldset>

              <label class="flex items-center gap-2 text-body cursor-pointer">
                <input v-model="form.has_vehicle" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
                I have a vehicle I can use for campaign activities
              </label>

              <div>
                <div class="flex justify-between items-baseline mb-1">
                  <label for="vol-motivation" class="block text-dark font-medium">Why do you want to volunteer? <span class="text-gray-500 font-normal">(optional)</span></label>
                  <span class="text-xs" :class="form.motivation.length > 1000 ? 'text-red-600' : 'text-gray-500'">{{ form.motivation.length }}/1000</span>
                </div>
                <textarea id="vol-motivation" v-model="form.motivation" rows="4" :class="inputClass('motivation')" />
                <p v-if="form.errors.motivation" class="text-sm text-red-600 mt-1">{{ form.errors.motivation }}</p>
              </div>

              <button type="submit" class="btn-primary w-full sm:w-auto" :disabled="form.processing">
                {{ form.processing ? 'Sending…' : 'Sign me up' }}
              </button>
              <p class="text-xs text-gray-500">We'll only use your details to organise volunteering. <Link href="/privacy" class="underline">Privacy policy</Link></p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { CheckCircleIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import { LAGOS_LGAS } from '@/Utils/lagos'

const page = usePage()

const points = [
  'Canvassing and community outreach near you',
  'Helping at rallies, town halls and events',
  'Training and campaign materials',
]

// Mirrors VolunteerRequest
const fields = [
  { key: 'first_name', label: 'First name', type: 'text', autocomplete: 'given-name' },
  { key: 'last_name', label: 'Last name', type: 'text', autocomplete: 'family-name' },
  { key: 'email', label: 'Email', type: 'email', autocomplete: 'email' },
  { key: 'phone', label: 'Phone', type: 'tel', autocomplete: 'tel' },
  { key: 'occupation', label: 'Occupation', type: 'text', autocomplete: 'organization-title', optional: true },
]

const SKILLS = {
  canvassing: 'Canvassing',
  social_media: 'Social media',
  event_management: 'Event management',
  public_speaking: 'Public speaking',
  data_entry: 'Data entry',
  photography: 'Photography',
  driving: 'Driving',
  fundraising: 'Fundraising',
}

const form = useForm({
  first_name: '', last_name: '', email: '', phone: '', occupation: '',
  lga: '', ward: '', skills: [], has_vehicle: false, motivation: '',
})
const sent = ref('')

const inputClass = (key) => [
  'w-full px-4 py-3 bg-white text-dark border-2 rounded focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary',
  form.errors[key] ? 'border-red-400' : 'border-gray-300',
]

const friendly = (key) => {
  const error = form.errors[key] || ''
  if (key === 'email' && /valid email/i.test(error)) return 'Please check your email address.'
  return error
}

const submitForm = () => {
  form.post('/volunteer/register', {
    preserveScroll: true,
    onSuccess: () => {
      if (Object.keys(form.errors).length) return
      sent.value = page.props.flash?.success || 'Thank you for volunteering! We will be in touch shortly.'
      form.reset()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
  })
}
</script>
