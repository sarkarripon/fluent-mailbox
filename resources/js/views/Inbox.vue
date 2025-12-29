<template>
  <div class="h-full flex flex-col">
      <header class="px-8 py-6 border-b border-gray-100/50 flex justify-between items-center bg-white/50 backdrop-blur-sm sticky top-0 z-10">
          <div class="flex items-center space-x-4">
               <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Inbox</h1>
               <div class="text-sm text-gray-500 bg-gray-100/70 px-3 py-1 rounded-full" v-if="emails.length">{{ emails.length }} messages</div>
          </div>

          <button @click="handleRefresh" :disabled="isRefreshing" class="p-2.5 text-gray-500 hover:text-blue-600 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 rounded-xl transition-all duration-300 flex items-center space-x-2 shadow-sm hover:shadow-md" title="Sync from S3">
              <svg :class="{'animate-spin': isRefreshing}" class="w-5 h-5 transition-transform hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          </button>
      </header>
      
      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="relative">
                  <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
              </div>
          </div>

          <div v-else-if="emails.length > 0" class="p-6 space-y-3">
              <div v-for="email in emails" :key="email.id" @click="$router.push(`/emails/${email.id}`)" class="px-6 py-4 bg-white/60 backdrop-blur-sm hover:bg-gradient-to-r hover:from-white hover:to-blue-50/30 border border-gray-100/50 hover:border-blue-200/50 rounded-2xl transition-all duration-300 cursor-pointer group hover:-translate-y-0.5">
                  <div class="flex justify-between items-start mb-2">
                      <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold text-sm">
                              {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                          </div>
                          <div>
                              <span class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">{{ email.sender }}</span>
                          </div>
                      </div>
                      <span class="text-xs text-gray-400 group-hover:text-blue-500 transition-colors">{{ formatDate(email.created_at) }}</span>
                  </div>
                  <h4 class="font-semibold text-gray-800 mb-1 group-hover:text-blue-900 transition-colors">{{ email.subject }}</h4>
                  <p class="text-sm text-gray-500 truncate group-hover:text-gray-600 transition-colors">{{ email.body.replace(/<[^>]*>?/gm, '') }}</p>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
              <div class="w-24 h-24 mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                  <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
              </div>
              <p class="text-lg font-medium text-gray-500">No messages yet</p>
              <p class="text-sm text-gray-400 mt-1">Your inbox is empty</p>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../utils/api';

const emails = ref([]);
const loading = ref(true);

const fetchEmails = async () => {
    loading.value = true;
    try {
        const response = await api.getEmails(1, 'all'); 
        emails.value = response.data.data || [];
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const isRefreshing = ref(false);

const handleRefresh = async () => {
    isRefreshing.value = true;
    try {
        await api.fetchEmails(); // Trigger S3 fetch
        await fetchEmails(); // Reload list
    } catch (e) {
        console.error('Failed to sync', e);
    } finally {
        isRefreshing.value = false;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

onMounted(fetchEmails);
</script>
