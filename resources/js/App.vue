<template>
  <div class="flex h-screen bg-gray-50 font-sans text-gray-900">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
      <div class="p-6 flex items-center space-x-3">
        <div class="bg-blue-600 rounded-lg p-2">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <span class="text-xl font-bold tracking-tight text-gray-800">Mailbox</span>
      </div>
      
      <div class="px-4 mb-6">
          <button v-if="store.isConfigured" @click="showCompose = true" class="w-full flex items-center justify-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-xl shadow-md transition-all duration-200 font-medium">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
             <span>Compose</span>
          </button>
      </div>

      <nav class="flex-1 px-4 space-y-1">
        <template v-if="store.isConfigured">
            <router-link to="/inbox" class="flex items-center px-4 py-3 rounded-xl transition-colors duration-200 group" :class="$route.path.includes('inbox') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                <span class="font-medium">Inbox</span>
            </router-link>
            <router-link to="/sent" class="flex items-center px-4 py-3 rounded-xl transition-colors duration-200 group" :class="$route.path.includes('sent') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                <span class="font-medium">Sent</span>
            </router-link>
    
            <router-link to="/trash" class="flex items-center px-4 py-3 rounded-xl transition-colors duration-200 group" :class="$route.path.includes('trash') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span class="font-medium">Trash</span>
            </router-link>
        </template>

        <div class="pt-4 mt-4 border-t border-gray-100">
            <router-link to="/settings" class="flex items-center px-4 py-3 rounded-xl transition-colors duration-200 group" :class="$route.path.includes('settings') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100'">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="font-medium">Settings</span>
            </router-link>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden bg-white m-2 rounded-2xl shadow-sm border border-gray-100">
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
</script>
