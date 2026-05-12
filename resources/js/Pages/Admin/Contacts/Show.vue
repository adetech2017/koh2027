<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header with Back Button -->
      <div class="flex items-center gap-4">
        <Link href="/admin/contacts" class="text-primary hover:text-primary-dark">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </Link>
        <h1 class="text-3xl font-bold text-dark">{{ contact.name }}</h1>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Contact Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Contact Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Contact Details</h2>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="text-dark font-medium">{{ contact.email }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Phone</p>
                <p class="text-dark font-medium">{{ contact.phone || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Subject</p>
                <p class="text-dark font-medium">{{ contact.subject || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Status</p>
                <div class="flex items-center gap-2 mt-1">
                  <select
                    v-model="selectedStatus"
                    @change="updateStatus"
                    class="px-3 py-1 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                  >
                    <option value="new">New</option>
                    <option value="read">Read</option>
                    <option value="replied">Replied</option>
                    <option value="archived">Archived</option>
                  </select>
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-600">Created</p>
                <p class="text-dark font-medium">{{ formatDate(contact.created_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Message Content -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Message</h2>
            <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">
              {{ contact.message || 'No message content' }}
            </div>
          </div>

          <!-- Notes Section -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Notes</h2>
            <div class="space-y-4">
              <!-- Add Note Form -->
              <form @submit.prevent="addNote" class="flex gap-2">
                <input
                  v-model="newNote"
                  type="text"
                  placeholder="Add a note..."
                  class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                />
                <button
                  type="submit"
                  :disabled="!newNote.trim() || addingNote"
                  class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
                >
                  {{ addingNote ? 'Adding...' : 'Add' }}
                </button>
              </form>

              <!-- Notes List -->
              <div class="space-y-3">
                <div v-for="note in contact.notes" :key="note.id" class="bg-gray-50 rounded-lg p-3">
                  <div class="flex justify-between items-start gap-2">
                    <div class="flex-1">
                      <p class="text-gray-800 text-sm">{{ note.body }}</p>
                      <p class="text-xs text-gray-500 mt-1">
                        {{ note.author?.name }} • {{ formatDate(note.created_at) }}
                      </p>
                    </div>
                    <button
                      v-if="canDeleteNote(note)"
                      @click="deleteNote(note.id)"
                      class="text-red-600 hover:text-red-800 text-xs font-medium"
                    >
                      Delete
                    </button>
                  </div>
                </div>
                <p v-if="!contact.notes || contact.notes.length === 0" class="text-gray-500 text-sm text-center py-4">
                  No notes yet
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Tags -->
        <div>
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Tags</h2>
            <div class="space-y-4">
              <!-- Add Tag Form -->
              <div>
                <input
                  v-model="newTagName"
                  type="text"
                  placeholder="New tag name..."
                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm mb-2"
                />
                <div class="flex gap-2">
                  <button
                    @click="createAndAttachTag"
                    :disabled="!newTagName.trim() || creatingTag"
                    class="flex-1 px-3 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
                  >
                    {{ creatingTag ? 'Creating...' : 'Create' }}
                  </button>
                </div>
              </div>

              <!-- Existing Tags -->
              <div class="space-y-2">
                <div v-if="!contact.tags || contact.tags.length === 0" class="text-gray-500 text-sm text-center py-4">
                  No tags yet
                </div>
                <div
                  v-for="tag in contact.tags"
                  :key="tag.id"
                  class="flex items-center justify-between gap-2 px-3 py-2 rounded-full text-sm font-medium text-white"
                  :style="{ backgroundColor: tag.color || '#003D82' }"
                >
                  <span>{{ tag.name }}</span>
                  <button
                    @click="removeTag(tag.id)"
                    class="hover:opacity-80 transition-opacity"
                    title="Remove tag"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

const contact = computed(() => page.props.contact || {})
const authUser = computed(() => page.props.auth?.user || {})

const selectedStatus = ref(contact.value.status || 'new')
const newNote = ref('')
const newTagName = ref('')
const addingNote = ref(false)
const creatingTag = ref(false)

const canDeleteNote = (note) => {
  return authUser.value.id === note.author_id || authUser.value.role === 'admin'
}

const updateStatus = () => {
  router.patch(`/admin/contacts/${contact.value.id}`, {
    status: selectedStatus.value
  })
}

const addNote = async () => {
  if (!newNote.value.trim()) return
  addingNote.value = true

  router.post(`/admin/contacts/${contact.value.id}/notes`, {
    body: newNote.value
  }, {
    onSuccess: () => {
      newNote.value = ''
      addingNote.value = false
    },
    onError: () => {
      addingNote.value = false
    }
  })
}

const deleteNote = (noteId) => {
  if (confirm('Delete this note?')) {
    router.delete(`/admin/notes/${noteId}`)
  }
}

const createAndAttachTag = async () => {
  if (!newTagName.value.trim()) return
  creatingTag.value = true

  router.post(`/admin/contacts/${contact.value.id}/tags`, {
    name: newTagName.value
  }, {
    onSuccess: () => {
      newTagName.value = ''
      creatingTag.value = false
    },
    onError: () => {
      creatingTag.value = false
    }
  })
}

const removeTag = (tagId) => {
  if (confirm('Remove this tag?')) {
    router.delete(`/admin/contacts/${contact.value.id}/tags/${tagId}`)
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>
