<template>
  <div class="h-full flex flex-col">
      <header class="py-6 border-b border-gray-100/50 flex justify-between items-center bg-white/50 backdrop-blur-sm transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Sent Items</h1>
          <div class="text-sm text-gray-500 bg-gray-100/70 px-3 py-1 rounded-full" v-if="emails.length">{{ emails.length }} messages</div>
      </header>

      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="relative">
                  <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
              </div>
          </div>

          <div v-else-if="emails.length > 0" class="p-6 space-y-3">
              <div v-for="email in emails" :key="email.id" @click="$router.push(`/emails/${email.id}`)" class="px-6 py-4 bg-white/60 backdrop-blur-sm hover:bg-gradient-to-r hover:from-white hover:to-emerald-50/30 border border-gray-100/50 hover:border-emerald-200/50 rounded-2xl transition-all duration-300 cursor-pointer group hover:-translate-y-0.5">
                  <div class="flex justify-between items-start mb-2">
                      <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white font-bold text-sm">
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                          </div>
                          <div>
                              <span class="font-semibold text-gray-900 group-hover:text-emerald-700 transition-colors">To: <span class="font-normal text-gray-600">{{ getRecipients(email.recipients) }}</span></span>
                          </div>
                      </div>
                      <span class="text-xs text-gray-400 group-hover:text-emerald-500 transition-colors">{{ formatDate(email.created_at) }}</span>
                  </div>
                  <h4 class="font-semibold text-gray-800 mb-1 group-hover:text-emerald-900 transition-colors">{{ email.subject }}</h4>
                  <p class="text-sm text-gray-500 truncate group-hover:text-gray-600 transition-colors">{{ email.body.replace(/<[^>]*>?/gm, '') }}</p>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
              <div class="w-24 h-24 mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                  <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
              </div>
              <p class="text-lg font-medium text-gray-500">No sent messages</p>
              <p class="text-sm text-gray-400 mt-1">Messages you send will appear here</p>
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
