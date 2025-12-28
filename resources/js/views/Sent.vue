<template>
  <div class="h-full flex flex-col">
      <header class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-800">Sent Items</h1>
          <div class="text-sm text-gray-500" v-if="emails.length">Showing {{ emails.length }} messages</div>
      </header>
      
      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          </div>

          <div v-else-if="emails.length > 0" class="divide-y divide-gray-100">
              <div v-for="email in emails" :key="email.id" @click="$router.push(`/emails/${email.id}`)" class="px-8 py-4 hover:bg-gray-50 transition-colors cursor-pointer group">
                  <div class="flex justify-between items-baseline mb-1">
                      <span class="font-semibold text-gray-900">To: <span class="font-normal text-gray-600">{{ getRecipients(email.recipients) }}</span></span>
                      <span class="text-xs text-gray-400 group-hover:text-gray-500">{{ formatDate(email.created_at) }}</span>
                  </div>
                  <h4 class="font-medium text-gray-800 mb-1">{{ email.subject }}</h4>
                  <p class="text-sm text-gray-500 truncate">{{ email.body.replace(/<[^>]*>?/gm, '') }}</p>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
              <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
              <p class="text-lg font-medium">No sent messages</p>
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
        const response = await api.getEmails(1, 'sent');
        emails.value = response.data.data || [];
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getRecipients = (json) => {
    try {
        const parsed = JSON.parse(json);
        if (Array.isArray(parsed)) return parsed.join(', ');
        return json; // Fallback
    } catch (e) {
        return json;
    }
};

onMounted(fetchEmails);
</script>
