<template>
  <div class="editor-container">
    <!-- Toolbar -->
    <div v-if="editor" class="editor-toolbar">
      <div class="toolbar-group">
        <button
          type="button"
          @click="editor.chain().focus().toggleBold().run()"
          :class="{ active: editor.isActive('bold') }"
          class="toolbar-button"
          title="Bold (Ctrl+B)"
        >
          <strong>B</strong>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleItalic().run()"
          :class="{ active: editor.isActive('italic') }"
          class="toolbar-button"
          title="Italic (Ctrl+I)"
        >
          <em>I</em>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleUnderline().run()"
          :class="{ active: editor.isActive('underline') }"
          class="toolbar-button"
          title="Underline (Ctrl+U)"
        >
          <u>U</u>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleStrike().run()"
          :class="{ active: editor.isActive('strike') }"
          class="toolbar-button"
          title="Strikethrough"
        >
          <s>S</s>
        </button>
        <button
          type="button"
          @click="openLinkPanel"
          :class="{ active: editor.isActive('link') || linkPanel.open }"
          class="toolbar-button"
          title="Add / edit link (Ctrl+K)"
        >
          🔗 Link
        </button>
      </div>

      <div class="toolbar-divider"></div>

      <div class="toolbar-group">
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          :class="{ active: editor.isActive('heading', { level: 1 }) }"
          class="toolbar-button"
          title="Heading 1"
        >
          H1
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="{ active: editor.isActive('heading', { level: 2 }) }"
          class="toolbar-button"
          title="Heading 2"
        >
          H2
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          :class="{ active: editor.isActive('heading', { level: 3 }) }"
          class="toolbar-button"
          title="Heading 3"
        >
          H3
        </button>
      </div>

      <div class="toolbar-divider"></div>

      <div class="toolbar-group">
        <button
          type="button"
          @click="editor.chain().focus().toggleBulletList().run()"
          :class="{ active: editor.isActive('bulletList') }"
          class="toolbar-button"
          title="Bullet List"
        >
          • List
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleOrderedList().run()"
          :class="{ active: editor.isActive('orderedList') }"
          class="toolbar-button"
          title="Ordered List"
        >
          1. List
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleBlockquote().run()"
          :class="{ active: editor.isActive('blockquote') }"
          class="toolbar-button"
          title="Blockquote"
        >
          "
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleCodeBlock().run()"
          :class="{ active: editor.isActive('codeBlock') }"
          class="toolbar-button"
          title="Code Block"
        >
          &lt;&gt;
        </button>
      </div>

      <div class="toolbar-divider"></div>

      <div class="toolbar-group">
        <button
          type="button"
          @click="editor.chain().focus().undo().run()"
          class="toolbar-button"
          title="Undo (Ctrl+Z)"
        >
          ↶
        </button>
        <button
          type="button"
          @click="editor.chain().focus().redo().run()"
          class="toolbar-button"
          title="Redo (Ctrl+Y)"
        >
          ↷
        </button>
      </div>
    </div>

    <!-- Link panel -->
    <div v-if="editor && linkPanel.open" class="link-panel" @keydown.esc.prevent="closeLinkPanel">
      <div class="link-panel-fields">
        <label class="link-field">
          <span class="link-label">URL</span>
          <input
            ref="linkUrlInput"
            v-model="linkPanel.url"
            type="text"
            inputmode="url"
            placeholder="https://example.com"
            class="link-input"
            @keydown.enter.prevent="applyLink"
          />
        </label>
        <label v-if="linkPanel.needsText" class="link-field">
          <span class="link-label">Text to display</span>
          <input
            v-model="linkPanel.text"
            type="text"
            placeholder="Defaults to the URL"
            class="link-input"
            @keydown.enter.prevent="applyLink"
          />
        </label>
      </div>
      <label class="link-checkbox">
        <input v-model="linkPanel.newTab" type="checkbox" @change="linkPanel.newTabTouched = true" />
        <span>Open in new tab</span>
      </label>
      <p v-if="linkPanel.error" class="link-error">{{ linkPanel.error }}</p>
      <div class="link-panel-actions">
        <button
          v-if="linkPanel.editing"
          type="button"
          class="link-btn link-btn-danger"
          @click="removeLink"
        >
          Remove link
        </button>
        <span class="link-spacer"></span>
        <button type="button" class="link-btn" @click="closeLinkPanel">Cancel</button>
        <button type="button" class="link-btn link-btn-primary" @click="applyLink">
          {{ linkPanel.editing ? 'Update' : 'Add link' }}
        </button>
      </div>
    </div>

    <!-- Editor -->
    <EditorContent :editor="editor" class="editor-content" />
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { watch, reactive, ref, nextTick } from 'vue'
import { isExternalUrl } from '@/Utils/links'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [1, 2, 3, 4, 5, 6],
      },
      link: {
        openOnClick: false,
        autolink: true,
        defaultProtocol: 'https',
        // No default target/rel: keep exactly what each link was saved with.
        // Links without a target (e.g. pasted URLs) get the external => new tab rule on the public page.
        HTMLAttributes: { target: null, rel: null, class: null },
      },
    }),
  ],
  editorProps: {
    handleKeyDown: (_view, event) => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        openLinkPanel()
        return true
      }
      return false
    },
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
})

const linkUrlInput = ref(null)
const linkPanel = reactive({
  open: false,
  url: '',
  text: '',
  needsText: false,
  editing: false,
  newTab: false,
  newTabTouched: false,
  error: '',
  from: 0,
  to: 0,
})

// Until the writer ticks/unticks the box, follow the URL: other websites => new tab
watch(
  () => linkPanel.url,
  (url) => {
    if (linkPanel.open && !linkPanel.newTabTouched) linkPanel.newTab = isExternalUrl(normaliseUrl(url))
  }
)

function openLinkPanel() {
  const ed = editor.value
  if (!ed) return

  // Grow the selection to the whole link when the cursor is inside one
  if (ed.isActive('link')) ed.chain().extendMarkRange('link').run()

  const { from, to, empty } = ed.state.selection
  const attrs = ed.getAttributes('link')
  const editing = ed.isActive('link')
  // Existing link with a saved choice keeps it; otherwise follow the URL rule
  const savedTarget = editing && (attrs.target === '_blank' || attrs.target === '_self') ? attrs.target : null
  Object.assign(linkPanel, {
    open: true,
    url: attrs.href || '',
    text: '',
    needsText: empty,
    editing,
    newTab: savedTarget ? savedTarget === '_blank' : isExternalUrl(attrs.href || ''),
    newTabTouched: !!savedTarget,
    error: '',
    from,
    to,
  })
  nextTick(() => linkUrlInput.value?.focus())
}

function closeLinkPanel() {
  linkPanel.open = false
  editor.value?.chain().focus().setTextSelection({ from: linkPanel.from, to: linkPanel.to }).run()
}

function normaliseUrl(raw) {
  const url = raw.trim()
  if (!url) return ''
  return /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url) ? url : `https://${url}`
}

function applyLink() {
  const href = normaliseUrl(linkPanel.url)
  if (!href) {
    linkPanel.error = 'Enter a URL.'
    linkUrlInput.value?.focus()
    return
  }

  const attrs = linkPanel.newTab
    ? { href, target: '_blank', rel: 'noopener noreferrer' }
    : { href, target: '_self', rel: null }
  const chain = editor.value.chain().focus().setTextSelection({ from: linkPanel.from, to: linkPanel.to })

  if (linkPanel.needsText) {
    const text = linkPanel.text.trim() || linkPanel.url.trim()
    chain.insertContent({ type: 'text', text, marks: [{ type: 'link', attrs }] }).run()
  } else {
    chain.extendMarkRange('link').setLink(attrs).run()
  }
  linkPanel.open = false
}

function removeLink() {
  editor.value
    .chain()
    .focus()
    .setTextSelection({ from: linkPanel.from, to: linkPanel.to })
    .extendMarkRange('link')
    .unsetLink()
    .run()
  linkPanel.open = false
}

watch(
  () => props.modelValue,
  (value) => {
    const isSame = editor.value?.getHTML() === value
    if (isSame) return
    editor.value?.commands.setContent(value, false)
  }
)
</script>

<style scoped>
.editor-container {
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  overflow: hidden;
  background-color: white;
}

.editor-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  align-items: center;
  padding: 0.75rem;
  background-color: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.toolbar-group {
  display: flex;
  gap: 0.25rem;
}

.toolbar-divider {
  width: 1px;
  height: 1.5rem;
  background-color: #d1d5db;
  margin: 0 0.25rem;
}

.toolbar-button {
  padding: 0.5rem 0.75rem;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  color: #1f2937;
  line-height: 1;
}

.toolbar-button:hover {
  background-color: #f3f4f6;
  border-color: #9ca3af;
}

.toolbar-button.active {
  background-color: #3b82f6;
  color: white;
  border-color: #2563eb;
}

.link-panel {
  padding: 0.75rem;
  background-color: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.link-panel-fields {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.link-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1 1 14rem;
}

.link-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #4b5563;
}

.link-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  background-color: white;
  color: #1f2937;
}

.link-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.link-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.75rem;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
  user-select: none;
}

.link-error {
  margin-top: 0.5rem;
  font-size: 0.8125rem;
  color: #dc2626;
}

.link-panel-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.link-spacer {
  flex: 1;
}

.link-btn {
  padding: 0.45rem 0.9rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background-color: white;
  color: #1f2937;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.link-btn:hover {
  background-color: #f3f4f6;
}

.link-btn-primary {
  background-color: #3b82f6;
  border-color: #2563eb;
  color: white;
}

.link-btn-primary:hover {
  background-color: #2563eb;
}

.link-btn-danger {
  color: #dc2626;
  border-color: #fca5a5;
}

.link-btn-danger:hover {
  background-color: #fef2f2;
}

:deep(.tiptap a) {
  color: #2563eb;
  text-decoration: underline;
  cursor: text;
}

.editor-content {
  padding: 1.5rem;
  min-height: 20rem;
  font-size: 1rem;
  line-height: 1.6;
}

:deep(.tiptap) {
  outline: none;
}

:deep(.tiptap p) {
  margin: 0.5rem 0;
}

:deep(.tiptap h1) {
  font-size: 1.875rem;
  font-weight: bold;
  margin: 1rem 0 0.5rem 0;
  line-height: 1.2;
}

:deep(.tiptap h2) {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 0.875rem 0 0.5rem 0;
  line-height: 1.2;
}

:deep(.tiptap h3) {
  font-size: 1.25rem;
  font-weight: bold;
  margin: 0.75rem 0 0.5rem 0;
  line-height: 1.2;
}

:deep(.tiptap h4),
:deep(.tiptap h5),
:deep(.tiptap h6) {
  font-weight: bold;
  margin: 0.5rem 0 0.25rem 0;
  line-height: 1.2;
}

:deep(.tiptap ul) {
  list-style: disc;
  margin-left: 1.5rem;
  margin: 0.5rem 0;
  padding-left: 1.5rem;
}

:deep(.tiptap ol) {
  list-style: decimal;
  margin-left: 1.5rem;
  margin: 0.5rem 0;
  padding-left: 1.5rem;
}

:deep(.tiptap li) {
  margin: 0.25rem 0;
}

:deep(.tiptap blockquote) {
  border-left: 4px solid #3b82f6;
  padding-left: 1rem;
  color: #6b7280;
  font-style: italic;
  margin: 0.5rem 0;
}

:deep(.tiptap code) {
  background-color: #f3f4f6;
  color: #1f2937;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-family: 'Courier New', monospace;
  font-size: 0.875em;
}

:deep(.tiptap pre) {
  background-color: #1f2937;
  color: #f3f4f6;
  padding: 1rem;
  border-radius: 0.375rem;
  overflow-x: auto;
  margin: 0.5rem 0;
}

:deep(.tiptap pre code) {
  background-color: transparent;
  color: inherit;
  padding: 0;
  border-radius: 0;
}

:deep(.tiptap strong) {
  font-weight: bold;
}

:deep(.tiptap em) {
  font-style: italic;
}

:deep(.tiptap u) {
  text-decoration: underline;
}

:deep(.tiptap s) {
  text-decoration: line-through;
}
</style>
