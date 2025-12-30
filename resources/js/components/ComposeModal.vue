<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl w-full max-w-2xl flex flex-col max-h-[90vh] border border-gray-200">
           <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
               <div class="flex items-center space-x-2.5">
                   <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                       <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                   </div>
                   <h3 class="font-semibold text-lg text-gray-800">New Message</h3>
               </div>
               <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
               </button>
           </div>

           <div class="p-6 overflow-y-auto flex-1">
               <form @submit.prevent="send" class="space-y-4">
                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1.5">To</label>
                       <input v-model="form.to" type="email" required class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all text-sm" placeholder="recipient@example.com">
                   </div>

                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject</label>
                       <input v-model="form.subject" type="text" required class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all text-sm" placeholder="Subject">
                   </div>

                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1.5">Message</label>
                       <WpEditor
                           v-model="form.body"
                           :height="250"
                           @update="(val) => {
                               form.body = val;
                           }"
                       />
                   </div>

                   <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-200 flex items-center">
                       <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                       {{ error }}
                   </div>
                   <div v-if="success" class="bg-green-50 text-green-600 p-3 rounded-lg text-sm border border-green-200 flex items-center">
                       <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                       Email sent successfully!
                   </div>

                   <div class="flex justify-end pt-1">
                       <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center disabled:opacity-70 disabled:cursor-not-allowed">
                           <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                           <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                           {{ loading ? 'Sending...' : 'Send' }}
                       </button>
                   </div>
               </form>
           </div>
      </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import api from '../utils/api';
import WpEditor from './WpEditor.vue';

const props = defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close', 'sent']);

const loading = ref(false);
const error = ref('');
const success = ref(false);

const form = reactive({
  to: '',
  subject: '',
  body: ''
});

const send = async () => {
  // Validate form
  if (!form.to || !form.subject || !form.body) {
    error.value = 'Please fill in all fields';
    return;
  }
  
  // Check if body has content (strip HTML tags)
  const bodyText = form.body.replace(/<[^>]*>?/gm, '').trim();
  if (!bodyText) {
    error.value = 'Message cannot be empty';
    return;
  }

  error.value = '';
  success.value = false;
  loading.value = true;

  try {
      await api.sendEmail(form);
      success.value = true;
      form.to = '';
      form.subject = '';
      form.body = '';
      emit('sent');
      setTimeout(() => {
          success.value = false;
          emit('close');
      }, 1500);
  } catch (e) {
      error.value = e.response?.data?.message || 'Failed to send email';
  } finally {
      loading.value = false;
  }
};
</script>
