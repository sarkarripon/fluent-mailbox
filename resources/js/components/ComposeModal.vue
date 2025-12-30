<template>
  <div 
      v-if="isOpen" 
      class="fixed z-50" 
      :class="isExpanded ? 'right-0 bottom-0 m-0' : 'w-[600px] max-w-[calc(100vw-2rem)]'"
      :style="isExpanded ? { left: sidebarWidth + 'px', top: adminBarHeight + 'px' } : (!isExpanded ? { bottom: position.y ? 'auto' : '1rem', right: position.x ? 'auto' : '1rem', top: position.y ? position.y + 'px' : 'auto', left: position.x ? position.x + 'px' : 'auto' } : {})"
  >
      <div 
          class="bg-white rounded-t-lg flex flex-col border border-gray-300 shadow-2xl transition-all duration-300"
          :class="isExpanded ? 'h-full rounded-none' : 'h-[600px] max-h-[calc(100vh-2rem)]'"
          style="box-shadow: 0 8px 10px 1px rgba(0,0,0,0.14), 0 3px 14px 2px rgba(0,0,0,0.12), 0 5px 5px -3px rgba(0,0,0,0.2);"
      >
           <div 
               class="flex justify-between items-center px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg cursor-move"
               :class="isExpanded ? 'rounded-none' : ''"
               @mousedown="startDrag"
           >
               <div class="flex items-center space-x-3 flex-1">
                   <h3 class="font-medium text-sm text-gray-700">New Message</h3>
               </div>
               <div class="flex items-center space-x-1">
                   <button 
                       @click="toggleExpand" 
                       class="text-gray-500 hover:text-gray-700 hover:bg-gray-200 p-1.5 rounded transition-colors"
                       :title="isExpanded ? 'Minimize' : 'Maximize'"
                   >
                      <svg v-if="!isExpanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"></path></svg>
                   </button>
                   <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700 hover:bg-gray-200 p-1.5 rounded transition-colors" title="Close">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                   </button>
               </div>
           </div>

           <div class="flex-1 overflow-y-auto">
               <form @submit.prevent="send" class="flex flex-col h-full">
                   <div class="px-4 py-2 border-b border-gray-200">
                       <div class="flex items-center">
                           <span class="text-sm text-gray-600 w-16 flex-shrink-0">To</span>
                           <input 
                               v-model="form.to" 
                               type="email" 
                               required 
                               class="flex-1 px-2 py-1.5 bg-transparent border-none focus:ring-0 focus:outline-none text-sm" 
                               placeholder="Recipients"
                           >
                       </div>
                   </div>

                   <div class="px-4 py-2 border-b border-gray-200">
                       <div class="flex items-center">
                           <span class="text-sm text-gray-600 w-16 flex-shrink-0">Subject</span>
                           <input 
                               v-model="form.subject" 
                               type="text" 
                               required 
                               class="flex-1 px-2 py-1.5 bg-transparent border-none focus:ring-0 focus:outline-none text-sm" 
                               placeholder="Subject"
                           >
                       </div>
                   </div>

                   <div class="flex-1 px-4 py-2 overflow-y-auto">
                       <WpEditor
                           v-model="form.body"
                           :height="isExpanded ? 400 : 300"
                           @update="(val) => {
                               form.body = val;
                           }"
                       />
                   </div>

                   <div v-if="error" class="px-4 py-2 bg-red-50 text-red-600 text-sm border-b border-red-200 flex items-center">
                       <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                       {{ error }}
                   </div>
                   <div v-if="success" class="px-4 py-2 bg-green-50 text-green-600 text-sm border-b border-green-200 flex items-center">
                       <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                       Email sent successfully!
                   </div>

                   <div class="px-4 py-3 border-t border-gray-200 flex justify-between items-center bg-gray-50 rounded-b-lg" :class="isExpanded ? 'rounded-none' : ''">
                       <div class="flex items-center space-x-2">
                           <button 
                               type="button"
                               class="text-sm text-gray-600 hover:text-gray-800 px-3 py-1.5 rounded hover:bg-gray-200 transition-colors"
                               title="Formatting options"
                           >
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                           </button>
                           <button 
                               type="button"
                               class="text-sm text-gray-600 hover:text-gray-800 px-3 py-1.5 rounded hover:bg-gray-200 transition-colors"
                               title="Attach files"
                           >
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                           </button>
                       </div>
                       <div class="flex items-center space-x-2">
                           <button 
                               type="button"
                               @click="$emit('close')"
                               class="text-sm text-gray-600 hover:text-gray-800 px-4 py-1.5 rounded hover:bg-gray-200 transition-colors"
                           >
                               Discard
                           </button>
                           <button 
                               type="submit" 
                               :disabled="loading" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-1.5 rounded text-sm font-medium transition-colors flex items-center disabled:opacity-70 disabled:cursor-not-allowed"
                           >
                               <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                               <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                               {{ loading ? 'Sending...' : 'Send' }}
                           </button>
                       </div>
                   </div>
               </form>
           </div>
      </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onUnmounted } from 'vue';
import api from '../utils/api';
import WpEditor from './WpEditor.vue';

const props = defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close', 'sent']);

const loading = ref(false);
const error = ref('');
const success = ref(false);
const isExpanded = ref(false);
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const position = ref({ x: 0, y: 0 });
const sidebarWidth = ref(160); // Default WordPress sidebar width
const adminBarHeight = ref(32); // Default WordPress admin bar height

const form = reactive({
  to: '',
  subject: '',
  body: ''
});

const getSidebarWidth = () => {
  // Detect WordPress admin sidebar width
  const adminMenu = document.getElementById('adminmenu');
  const adminMenuWrap = document.getElementById('adminmenuwrap');
  const body = document.body;
  
  if (adminMenu || adminMenuWrap) {
    const menu = adminMenu || adminMenuWrap;
    if (menu) {
      const width = menu.offsetWidth;
      if (width > 0) {
        return width;
      }
    }
  }
  
  // Check if sidebar is collapsed (folded class)
  if (body && body.classList.contains('folded')) {
    return 36; // Collapsed sidebar width
  }
  
  return 160; // Default expanded sidebar width
};

const getAdminBarHeight = () => {
  // Detect WordPress admin bar height
  const adminBar = document.getElementById('wpadminbar');
  if (adminBar) {
    const height = adminBar.offsetHeight;
    if (height > 0) {
      return height;
    }
  }
  
  // Check if admin bar is visible (body has admin-bar class)
  const body = document.body;
  if (body && body.classList.contains('admin-bar')) {
    // Check viewport width for mobile (admin bar is taller on mobile)
    if (window.innerWidth <= 782) {
      return 46; // Mobile admin bar height
    }
    return 32; // Desktop admin bar height
  }
  
  return 0; // No admin bar
};

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
  if (isExpanded.value) {
    // Update sidebar width and admin bar height when expanding
    sidebarWidth.value = getSidebarWidth();
    adminBarHeight.value = getAdminBarHeight();
  } else {
    // Reset to bottom-right when minimizing
    position.value = { x: 0, y: 0 };
  }
};

const startDrag = (e) => {
  if (isExpanded.value) return; // Don't drag when expanded
  isDragging.value = true;
  const container = e.currentTarget.closest('.fixed');
  if (container) {
    const rect = container.getBoundingClientRect();
    dragOffset.value = {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top
    };
  }
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', stopDrag);
  e.preventDefault();
};

const onDrag = (e) => {
  if (!isDragging.value || isExpanded.value) return;
  const container = e.target.closest('.fixed');
  if (container) {
    const maxX = window.innerWidth - container.offsetWidth;
    const maxY = window.innerHeight - container.offsetHeight;
    position.value = {
      x: Math.max(0, Math.min(e.clientX - dragOffset.value.x, maxX)),
      y: Math.max(0, Math.min(e.clientY - dragOffset.value.y, maxY))
    };
  }
};

const stopDrag = () => {
  isDragging.value = false;
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
};

watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    // Reset position when opening
    position.value = { x: 0, y: 0 };
    isExpanded.value = false;
    sidebarWidth.value = getSidebarWidth();
    adminBarHeight.value = getAdminBarHeight();
  }
});

// Watch for sidebar collapse/expand and admin bar changes
let sidebarObserver = null;

if (typeof window !== 'undefined' && document.body) {
  sidebarObserver = new MutationObserver(() => {
    if (isExpanded.value) {
      sidebarWidth.value = getSidebarWidth();
      adminBarHeight.value = getAdminBarHeight();
    }
  });
  
  // Observe body class changes for folded/unfolded sidebar and admin bar
  sidebarObserver.observe(document.body, {
    attributes: true,
    attributeFilter: ['class']
  });
  
  // Also observe admin menu width changes
  const adminMenu = document.getElementById('adminmenu') || document.getElementById('adminmenuwrap');
  if (adminMenu) {
    sidebarObserver.observe(adminMenu, {
      attributes: true,
      attributeFilter: ['style', 'class'],
      childList: false,
      subtree: false
    });
  }
  
  // Observe admin bar changes
  const adminBar = document.getElementById('wpadminbar');
  if (adminBar) {
    sidebarObserver.observe(adminBar, {
      attributes: true,
      attributeFilter: ['style', 'class'],
      childList: false,
      subtree: false
    });
  }
  
  // Watch for window resize (admin bar height changes on mobile/desktop)
  const handleResize = () => {
    if (isExpanded.value) {
      adminBarHeight.value = getAdminBarHeight();
    }
  };
  window.addEventListener('resize', handleResize);
  
  // Store handler for cleanup
  window._fluentMailboxResizeHandler = handleResize;
}

onUnmounted(() => {
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
  if (sidebarObserver) {
    sidebarObserver.disconnect();
  }
  if (typeof window !== 'undefined' && window._fluentMailboxResizeHandler) {
    window.removeEventListener('resize', window._fluentMailboxResizeHandler);
    delete window._fluentMailboxResizeHandler;
  }
});

const send = async () => {
  // Validate form
  if (!form.to || !form.subject || !form.body) {
    error.value = 'Please fill in all fields';
    return;
  }
  
  // Check if body has content (strip HTML tags)
  const bodyText = form.body.replace(/<[^>]*>?/gm, '').trim();
  if (!bodyText) {
    error.value = 'Message cannot be empty';
    return;
  }

  error.value = '';
  success.value = false;
  loading.value = true;

  try {
      await api.sendEmail(form);
      success.value = true;
      form.to = '';
      form.subject = '';
      form.body = '';
      emit('sent');
      setTimeout(() => {
          success.value = false;
          emit('close');
      }, 1500);
  } catch (e) {
      error.value = e.response?.data?.message || 'Failed to send email';
  } finally {
      loading.value = false;
  }
};
</script>
