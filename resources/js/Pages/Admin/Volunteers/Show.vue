<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header with Back Button -->
      <div class="flex items-center gap-4">
        <Link href="/admin/volunteers" class="text-primary hover:text-primary-dark">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </Link>
        <h1 class="text-3xl font-bold text-dark">{{ volunteer.name }}</h1>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Profile & Status -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Profile Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Profile Information</h2>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600">Email</p>
                  <p class="text-dark font-medium">{{ volunteer.email }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Phone</p>
                  <p class="text-dark font-medium">{{ volunteer.phone || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">LGA</p>
                  <p class="text-dark font-medium">{{ volunteer.lga }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Occupation</p>
                  <p class="text-dark font-medium">{{ volunteer.occupation || 'N/A' }}</p>
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-600 mb-2">Skills</p>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="skill in (volunteer.skills || [])"
                    :key="skill"
                    class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full"
                  >
                    {{ skill }}
                  </span>
                </div>
              </div>
              <div v-if="volunteer.bio" class="pt-4 border-t">
                <p class="text-sm text-gray-600 mb-2">Bio</p>
                <p class="text-gray-700">{{ volunteer.bio }}</p>
              </div>
            </div>
          </div>

          <!-- Status Update -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Status</h2>
            <div class="flex items-center gap-4">
              <select
                v-model="selectedStatus"
                class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
              <button
                @click="updateStatus"
                :disabled="updatingStatus"
                class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
              >
                {{ updatingStatus ? 'Updating...' : 'Update' }}
              </button>
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
                <div v-for="note in volunteer.notes" :key="note.id" class="bg-gray-50 rounded-lg p-3">
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
                <p v-if="!volunteer.notes || volunteer.notes.length === 0" class="text-gray-500 text-sm text-center py-4">
                  No notes yet
                </p>
              </div>
            </div>
          </div>

          <!-- Activity Timeline -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-dark mb-4">Activity Timeline</h2>
            <div class="space-y-4">
              <div v-for="(activity, index) in volunteer.activities" :key="index" class="flex gap-4 pb-4 border-b last:border-b-0">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-dark">{{ activity.action }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(activity.created_at) }}</p>
                </div>
              </div>
              <p v-if="!volunteer.activities || volunteer.activities.length === 0" class="text-gray-500 text-sm text-center py-4">
                No activity recorded yet
              </p>
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
                <button
                  @click="createAndAttachTag"
                  :disabled="!newTagName.trim() || creatingTag"
                  class="w-full px-3 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
                >
                  {{ creatingTag ? 'Creating...' : 'Create & Add' }}
                </button>
              </div>

              <!-- Existing Tags -->
              <div class="space-y-2">
                <div v-if="!volunteer.tags || volunteer.tags.length === 0" class="text-gray-500 text-sm text-center py-4">
                  No tags yet
                </div>
                <div
                  v-for="tag in volunteer.tags"
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

const volunteer = computed(() => page.props.volunteer || {})
const authUser = computed(() => page.props.auth?.user || {})

const selectedStatus = ref(volunteer.value.status || 'pending')
const newNote = ref('')
const newTagName = ref('')
const updatingStatus = ref(false)
const addingNote = ref(false)
const creatingTag = ref(false)

const canDeleteNote = (note) => {
  return authUser.value.id === note.author_id || authUser.value.role === 'admin'
}

const updateStatus = () => {
  updatingStatus.value = true
  router.patch(`/admin/volunteers/${volunteer.value.id}`, {
    status: selectedStatus.value
  }, {
    onSuccess: () => {
      updatingStatus.value = false
    },
    onError: () => {
      updatingStatus.value = false
    }
  })
}

const addNote = async () => {
  if (!newNote.value.trim()) return
  addingNote.value = true

  router.post(`/admin/volunteers/${volunteer.value.id}/notes`, {
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

  router.post(`/admin/volunteers/${volunteer.value.id}/tags`, {
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
    router.delete(`/admin/volunteers/${volunteer.value.id}/tags/${tagId}`)
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>
