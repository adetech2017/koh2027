<template>
  <form class="space-y-6" @submit.prevent="submit">
    <div v-if="hasErrors" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
      <p class="font-semibold">Please fix the highlighted fields.</p>
    </div>

    <!-- Details -->
    <section class="bg-white rounded-lg shadow p-6 space-y-5">
      <h3 class="text-lg font-semibold text-dark">Details</h3>
      <div>
        <label for="m-name" class="block text-sm font-medium text-dark mb-1">Design name <span class="text-red-600">*</span></label>
        <input id="m-name" v-model="form.name" type="text" maxlength="200" placeholder="e.g. Campaign T-Shirt" :class="inputClass('name')" />
        <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</p>
      </div>
      <div>
        <label for="m-category" class="block text-sm font-medium text-dark mb-1">Category <span class="text-red-600">*</span></label>
        <input id="m-category" v-model="form.category" type="text" list="m-category-options" maxlength="100" placeholder="e.g. t-shirt" :class="inputClass('category')" />
        <datalist id="m-category-options">
          <option v-for="cat in categories" :key="cat" :value="cat" />
        </datalist>
        <p v-if="form.errors.category" class="text-red-600 text-sm mt-1">{{ form.errors.category }}</p>
        <p v-else class="text-xs text-gray-500 mt-1">Used for the category filter on the website. Pick an existing one to keep designs grouped.</p>
      </div>
      <div>
        <label for="m-description" class="block text-sm font-medium text-dark mb-1">Description <span class="text-red-600">*</span></label>
        <textarea id="m-description" v-model="form.description" rows="4" placeholder="Item, material, what's printed where, approved usage…" :class="inputClass('description')" />
        <p v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</p>
      </div>
    </section>

    <!-- Sizes & colours -->
    <section class="bg-white rounded-lg shadow p-6 space-y-5">
      <h3 class="text-lg font-semibold text-dark">Sizes & colours</h3>
      <div>
        <label for="m-sizes" class="block text-sm font-medium text-dark mb-1">Sizes</label>
        <ChipInput id="m-sizes" v-model="form.sizes" :presets="sizePresets" placeholder="e.g. M, then Enter" :error="sizeError" />
        <p v-if="sizeError" class="text-red-600 text-sm mt-1">{{ sizeError }}</p>
        <p v-else class="text-xs text-gray-500 mt-1">Leave empty if the design has no sizes.</p>
      </div>
      <div>
        <label for="m-colors" class="block text-sm font-medium text-dark mb-1">Colours</label>
        <ChipInput id="m-colors" v-model="form.colors" :presets="colorPresets" placeholder="e.g. Navy Blue, then Enter" :error="colorError" />
        <p v-if="colorError" class="text-red-600 text-sm mt-1">{{ colorError }}</p>
      </div>
    </section>

    <!-- Visibility -->
    <section class="bg-white rounded-lg shadow p-6 space-y-4">
      <h3 class="text-lg font-semibold text-dark">Visibility</h3>
      <label class="flex items-start gap-3 cursor-pointer">
        <input v-model="form.is_active" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
        <span>
          <span class="block text-sm font-medium text-dark">Show on the website</span>
          <span class="block text-xs text-gray-500">Hidden designs stay here but visitors can't see them.</span>
        </span>
      </label>
      <label class="flex items-start gap-3 cursor-pointer">
        <input v-model="form.is_featured" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
        <span>
          <span class="block text-sm font-medium text-dark">Featured</span>
          <span class="block text-xs text-gray-500">Highlight this design on the site.</span>
        </span>
      </label>
    </section>

    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
      <Link href="/admin/merchandise" class="px-6 py-2 text-center bg-gray-100 hover:bg-gray-200 text-dark rounded-lg font-medium transition-colors">
        {{ product ? 'Back' : 'Cancel' }}
      </Link>
      <button
        type="submit"
        :disabled="form.processing"
        class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
      >
        {{ form.processing ? 'Saving…' : (product ? 'Save changes' : 'Create design & add photos') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import ChipInput from '@/Components/ChipInput.vue'

const props = defineProps({
  product: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
})

const sizePresets = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'One Size']
const colorPresets = ['Navy Blue', 'White', 'Gold', 'Black']

// Older rows stored these as JSON strings; accept either shape
const toList = (value) => {
  if (Array.isArray(value)) return value
  try {
    const parsed = JSON.parse(value || '[]')
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}

const p = props.product || {}
const form = useForm({
  name: p.name || '',
  category: p.category || '',
  description: p.description || '',
  sizes: toList(p.sizes),
  colors: toList(p.colors),
  is_active: props.product ? !!p.is_active : true,
  is_featured: !!p.is_featured,
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)
const firstError = (prefix) => Object.entries(form.errors).find(([key]) => key === prefix || key.startsWith(`${prefix}.`))?.[1] || ''
const sizeError = computed(() => firstError('sizes'))
const colorError = computed(() => firstError('colors'))


const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const submit = () => {
  const options = { preserveScroll: true, onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }) }
  if (props.product) form.put(`/admin/merchandise/${props.product.id}`, options)
  else form.post('/admin/merchandise', options)
}
</script>
