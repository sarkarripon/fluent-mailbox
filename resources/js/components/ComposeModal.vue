<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black/60 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white/95 backdrop-blur-xl rounded-3xl w-full max-w-2xl flex flex-col max-h-[90vh] border border-white/20">
           <div class="flex justify-between items-center px-8 py-5 border-b border-gray-100/50 bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-t-3xl">
               <div class="flex items-center space-x-3">
                   <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center">
                       <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                   </div>
                   <h3 class="font-bold text-xl text-gray-800">New Message</h3>
               </div>
               <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100/80 p-2 rounded-xl transition-all duration-300">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
               </button>
           </div>

           <div class="p-8 overflow-y-auto flex-1">
               <form @submit.prevent="send" class="space-y-5">
                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-2">To</label>
                       <input v-model="form.to" type="email" required class="w-full px-4 py-3.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300" placeholder="recipient@example.com">
                   </div>

                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                       <input v-model="form.subject" type="text" required class="w-full px-4 py-3.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300" placeholder="Subject">
                   </div>

                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                       <textarea v-model="form.body" required rows="6" class="w-full px-4 py-3.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all font-sans hover:border-blue-300 resize-none" placeholder="Write your message..."></textarea>
                   </div>

                   <div v-if="error" class="bg-red-50/80 backdrop-blur-sm text-red-600 p-4 rounded-xl text-sm border border-red-200/50 flex items-center">
                       <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                       {{ error }}
                   </div>
                   <div v-if="success" class="bg-green-50/80 backdrop-blur-sm text-green-600 p-4 rounded-xl text-sm border border-green-200/50 flex items-center">
                       <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                       Email sent successfully!
                   </div>

                   <div class="flex justify-end pt-2">
                       <button type="submit" :disabled="loading" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 py-3.5 rounded-2xl font-bold transition-all flex items-center disabled:opacity-70 disabled:cursor-not-allowed hover:-translate-y-0.5 active:translate-y-0">
                           <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                           <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                           {{ loading ? 'Sending...' : 'Send Message' }}
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
  loading.value = true;
  error.value = '';
  success.value = false;

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
