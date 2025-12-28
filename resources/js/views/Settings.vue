<template>
  <div class="h-full flex flex-col">
      <header class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-800">Settings</h1>
      </header>
      
      <div class="flex-1 overflow-auto p-8">
          <div class="max-w-3xl bg-white rounded-xl">
              <form @submit.prevent="saveSettings" class="space-y-6">
                  <!-- AWS Configuration -->
                  <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                      <h2 class="text-xl font-semibold mb-4 text-gray-800">AWS Configuration</h2>
                      <div class="grid grid-cols-1 gap-6">
                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1">AWS Region</label>
                              <select v-model="form.region" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                  <option value="us-east-1">US East (N. Virginia)</option>
                                  <option value="us-west-2">US West (Oregon)</option>
                                  <option value="eu-west-1">EU (Ireland)</option>
                                  <option value="eu-central-1">EU (Frankfurt)</option>
                              </select>
                          </div>
                          
                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1">Access Key ID</label>
                              <input v-model="form.key" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-mono">
                          </div>

                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1">Secret Access Key</label>
                              <input v-model="form.secret" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-mono">
                          </div>
                      </div>
                  </div>

                  <!-- Email Configuration -->
                  <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                      <h2 class="text-xl font-semibold mb-4 text-gray-800">Email Configuration</h2>
                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">From Email Address</label>
                          <input v-model="form.from_email" type="email" placeholder="verified@example.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                          <p class="text-xs text-gray-500 mt-1">Must be verified in AWS SES.</p>
                      </div>
                  </div>

                  <div v-if="success" class="bg-green-50 text-green-600 p-4 rounded-lg flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                      Settings saved successfully
                  </div>

                  <div class="flex justify-start">
                      <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-medium shadow-md transition-all flex items-center disabled:opacity-50">
                          <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                          {{ loading ? 'Saving...' : 'Save Settings' }}
                      </button>
                  </div>
              </form>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../utils/api';

const loading = ref(false);
const success = ref(false);

const form = reactive({
    region: 'us-east-1',
    key: '',
    secret: '',
    from_email: ''
});

onMounted(async () => {
    try {
        const { data } = await api.getSettings();
        if (data) {
            form.region = data.region;
            form.key = data.key;
            form.secret = data.secret;
            form.from_email = data.from_email;
        }
    } catch (e) {
        console.error('Failed to load settings', e);
    }
});

const saveSettings = async () => {
    loading.value = true;
    success.value = false;
    try {
        await api.saveSettings(form);
        success.value = true;
        setTimeout(() => success.value = false, 3000);
    } catch (e) {
        alert('Failed to save settings');
    } finally {
        loading.value = false;
    }
};
</script>
