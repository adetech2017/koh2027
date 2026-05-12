<template>
  <div class="editor-container">
    <!-- Toolbar -->
    <div v-if="editor" class="editor-toolbar">
      <div class="toolbar-group">
        <button
          @click="editor.chain().focus().toggleBold().run()"
          :class="{ active: editor.isActive('bold') }"
          class="toolbar-button"
          title="Bold (Ctrl+B)"
        >
          <strong>B</strong>
        </button>
        <button
          @click="editor.chain().focus().toggleItalic().run()"
          :class="{ active: editor.isActive('italic') }"
          class="toolbar-button"
          title="Italic (Ctrl+I)"
        >
          <em>I</em>
        </button>
        <button
          @click="editor.chain().focus().toggleUnderline().run()"
          :class="{ active: editor.isActive('underline') }"
          class="toolbar-button"
          title="Underline (Ctrl+U)"
        >
          <u>U</u>
        </button>
        <button
          @click="editor.chain().focus().toggleStrike().run()"
          :class="{ active: editor.isActive('strike') }"
          class="toolbar-button"
          title="Strikethrough"
        >
          <s>S</s>
        </button>
      </div>

      <div class="toolbar-divider"></div>

      <div class="toolbar-group">
        <button
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          :class="{ active: editor.isActive('heading', { level: 1 }) }"
          class="toolbar-button"
          title="Heading 1"
        >
          H1
        </button>
        <button
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="{ active: editor.isActive('heading', { level: 2 }) }"
          class="toolbar-button"
          title="Heading 2"
        >
          H2
        </button>
        <button
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
          @click="editor.chain().focus().toggleBulletList().run()"
          :class="{ active: editor.isActive('bulletList') }"
          class="toolbar-button"
          title="Bullet List"
        >
          • List
        </button>
        <button
          @click="editor.chain().focus().toggleOrderedList().run()"
          :class="{ active: editor.isActive('orderedList') }"
          class="toolbar-button"
          title="Ordered List"
        >
          1. List
        </button>
        <button
          @click="editor.chain().focus().toggleBlockquote().run()"
          :class="{ active: editor.isActive('blockquote') }"
          class="toolbar-button"
          title="Blockquote"
        >
          "
        </button>
        <button
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
          @click="editor.chain().focus().undo().run()"
          class="toolbar-button"
          title="Undo (Ctrl+Z)"
        >
          ↶
        </button>
        <button
          @click="editor.chain().focus().redo().run()"
          class="toolbar-button"
          title="Redo (Ctrl+Y)"
        >
          ↷
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
import { watch } from 'vue'

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
    }),
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
})

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
