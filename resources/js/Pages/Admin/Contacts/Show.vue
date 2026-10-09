<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="space-y-3">
        <Link href="/admin/contacts" class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All contacts
        </Link>
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div class="min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <h1 class="text-2xl sm:text-3xl font-bold text-dark break-words">{{ contact.name }}</h1>
              <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusBadgeColor(contact.status)">
                {{ statusLabel(contact.status) }}
              </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Received {{ fullDate(contact.created_at) }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <a
              :href="replyHref"
              class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium text-sm transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
              </svg>
              Reply by email
            </a>
            <button
              v-if="contact.status !== 'replied'"
              type="button"
              :disabled="updatingStatus"
              class="px-4 py-2 border border-gray-300 hover:border-green-600 hover:text-green-700 text-dark rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
              @click="setStatus('replied')"
            >
              Mark as replied
            </button>
            <button
              v-if="contact.status !== 'archived'"
              type="button"
              :disabled="updatingStatus"
              class="px-4 py-2 border border-gray-300 hover:border-amber-600 hover:text-amber-700 text-dark rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
              @click="setStatus('archived')"
            >
              Archive
            </button>
            <button
              v-else
              type="button"
              :disabled="updatingStatus"
              class="px-4 py-2 border border-gray-300 hover:border-primary hover:text-primary text-dark rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
              @click="setStatus('read')"
            >
              Unarchive
            </button>
            <button
              v-if="contact.status !== 'new'"
              type="button"
              :disabled="updatingStatus"
              class="px-4 py-2 text-gray-600 hover:text-primary rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
              @click="setStatus('new')"
            >
              Mark unread
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Message, Notes, History -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Message -->
          <div class="bg-white rounded-lg shadow p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Subject</p>
            <h2 class="text-lg font-semibold text-dark mb-4 break-words">{{ contact.subject || '(No subject)' }}</h2>
            <div class="text-gray-700 whitespace-pre-wrap break-words leading-relaxed">{{ contact.message || 'No message content' }}</div>
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
              <div v-for="note in contact.notes" :key="note.id" class="bg-gray-50 rounded-lg p-3">
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
              <p v-if="!contact.notes?.length" class="text-gray-500 text-sm text-center py-4">
                No notes yet
              </p>
            </div>
          </div>

          <!-- History -->
          <div v-if="contact.activities?.length" class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">History</h2>
            <ol class="space-y-3">
              <li v-for="log in contact.activities" :key="log.id" class="flex gap-3 text-sm">
                <span class="mt-1.5 w-2 h-2 rounded-full bg-gray-300 flex-shrink-0" />
                <div>
                  <p class="text-dark">
                    <span class="font-medium">{{ log.user?.name || 'System' }}</span>
                    {{ describeActivity(log) }}
                  </p>
                  <p class="text-xs text-gray-500">{{ fullDate(log.created_at) }}</p>
                </div>
              </li>
            </ol>
          </div>
        </div>

        <!-- Right Column: Details & Tags -->
        <div class="space-y-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Contact details</h2>
            <dl class="space-y-4">
              <div>
                <dt class="text-sm text-gray-500">Email</dt>
                <dd class="flex items-center gap-2 min-w-0">
                  <a :href="`mailto:${contact.email}`" class="text-primary hover:text-primary-dark font-medium break-all">{{ contact.email }}</a>
                  <button
                    type="button"
                    class="text-xs text-gray-500 hover:text-primary flex-shrink-0"
                    @click="copyEmail"
                  >
                    {{ copied ? 'Copied' : 'Copy' }}
                  </button>
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Phone</dt>
                <dd>
                  <a v-if="contact.phone" :href="`tel:${contact.phone}`" class="text-primary hover:text-primary-dark font-medium">{{ contact.phone }}</a>
                  <span v-else class="text-gray-400">Not provided</span>
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Received</dt>
                <dd class="text-dark">{{ fullDate(contact.created_at) }}</dd>
              </div>
              <div v-if="contact.read_at">
                <dt class="text-sm text-gray-500">First read</dt>
                <dd class="text-dark">{{ fullDate(contact.read_at) }}</dd>
              </div>
            </dl>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Tags</h2>
            <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="tag in contact.tags"
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
              <p v-if="!contact.tags?.length" class="text-gray-500 text-sm">No tags yet</p>
            </div>
            <form @submit.prevent="addTag" class="flex gap-2">
              <input
                v-model="newTagName"
                type="text"
                list="contact-tag-options"
                maxlength="100"
                placeholder="Add or create a tag..."
                class="flex-1 min-w-0 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
              />
              <datalist id="contact-tag-options">
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

const contact = computed(() => page.props.contact || {})
const allTags = computed(() => page.props.allTags || [])
const authUser = computed(() => page.props.auth?.user || {})

const confirmDialog = ref(null)
const newNote = ref('')
const newTagName = ref('')
const addingNote = ref(false)
const addingTag = ref(false)
const updatingStatus = ref(false)
const noteError = ref('')
const tagError = ref('')
const copied = ref(false)

const availableTags = computed(() => {
  const attached = new Set((contact.value.tags || []).map(t => t.id))
  return allTags.value.filter(t => !attached.has(t.id))
})

const replyHref = computed(() => {
  const subject = contact.value.subject ? `Re: ${contact.value.subject}` : 'Re: Your message to KOH 2027'
  return `mailto:${contact.value.email}?subject=${encodeURIComponent(subject)}`
})

const canDeleteNote = (note) => {
  return authUser.value.id === note.author_id || authUser.value.role === 'admin'
}

const setStatus = (status) => {
  updatingStatus.value = true
  router.patch(`/admin/contacts/${contact.value.id}`, { status }, {
    preserveScroll: true,
    onFinish: () => { updatingStatus.value = false },
  })
}

const addNote = () => {
  if (!newNote.value.trim() || addingNote.value) return
  addingNote.value = true
  noteError.value = ''

  router.post(`/admin/contact/${contact.value.id}/notes`, {
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

  router.post(`/admin/contact/${contact.value.id}/tags`, payload, {
    preserveScroll: true,
    onSuccess: () => { newTagName.value = '' },
    onError: (errors) => { tagError.value = errors.name || errors.tag_id || 'Could not add the tag.' },
    onFinish: () => { addingTag.value = false },
  })
}

const removeTag = async (tag) => {
  const ok = await confirmDialog.value.open('Remove tag', `Remove "${tag.name}" from this contact?`, {
    confirmText: 'Remove',
  })
  if (ok) {
    router.delete(`/admin/contact/${contact.value.id}/tags/${tag.id}`, { preserveScroll: true })
  }
}

const copyEmail = async () => {
  try {
    await navigator.clipboard.writeText(contact.value.email)
    copied.value = true
    setTimeout(() => { copied.value = false }, 1500)
  } catch {
    // Clipboard unavailable (e.g. insecure context); the mailto link still works
  }
}

const statusLabel = (status) => {
  if (status === 'new') return 'Unread'
  return status ? status.charAt(0).toUpperCase() + status.slice(1) : ''
}

const statusBadgeColor = (status) => {
  const colors = {
    'new': 'bg-blue-100 text-blue-800',
    'read': 'bg-gray-100 text-gray-800',
    'replied': 'bg-green-100 text-green-800',
    'archived': 'bg-amber-100 text-amber-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const describeActivity = (log) => {
  if (log.action === 'status_updated' && log.properties) {
    return `changed status from ${statusLabel(log.properties.old_status)} to ${statusLabel(log.properties.new_status)}`
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
