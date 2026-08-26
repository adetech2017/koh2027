<template>
  <div class="fixed bottom-5 right-5 z-50 flex flex-col items-end">
    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div
        v-if="open"
        class="mb-3 w-[22rem] max-w-[calc(100vw-2.5rem)] h-[28rem] max-h-[calc(100vh-8rem)] bg-white rounded-lg shadow-card-prominent border border-light-gray flex flex-col overflow-hidden"
      >
        <div class="bg-primary text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
          <div>
            <p class="font-semibold text-sm leading-tight">Manifesto Assistant</p>
            <p class="text-xs text-white/70 leading-tight">Ask about The Lagos Promise</p>
          </div>
          <button type="button" class="text-white/80 hover:text-white" @click="open = false">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div ref="scrollEl" class="flex-grow overflow-y-auto px-3 py-3 space-y-3 bg-light-gray/40">
          <div v-if="messages.length === 0" class="text-sm text-body/70 text-center mt-6 px-4">
            Ask me anything about the campaign's manifesto — the pillars, promises, and policies.
          </div>

          <div
            v-for="(m, i) in messages"
            :key="i"
            class="flex"
            :class="m.role === 'user' ? 'justify-end' : 'justify-start'"
          >
            <div
              v-if="m.role === 'assistant'"
              class="max-w-[85%] rounded-lg rounded-bl-sm px-3 py-2 text-sm bg-white border border-light-gray text-body chat-markdown"
              v-html="renderMarkdown(m.content)"
            />
            <div
              v-else
              class="max-w-[85%] rounded-lg rounded-br-sm px-3 py-2 text-sm whitespace-pre-wrap bg-primary text-white"
            >
              {{ m.content }}
            </div>
          </div>

          <div v-if="loading" class="flex justify-start">
            <div class="bg-white border border-light-gray rounded-lg rounded-bl-sm px-3 py-2 text-sm text-body/60">
              Thinking…
            </div>
          </div>

          <div v-if="error" class="text-xs text-red-600 text-center">{{ error }}</div>
        </div>

        <form class="flex-shrink-0 border-t border-light-gray p-2 flex gap-2" @submit.prevent="send">
          <input
            v-model="draft"
            type="text"
            placeholder="Ask about a pillar or policy…"
            class="flex-grow text-sm rounded border border-light-gray px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
            :disabled="loading"
          >
          <button
            type="submit"
            class="bg-primary text-white rounded px-3 py-2 disabled:opacity-50"
            :disabled="loading || !draft.trim()"
          >
            <PaperAirplaneIcon class="w-4 h-4" />
          </button>
        </form>
      </div>
    </transition>

    <button
      type="button"
      class="w-14 h-14 rounded-full bg-primary text-white shadow-card-hover flex items-center justify-center hover:bg-primary-dark transition-colors"
      @click="open = !open"
    >
      <XMarkIcon v-if="open" class="w-6 h-6" />
      <ChatBubbleLeftRightIcon v-else class="w-6 h-6" />
    </button>
  </div>
</template>

<script setup>
import { inject, ref, nextTick } from 'vue'
import { ChatBubbleLeftRightIcon, PaperAirplaneIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

marked.setOptions({ breaks: true })

function renderMarkdown(content) {
  return DOMPurify.sanitize(marked.parse(content))
}

const route = inject('route')
const open = ref(false)
const draft = ref('')
const messages = ref([])
const loading = ref(false)
const error = ref('')
const scrollEl = ref(null)

async function scrollToBottom() {
  await nextTick()
  if (scrollEl.value) {
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
  }
}

async function send() {
  const question = draft.value.trim()
  if (!question || loading.value) return

  error.value = ''
  messages.value.push({ role: 'user', content: question })
  draft.value = ''
  loading.value = true
  scrollToBottom()

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    const response = await fetch(route('manifesto-chat'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': token ?? '',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        message: question,
        history: messages.value.slice(0, -1),
      }),
    })

    const data = await response.json()

    if (!response.ok) {
      error.value = data.message ?? 'Something went wrong. Please try again.'
      return
    }

    messages.value.push({ role: 'assistant', content: data.message })
  } catch (e) {
    error.value = 'Could not reach the assistant. Please check your connection and try again.'
  } finally {
    loading.value = false
    scrollToBottom()
  }
}
</script>

<style scoped>
.chat-markdown :deep(p) {
  margin-bottom: 0.5rem;
}
.chat-markdown :deep(p:last-child) {
  margin-bottom: 0;
}
.chat-markdown :deep(ul),
.chat-markdown :deep(ol) {
  margin: 0.25rem 0 0.5rem 1.1rem;
}
.chat-markdown :deep(ul) {
  list-style: disc;
}
.chat-markdown :deep(ol) {
  list-style: decimal;
}
.chat-markdown :deep(li) {
  margin-bottom: 0.15rem;
}
.chat-markdown :deep(strong) {
  font-weight: 600;
}
.chat-markdown :deep(a) {
  color: var(--color-primary, #1d4ed8);
  text-decoration: underline;
}
.chat-markdown :deep(code) {
  background: rgba(0, 0, 0, 0.06);
  border-radius: 0.25rem;
  padding: 0.05rem 0.3rem;
  font-size: 0.85em;
}
</style>
