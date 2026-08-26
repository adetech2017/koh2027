<template>
  <div class="w-full md:w-56 flex-shrink-0 md:border-r md:pr-4">
    <h3 class="text-sm font-semibold text-dark mb-3">Category</h3>

    <div class="space-y-1 mb-4 max-h-64 overflow-y-auto">
      <label
        v-for="cat in localCategories"
        :key="cat.id"
        class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm cursor-pointer hover:bg-gray-100"
      >
        <span class="flex items-center gap-2 min-w-0">
          <input
            type="checkbox"
            :checked="modelValue === cat.id"
            @change="select(cat.id)"
            class="rounded border-gray-300 text-primary focus:ring-primary focus:ring-2"
          />
          <span class="truncate text-dark">{{ cat.name }}</span>
        </span>
        <span class="text-gray-400 text-xs flex-shrink-0">{{ cat.gallery_images_count ?? 0 }}</span>
      </label>
      <p v-if="localCategories.length === 0" class="text-xs text-gray-400 px-3 py-2">No categories yet.</p>
    </div>

    <div v-if="!creatingNew">
      <button type="button" @click="creatingNew = true" class="text-sm text-primary hover:text-primary-dark font-medium">
        + New category
      </button>
    </div>
    <div v-else class="space-y-2">
      <input
        v-model="newCategoryName"
        type="text"
        placeholder="Category name"
        class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
        :disabled="creating"
        @keydown.enter.prevent="confirmNewCategory"
      />
      <p v-if="createError" class="text-red-600 text-xs">{{ createError }}</p>
      <div class="flex gap-2">
        <button
          type="button"
          @click="confirmNewCategory"
          :disabled="creating || !newCategoryName.trim()"
          class="px-3 py-1.5 bg-primary hover:bg-primary-dark text-white rounded text-xs font-medium disabled:opacity-50"
        >
          {{ creating ? 'Adding…' : 'Add' }}
        </button>
        <button type="button" @click="cancelNewCategory" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-dark rounded text-xs font-medium">
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { inject, ref, watch } from 'vue'

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: [Number, String],
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const route = inject('route')

const localCategories = ref([...props.categories])
watch(() => props.categories, (val) => {
  localCategories.value = [...val]
})

const creatingNew = ref(false)
const newCategoryName = ref('')
const creating = ref(false)
const createError = ref('')

function select(id) {
  emit('update:modelValue', id)
}

async function confirmNewCategory() {
  const name = newCategoryName.value.trim()
  if (!name || creating.value) return

  creating.value = true
  createError.value = ''

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    const response = await fetch(route('admin.gallery-categories.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': token ?? '',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ name }),
    })

    const data = await response.json()

    if (!response.ok) {
      createError.value = data.errors?.name?.[0] ?? data.message ?? 'Could not add category.'
      return
    }

    localCategories.value.push({ ...data, gallery_images_count: 0 })
    emit('update:modelValue', data.id)
    newCategoryName.value = ''
    creatingNew.value = false
  } catch (e) {
    createError.value = 'Could not reach the server. Please try again.'
  } finally {
    creating.value = false
  }
}

function cancelNewCategory() {
  newCategoryName.value = ''
  creatingNew.value = false
  createError.value = ''
}
</script>
