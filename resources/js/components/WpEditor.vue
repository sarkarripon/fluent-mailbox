<template>
  <div class="custom-wp-editor-wrapper">
    <textarea
      v-if="hasWpEditor"
      :id="editorId"
      class="wp_vue_editor"
      :value="modelValue"
    ></textarea>
    <textarea
      v-else
      v-model="plainContent"
      class="wp_vue_editor wp_vue_editor_plain w-full px-3 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all font-sans resize-none text-sm"
      rows="6"
    ></textarea>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, onBeforeUnmount } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  editorId: {
    type: String,
    default: () => `wp_editor_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
  },
  height: {
    type: Number,
    default: 250
  }
});

const emit = defineEmits(['update:modelValue', 'update']);

const hasWpEditor = ref(false);
const plainContent = ref(props.modelValue);

onMounted(() => {
  // Wait for WordPress editor to be available
  let retryCount = 0;
  const maxRetries = 50; // 5 seconds max wait time
  
  const checkWpEditor = () => {
    if (window.wp && window.wp.editor && typeof window.wp.editor.initialize === 'function' && window.jQuery) {
      hasWpEditor.value = true;
      nextTick(() => {
        setTimeout(() => {
          initEditor();
        }, 300);
      });
    } else if (retryCount < maxRetries) {
      retryCount++;
      // Retry after a short delay if wp.editor is not yet available
      setTimeout(checkWpEditor, 100);
    } else {
      // Fallback to plain textarea if wp.editor never loads
      console.warn('WordPress editor not available, using plain textarea');
      hasWpEditor.value = false;
    }
  };
  
  checkWpEditor();
});

onBeforeUnmount(() => {
  if (hasWpEditor.value && window.wp && window.wp.editor) {
    window.wp.editor.remove(props.editorId);
  }
});

const initEditor = () => {
  if (!window.wp || !window.wp.editor || !window.jQuery) return;
  
  // Check if textarea exists in DOM
  const textarea = document.getElementById(props.editorId);
  if (!textarea) {
    console.warn('Textarea element not found for editor:', props.editorId);
    return;
  }
  
  // Remove existing editor if any
  try {
    window.wp.editor.remove(props.editorId);
  } catch (e) {
    // Editor might not exist yet, that's okay
  }
  
  // Initialize WordPress editor
  try {
    window.wp.editor.initialize(props.editorId, {
      mediaButtons: true,
      tinymce: {
        plugins: 'fullscreen,lists,link',
        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,underline,strikethrough,forecolor,removeformat,codeformat,outdent,indent,undo,redo',
        setup(ed) {
          ed.on('OpenWindow', (e) => {
            if (window.jQuery && e.win && e.win.$el) {
              const dialog = window.jQuery(e.win.$el);
              if (dialog) {
                setTimeout(() => {
                  dialog.addClass('fluent-cart');
                }, 100);
              }
            }
          });
          
          ed.on('input cut paste ExecCommand', () => {
            updateEditorValue();
          });
          
          ed.on('keyup', () => {
            updateEditorValue();
          });
        }
      },
      quicktags: true
    });
    
    // Handle link dialog when switching to text tab
    if (window.jQuery) {
      const $textarea = window.jQuery(`#${props.editorId}`);
      const container = $textarea.parents('.wp-editor-container');
      
      if (container.length) {
        const buttons = container.find('.ed_button.button.button-small');
        buttons.on('click', function() {
          setTimeout(() => {
            const $wpLinkModal = window.jQuery('#wp-link-wrap');
            if ($wpLinkModal.is(':visible')) {
              $wpLinkModal.addClass('fluent-cart');
            }
          }, 100);
        });
      }
    }
  } catch (e) {
    console.error('Failed to initialize WordPress editor:', e);
    hasWpEditor.value = false;
  }
};

const updateEditorValue = () => {
  if (hasWpEditor.value && window.wp && window.wp.editor) {
    const content = window.wp.editor.getContent(props.editorId);
    emit('update:modelValue', content);
    emit('update', content);
  }
};

watch(plainContent, (newValue) => {
  emit('update:modelValue', newValue);
  emit('update', newValue);
});

watch(() => props.modelValue, (newValue) => {
  if (hasWpEditor.value && window.wp && window.wp.editor) {
    const currentContent = window.wp.editor.getContent(props.editorId);
    if (currentContent !== newValue) {
      window.wp.editor.setContent(props.editorId, newValue);
    }
  } else {
    plainContent.value = newValue;
  }
});
</script>

<style scoped>
.custom-wp-editor-wrapper {
  width: 100%;
}

.wp_vue_editor {
  width: 100%;
  min-height: 250px;
}

.wp_vue_editor_plain {
  font-family: inherit;
}
</style>

