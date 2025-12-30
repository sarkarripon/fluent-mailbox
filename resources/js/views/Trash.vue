<template>
  <div class="h-full flex flex-col">
      <header class="py-4 border-b border-gray-200 flex justify-between items-center bg-white/50 backdrop-blur-sm transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <div class="flex items-center space-x-4">
              <h1 class="text-xl font-semibold text-gray-800">Trash</h1>
              <div class="text-sm text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full" v-if="emails.length">{{ emails.length }} messages</div>
          </div>
          <button v-if="emails.length" @click="emptyTrash" class="text-red-600 text-sm font-medium hover:text-red-700 hover:bg-red-50 px-4 py-2 rounded-lg transition-all">Empty Trash</button>
      </header>

      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="relative">
                  <div class="w-16 h-16 border-4 border-gray-100 border-t-red-400 rounded-full animate-spin"></div>
              </div>
          </div>

          <div v-else-if="emails.length > 0" class="divide-y divide-gray-100">
              <div 
                  v-for="email in emails" 
                  :key="email.id" 
                  class="px-6 py-3 bg-white hover:bg-gray-50 group transition-colors"
              >
                  <div class="flex items-start gap-4">
                      <div class="flex-shrink-0">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center text-white font-semibold text-sm">
                              {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                          </div>
                      </div>
                      <div class="flex-1 min-w-0">
                          <div class="flex items-center justify-between gap-3 mb-1">
                              <div class="flex items-center gap-2 min-w-0 flex-1">
                                  <span @click="$router.push(`/emails/${email.id}`)" class="text-sm font-medium text-gray-900 truncate cursor-pointer">
                                      {{ email.sender }}
                                  </span>
                              </div>
                              <div class="flex items-center gap-2 flex-shrink-0">
                                  <span class="text-xs text-gray-500">{{ formatRelativeDate(email.created_at) }}</span>
                                  <button 
                                      @click.stop="deleteEmail(email)"
                                      class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors opacity-0 group-hover:opacity-100"
                                      title="Permanently delete"
                                  >
                                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                  </button>
                              </div>
                          </div>
                          <h4 @click="$router.push(`/emails/${email.id}`)" class="text-sm font-medium text-gray-900 mb-1 truncate cursor-pointer">
                              {{ email.subject || '(No Subject)' }}
                          </h4>
                          <p @click="$router.push(`/emails/${email.id}`)" class="text-sm text-gray-500 truncate line-clamp-1 cursor-pointer">
                              {{ getEmailSnippet(email.body) }}
                          </p>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full py-12">
              <div class="w-32 h-32 mb-6 rounded-full bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                  <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </div>
              <h3 class="text-xl font-semibold text-gray-700 mb-2">Trash is empty</h3>
              <p class="text-sm text-gray-500 max-w-sm text-center">Deleted emails will appear here.</p>
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

const deleteEmail = async (email) => {
    if (!confirm('Are you sure you want to permanently delete this email? This action cannot be undone.')) return;
    try {
        await api.deleteEmail(email.id, { permanent: true });
        await fetchEmails();
    } catch (e) {
        alert('Failed to delete email');
    }
};

const emptyTrash = async () => {
    if(!confirm('Are you sure you want to permanently delete all items in Trash? This action cannot be undone.')) return;
    try {
        await api.emptyTrash();
        await fetchEmails();
    } catch (e) {
        alert('Failed to empty trash');
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

onMounted(fetchEmails);
</script>
