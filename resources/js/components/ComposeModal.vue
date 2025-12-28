<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[90vh]">
           <div class="flex justify-between items-center px-6 py-4 border-b">
               <h3 class="font-bold text-lg text-gray-800">New Message</h3>
               <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
               </button>
           </div>
           
           <div class="p-6 overflow-y-auto flex-1">
               <form @submit.prevent="send" class="space-y-4">
                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                       <input v-model="form.to" type="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="recipient@example.com">
                   </div>
                   
                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                       <input v-model="form.subject" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Subject">
                   </div>
                   
                   <div>
                       <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                       <textarea v-model="form.body" required rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-sans" placeholder="Write your message..."></textarea>
                   </div>

                   <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
                       {{ error }}
                   </div>
                   <div v-if="success" class="bg-green-50 text-green-600 p-3 rounded-lg text-sm">
                       Email sent successfully!
                   </div>

                   <div class="flex justify-end pt-2">
                       <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium shadow-md transition-all flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                           <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
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
