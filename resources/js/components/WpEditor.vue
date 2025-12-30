<template>
  <div class="custom-wp-editor-wrapper" :style="{ height: editorHeight ? editorHeight + 'px' : 'auto', minHeight: minHeight + 'px' }">
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
    
    <!-- Resize Handle -->
    <div 
      class="resize-handle"
      @mousedown="startResize"
      :class="{ 'resizing': isResizing }"
    >
      <div class="resize-handle-line"></div>
    </div>
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
    type: [Number, String],
    default: 'auto'
  },
  minHeight: {
    type: Number,
    default: 200
  }
});

const emit = defineEmits(['update:modelValue', 'update']);

const hasWpEditor = ref(false);
const plainContent = ref(props.modelValue);
const editorHeight = ref(null);
const isResizing = ref(false);
const resizeStartY = ref(0);
const resizeStartHeight = ref(0);

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
  
  // Determine height - use 'auto' for flexible height, or specific value
  const editorHeight = props.height === 'auto' || props.height === 'flex' ? undefined : props.height;
  
  // Initialize WordPress editor
  try {
    const editorConfig = {
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
          
          // Auto-resize functionality
          if (props.height === 'auto' || props.height === 'flex') {
            ed.on('init', () => {
              ed.getBody().style.minHeight = props.minHeight + 'px';
            });
          }
        }
      },
      quicktags: true
    };
    
    // Add height only if specified
    if (editorHeight) {
      editorConfig.height = editorHeight;
    }
    
    window.wp.editor.initialize(props.editorId, editorConfig);
    
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

// Resize functionality
const startResize = (e) => {
  e.preventDefault();
  isResizing.value = true;
  resizeStartY.value = e.clientY;
  const wrapper = e.currentTarget.closest('.custom-wp-editor-wrapper');
  if (wrapper) {
    resizeStartHeight.value = wrapper.offsetHeight;
  }
  
  document.addEventListener('mousemove', handleResize);
  document.addEventListener('mouseup', stopResize);
  document.body.style.cursor = 'ns-resize';
  document.body.style.userSelect = 'none';
};

const handleResize = (e) => {
  if (!isResizing.value) return;
  
  const deltaY = e.clientY - resizeStartY.value;
  const newHeight = Math.max(props.minHeight, resizeStartHeight.value + deltaY);
  editorHeight.value = newHeight;
  
  // Update TinyMCE iframe height if editor is initialized
  if (hasWpEditor.value && window.wp && window.wp.editor) {
    const iframe = document.querySelector(`#${props.editorId}_ifr`);
    if (iframe) {
      const editorContainer = iframe.closest('.wp-editor-container');
      if (editorContainer) {
        const toolbarHeight = editorContainer.querySelector('.mce-toolbar')?.offsetHeight || 0;
        const actualEditorHeight = newHeight - toolbarHeight - 40; // Account for toolbar and padding
        iframe.style.height = Math.max(200, actualEditorHeight) + 'px';
      }
    }
  }
};

const stopResize = () => {
  isResizing.value = false;
  document.removeEventListener('mousemove', handleResize);
  document.removeEventListener('mouseup', stopResize);
  document.body.style.cursor = '';
  document.body.style.userSelect = '';
  
  // Save height preference to localStorage
  if (editorHeight.value) {
    localStorage.setItem(`wp-editor-height-${props.editorId}`, editorHeight.value.toString());
  }
};

// Load saved height preference
onMounted(() => {
  const savedHeight = localStorage.getItem(`wp-editor-height-${props.editorId}`);
  if (savedHeight && props.height === 'auto') {
    editorHeight.value = parseInt(savedHeight);
  } else if (typeof props.height === 'number') {
    editorHeight.value = props.height;
  }
});

onBeforeUnmount(() => {
  // Cleanup resize listeners
  document.removeEventListener('mousemove', handleResize);
  document.removeEventListener('mouseup', stopResize);
  document.body.style.cursor = '';
  document.body.style.userSelect = '';
});
</script>

<style scoped>
.custom-wp-editor-wrapper {
  width: 100%;
}

.wp_vue_editor {
  width: 100%;
  min-height: 200px;
}

/* Make editor container flexible */
.custom-wp-editor-wrapper {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

/* TinyMCE iframe should be flexible */
.custom-wp-editor-wrapper :deep(.mce-container) {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.custom-wp-editor-wrapper :deep(.mce-edit-area) {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.custom-wp-editor-wrapper :deep(iframe) {
  flex: 1;
  min-height: 200px;
}

/* Resize Handle */
.resize-handle {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 8px;
  cursor: ns-resize;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  transition: background-color 0.2s;
}

.resize-handle:hover {
  background-color: rgba(59, 130, 246, 0.1);
}

.resize-handle.resizing {
  background-color: rgba(59, 130, 246, 0.2);
}

.resize-handle-line {
  width: 40px;
  height: 3px;
  background-color: #cbd5e1;
  border-radius: 2px;
  transition: background-color 0.2s;
}

.resize-handle:hover .resize-handle-line,
.resize-handle.resizing .resize-handle-line {
  background-color: #3b82f6;
}

.custom-wp-editor-wrapper {
  position: relative;
}

.wp_vue_editor_plain {
  font-family: inherit;
}
</style>

