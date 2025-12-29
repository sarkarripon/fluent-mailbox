<template>
  <div class="h-full flex flex-col">
      <header class="px-8 py-6 border-b border-gray-100/50 flex justify-between items-center bg-white/50 backdrop-blur-sm sticky top-0 z-10">
          <div class="flex items-center space-x-4">
              <button @click="$router.back()" class="p-2.5 hover:bg-gray-100/70 rounded-xl text-gray-600 hover:text-blue-600 transition-all duration-300">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
              </button>
              <h1 class="text-xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent truncate max-w-xl">{{ email ? email.subject : 'Loading...' }}</h1>
          </div>

          <div class="flex items-center space-x-2" v-if="email">
               <button @click="deleteEmail" class="p-2.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all duration-300" title="Delete">
                  <svg class="w-5 h-5 transition-transform hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
               </button>
          </div>
      </header>

      <div class="flex-1 overflow-auto p-8 bg-white/50" v-if="!loading && email">
          <!-- Metadata -->
          <div class="bg-white/70 backdrop-blur-sm rounded-2xl p-6 mb-8 border border-gray-100/50">
              <div class="flex justify-between items-start">
                  <div class="flex items-center space-x-4">
                      <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl">
                          {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                      </div>
                      <div>
                          <div class="font-bold text-gray-900 text-lg">{{ email.sender }}</div>
                          <div class="text-sm text-gray-500 mt-1">To: <span class="text-gray-700">{{ getRecipients(email.recipients) }}</span></div>
                      </div>
                  </div>
                  <div class="text-sm text-gray-500 bg-gray-100/70 px-4 py-2 rounded-full">
                      {{ formatDate(email.created_at) }}
                  </div>
              </div>
          </div>

          <!-- Body -->
          <div class="bg-white/70 backdrop-blur-sm rounded-2xl p-8 border border-gray-100/50 prose max-w-none text-gray-800" v-html="email.body"></div>
      </div>

      <div v-else-if="loading" class="flex-1 flex justify-center items-center">
           <div class="relative">
               <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
           </div>
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
