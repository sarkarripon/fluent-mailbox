<template>
  <div class="flex h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 font-sans text-gray-900">
    <!-- Sidebar -->
    <aside v-show="!store.isCompact" class="w-48 bg-white border-r border-gray-200 flex flex-col transition-all duration-300 ease-in-out">
      <div class="p-4 flex items-center space-x-2">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <span class="text-base font-semibold text-gray-800">Mailbox</span>
      </div>
      
      <div class="px-3 mb-3">
          <button v-if="store.isConfigured" @click="showCompose = true" class="w-full flex items-center justify-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 rounded-lg text-sm font-medium transition-colors">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
             <span>Compose</span>
          </button>
      </div>

      <nav class="flex-1 px-2 space-y-0.5">
        <template v-if="store.isConfigured">
            <router-link to="/inbox" class="flex items-center px-3 py-2 rounded-lg transition-colors group" :class="$route.path.includes('inbox') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                <span class="text-sm font-medium">Inbox</span>
            </router-link>
            <router-link to="/sent" class="flex items-center px-3 py-2 rounded-lg transition-colors group" :class="$route.path.includes('sent') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'">
                <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                <span class="text-sm font-medium">Sent</span>
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
      
      <!-- Compact Mode Toggle Button -->
      <div class="p-3 border-t border-gray-200">
          <button @click="toggleCompact" class="w-full flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors group">
              <svg v-if="!store.isCompact" class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V5l12 7-12 7z"></path></svg>
              <svg v-else class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
              <span class="text-sm font-medium">{{ store.isCompact ? 'Show Sidebar' : 'Compact' }}</span>
          </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden bg-white/70 backdrop-blur-xl m-2 rounded-3xl border border-white/50 relative">
      <!-- Expand Sidebar Button (shown when compact) -->
      <button v-if="store.isCompact" @click="toggleCompact" class="absolute top-4 left-4 z-20 p-2.5 bg-white hover:bg-gray-50 rounded-lg shadow-sm border border-gray-200 transition-colors flex items-center justify-center">
          <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
      </button>
      <router-view></router-view>
    </main>

    <ComposeModal :is-open="showCompose" @close="showCompose = false" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ComposeModal from './components/ComposeModal.vue';
import { useAppStore } from './stores/useAppStore';

const store = useAppStore();
const showCompose = ref(false);

const toggleCompact = () => {
  store.toggleCompact();
};
</script>
