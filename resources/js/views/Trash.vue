<template>
  <div class="h-full flex flex-col">
      <header class="py-6 border-b border-gray-100/50 flex justify-between items-center bg-white/50 backdrop-blur-sm transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Trash</h1>
          <button v-if="emails.length" @click="emptyTrash" class="text-red-600 text-sm font-medium hover:text-red-700 hover:bg-red-50 px-4 py-2 rounded-xl transition-all duration-300">Empty Trash</button>
      </header>

      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="relative">
                  <div class="w-16 h-16 border-4 border-gray-100 border-t-red-400 rounded-full animate-spin"></div>
              </div>
          </div>

          <div v-else-if="emails.length > 0" class="p-6 space-y-3">
              <div v-for="email in emails" :key="email.id" @click="$router.push(`/emails/${email.id}`)" class="px-6 py-4 bg-white/60 backdrop-blur-sm hover:bg-gradient-to-r hover:from-white hover:to-red-50/30 border border-gray-100/50 hover:border-red-200/50 rounded-2xl transition-all duration-300 cursor-pointer group hover:-translate-y-0.5">
                  <div class="flex justify-between items-start mb-2">
                      <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center text-white font-bold text-sm">
                              {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                          </div>
                          <div>
                              <span class="font-semibold text-gray-900 group-hover:text-red-700 transition-colors">{{ email.sender }}</span>
                          </div>
                      </div>
                      <span class="text-xs text-gray-400 group-hover:text-red-500 transition-colors">{{ formatDate(email.created_at) }}</span>
                  </div>
                  <h4 class="font-semibold text-gray-800 mb-1 group-hover:text-red-900 transition-colors">{{ email.subject }}</h4>
                  <p class="text-sm text-gray-500 truncate group-hover:text-gray-600 transition-colors">{{ email.body.replace(/<[^>]*>?/gm, '') }}</p>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
              <div class="w-24 h-24 mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                  <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </div>
              <p class="text-lg font-medium text-gray-500">Trash is empty</p>
              <p class="text-sm text-gray-400 mt-1">Deleted items will appear here</p>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';

const store = useAppStore();

const emails = ref([]);
const loading = ref(true);

const fetchEmails = async () => {
    loading.value = true;
    try {
        const response = await api.getEmails(1, 'trash');
        emails.value = response.data.data || [];
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const emptyTrash = async () => {
    if(!confirm('Are you sure you want to permanently delete all items in Trash?')) return;
    // Todo: Implement empty trash API
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};

onMounted(fetchEmails);
</script>
