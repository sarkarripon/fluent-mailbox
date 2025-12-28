<template>
  <div class="h-full flex flex-col">
      <header class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-800">Trash</h1>
           <button v-if="emails.length" @click="emptyTrash" class="text-red-600 text-sm font-medium hover:underline">Empty Trash</button>
      </header>
      
      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          </div>

          <div v-else-if="emails.length > 0" class="divide-y divide-gray-100">
              <div v-for="email in emails" :key="email.id" @click="$router.push(`/emails/${email.id}`)" class="px-8 py-4 hover:bg-gray-50 transition-colors cursor-pointer group opacity-75">
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
              <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              <p class="text-lg font-medium">Trash is empty</p>
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
