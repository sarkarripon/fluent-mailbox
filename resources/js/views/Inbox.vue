<template>
  <div class="h-full flex flex-col">
      <header class="py-4 border-b border-gray-200 flex flex-col gap-3 bg-white/50 backdrop-blur-sm sticky top-0 z-10 transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                   <div v-if="isSelectionMode" class="flex items-center gap-3">
                       <input 
                           type="checkbox"
                           :checked="selectedEmails.length === filteredEmails.length && filteredEmails.length > 0"
                           :indeterminate="selectedEmails.length > 0 && selectedEmails.length < filteredEmails.length"
                           @change="toggleSelectAll"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                       >
                       <span class="text-sm text-gray-700 font-medium">
                           {{ selectedEmails.length }} selected
                       </span>
                   </div>
                   <div v-else class="flex items-center space-x-4">
                       <h1 class="text-xl font-semibold text-gray-800">Inbox</h1>
                       <div class="text-sm text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full" v-if="emails.length">
                           {{ unreadCount }} of {{ emails.length }}
                       </div>
                   </div>
              </div>

              <div class="flex items-center space-x-2">
                  <div v-if="isSelectionMode" class="flex items-center gap-2">
                      <Tooltip text="Mark all selected emails as read">
                          <button @click="bulkMarkAsRead" :disabled="selectedEmails.length === 0" class="px-3 py-1.5 text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                              Mark as read
                          </button>
                      </Tooltip>
                      <Tooltip text="Move all selected emails to trash">
                          <button @click="bulkDelete" :disabled="selectedEmails.length === 0" class="px-3 py-1.5 text-sm text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                              Delete
                          </button>
                      </Tooltip>
                      <button @click="exitSelectionMode" class="px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-all">
                          Cancel
                      </button>
                  </div>
                  <template v-else>
                      <Tooltip text="Select multiple emails to perform bulk actions">
                          <button @click="enterSelectionMode" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                          </button>
                      </Tooltip>
                  </template>
                  <Tooltip text="Sync emails from S3 bucket. This fetches any new emails that have been received.">
                      <button @click="handleRefresh" :disabled="isRefreshing" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                          <svg :class="{'animate-spin': isRefreshing}" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                      </button>
                  </Tooltip>
              </div>
          </div>

          <!-- Search Bar -->
          <div class="relative">
              <input 
                  v-model="searchQuery"
                  type="text" 
                  placeholder="Search..." 
                  class="w-300[px] float-right pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all"
              >
          </div>
      </header>
      
      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="relative">
                  <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
              </div>
          </div>

          <div v-else-if="filteredEmails.length > 0" class="divide-y divide-gray-100">
              <div 
                  v-for="email in filteredEmails" 
                  :key="email.id" 
                  @click="isSelectionMode ? toggleSelect(email) : openEmail(email.id)"
                  class="px-6 py-3 bg-white hover:bg-gray-50 cursor-pointer group transition-colors relative"
                  :class="!email.is_read ? 'bg-blue-50/30' : '', isSelected(email.id) ? 'bg-blue-100/50' : ''"
              >
                  <div class="flex items-start gap-4">
                      <!-- Selection Checkbox or Unread Indicator -->
                      <div class="flex-shrink-0 pt-1">
                          <input 
                              v-if="isSelectionMode"
                              type="checkbox"
                              :checked="isSelected(email.id)"
                              @click.stop="toggleSelect(email)"
                              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                          >
                          <div v-else-if="!email.is_read" class="w-2 h-2 bg-blue-600 rounded-full"></div>
                      </div>

                      <!-- Avatar -->
                      <div class="flex-shrink-0">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-semibold text-sm">
                              {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                          </div>
                      </div>

                      <!-- Email Content -->
                      <div class="flex-1 min-w-0">
                          <div class="flex items-center justify-between gap-3 mb-1">
                              <div class="flex items-center gap-2 min-w-0 flex-1">
                                  <svg v-if="email.is_starred" class="w-3.5 h-3.5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                                  <span class="text-sm font-medium text-gray-900 truncate" :class="!email.is_read ? 'font-semibold' : ''">
                                      {{ email.sender }}
                                  </span>
                              </div>
                              <span class="text-xs text-gray-500 flex-shrink-0">{{ formatRelativeDate(email.created_at) }}</span>
                          </div>
                          <h4 class="text-sm font-medium text-gray-900 mb-1 truncate flex items-center gap-2" :class="!email.is_read ? 'font-semibold' : ''">
                              <span>{{ email.subject || '(No Subject)' }}</span>
                          </h4>
                          <p class="text-sm text-gray-500 truncate line-clamp-1">
                              {{ getEmailSnippet(email.body) }}
                          </p>
                      </div>

                      <!-- Quick Actions (shown on hover) -->
                      <div v-if="!isSelectionMode" class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button 
                              @click.stop="toggleStar(email)"
                              class="p-1.5 text-gray-400 hover:text-yellow-500 hover:bg-yellow-50 rounded transition-colors"
                              :title="email.is_starred ? 'Unstar' : 'Star'"
                          >
                              <svg v-if="email.is_starred" class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                          </button>
                          <button 
                              @click.stop="toggleRead(email)"
                              class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                              :title="email.is_read ? 'Mark as unread' : 'Mark as read'"
                          >
                              <svg v-if="email.is_read" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                          </button>
                          <button 
                              @click.stop="deleteEmail(email)"
                              class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                              title="Delete"
                          >
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                          </button>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full py-12">
              <div class="w-32 h-32 mb-6 rounded-full bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                  <svg class="w-16 h-16 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
              </div>
              <h3 class="text-xl font-semibold text-gray-700 mb-2">Your inbox is empty</h3>
              <p class="text-sm text-gray-500 mb-6 max-w-sm text-center">No emails here yet. New messages will appear in your inbox.</p>
              <button 
                  v-if="store.isConfigured"
                  @click="router.push('/inbox')"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
              >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                  Compose Email
              </button>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';
import { useEmailCounts } from '../composables/useEmailCounts';
import Tooltip from '../components/Tooltip.vue';

const store = useAppStore();
const router = useRouter();
const emailCounts = useEmailCounts();

const emails = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const isRefreshing = ref(false);
const isSelectionMode = ref(false);
const selectedEmails = ref([]);

const fetchEmails = async () => {
    loading.value = true;
    try {
        // Get inbox emails (status = 'inbox' or not 'sent' and not 'trash')
        const response = await api.getEmails(1, 'all'); 
        const allEmails = response.data.data || [];
        // Filter for inbox emails
        emails.value = allEmails.filter(email => 
            email.status === 'inbox' || (email.status !== 'sent' && email.status !== 'trash')
        );
        // Refresh counts
        emailCounts.fetchCounts();
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

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

const unreadCount = computed(() => {
    return emails.value.filter(email => !email.is_read).length;
});

const filteredEmails = computed(() => {
    if (!searchQuery.value.trim()) {
        return emails.value;
    }
    const query = searchQuery.value.toLowerCase();
    return emails.value.filter(email => {
        return (
            email.subject?.toLowerCase().includes(query) ||
            email.sender?.toLowerCase().includes(query) ||
            email.body?.toLowerCase().includes(query)
        );
    });
});

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

const openEmail = (id) => {
    router.push(`/emails/${id}`);
};

const toggleRead = async (email) => {
    try {
        const newStatus = email.is_read ? 0 : 1;
        await api.updateEmail(email.id, { is_read: newStatus });
        email.is_read = newStatus;
        // Refresh counts after toggling read status
        emailCounts.fetchCounts();
    } catch (e) {
        console.error('Failed to update read status', e);
    }
};

const deleteEmail = async (email) => {
    if (!confirm('Are you sure you want to delete this email?')) return;
    try {
        await api.deleteEmail(email.id);
        await fetchEmails();
        emailCounts.fetchCounts();
    } catch (e) {
        alert('Failed to delete email');
    }
};

// Bulk selection functions
const enterSelectionMode = () => {
    isSelectionMode.value = true;
    selectedEmails.value = [];
};

const exitSelectionMode = () => {
    isSelectionMode.value = false;
    selectedEmails.value = [];
};

const isSelected = (emailId) => {
    return selectedEmails.value.some(e => e.id === emailId);
};

const toggleSelect = (email) => {
    const index = selectedEmails.value.findIndex(e => e.id === email.id);
    if (index > -1) {
        selectedEmails.value.splice(index, 1);
    } else {
        selectedEmails.value.push(email);
    }
};

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedEmails.value = [...filteredEmails.value];
    } else {
        selectedEmails.value = [];
    }
};

const bulkMarkAsRead = async () => {
    try {
        const promises = selectedEmails.value.map(email => 
            api.updateEmail(email.id, { is_read: 1 })
        );
        await Promise.all(promises);
        await fetchEmails();
        emailCounts.fetchCounts();
        exitSelectionMode();
    } catch (e) {
        alert('Failed to mark emails as read');
    }
};

const bulkDelete = async () => {
    if (!confirm(`Are you sure you want to delete ${selectedEmails.value.length} email(s)?`)) return;
    try {
        const promises = selectedEmails.value.map(email => 
            api.deleteEmail(email.id)
        );
        await Promise.all(promises);
        await fetchEmails();
        emailCounts.fetchCounts();
        exitSelectionMode();
    } catch (e) {
        alert('Failed to delete emails');
    }
};

// Star functionality (using localStorage for now, can be moved to backend later)
const toggleStar = (email) => {
    if (!email.is_starred) {
        email.is_starred = 1;
        localStorage.setItem(`starred_${email.id}`, '1');
    } else {
        email.is_starred = 0;
        localStorage.removeItem(`starred_${email.id}`);
    }
};

// Initialize star status from localStorage
const initializeStars = () => {
    emails.value.forEach(email => {
        email.is_starred = localStorage.getItem(`starred_${email.id}`) === '1' ? 1 : 0;
    });
};

onMounted(() => {
    fetchEmails().then(() => {
        initializeStars();
    });
});

// Watch for route changes to exit selection mode
watch(() => router.currentRoute.value.path, () => {
    if (isSelectionMode.value) {
        exitSelectionMode();
    }
});
</script>
