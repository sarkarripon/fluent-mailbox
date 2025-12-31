<template>
  <div class="h-full flex flex-col">
      <header class="py-4 border-b border-gray-200 flex justify-between items-center bg-white/50 backdrop-blur-sm transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <div class="flex items-center space-x-4">
              <h1 class="text-xl font-semibold text-gray-800">Drafts</h1>
              <div class="text-sm text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full" v-if="drafts.length">{{ drafts.length }} drafts</div>
          </div>
      </header>

      <div class="flex-1 overflow-auto p-0">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="relative">
                  <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
              </div>
          </div>

          <div v-else-if="drafts.length > 0" class="divide-y divide-gray-100">
              <div
                  v-for="draft in drafts"
                  :key="draft.id"
                  @click="openDraft(draft)"
                  class="px-6 py-3 bg-white hover:bg-gray-50 cursor-pointer group transition-colors"
              >
                  <div class="flex items-start gap-4">
                      <div class="flex-shrink-0">
                          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center text-white font-semibold text-sm">
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                          </div>
                      </div>
                      <div class="flex-1 min-w-0">
                          <div class="flex items-center justify-between gap-3 mb-1">
                              <div class="flex items-center gap-2 min-w-0 flex-1">
                                  <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Draft</span>
                                  <span class="text-sm font-medium text-gray-900 truncate">
                                      {{ getRecipients(draft.recipients) || '(No recipients)' }}
                                  </span>
                              </div>
                              <span class="text-xs text-gray-500 flex-shrink-0">{{ formatRelativeDate(draft.updated_at) }}</span>
                          </div>
                          <h4 class="text-sm font-medium text-gray-900 mb-1 truncate">
                              {{ draft.subject || '(No Subject)' }}
                          </h4>
                          <p class="text-sm text-gray-500 truncate line-clamp-1">
                              {{ getEmailSnippet(draft.body) }}
                          </p>
                      </div>
                      <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button
                              @click.stop="deleteDraft(draft)"
                              class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                              title="Delete draft"
                          >
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                          </button>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center h-full py-12">
              <div class="w-32 h-32 mb-6 rounded-full bg-gradient-to-br from-amber-50 to-orange-50 flex items-center justify-center">
                  <svg class="w-16 h-16 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
              </div>
              <h3 class="text-xl font-semibold text-gray-700 mb-2">No drafts</h3>
              <p class="text-sm text-gray-500 max-w-sm text-center">Draft emails will appear here. Start composing to create a draft.</p>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';

const store = useAppStore();
const router = useRouter();

const drafts = ref([]);
const loading = ref(true);

const fetchDrafts = async () => {
    loading.value = true;
    try {
        const response = await api.getDrafts();
        drafts.value = response.data.data || [];
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
        return json;
    } catch (e) {
        return json;
    }
};

const openDraft = (draft) => {
    // Open compose modal with draft data
    store.openCompose('new', draft);
};

const deleteDraft = async (draft) => {
    if (!confirm('Are you sure you want to delete this draft?')) return;
    try {
        await api.deleteDraft(draft.id);
        await fetchDrafts();
    } catch (e) {
        alert('Failed to delete draft');
    }
};

onMounted(fetchDrafts);
</script>

