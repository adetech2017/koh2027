<template>
  <form class="grid grid-cols-1 xl:grid-cols-3 gap-6" @submit.prevent="submit">
    <div class="xl:col-span-2 space-y-6">
      <div v-if="hasErrors" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
        <p class="font-semibold">Please fix the highlighted fields.</p>
      </div>

      <!-- Text -->
      <section class="bg-white rounded-lg shadow p-6 space-y-5">
        <div>
          <div class="flex justify-between items-baseline mb-1">
            <label for="p-title" class="block text-sm font-medium text-dark">Title <span class="text-red-600">*</span></label>
            <span class="text-xs text-gray-400">{{ form.title.length }}/150</span>
          </div>
          <input id="p-title" v-model="form.title" type="text" maxlength="150" placeholder="e.g. People First" :class="inputClass('title')" />
          <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
        </div>
        <div>
          <div class="flex justify-between items-baseline mb-1">
            <label for="p-summary" class="block text-sm font-medium text-dark">Summary <span class="text-red-600">*</span></label>
            <span class="text-xs" :class="form.summary.length > 300 ? 'text-red-600' : 'text-gray-400'">{{ form.summary.length }}/300</span>
          </div>
          <textarea id="p-summary" v-model="form.summary" rows="2" placeholder="One sentence shown on the homepage card" :class="inputClass('summary')" />
          <p v-if="form.errors.summary" class="text-red-600 text-sm mt-1">{{ form.errors.summary }}</p>
        </div>
        <div>
          <label for="p-body" class="block text-sm font-medium text-dark mb-1">Full description <span class="text-red-600">*</span></label>
          <textarea id="p-body" v-model="form.body" rows="10" placeholder="The detailed policy shown on the Platforms page" :class="inputClass('body')" />
          <p v-if="form.errors.body" class="text-red-600 text-sm mt-1">{{ form.errors.body }}</p>
          <p v-else class="text-xs text-gray-500 mt-1">Shown on the Platforms page. Leave a blank line between paragraphs.</p>
        </div>
      </section>

      <!-- Icon -->
      <section class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-dark mb-1">Icon <span class="text-red-600">*</span></h3>
        <p v-if="form.errors.icon" class="text-red-600 text-sm mb-2">{{ form.errors.icon }}</p>
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mt-3" role="radiogroup" aria-label="Icon">
          <button
            v-for="(icon, key) in platformIcons"
            :key="key"
            type="button"
            role="radio"
            :aria-checked="form.icon === key"
            :title="icon.label"
            class="flex flex-col items-center gap-1 p-3 rounded-lg border text-xs transition-colors"
            :class="form.icon === key ? 'border-primary ring-1 ring-primary bg-blue-50/50 text-dark' : 'border-gray-200 text-gray-500 hover:border-gray-400'"
            @click="form.icon = key"
          >
            <component :is="icon.component" class="w-6 h-6" :style="form.icon === key ? { color: form.color } : {}" />
            <span class="truncate w-full text-center">{{ icon.label }}</span>
          </button>
        </div>
      </section>

      <!-- Colour -->
      <section class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-dark mb-3">Colour <span class="text-red-600">*</span></h3>
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="swatch in swatches"
            :key="swatch"
            type="button"
            class="w-9 h-9 rounded-full border-2 transition-transform"
            :class="form.color.toUpperCase() === swatch ? 'border-dark scale-110' : 'border-white shadow'"
            :style="{ backgroundColor: swatch }"
            :aria-label="`Use colour ${swatch}`"
            @click="form.color = swatch"
          />
          <label class="flex items-center gap-2 ml-2 text-sm text-gray-600">
            <input v-model="form.color" type="color" class="w-9 h-9 rounded cursor-pointer border border-gray-300 p-0.5" aria-label="Custom colour" />
            Custom
          </label>
          <input v-model="form.color" type="text" maxlength="7" class="w-24 px-2 py-1.5 border rounded-lg text-sm font-mono" :class="form.errors.color ? 'border-red-400' : 'border-gray-300'" aria-label="Hex colour" />
        </div>
        <p v-if="form.errors.color" class="text-red-600 text-sm mt-2">{{ form.errors.color }}</p>
      </section>

      <!-- Visibility & actions -->
      <section class="bg-white rounded-lg shadow p-6">
        <label class="flex items-start gap-3 cursor-pointer">
          <input v-model="form.is_active" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
          <span>
            <span class="block text-sm font-medium text-dark">Show on the website</span>
            <span class="block text-xs text-gray-500">Hidden pillars are kept here but not shown to visitors.</span>
          </span>
        </label>
      </section>

      <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
        <Link href="/admin/platform-pillars" class="px-6 py-2 text-center bg-gray-100 hover:bg-gray-200 text-dark rounded-lg font-medium transition-colors">
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
        >
          {{ form.processing ? 'Saving…' : (pillar ? 'Save changes' : 'Create pillar') }}
        </button>
      </div>
    </div>

    <!-- Preview -->
    <div>
      <div class="xl:sticky xl:top-6 space-y-2">
        <p class="text-sm font-medium text-gray-600">Homepage card preview</p>
        <div class="pointer-events-none" aria-hidden="true">
          <PlatformCard :pillar="previewPillar" />
        </div>
        <p v-if="!form.is_active" class="text-xs text-amber-700">Hidden: this pillar won't appear on the site.</p>
      </div>
    </div>
  </form>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import PlatformCard from '@/Components/PlatformCard.vue'
import { platformIcons } from '@/Utils/platformIcons'

const props = defineProps({
  pillar: { type: Object, default: null },
})

// Campaign palette (the colours used by the existing pillars)
const swatches = ['#003D82', '#27AE60', '#FFB81C', '#6B21A8', '#DB2777', '#B91C1C', '#0D9488', '#EA580C']

const p = props.pillar || {}
const form = useForm({
  title: p.title || '',
  summary: p.summary || '',
  body: p.body || '',
  // Unknown stored icons fall back to the first choice so the form can still be saved
  icon: platformIcons[p.icon] ? p.icon : (props.pillar ? '' : 'heart'),
  color: /^#[0-9A-Fa-f]{6}$/.test(p.color || '') ? p.color.toUpperCase() : '#003D82',
  is_active: props.pillar ? !!p.is_active : true,
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

const previewPillar = computed(() => ({
  slug: p.slug || 'preview',
  title: form.title || 'Pillar title',
  summary: form.summary || 'A short summary of this pillar appears here.',
  icon: form.icon,
  color: /^#[0-9A-Fa-f]{6}$/.test(form.color) ? form.color : '#003D82',
}))

const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const submit = () => {
  const options = { preserveScroll: true, onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }) }
  if (props.pillar) form.put(`/admin/platform-pillars/${props.pillar.id}`, options)
  else form.post('/admin/platform-pillars', options)
}
</script>
