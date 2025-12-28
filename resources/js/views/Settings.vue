<template>
  <div class="h-full flex flex-col">
      <header class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-800">Settings</h1>
      </header>
      
      <div class="flex-1 overflow-auto p-8">
          <div class="max-w-3xl mx-auto">
              
              <!-- Step 1: Credentials configuration -->
              <div v-if="step === 'credentials'" class="bg-white p-8 rounded-xl border border-gray-200 shadow-sm transition-all duration-300">
                  <h2 class="text-xl font-bold mb-2 text-gray-800">Connect to AWS SES</h2>
                  <p class="text-gray-500 mb-6">Enter your AWS IAM credentials. Ensure the user has full access to SES, SNS, and S3.</p>

                  <form @submit.prevent="verifyCredentials" class="space-y-5">
                       <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">AWS Region</label>
                          <select v-model="form.region" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                              <option value="us-east-1">US East (N. Virginia)</option>
                              <option value="us-west-2">US West (Oregon)</option>
                              <option value="eu-west-1">EU (Ireland)</option>
                              <option value="eu-central-1">EU (Frankfurt)</option>
                          </select>
                      </div>
                      
                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Access Key ID</label>
                          <input v-model="form.key" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-mono" placeholder="AKIA...">
                      </div>

                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Secret Access Key</label>
                          <input v-model="form.secret" type="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-mono" placeholder="********">
                      </div>

                      <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-100">
                         {{ error }}
                      </div>

                      <div class="pt-2">
                          <button type="submit" :disabled="loading" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-md transition-all flex justify-center items-center disabled:opacity-70 disabled:cursor-not-allowed">
                              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                              {{ loading ? 'Verifying Credentials...' : 'Connect to AWS' }}
                          </button>
                      </div>
                  </form>
              </div>

              <!-- Step 2: Select Identity -->
              <div v-else-if="step === 'identity'" class="bg-white p-8 rounded-xl border border-gray-200 shadow-sm transition-all duration-300">
                  <div class="flex items-center mb-6 text-green-600 bg-green-50 p-3 rounded-lg">
                      <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                      <span class="font-semibold">Connection Successful!</span>
                  </div>
                  
                  <h2 class="text-xl font-bold mb-2 text-gray-800">Select Sender Identity</h2>
                  <p class="text-gray-500 mb-6">Choose a verified email address or domain to send emails from.</p>

                   <form @submit.prevent="saveIdentity" class="space-y-5">
                       <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Verify Identity</label>
                          <select v-model="form.from_email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                              <option value="" disabled>Select an identity</option>
                              <option v-for="id in identities" :key="id" :value="id">{{ id }}</option>
                          </select>
                           <p v-if="identities.length === 0" class="text-sm text-yellow-600 mt-2">No identities found in this region. Please verify an email in AWS Console.</p>
                      </div>

                      <div v-if="form.from_email && !form.from_email.includes('@')" class="space-y-4 animate-fade-in-down">
                           <!-- Sender Name -->
                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1">Sender Name (Optional)</label>
                              <input v-model="form.sender_name" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. Sarkar Ripon">
                              <p class="text-xs text-gray-500 mt-1">This name will be displayed to recipients.</p>
                          </div>
                          
                          <!-- Email Prefix -->
                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1">Email Username</label>
                              <div class="flex">
                                  <input v-model="form.email_username" type="text" required class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. self">
                                  <span class="inline-flex items-center px-4 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500">
                                      @{{ form.from_email }}
                                  </span>
                              </div>
                          </div>
                      </div>

                      <div class="pt-2 flex space-x-3">
                          <button type="button" @click="step = 'credentials'" class="flex-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 py-3 rounded-xl font-medium transition-colors">
                              Back
                          </button>
                          <button type="submit" :disabled="loading || !form.from_email" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-md transition-all flex justify-center items-center disabled:opacity-70 disabled:cursor-not-allowed">
                              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                              {{ loading ? 'Saving...' : 'Complete Setup' }}
                          </button>
                      </div>
                   </form>
              </div>

              <!-- Step 3: Dashboard / Connected State -->
              <div v-else-if="step === 'dashboard'" class="space-y-6">
                  <!-- Success Banner -->
                   <div class="bg-green-600 rounded-xl p-8 text-white shadow-lg text-center">
                       <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                           <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                       </div>
                       <h2 class="text-3xl font-bold mb-2">You are all set!</h2>
                       <p class="text-blue-100 text-lg">Your mailbox is ready to send emails using <span class="font-bold">{{ form.from_email }}</span>.</p>
                   </div>
                   
                   <!-- Configuration Summary -->
                   <div class="bg-white rounded-xl border border-gray-200 p-6 flex justify-between items-center shadow-sm">
                       <div>
                           <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Connected Region</div>
                           <div class="font-medium text-gray-800">{{ form.region }}</div>
                       </div>
                       <button @click="disconnect" class="text-red-500 hover:text-red-700 text-sm font-medium hover:underline">
                           Disconnect & Reset
                       </button>
                   </div>

                   <!-- Incoming Config -->
                   <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                             <h3 class="text-lg font-bold text-gray-800">Incoming Configuration</h3>
                             <button v-if="inboundConfigured" @click="resetInbound" class="text-red-500 hover:text-red-700 text-sm font-medium hover:underline">
                                 Reset Configuration
                             </button>
                        </div>
                        
                        <div v-if="!inboundConfigured" class="p-8 text-center bg-gray-50">
                             <div class="inline-flex p-3 bg-yellow-100 text-yellow-600 rounded-full mb-4">
                                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                             </div>
                             <h4 class="text-lg font-bold text-gray-800 mb-2">Incoming message is not configured</h4>
                             <p class="text-gray-500 max-w-md mx-auto mb-6">Setup now to get full featured mailbox. This will automatically create the necessary S3 Bucket, SNS Topic, and SES Rules for you.</p>
                             
                             <button @click="setupInbound" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium shadow-md transition-all inline-flex items-center disabled:opacity-70">
                                 <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                 {{ loading ? 'Configuring AWS Resources...' : 'Setup Now' }}
                             </button>
                             <div v-if="error" class="mt-4 text-red-600 text-sm max-w-lg mx-auto">{{ error }}</div>
                        </div>

                        <div v-else class="p-6">
                            <div class="flex items-center mb-4 text-green-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="font-medium">Inbound handling is active</span>
                            </div>
                            <p class="text-gray-500 text-sm">Emails sent to this domain will be processed via S3 and SNS Webhooks.</p>
                        </div>
                   </div>
              </div>

          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';

const store = useAppStore();
const step = ref('credentials'); // credentials, identity, dashboard
const loading = ref(false);
const error = ref('');
const identities = ref([]);
const inboundConfigured = ref(false);

const form = reactive({
    region: 'us-east-1',
    key: '',
    secret: '',
    from_email: ''
});

onMounted(async () => {
    loading.value = true;
    try {
        const { data } = await api.getSettings();
        if (data.key && !data.key.includes('****')) {
             // Not masked = empty or invalid likely? Or just check if set.
        }
        
        // If we have data, populate. If we have a verified email, go to dashboard.
        if (data.key && data.from_email) {
            form.region = data.region;
            form.key = data.key;
            form.secret = data.secret;
            form.from_email = data.from_email;
            inboundConfigured.value = data.inbound_configured;
            step.value = 'dashboard';
        } else if (data.key) {
             // We have key but no email? Maybe partial setup
             form.region = data.region;
             form.key = data.key;
             form.secret = data.secret;
        }

    } catch (e) {
        console.error('Failed to load settings', e);
    } finally {
        loading.value = false;
    }
});

const verifyCredentials = async () => {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.verifyCredentials(form);
        identities.value = data.identities;
        step.value = 'identity';
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to connect. Please check your credentials.';
    } finally {
        loading.value = false;
    }
};

const saveIdentity = async () => {
    loading.value = true;
    error.value = '';
    
    // Clone form to avoid mutating UI
    let payload = { ...form };
    
    // If it's a domain, merge with email_username
    if (payload.from_email && !payload.from_email.includes('@')) {
        const user = payload.email_username || 'contact';
        payload.from_email = `${user}@${payload.from_email}`;
    }

    try {
        await api.saveConnection(payload);
        // Update local form
        form.from_email = payload.from_email;
        store.setConfigured(true);
        step.value = 'dashboard';
    } catch (e) {
        error.value = 'Failed to save configuration.';
    } finally {
        loading.value = false;
    }
};

const setupInbound = async () => {
    loading.value = true;
    error.value = '';
    try {
        await api.setupInbound();
        inboundConfigured.value = true;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to setup inbound resources.';
    } finally {
        loading.value = false;
    }
};

const disconnect = async () => {
    if(!confirm('Are you sure you want to disconnect? This will clear your AWS credentials from this site.')) return;
    
    // Disconnect credentials
    try {
        await api.saveConnection({ key: '', secret: '', from_email: '' });
        form.key = '';
        form.secret = '';
        form.from_email = '';
        store.setConfigured(false);
        step.value = 'credentials';
    } catch(e) {
        alert('Failed to disconnect');
    }
};

const resetInbound = async () => {
    if(!confirm('Are you sure you want to reset inbound configuration? Use this if you deleted resources on AWS and need to setup again.')) return;
    
    loading.value = true;
    try {
        await api.disconnect(); // This endpoint clears ONLY the inbound S3/SNS options
        inboundConfigured.value = false;
        alert('Configuration reset. You can now run "Setup Now" again.');
    } catch (e) {
        console.error(e);
        alert('Failed to reset configuration');
    } finally {
        loading.value = false;
    }
};
</script>
