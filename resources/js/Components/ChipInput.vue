<template>
  <div>
    <div
      class="flex flex-wrap items-center gap-1.5 px-2 py-1.5 border rounded-lg focus-within:ring-2 focus-within:ring-primary"
      :class="error ? 'border-red-400' : 'border-gray-300'"
      @click="inputEl?.focus()"
    >
      <span
        v-for="(value, index) in modelValue"
        :key="value"
        class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-full bg-gray-100 text-sm text-dark"
      >
        {{ value }}
        <button
          type="button"
          class="w-5 h-5 rounded-full hover:bg-gray-200 flex items-center justify-center text-gray-500"
          :aria-label="`Remove ${value}`"
          @click.stop="remove(index)"
        >×</button>
      </span>
      <input
        :id="id"
        ref="inputEl"
        v-model="draft"
        type="text"
        maxlength="30"
        :placeholder="modelValue.length ? '' : placeholder"
        class="flex-1 min-w-[8rem] border-0 p-1 text-sm focus:outline-none focus:ring-0"
        @keydown.enter.prevent="add(draft)"
        @keydown="onKeydown"
        @blur="add(draft)"
      />
    </div>
    <div v-if="availablePresets.length" class="flex flex-wrap gap-1.5 mt-2">
      <span class="text-xs text-gray-500 self-center">Quick add:</span>
      <button
        v-for="preset in availablePresets"
        :key="preset"
        type="button"
        class="text-xs px-2 py-0.5 rounded-full border border-dashed border-gray-300 text-gray-600 hover:border-primary hover:text-primary"
        @click="add(preset)"
      >+ {{ preset }}</button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  presets: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Type and press Enter' },
  error: { type: String, default: '' },
  id: { type: String, default: undefined },
})

const emit = defineEmits(['update:modelValue'])

const draft = ref('')
const inputEl = ref(null)

const availablePresets = computed(() => {
  const taken = new Set(props.modelValue.map(v => v.toLowerCase()))
  return props.presets.filter(p => !taken.has(p.toLowerCase()))
})

const add = (raw) => {
  // Allow pasting "S, M, L" in one go
  const values = String(raw || '').split(',').map(v => v.trim()).filter(Boolean)
  draft.value = ''
  if (!values.length) return
  const next = [...props.modelValue]
  for (const value of values) {
    if (!next.some(v => v.toLowerCase() === value.toLowerCase())) next.push(value)
  }
  emit('update:modelValue', next)
}

const remove = (index) => {
  const next = [...props.modelValue]
  next.splice(index, 1)
  emit('update:modelValue', next)
}

const onKeydown = (e) => {
  if (e.key === ',') {
    e.preventDefault()
    add(draft.value)
  } else if (e.key === 'Backspace' && !draft.value && props.modelValue.length) {
    remove(props.modelValue.length - 1)
  }
}
</script>
