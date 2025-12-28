<template>
  <div class="h-full flex flex-col">
      <header class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10">
          <div class="flex items-center space-x-4">
              <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
              </button>
              <h1 class="text-xl font-bold text-gray-800 truncate max-w-xl">{{ email ? email.subject : 'Loading...' }}</h1>
          </div>
          
          <div class="flex items-center space-x-2" v-if="email">
               <button @click="deleteEmail" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
               </button>
          </div>
      </header>

      <div class="flex-1 overflow-auto p-8 bg-white" v-if="!loading && email">
          <!-- Metadata -->
          <div class="flex justify-between items-start mb-8">
              <div class="flex items-center space-x-3">
                  <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                      {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                  </div>
                  <div>
                      <div class="font-bold text-gray-900">{{ email.sender }}</div>
                      <div class="text-sm text-gray-500">To: {{ getRecipients(email.recipients) }}</div>
                  </div>
              </div>
              <div class="text-sm text-gray-500">
                  {{ formatDate(email.created_at) }}
              </div>
          </div>

          <!-- Body -->
          <div class="prose max-w-none text-gray-800" v-html="email.body"></div>
      </div>
      
      <div v-else-if="loading" class="flex-1 flex justify-center items-center">
           <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
      </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../utils/api';

const route = useRoute();
const router = useRouter();
const email = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.getEmail(route.params.id);
        email.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const deleteEmail = async () => {
    if (!confirm('Are you sure you want to delete this email?')) return;
    try {
        await api.deleteEmail(email.value.id);
        router.back();
    } catch (e) {
        alert('Failed to delete email');
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
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
</script>
