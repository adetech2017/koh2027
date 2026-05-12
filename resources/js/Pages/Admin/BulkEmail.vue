<template>
  <AdminLayout>
    <div class="max-w-2xl">
      <div class="bg-white rounded-lg shadow p-8">
        <h2 class="text-2xl font-bold text-dark mb-6">Bulk Email Campaign</h2>

        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Audience Selection -->
          <div>
            <label class="block text-sm font-semibold text-dark mb-3">Select Audience</label>
            <div class="space-y-3">
              <label class="flex items-center gap-3 p-4 border rounded-lg hover:bg-gray-50 cursor-pointer" :class="{ 'border-primary bg-blue-50': audience === 'subscribers' }">
                <input
                  v-model="audience"
                  type="radio"
                  value="subscribers"
                  class="w-4 h-4 text-primary"
                />
                <div>
                  <p class="font-medium text-dark">Newsletter Subscribers</p>
                  <p class="text-sm text-gray-600">{{ audienceCounts.confirmedSubscribers || 0 }} confirmed recipients</p>
                </div>
              </label>

              <label class="flex items-center gap-3 p-4 border rounded-lg hover:bg-gray-50 cursor-pointer" :class="{ 'border-primary bg-blue-50': audience === 'volunteers' }">
                <input
                  v-model="audience"
                  type="radio"
                  value="volunteers"
                  class="w-4 h-4 text-primary"
                />
                <div>
                  <p class="font-medium text-dark">Active Volunteers</p>
                  <p class="text-sm text-gray-600">{{ audienceCounts.activeVolunteers || 0 }} recipients</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Recipients Preview -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-900">
              <span class="font-semibold">{{ recipientCount }}</span> people will receive this email
            </p>
          </div>

          <!-- Subject -->
          <div>
            <label for="subject" class="block text-sm font-semibold text-dark mb-2">Email Subject</label>
            <input
              id="subject"
              v-model="form.subject"
              type="text"
              placeholder="e.g., Important Update on KOH 2027"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              required
            />
            <p v-if="errors.subject" class="text-sm text-red-600 mt-1">{{ errors.subject[0] }}</p>
          </div>

          <!-- Body -->
          <div>
            <label for="body" class="block text-sm font-semibold text-dark mb-2">Email Body</label>
            <textarea
              id="body"
              v-model="form.body"
              placeholder="Write your message here..."
              rows="10"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent font-mono text-sm"
              required
            />
            <p class="text-xs text-gray-500 mt-2">Plain text only. No HTML formatting.</p>
            <p v-if="errors.body" class="text-sm text-red-600 mt-1">{{ errors.body[0] }}</p>
          </div>

          <!-- Preview -->
          <div v-if="form.subject || form.body" class="bg-gray-50 rounded-lg p-4 border">
            <p class="text-xs font-semibold text-gray-600 uppercase mb-2">Preview</p>
            <div class="bg-white p-4 rounded border">
              <p class="font-semibold text-dark mb-3">{{ form.subject || '(No subject)' }}</p>
              <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ form.body }}</p>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3 pt-4">
            <button
              type="submit"
              :disabled="loading"
              class="flex-1 bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-50"
            >
              <span v-if="!loading">Send Campaign</span>
              <span v-else>Sending...</span>
            </button>
            <Link
              href="/admin"
              class="px-6 py-3 border border-gray-300 text-dark font-semibold rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancel
            </Link>
          </div>
        </form>

        <!-- Confirmation Modal -->
        <div v-if="showConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow-xl p-8 max-w-md">
            <h3 class="text-xl font-bold text-dark mb-4">Confirm Campaign Send</h3>
            <p class="text-gray-600 mb-6">
              Are you sure you want to send this email to <span class="font-semibold">{{ recipientCount }}</span> {{ audience === 'subscribers' ? 'newsletter subscribers' : 'volunteers' }}?
            </p>
            <p class="text-sm text-gray-500 mb-6 bg-gray-50 p-3 rounded">
              <span class="font-semibold">Subject:</span> {{ form.subject }}
            </p>
            <div class="flex gap-3">
              <button
                @click="confirmSend"
                :disabled="loading"
                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded-lg transition-colors disabled:opacity-50"
              >
                <span v-if="!loading">Yes, Send</span>
                <span v-else>Sending...</span>
              </button>
              <button
                @click="showConfirmation = false"
                :disabled="loading"
                class="flex-1 bg-gray-200 hover:bg-gray-300 text-dark font-semibold py-2 rounded-lg transition-colors disabled:opacity-50"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()
const audienceCounts = computed(() => page.props.audienceCounts || {})

const audience = ref('subscribers')
const showConfirmation = ref(false)
const loading = ref(false)

const form = useForm({
  audience: 'subscribers',
  subject: '',
  body: ''
})

const errors = reactive({
  subject: [],
  body: []
})

const recipientCount = computed(() => {
  if (audience.value === 'subscribers') {
    return audienceCounts.value.confirmedSubscribers || 0
  }
  return audienceCounts.value.activeVolunteers || 0
})

const submitForm = () => {
  errors.subject = []
  errors.body = []

  if (!form.subject.trim()) {
    errors.subject = ['Subject is required']
  }
  if (!form.body.trim()) {
    errors.body = ['Body is required']
  }
  if (recipientCount.value === 0) {
    errors.subject = ['No recipients available for this audience']
  }

  if (errors.subject.length === 0 && errors.body.length === 0) {
    showConfirmation.value = true
  }
}

const confirmSend = async () => {
  loading.value = true
  form.audience = audience.value

  form.post('/admin/bulk-email', {
    onSuccess: () => {
      loading.value = false
      // Redirect or show success message
    },
    onError: (errors) => {
      loading.value = false
      // Handle errors
      if (errors.subject) {
        errors.subject = Array.isArray(errors.subject) ? errors.subject : [errors.subject]
      }
      if (errors.body) {
        errors.body = Array.isArray(errors.body) ? errors.body : [errors.body]
      }
    }
  })
}
</script>
