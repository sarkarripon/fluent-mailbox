<template>
  <div class="h-full flex flex-col">
      <header class="py-4 border-b border-gray-200 flex justify-between items-center bg-white/50 backdrop-blur-sm transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <div class="flex items-center space-x-4">
              <h1 class="text-xl font-semibold text-gray-800">Sent</h1>
              <div class="text-sm text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full" v-if="emails.length">{{ emails.length }} messages</div>
          </div>
      </header>
      
      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="relative">
                  <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
              </div>
          </div>

          <div v-else-if="emails.length > 0" class="divide-y divide-gray-100">
              <div 
                  v-for="email in emails" 
                  :key="email.id" 
                  @click="$router.push(`/emails/${email.id}`)"
                  class="px-6 py-3 bg-white hover:bg-gray-50 cursor-pointer group transition-colors"
              >
                  <div class="flex items-start gap-4">
                      <div class="flex-shrink-0">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white font-semibold text-sm">
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                          </div>
                      </div>
                      <div class="flex-1 min-w-0">
                          <div class="flex items-center justify-between gap-3 mb-1">
                              <div class="flex items-center gap-2 min-w-0 flex-1">
                                  <span class="text-sm font-medium text-gray-900 truncate">
                                      To: {{ getRecipients(email.recipients) }}
                                  </span>
                              </div>
                              <span class="text-xs text-gray-500 flex-shrink-0">{{ formatRelativeDate(email.created_at) }}</span>
                          </div>
                          <h4 class="text-sm font-medium text-gray-900 mb-1 truncate">
                              {{ email.subject || '(No Subject)' }}
                          </h4>
                          <p class="text-sm text-gray-500 truncate line-clamp-1">
                              {{ getEmailSnippet(email.body) }}
                          </p>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full py-12">
              <div class="w-32 h-32 mb-6 rounded-full bg-gradient-to-br from-emerald-50 to-teal-50 flex items-center justify-center">
                  <svg class="w-16 h-16 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
              </div>
              <h3 class="text-xl font-semibold text-gray-700 mb-2">No sent messages</h3>
              <p class="text-sm text-gray-500 mb-6 max-w-sm text-center">Messages you send will appear here.</p>
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

const formatRelativeDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
    
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};

const getEmailSnippet = (body) => {
    if (!body) return '';
    const text = body.replace(/<[^>]*>?/gm, '').trim();
    return text.length > 100 ? text.substring(0, 100) + '...' : text;
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
