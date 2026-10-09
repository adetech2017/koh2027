<template>
  <form class="space-y-6" @submit.prevent="submit">
    <div v-if="hasErrors" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
      <p class="font-semibold">Please fix the highlighted fields.</p>
    </div>

    <section class="bg-white rounded-lg shadow p-6 space-y-5">
      <h3 class="text-lg font-semibold text-dark">Details</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label for="u-name" class="block text-sm font-medium text-dark mb-1">Full name <span class="text-red-600">*</span></label>
          <input id="u-name" v-model="form.name" type="text" autocomplete="off" :class="inputClass('name')" />
          <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</p>
        </div>
        <div>
          <label for="u-email" class="block text-sm font-medium text-dark mb-1">Email <span class="text-red-600">*</span></label>
          <input id="u-email" v-model="form.email" type="email" autocomplete="off" :class="inputClass('email')" />
          <p v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</p>
          <p v-else class="text-xs text-gray-500 mt-1">They sign in with this.</p>
        </div>
      </div>
    </section>

    <!-- Role -->
    <section class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-dark mb-3">Access level</h3>
      <fieldset class="grid grid-cols-1 md:grid-cols-3 gap-3" :disabled="roleLocked">
        <legend class="sr-only">Role</legend>
        <label
          v-for="role in roles"
          :key="role.value"
          class="flex flex-col p-4 border rounded-lg transition-colors"
          :class="[
            form.role === role.value ? 'border-primary ring-1 ring-primary bg-blue-50/50' : 'border-gray-200',
            roleLocked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:border-gray-400',
          ]"
        >
          <span class="flex items-center gap-2">
            <input v-model="form.role" type="radio" :value="role.value" class="text-primary focus:ring-primary" />
            <span class="font-semibold text-dark">{{ role.label }}</span>
          </span>
          <span class="text-xs text-gray-500 mt-1 mb-2">{{ role.summary }}</span>
          <ul class="text-xs space-y-1">
            <li v-for="item in role.can" :key="item" class="flex gap-1.5 text-gray-700"><span class="text-green-600">✓</span>{{ item }}</li>
            <li v-for="item in role.cannot" :key="item" class="flex gap-1.5 text-gray-400"><span>✕</span>{{ item }}</li>
          </ul>
        </label>
      </fieldset>
      <p v-if="form.errors.role" class="text-red-600 text-sm mt-2">{{ form.errors.role }}</p>
      <p v-else-if="roleLocked" class="text-xs text-gray-500 mt-2">{{ roleLockReason }}</p>
    </section>

    <!-- Password -->
    <section class="bg-white rounded-lg shadow p-6 space-y-4">
      <div>
        <h3 class="text-lg font-semibold text-dark">{{ user ? 'Change password' : 'Password' }}</h3>
        <p class="text-sm text-gray-500">
          {{ user ? (isSelf ? 'Leave blank to keep your current password.' : 'Leave blank to keep their current password. Setting a new one signs them out on all devices.') : 'Share it with them privately, not by group chat.' }}
        </p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label for="u-password" class="block text-sm font-medium text-dark mb-1">{{ user ? 'New password' : 'Password' }} <span v-if="!user" class="text-red-600">*</span></label>
          <div class="relative">
            <input
              id="u-password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              :class="[inputClass('password'), 'pr-16 font-mono']"
            />
            <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-500 hover:text-dark px-2 py-1" @click="showPassword = !showPassword">
              {{ showPassword ? 'Hide' : 'Show' }}
            </button>
          </div>
          <p v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</p>
          <ul v-else-if="form.password" class="text-xs mt-1 space-y-0.5">
            <li v-for="rule in passwordRules" :key="rule.label" :class="rule.ok ? 'text-green-700' : 'text-gray-500'">{{ rule.ok ? '✓' : '○' }} {{ rule.label }}</li>
          </ul>
        </div>
        <div v-if="form.password || !user">
          <label for="u-password2" class="block text-sm font-medium text-dark mb-1">Confirm password <span v-if="!user" class="text-red-600">*</span></label>
          <input
            id="u-password2"
            v-model="form.password_confirmation"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="new-password"
            :class="[inputClass('password_confirmation'), 'font-mono']"
          />
          <p v-if="form.password_confirmation && form.password !== form.password_confirmation" class="text-amber-700 text-xs mt-1">Doesn't match yet.</p>
        </div>
      </div>
      <div class="flex flex-wrap gap-3 text-sm">
        <button type="button" class="text-primary hover:text-primary-dark font-medium" @click="generate">Generate a strong password</button>
        <button v-if="form.password && showPassword" type="button" class="text-gray-600 hover:text-dark" @click="copyPassword">{{ copied ? 'Copied' : 'Copy password' }}</button>
      </div>
    </section>

    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
      <Link href="/admin/users" class="px-6 py-2 text-center bg-gray-100 hover:bg-gray-200 text-dark rounded-lg font-medium transition-colors">Cancel</Link>
      <button
        type="submit"
        :disabled="form.processing"
        class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
      >
        {{ form.processing ? 'Saving…' : (user ? 'Save changes' : 'Create user') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  user: { type: Object, default: null },
  isSelf: { type: Boolean, default: false },
  isLastAdmin: { type: Boolean, default: false },
})

// Mirrors the server: admin.role:admin,editor on content routes, manage-users / delete-content gates for admins
const roles = [
  {
    value: 'moderator',
    label: 'Moderator',
    summary: 'Handles supporters and messages.',
    can: ['Contacts, volunteers & newsletter', 'Update statuses, notes & tags', 'Analytics'],
    cannot: ['Edit website content', 'Exports, bulk email & users'],
  },
  {
    value: 'editor',
    label: 'Editor',
    summary: 'Runs the website content.',
    can: ['Everything a moderator can do', 'News, events, gallery, slides, platform, merch & manifesto', 'CSV exports'],
    cannot: ['Delete content', 'Bulk email & users'],
  },
  {
    value: 'admin',
    label: 'Admin',
    summary: 'Full control.',
    can: ['Everything an editor can do', 'Delete content', 'Send bulk emails', 'Add, suspend & remove users'],
    cannot: [],
  },
]

const u = props.user || {}
const form = useForm({
  name: u.name || '',
  email: u.email || '',
  role: u.role || 'editor',
  password: '',
  password_confirmation: '',
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

// Taking away your own (or the only) admin access would lock everyone out of user management
const roleLocked = computed(() => !!props.user && props.user.role === 'admin' && (props.isSelf || props.isLastAdmin))
const roleLockReason = computed(() =>
  props.isSelf ? "You can't change your own access level. Ask another admin." : 'This is the only active admin. Make someone else an admin before changing this.'
)

const showPassword = ref(false)
const copied = ref(false)
const passwordRules = computed(() => [
  { label: 'At least 10 characters', ok: form.password.length >= 10 },
  { label: 'Contains a letter', ok: /[A-Za-z]/.test(form.password) },
  { label: 'Contains a number', ok: /\d/.test(form.password) },
])

const generate = () => {
  // Readable characters only (no 0/O, 1/l/I), with at least one letter and number
  const alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  const bytes = crypto.getRandomValues(new Uint32Array(14))
  let password = Array.from(bytes, b => alphabet[b % alphabet.length]).join('')
  if (!/\d/.test(password)) password = password.slice(0, -1) + '7'
  if (!/[A-Za-z]/.test(password)) password = 'k' + password.slice(1)
  form.password = password
  form.password_confirmation = password
  showPassword.value = true
}

const copyPassword = async () => {
  try {
    await navigator.clipboard.writeText(form.password)
    copied.value = true
    setTimeout(() => { copied.value = false }, 1500)
  } catch {
    // Clipboard unavailable; the password is visible to copy by hand
  }
}

const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const submit = () => {
  const options = {
    preserveScroll: true,
    onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    onSuccess: () => form.reset('password', 'password_confirmation'),
  }
  if (props.user) form.put(`/admin/users/${props.user.id}`, options)
  else form.post('/admin/users', options)
}
</script>
