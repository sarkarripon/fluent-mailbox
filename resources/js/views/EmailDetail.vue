<template>
  <div class="h-full flex flex-col">
      <header class="py-4 border-b border-gray-200 flex justify-between items-center bg-white/50 backdrop-blur-sm sticky top-0 z-10 transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <div class="flex items-center space-x-3">
              <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600 hover:text-gray-900 transition-all">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
              </button>
              <h1 class="text-lg font-semibold text-gray-800 truncate max-w-xl">{{ email ? email.subject : 'Loading...' }}</h1>
          </div>
          
          <div class="flex items-center space-x-1" v-if="email">
               <button @click="handleReply" class="px-3 py-1.5 text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                  Reply
               </button>
               <button @click="handleForward" class="px-3 py-1.5 text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                  Forward
               </button>
               <button @click="toggleRead" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" :title="email.is_read ? 'Mark as unread' : 'Mark as read'">
                  <svg v-if="email.is_read" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
               </button>
               <button @click="deleteEmail" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
               </button>
          </div>
      </header>

      <div class="flex-1 overflow-auto p-6 bg-white/50" v-if="!loading && email">
          <!-- Metadata -->
          <div class="bg-white rounded-lg p-5 mb-4 border border-gray-200">
              <div class="flex justify-between items-start mb-4">
              <div class="flex items-center space-x-3">
                      <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-lg">
                      {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                  </div>
                  <div>
                          <div class="font-semibold text-gray-900">{{ email.sender }}</div>
                          <div class="text-sm text-gray-500 mt-0.5 space-y-0.5">
                              <div>To: <span class="text-gray-700">{{ getRecipients(email.recipients) }}</span></div>
                              <div v-if="getRecipients(email.cc)">
                                  Cc: <span class="text-gray-700">{{ getRecipients(email.cc) }}</span>
                              </div>
                              <div v-if="getRecipients(email.bcc)">
                                  Bcc: <span class="text-gray-700">{{ getRecipients(email.bcc) }}</span>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="text-xs text-gray-500">
                      {{ formatRelativeDate(email.created_at) }}
                  </div>
              </div>
          </div>

          <!-- Attachments -->
          <div v-if="emailAttachments.length > 0" class="bg-white rounded-lg p-4 mb-4 border border-gray-200">
              <h3 class="text-sm font-semibold text-gray-700 mb-3">Attachments</h3>
              <div class="flex flex-wrap gap-2">
                  <a 
                      v-for="(attachment, index) in emailAttachments" 
                      :key="index"
                      :href="getAttachmentUrl(attachment)"
                      target="_blank"
                      class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 text-sm text-gray-700 transition-colors"
                  >
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                      <span>{{ attachment.filename || `Attachment ${index + 1}` }}</span>
                      <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                  </a>
              </div>
          </div>

          <!-- Body -->
          <div class="bg-white rounded-lg p-6 border border-gray-200 prose prose-sm max-w-none text-gray-800" v-html="email.body"></div>
      </div>
      
      <div v-else-if="loading" class="flex-1 flex justify-center items-center">
           <div class="relative">
               <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
           </div>
      </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';
import { useEmailCounts } from '../composables/useEmailCounts';

const store = useAppStore();
const emailCounts = useEmailCounts();

const route = useRoute();
const router = useRouter();
const email = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.getEmail(route.params.id);
        email.value = data;
        // Email is automatically marked as read when fetched (in backend)
        // Refresh counts
        emailCounts.fetchCounts();
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const toggleRead = async () => {
    try {
        const newStatus = email.value.is_read ? 0 : 1;
        await api.updateEmail(email.value.id, { is_read: newStatus });
        email.value.is_read = newStatus;
        emailCounts.fetchCounts();
    } catch (e) {
        console.error('Failed to update read status', e);
    }
};

const deleteEmail = async () => {
    if (!confirm('Are you sure you want to delete this email?')) return;
    try {
        await api.deleteEmail(email.value.id);
        emailCounts.fetchCounts();
        router.back();
    } catch (e) {
        alert('Failed to delete email');
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
    
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const handleReply = () => {
    store.openCompose('reply', email.value);
};

const handleForward = () => {
    store.openCompose('forward', email.value);
};

const getRecipients = (json) => {
    if (!json) return '';
    try {
        const parsed = JSON.parse(json);
        if (Array.isArray(parsed)) return parsed.join(', ');
        return json;
    } catch (e) {
        return json;
    }
};

const emailAttachments = computed(() => {
    if (!email.value || !email.value.attachments) return [];
    try {
        const attIds = JSON.parse(email.value.attachments);
        return attIds.map(id => ({ id, filename: `Attachment ${id}` }));
    } catch (e) {
        return [];
    }
});

const getAttachmentUrl = (attachment) => {
    // Get WordPress attachment URL via REST API
    return `${window.FluentMailbox?.root || ''}/attachments/${attachment.id}/download` || '#';
};
</script>
