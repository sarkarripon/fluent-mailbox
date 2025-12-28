<template>
  <div class="h-full flex flex-col">
      <header class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10">
          <div class="flex items-center space-x-4">
               <h1 class="text-2xl font-bold text-gray-800">Inbox</h1>
               <div class="text-sm text-gray-500" v-if="emails.length">Showing {{ emails.length }} messages</div>
          </div>
          
          <button @click="handleRefresh" :disabled="isRefreshing" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center space-x-2" title="Sync from S3">
              <svg :class="{'animate-spin': isRefreshing}" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
              <!-- <span class="text-sm font-medium">Sync</span> -->
          </button>
      </header>
      
      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          </div>

          <div v-else-if="emails.length > 0" class="divide-y divide-gray-100">
              <div v-for="email in emails" :key="email.id" @click="$router.push(`/emails/${email.id}`)" class="px-8 py-4 hover:bg-gray-50 transition-colors cursor-pointer group">
                  <div class="flex justify-between items-baseline mb-1">
                      <span class="font-semibold text-gray-900">{{ email.sender }}</span>
                      <span class="text-xs text-gray-400 group-hover:text-gray-500">{{ formatDate(email.created_at) }}</span>
                  </div>
                  <h4 class="font-medium text-gray-800 mb-1">{{ email.subject }}</h4>
                  <p class="text-sm text-gray-500 truncate">{{ email.body.replace(/<[^>]*>?/gm, '') }}</p>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
              <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
              <p class="text-lg font-medium">No messages yet</p>
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
