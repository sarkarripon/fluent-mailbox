<template>
  <div class="flex bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 font-sans text-gray-900" :style="{ height: `calc(100vh - ${adminBarHeight}px)`, marginTop: adminBarHeight + 'px' }">
    <!-- Sidebar -->
    <aside class="w-48 bg-white border-r border-gray-200 flex flex-col transition-all duration-300 ease-in-out" :style="{ maxHeight: `calc(100vh - ${adminBarHeight + 32}px)` }">
      <div class="p-4 flex items-center space-x-2">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <div class="flex items-center space-x-2 flex-1">
          <span class="text-base text-sm font-semibold text-gray-800">Fluent Mailbox</span>
          <Tooltip v-if="store.isConfigured" text="Mailbox is connected and ready" position="right">
            <div class="relative">
              <div class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"></div>
              <div class="absolute inset-0 w-2.5 h-2.5 bg-green-500 rounded-full animate-ping opacity-75"></div>
            </div>
          </Tooltip>
          <Tooltip v-else text="Mailbox is not connected. Go to Settings to configure." position="right">
            <div class="w-2.5 h-2.5 bg-gray-400 rounded-full"></div>
          </Tooltip>
        </div>
      </div>
      
      <div class="px-3 mb-3">
          <Tooltip text="Create and send a new email message" position="right">
              <button v-if="store.isConfigured" @click="store.openCompose('new')" class="w-full flex items-center justify-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                 <span>Compose</span>
              </button>
          </Tooltip>
      </div>

      <nav class="flex-1 px-2 space-y-0.5">
        <template v-if="store.isConfigured">
            <router-link to="/inbox" class="flex items-center justify-between px-3 py-2 rounded-lg transition-colors group" :class="$route.path.includes('inbox') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    <span class="text-sm font-medium">Inbox</span>
                </div>
                <span v-if="emailCounts.inboxUnreadCount > 0" class="text-xs font-semibold bg-blue-600 text-white px-2 py-0.5 rounded-full min-w-[20px] text-center">{{ emailCounts.inboxUnreadCount }}</span>
            </router-link>
            <router-link to="/sent" class="flex items-center px-3 py-2 rounded-lg transition-colors group" :class="$route.path.includes('sent') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                <span class="text-sm font-medium">Sent</span>
            </router-link>

            <router-link to="/drafts" class="flex items-center px-3 py-2 rounded-lg transition-colors group" :class="$route.path.includes('drafts') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                <span class="text-sm font-medium">Drafts</span>
            </router-link>

            <router-link to="/trash" class="flex items-center px-3 py-2 rounded-lg transition-colors group" :class="$route.path.includes('trash') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span class="text-sm font-medium">Trash</span>
            </router-link>
        </template>

        <div class="pt-2 mt-2 border-t border-gray-200">
            <router-link to="/settings" class="flex items-center px-3 py-2 rounded-lg transition-colors group" :class="$route.path.includes('settings') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="text-sm font-medium">Settings</span>
            </router-link>
        </div>
      </nav>
      
      <!-- WordPress Sidebar Toggle Button -->
      <div class="p-3 border-t border-gray-200">
          <Tooltip :text="isWordPressSidebarFolded ? 'Expand WordPress Menu' : 'Collapse WordPress Menu'" position="right">
              <button @click="toggleCompact" class="w-full flex items-center justify-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors group">
                  <svg v-if="!isWordPressSidebarFolded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
              </button>
          </Tooltip>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden bg-white/70 backdrop-blur-xl m-2 mb-2 rounded-3xl border border-white/50 relative" :style="{ maxHeight: `calc(100vh - ${adminBarHeight + 16}px)` }">
      <router-view></router-view>
    </main>

    <ComposeModal 
        :is-open="store.showCompose" 
        :mode="store.composeMode"
        :email-data="store.composeEmailData"
        @close="store.closeCompose" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import ComposeModal from './components/ComposeModal.vue';
import { useAppStore } from './stores/useAppStore';
import { useEmailCounts } from './composables/useEmailCounts';
import { useKeyboardShortcuts } from './composables/useKeyboardShortcuts';
import Tooltip from './components/Tooltip.vue';

const store = useAppStore();
const emailCounts = useEmailCounts();
const adminBarHeight = ref(0);
const isWordPressSidebarFolded = ref(false);

// Enable keyboard shortcuts
useKeyboardShortcuts();

const getAdminBarHeight = () => {
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

const updateAdminBarHeight = () => {
  adminBarHeight.value = getAdminBarHeight();
};

const checkWordPressSidebarState = () => {
  const body = document.body;
  if (body) {
    isWordPressSidebarFolded.value = body.classList.contains('folded');
  }
};

const loadWordPressSidebarState = () => {
  // WordPress stores the state in localStorage with key 'wp-admin-folded'
  const savedState = localStorage.getItem('wp-admin-folded');
  const body = document.body;
  
  if (body && savedState !== null) {
    // WordPress uses '1' for folded, '0' or null for expanded
    const shouldBeFolded = savedState === '1';
    
    if (shouldBeFolded && !body.classList.contains('folded')) {
      body.classList.add('folded');
    } else if (!shouldBeFolded && body.classList.contains('folded')) {
      body.classList.remove('folded');
    }
  }
  
  checkWordPressSidebarState();
};

const toggleCompact = () => {
  // Toggle WordPress admin sidebar
  const body = document.body;
  if (body) {
    if (!body.classList.contains('folded')) {
      body.classList.add('folded');
      // Save to localStorage (WordPress uses '1' for folded)
      localStorage.setItem('wp-admin-folded', '1');
    } else {
      body.classList.remove('folded');
      // Save to localStorage (WordPress uses '0' for expanded)
      localStorage.setItem('wp-admin-folded', '0');
    }
    // Update our state
    checkWordPressSidebarState();
  }
};

let adminBarObserver = null;
let resizeHandler = null;

onMounted(() => {
  updateAdminBarHeight();
  // Load WordPress sidebar state from localStorage on mount
  loadWordPressSidebarState();
  emailCounts.fetchCounts();
  
  // Refresh counts every 30 seconds
  setInterval(() => {
    emailCounts.fetchCounts();
  }, 30000);

  // Watch for admin bar changes
  if (typeof window !== 'undefined' && document.body) {
    adminBarObserver = new MutationObserver(() => {
      updateAdminBarHeight();
      checkWordPressSidebarState();
    });

    // Observe admin bar
    const adminBar = document.getElementById('wpadminbar');
    if (adminBar) {
      adminBarObserver.observe(adminBar, {
        attributes: true,
        attributeFilter: ['style', 'class']
      });
    }

    // Watch body class changes for admin-bar class and folded class
    adminBarObserver.observe(document.body, {
      attributes: true,
      attributeFilter: ['class']
    });

    // Watch for window resize (admin bar height changes on mobile/desktop)
    resizeHandler = () => {
      updateAdminBarHeight();
    };
    window.addEventListener('resize', resizeHandler);
    
    // Also listen for WordPress's own localStorage changes (if user clicks WordPress's toggle button)
    window.addEventListener('storage', (e) => {
      if (e.key === 'wp-admin-folded') {
        loadWordPressSidebarState();
      }
    });
  }
});

onBeforeUnmount(() => {
  if (adminBarObserver) {
    adminBarObserver.disconnect();
  }
  if (resizeHandler) {
    window.removeEventListener('resize', resizeHandler);
  }
});
</script>
