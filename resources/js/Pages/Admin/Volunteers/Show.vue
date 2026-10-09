<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="space-y-3">
        <Link href="/admin/volunteers" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All volunteers
        </Link>
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div class="min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <h1 class="text-2xl sm:text-3xl font-bold text-dark break-words">{{ volunteer.full_name }}</h1>
              <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusBadgeColor(volunteer.status)">
                {{ capitalize(volunteer.status) }}
              </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
              Registered {{ fullDate(volunteer.created_at) }}
              <template v-if="volunteer.approved_at"> · Approved {{ fullDate(volunteer.approved_at) }}</template>
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-if="primaryAction"
              type="button"
              :disabled="updatingStatus"
              :class="['px-4 py-2 text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50', primaryAction.class]"
              @click="setStatus(primaryAction.status)"
            >
              {{ updatingStatus ? 'Updating...' : primaryAction.label }}
            </button>
            <a
              :href="`mailto:${volunteer.email}`"
              class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
            >
              Email
            </a>
            <a
              v-if="volunteer.phone"
              :href="`tel:${volunteer.phone}`"
              class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors"
            >
              Call
            </a>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Motivation -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-3">Why they want to volunteer</h2>
            <p v-if="volunteer.motivation" class="text-gray-700 whitespace-pre-wrap break-words leading-relaxed">{{ volunteer.motivation }}</p>
            <p v-else class="text-gray-400 text-sm">No motivation provided</p>
          </div>

          <!-- Profile -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Profile</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm text-gray-500">LGA</dt>
                <dd class="text-dark font-medium">{{ volunteer.lga || '—' }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Ward</dt>
                <dd class="text-dark font-medium">{{ volunteer.ward || '—' }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Occupation</dt>
                <dd class="text-dark font-medium">{{ volunteer.occupation || '—' }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Vehicle</dt>
                <dd class="text-dark font-medium">{{ volunteer.has_vehicle ? 'Has a vehicle' : 'No vehicle' }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-sm text-gray-500 mb-2">Skills</dt>
                <dd class="flex flex-wrap gap-2">
                  <span
                    v-for="skill in (volunteer.skills || [])"
                    :key="skill"
                    class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full"
                  >
                    {{ skill }}
                  </span>
                  <span v-if="!volunteer.skills?.length" class="text-gray-400 text-sm">None listed</span>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Notes -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-1">Internal notes</h2>
            <p class="text-sm text-gray-500 mb-4">Only visible to the campaign team.</p>
            <form @submit.prevent="addNote" class="space-y-2">
              <textarea
                v-model="newNote"
                rows="3"
                maxlength="5000"
                placeholder="Add a note..."
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm resize-y"
                @keydown.meta.enter="addNote"
                @keydown.ctrl.enter="addNote"
              />
              <p v-if="noteError" class="text-sm text-red-600">{{ noteError }}</p>
              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="!newNote.trim() || addingNote"
                  class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
                >
                  {{ addingNote ? 'Adding...' : 'Add note' }}
                </button>
              </div>
            </form>

            <div class="space-y-3 mt-4">
              <div v-for="note in volunteer.notes" :key="note.id" class="bg-gray-50 rounded-lg p-3">
                <div class="flex justify-between items-start gap-2">
                  <div class="flex-1 min-w-0">
                    <p class="text-gray-800 text-sm whitespace-pre-wrap break-words">{{ note.body }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ note.author?.name || 'Deleted user' }} • {{ fullDate(note.created_at) }}
                    </p>
                  </div>
                  <button
                    v-if="canDeleteNote(note)"
                    type="button"
                    @click="deleteNote(note.id)"
                    class="text-red-600 hover:text-red-800 text-xs font-medium"
                  >
                    Delete
                  </button>
                </div>
              </div>
              <p v-if="!volunteer.notes?.length" class="text-gray-500 text-sm text-center py-4">
                No notes yet
              </p>
            </div>
          </div>

          <!-- History -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">History</h2>
            <ol class="space-y-3">
              <li v-for="log in volunteer.activities" :key="log.id" class="flex gap-3 text-sm">
                <span class="mt-1.5 w-2 h-2 rounded-full bg-gray-300 flex-shrink-0" />
                <div>
                  <p class="text-dark">
                    <span class="font-medium">{{ log.user?.name || 'System' }}</span>
                    {{ describeActivity(log) }}
                  </p>
                  <p class="text-xs text-gray-500">{{ fullDate(log.created_at) }}</p>
                </div>
              </li>
              <li class="flex gap-3 text-sm">
                <span class="mt-1.5 w-2 h-2 rounded-full bg-gray-300 flex-shrink-0" />
                <div>
                  <p class="text-dark">Registered through the website</p>
                  <p class="text-xs text-gray-500">{{ fullDate(volunteer.created_at) }}</p>
                </div>
              </li>
            </ol>
          </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
          <!-- Contact -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Contact</h2>
            <dl class="space-y-4">
              <div>
                <dt class="text-sm text-gray-500">Email</dt>
                <dd class="flex items-center gap-2 min-w-0">
                  <a :href="`mailto:${volunteer.email}`" class="text-primary hover:text-primary-dark font-medium break-all">{{ volunteer.email }}</a>
                  <button type="button" class="text-xs text-gray-500 hover:text-primary flex-shrink-0" @click="copy(volunteer.email, 'email')">
                    {{ copied === 'email' ? 'Copied' : 'Copy' }}
                  </button>
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Phone</dt>
                <dd class="flex items-center gap-2">
                  <template v-if="volunteer.phone">
                    <a :href="`tel:${volunteer.phone}`" class="text-primary hover:text-primary-dark font-medium">{{ volunteer.phone }}</a>
                    <button type="button" class="text-xs text-gray-500 hover:text-primary" @click="copy(volunteer.phone, 'phone')">
                      {{ copied === 'phone' ? 'Copied' : 'Copy' }}
                    </button>
                  </template>
                  <span v-else class="text-gray-400">Not provided</span>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Status -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-1">Status</h2>
            <p class="text-sm text-gray-500 mb-4">Approving or activating emails the volunteer.</p>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="status in statuses"
                :key="status"
                type="button"
                :disabled="updatingStatus || volunteer.status === status"
                :class="[
                  'px-3 py-2 rounded-lg text-sm font-medium border transition-colors',
                  volunteer.status === status
                    ? 'bg-primary text-white border-primary cursor-default'
                    : 'border-gray-300 text-dark hover:border-primary hover:text-primary disabled:opacity-50',
                ]"
                @click="setStatus(status)"
              >
                {{ capitalize(status) }}
              </button>
            </div>
          </div>

          <!-- Tags -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Tags</h2>
            <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="tag in volunteer.tags"
                :key="tag.id"
                class="inline-flex items-center gap-1 pl-3 pr-1.5 py-1 rounded-full text-sm font-medium text-white"
                :style="{ backgroundColor: tag.color || '#003D82' }"
              >
                {{ tag.name }}
                <button
                  type="button"
                  @click="removeTag(tag)"
                  class="w-5 h-5 rounded-full hover:bg-black/20 flex items-center justify-center"
                  :aria-label="`Remove tag ${tag.name}`"
                >
                  ×
                </button>
              </span>
              <p v-if="!volunteer.tags?.length" class="text-gray-500 text-sm">No tags yet</p>
            </div>
            <form @submit.prevent="addTag" class="flex gap-2">
              <input
                v-model="newTagName"
                type="text"
                list="volunteer-tag-options"
                maxlength="100"
                placeholder="Add or create a tag..."
                class="flex-1 min-w-0 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
              />
              <datalist id="volunteer-tag-options">
                <option v-for="tag in availableTags" :key="tag.id" :value="tag.name" />
              </datalist>
              <button
                type="submit"
                :disabled="!newTagName.trim() || addingTag"
                class="px-3 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
              >
                {{ addingTag ? 'Adding...' : 'Add' }}
              </button>
            </form>
            <p v-if="tagError" class="text-sm text-red-600 mt-2">{{ tagError }}</p>
          </div>
        </div>
      </div>
    </div>

    <template #dialogs>
      <ConfirmDialog ref="confirmDialog" />
    </template>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const page = usePage()

const volunteer = computed(() => page.props.volunteer || {})
const allTags = computed(() => page.props.allTags || [])
const authUser = computed(() => page.props.auth?.user || {})

const statuses = ['pending', 'approved', 'active', 'inactive']

const confirmDialog = ref(null)
const newNote = ref('')
const newTagName = ref('')
const updatingStatus = ref(false)
const addingNote = ref(false)
const addingTag = ref(false)
const noteError = ref('')
const tagError = ref('')
const copied = ref(null)

// The obvious next step for each status
const primaryAction = computed(() => ({
  pending: { status: 'approved', label: 'Approve', class: 'bg-green-600 hover:bg-green-700' },
  approved: { status: 'active', label: 'Mark active', class: 'bg-primary hover:bg-primary-dark' },
  inactive: { status: 'active', label: 'Reactivate', class: 'bg-primary hover:bg-primary-dark' },
}[volunteer.value.status] || null))

const availableTags = computed(() => {
  const attached = new Set((volunteer.value.tags || []).map(t => t.id))
  return allTags.value.filter(t => !attached.has(t.id))
})

const canDeleteNote = (note) => {
  return authUser.value.id === note.author_id || authUser.value.role === 'admin'
}

const setStatus = async (status) => {
  if (status === volunteer.value.status || updatingStatus.value) return

  // These transitions email the volunteer, so confirm first
  if (['approved', 'active'].includes(status)) {
    const ok = await confirmDialog.value.open(
      `Mark as ${status}`,
      `${volunteer.value.full_name} will receive an email letting them know.`,
      { confirmText: capitalize(status === 'approved' ? 'approve' : 'mark active') }
    )
    if (!ok) return
  }

  updatingStatus.value = true
  router.patch(`/admin/volunteers/${volunteer.value.id}`, { status }, {
    preserveScroll: true,
    onFinish: () => { updatingStatus.value = false },
  })
}

const addNote = () => {
  if (!newNote.value.trim() || addingNote.value) return
  addingNote.value = true
  noteError.value = ''

  router.post(`/admin/volunteer/${volunteer.value.id}/notes`, {
    body: newNote.value
  }, {
    preserveScroll: true,
    onSuccess: () => { newNote.value = '' },
    onError: (errors) => { noteError.value = errors.body || 'Could not save the note.' },
    onFinish: () => { addingNote.value = false },
  })
}

const deleteNote = async (noteId) => {
  const ok = await confirmDialog.value.open('Delete note', 'This note will be permanently removed.', {
    confirmText: 'Delete',
    isDangerous: true,
  })
  if (ok) {
    router.delete(`/admin/notes/${noteId}`, { preserveScroll: true })
  }
}

const addTag = () => {
  const name = newTagName.value.trim()
  if (!name || addingTag.value) return
  addingTag.value = true
  tagError.value = ''

  // Reuse an existing tag when the name matches, otherwise the server creates it
  const existing = allTags.value.find(t => t.name.toLowerCase() === name.toLowerCase())
  const payload = existing ? { tag_id: existing.id } : { name }

  router.post(`/admin/volunteer/${volunteer.value.id}/tags`, payload, {
    preserveScroll: true,
    onSuccess: () => { newTagName.value = '' },
    onError: (errors) => { tagError.value = errors.name || errors.tag_id || 'Could not add the tag.' },
    onFinish: () => { addingTag.value = false },
  })
}

const removeTag = async (tag) => {
  const ok = await confirmDialog.value.open('Remove tag', `Remove "${tag.name}" from this volunteer?`, {
    confirmText: 'Remove',
  })
  if (ok) {
    router.delete(`/admin/volunteer/${volunteer.value.id}/tags/${tag.id}`, { preserveScroll: true })
  }
}

const copy = async (text, key) => {
  try {
    await navigator.clipboard.writeText(text)
    copied.value = key
    setTimeout(() => { copied.value = null }, 1500)
  } catch {
    // Clipboard unavailable (e.g. insecure context); the links still work
  }
}

const statusBadgeColor = (status) => {
  const colors = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-blue-100 text-blue-800',
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-gray-100 text-gray-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const capitalize = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const describeActivity = (log) => {
  if (log.action === 'status_updated' && log.properties) {
    return `changed status from ${capitalize(log.properties.old_status)} to ${capitalize(log.properties.new_status)}`
  }
  return log.action.replace(/[._]/g, ' ')
}

const fullDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString(undefined, {
    day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit',
  })
}
</script>
