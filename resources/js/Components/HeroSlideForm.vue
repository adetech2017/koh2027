<template>
  <form class="grid grid-cols-1 xl:grid-cols-5 gap-6" @submit.prevent="submit">
    <div class="xl:col-span-3 space-y-6">
      <div v-if="hasErrors" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm" role="alert">
        <p class="font-semibold">Please fix the highlighted fields.</p>
      </div>

      <!-- Text -->
      <section class="bg-white rounded-lg shadow p-6 space-y-5">
        <h3 class="text-lg font-semibold text-dark">Text</h3>
        <div v-for="field in textFields" :key="field.key">
          <div class="flex justify-between items-baseline mb-1">
            <label :for="`hs-${field.key}`" class="block text-sm font-medium text-dark">{{ field.label }} <span class="text-red-600">*</span></label>
            <span class="text-xs" :class="(form[field.key] || '').length > field.max ? 'text-red-600' : 'text-gray-400'">
              {{ (form[field.key] || '').length }}/{{ field.max }}
            </span>
          </div>
          <component
            :is="field.multiline ? 'textarea' : 'input'"
            :id="`hs-${field.key}`"
            v-model="form[field.key]"
            :rows="field.multiline ? 3 : undefined"
            type="text"
            :placeholder="field.placeholder"
            :class="inputClass(field.key)"
          />
          <p v-if="form.errors[field.key]" class="text-red-600 text-sm mt-1">{{ form.errors[field.key] }}</p>
          <p v-else-if="field.help" class="text-xs text-gray-500 mt-1">{{ field.help }}</p>
        </div>
      </section>

      <!-- Button -->
      <section class="bg-white rounded-lg shadow p-6 space-y-5">
        <div>
          <h3 class="text-lg font-semibold text-dark">Button</h3>
          <p class="text-sm text-gray-500">Optional. Leave both fields empty for a slide without a button.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label for="hs-cta-text" class="block text-sm font-medium text-dark mb-1">Button text</label>
            <input id="hs-cta-text" v-model="form.cta_text" type="text" maxlength="50" placeholder="e.g. Get Involved" :class="inputClass('cta_text')" />
            <p v-if="form.errors.cta_text" class="text-red-600 text-sm mt-1">{{ form.errors.cta_text }}</p>
          </div>
          <div>
            <label for="hs-cta-url" class="block text-sm font-medium text-dark mb-1">Link</label>
            <input id="hs-cta-url" v-model="form.cta_url" type="text" maxlength="255" list="hs-page-options" placeholder="/volunteer or https://…" :class="inputClass('cta_url')" />
            <datalist id="hs-page-options">
              <option v-for="path in pageSuggestions" :key="path" :value="path" />
            </datalist>
            <p v-if="form.errors.cta_url" class="text-red-600 text-sm mt-1">{{ form.errors.cta_url }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">A page on this site (starts with /) or a full https:// link.</p>
          </div>
        </div>
        <fieldset>
          <legend class="block text-sm font-medium text-dark mb-2">Button colour</legend>
          <div class="flex gap-3">
            <label
              v-for="style in ctaStyles"
              :key="style.value"
              class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer"
              :class="form.cta_style === style.value ? 'border-primary ring-1 ring-primary' : 'border-gray-300'"
            >
              <input v-model="form.cta_style" type="radio" :value="style.value" class="sr-only" />
              <span :class="['w-4 h-4 rounded', style.swatch]" />
              <span class="text-sm text-dark">{{ style.label }}</span>
            </label>
          </div>
        </fieldset>
      </section>

      <!-- Image -->
      <section class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold text-dark">Background image <span v-if="!slide" class="text-red-600">*</span></h3>
        <div>
          <label for="hs-image" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-dark hover:border-primary hover:text-primary cursor-pointer transition-colors">
            {{ previewUrl ? 'Replace image' : 'Choose image' }}
          </label>
          <input id="hs-image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" @change="onImage" />
          <p class="text-xs text-gray-500 mt-1">
            <template v-if="form.image_path">{{ form.image_path.name }} · </template>
            Landscape, at least 1920×1080 works best. JPG, PNG, GIF or WebP, up to 2 MB.
            <template v-if="slide && !form.image_path"> Leave empty to keep the current image.</template>
          </p>
          <p v-if="imageWarning" class="text-xs text-amber-700 mt-1">{{ imageWarning }}</p>
          <p v-if="form.errors.image_path" class="text-red-600 text-sm mt-1">{{ form.errors.image_path }}</p>
        </div>
        <div>
          <label for="hs-alt" class="block text-sm font-medium text-dark mb-1">Image description</label>
          <input id="hs-alt" v-model="form.image_alt" type="text" maxlength="200" placeholder="e.g. Supporters at the Ikeja rally" :class="inputClass('image_alt')" />
          <p v-if="form.errors.image_alt" class="text-red-600 text-sm mt-1">{{ form.errors.image_alt }}</p>
          <p v-else class="text-xs text-gray-500 mt-1">Read aloud by screen readers.</p>
        </div>
      </section>

      <!-- Visibility -->
      <section class="bg-white rounded-lg shadow p-6">
        <label class="flex items-start gap-3 cursor-pointer">
          <input v-model="form.is_active" type="checkbox" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary" />
          <span>
            <span class="block text-sm font-medium text-dark">Show on homepage</span>
            <span class="block text-xs text-gray-500">Hidden slides are kept here but not shown to visitors.</span>
          </span>
        </label>
      </section>

      <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
        <Link href="/admin/hero-slides" class="px-6 py-2 text-center bg-gray-100 hover:bg-gray-200 text-dark rounded-lg font-medium transition-colors">
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : (slide ? 'Save changes' : 'Create slide') }}
        </button>
      </div>
    </div>

    <!-- Live preview -->
    <div class="xl:col-span-2">
      <div class="xl:sticky xl:top-6 space-y-2">
        <p class="text-sm font-medium text-gray-600">Preview</p>
        <div class="relative aspect-video rounded-lg overflow-hidden bg-dark shadow">
          <img v-if="previewUrl" :src="previewUrl" alt="" class="absolute inset-0 w-full h-full object-cover" />
          <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-transparent" />
          <div class="relative h-full flex flex-col justify-center p-5 w-3/4">
            <span v-if="form.tagline" class="self-start text-gold text-[10px] font-semibold uppercase tracking-widest border-b border-gold pb-1 mb-2">{{ form.tagline }}</span>
            <p class="text-white font-bold text-lg leading-tight mb-2 line-clamp-3">{{ form.headline || 'Your headline' }}</p>
            <p v-if="form.subtitle" class="text-gray-200 text-xs leading-snug mb-3 line-clamp-3">{{ form.subtitle }}</p>
            <span
              v-if="form.cta_text"
              :class="['self-start px-3 py-1.5 rounded text-xs font-semibold', form.cta_style === 'primary' ? 'bg-primary text-white' : 'bg-gold text-dark']"
            >{{ form.cta_text }}</span>
          </div>
          <span v-if="!form.is_active" class="absolute top-2 right-2 text-[10px] font-semibold uppercase bg-white/90 text-dark px-2 py-0.5 rounded">Hidden</span>
        </div>
        <p class="text-xs text-gray-500">Approximate. Text sits over the left half of the image, so keep faces and key details on the right.</p>
      </div>
    </div>
  </form>
</template>

<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  slide: { type: Object, default: null },
})

const textFields = [
  { key: 'tagline', label: 'Tagline', max: 100, placeholder: 'e.g. Lagos 2027', help: 'Small gold text above the headline.' },
  { key: 'headline', label: 'Headline', max: 200, placeholder: 'e.g. Join the Movement for Better Lagos' },
  { key: 'subtitle', label: 'Subtitle', max: 300, placeholder: 'One or two supporting sentences', multiline: true },
]

const ctaStyles = [
  { value: 'primary', label: 'Blue', swatch: 'bg-primary' },
  { value: 'secondary', label: 'Gold', swatch: 'bg-gold' },
]

// Existing public pages; volunteering happens on the external campaign site, as in the main navigation
const pageSuggestions = [
  '/about', '/platforms', '/events', '/news', '/gallery', '/materials', '/merchandise', '/contact',
  'https://hamzatforlagos.com/volunteer',
]

const s = props.slide || {}
const form = useForm({
  tagline: s.tagline || '',
  headline: s.headline || '',
  subtitle: s.subtitle || '',
  cta_text: s.cta_text || '',
  cta_url: s.cta_url || '',
  cta_style: s.cta_style || 'primary',
  image_path: null,
  image_alt: s.image_alt || '',
  is_active: props.slide ? !!s.is_active : true,
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

const localPreview = ref(null)
const imageWarning = ref('')
const previewUrl = computed(() => localPreview.value || (s.image_path ? s.image_url : null))

const onImage = (evt) => {
  const file = evt.target.files[0]
  if (!file) return
  form.image_path = file
  if (localPreview.value) URL.revokeObjectURL(localPreview.value)
  localPreview.value = URL.createObjectURL(file)

  // Warn (not block) when the image will look soft or badly cropped full-screen
  imageWarning.value = ''
  const img = new Image()
  img.onload = () => {
    if (img.width < img.height) imageWarning.value = 'This image is portrait; it will be heavily cropped on wide screens.'
    else if (img.width < 1280) imageWarning.value = `This image is only ${img.width}px wide and may look blurry full-screen.`
  }
  img.src = localPreview.value
}

onBeforeUnmount(() => {
  if (localPreview.value) URL.revokeObjectURL(localPreview.value)
})

const inputClass = (field) => [
  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
  form.errors[field] ? 'border-red-400' : 'border-gray-300',
]

const submit = () => {
  // File uploads must be POST with a spoofed method, since PHP can't parse multipart PUT bodies
  form
    .transform((data) => ({ ...data, ...(props.slide ? { _method: 'put' } : {}) }))
    .post(props.slide ? `/admin/hero-slides/${props.slide.id}` : '/admin/hero-slides', {
      forceFormData: true,
      preserveScroll: true,
      onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    })
}
</script>
