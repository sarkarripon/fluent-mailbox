<template>
  <div class="h-full flex flex-col">
      <header class="px-6 py-4 border-b border-gray-100/50 flex justify-between items-center bg-white/50 backdrop-blur-sm">
          <h1 class="text-xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Settings</h1>
      </header>

      <div class="flex-1 overflow-auto p-6">
          <div class="max-w-2xl mx-auto">

              <!-- Step 1: Credentials configuration -->
              <div v-if="step === 'credentials'" class="bg-white/80 backdrop-blur-sm p-6 rounded-2xl border border-gray-200/50 transition-all duration-300">
                  <div class="flex items-center mb-4">
                      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mr-3">
                          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                      </div>
                      <div>
                          <h2 class="text-lg font-bold text-gray-800">Connect to AWS SES</h2>
                          <p class="text-sm text-gray-500">Enter your AWS IAM credentials. Ensure the user has full access to SES, SNS, and S3.</p>
                      </div>
                  </div>

                  <form @submit.prevent="verifyCredentials" class="space-y-4">
                       <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1.5">AWS Region</label>
                          <select v-model="form.region" class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300">
                              <option value="us-east-1">US East (N. Virginia)</option>
                              <option value="us-west-2">US West (Oregon)</option>
                              <option value="eu-west-1">EU (Ireland)</option>
                              <option value="eu-central-1">EU (Frankfurt)</option>
                          </select>
                      </div>

                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1.5">Access Key ID</label>
                          <input v-model="form.key" type="text" required class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all font-mono hover:border-blue-300" placeholder="AKIA...">
                      </div>

                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1.5">Secret Access Key</label>
                          <input v-model="form.secret" type="password" required class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all font-mono hover:border-blue-300" placeholder="********">
                      </div>

                      <div v-if="error" class="bg-red-50/80 backdrop-blur-sm text-red-600 p-3 rounded-xl text-sm border border-red-200/50">
                         {{ error }}
                      </div>

                      <div class="pt-2">
                          <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-3 rounded-2xl font-bold transition-all flex justify-center items-center disabled:opacity-70 disabled:cursor-not-allowed hover:-translate-y-0.5 active:translate-y-0">
                              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                              {{ loading ? 'Verifying Credentials...' : 'Connect to AWS' }}
                          </button>
                      </div>
                  </form>
              </div>

              <!-- Step 2: Select Identity -->
              <div v-else-if="step === 'identity'" class="bg-white/80 backdrop-blur-sm p-6 rounded-2xl border border-gray-200/50 transition-all duration-300">
                  <div class="flex items-center mb-4 bg-gradient-to-r from-green-50 to-emerald-50 p-3 rounded-xl border border-green-200/50">
                      <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center mr-3">
                          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                      </div>
                      <span class="font-semibold text-green-700 text-sm">Connection Successful!</span>
                  </div>

                  <div class="flex items-center mb-4">
                      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center mr-3">
                          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                      </div>
                      <div>
                          <h2 class="text-lg font-bold text-gray-800">Select Sender Identity</h2>
                          <p class="text-sm text-gray-500">Choose a verified email address or domain to send emails from.</p>
                      </div>
                  </div>

                   <form @submit.prevent="saveIdentity" class="space-y-4">
                       <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1.5">Verify Identity</label>
                          <select v-model="form.from_email" required class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300">
                              <option value="" disabled>Select an identity</option>
                              <option v-for="id in identities" :key="id" :value="id">{{ id }}</option>
                          </select>
                           <p v-if="identities.length === 0" class="text-xs text-amber-600 mt-1.5 bg-amber-50/80 p-2 rounded-lg">No identities found in this region. Please verify an email in AWS Console.</p>
                      </div>

                      <div v-if="form.from_email && !form.from_email.includes('@')" class="space-y-3 p-4 bg-gray-50/80 rounded-xl">
                           <!-- Sender Name -->
                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1.5">Sender Name (Optional)</label>
                              <input v-model="form.sender_name" type="text" class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300" placeholder="e.g. Sarkar Ripon">
                              <p class="text-xs text-gray-500 mt-1.5">This name will be displayed to recipients.</p>
                          </div>

                          <!-- Email Prefix -->
                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Username</label>
                              <div class="flex">
                                  <input v-model="form.email_username" type="text" required class="flex-1 px-3 py-2.5 bg-white border border-gray-200/70 rounded-l-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300" placeholder="e.g. self">
                                  <span class="inline-flex items-center px-3 rounded-r-xl border border-l-0 border-gray-200/70 bg-gray-100/80 text-gray-600 font-medium text-sm">
                                      @{{ form.from_email }}
                                  </span>
                              </div>
                          </div>
                      </div>

                      <div class="pt-2 flex space-x-2">
                          <button type="button" @click="step = 'credentials'" class="flex-1 bg-white border border-gray-200/70 hover:bg-gray-50 text-gray-700 py-2.5 rounded-2xl font-medium transition-all text-sm">
                              Back
                          </button>
                          <button type="submit" :disabled="loading || !form.from_email" class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white py-2.5 rounded-2xl font-bold transition-all flex justify-center items-center disabled:opacity-70 disabled:cursor-not-allowed hover:-translate-y-0.5 active:translate-y-0">
                              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                              {{ loading ? 'Saving...' : 'Complete Setup' }}
                          </button>
                      </div>
                   </form>
              </div>

              <!-- Step 3: Dashboard / Connected State -->
              <div v-else-if="step === 'dashboard' || true" class="space-y-4">
                  <!-- Success Banner -->
                   <div class="bg-gradient-to-br from-green-600 via-emerald-500 to-teal-600 rounded-2xl p-5 text-white relative overflow-hidden">
                       <div class="absolute inset-0">
                           <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-32 translate-x-32"></div>
                       </div>
                       <div class="relative z-10 flex items-center justify-between">
                           <div class="flex items-center space-x-4">
                               <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/30">
                                   <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                               </div>
                               <div class="text-left">
                                   <h2 class="text-xl font-bold mb-1">All Set!</h2>
                                   <p class="text-green-50/90 text-sm">Ready to send emails from <span class="font-semibold text-white bg-white/20 px-2 py-0.5 rounded-full mx-1 text-xs inline-block">{{ form.from_email }}</span></p>
                               </div>
                           </div>
                           <div class="text-5xl text-white/5 font-light">
                               ✓
                           </div>
                       </div>
                   </div>

                   <!-- Configuration Summary -->
                   <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-4 flex justify-between items-center">
                       <div>
                           <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Connected Region</div>
                           <div class="font-semibold text-gray-800 text-base">{{ form.region }}</div>
                       </div>
                       <button @click="disconnect" class="text-red-500 hover:text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-xl text-xs font-medium transition-all duration-300">
                           Disconnect & Reset
                       </button>
                   </div>

                   <!-- Incoming Config -->
                   <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                        <div class="p-4 border-b border-gray-100/50 flex justify-between items-center">
                             <div class="flex items-center">
                                 <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mr-2">
                                     <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                 </div>
                                 <h3 class="text-lg font-bold text-gray-800">Incoming Configuration</h3>
                             </div>
                             <button v-if="inboundConfigured" @click="resetInbound" class="text-red-500 hover:text-red-700 hover:bg-red-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300">
                                 Reset Configuration
                             </button>
                        </div>

                        <div v-if="!inboundConfigured" class="p-6 text-center bg-gradient-to-br from-gray-50 to-blue-50/30">
                             <div class="inline-flex p-3 bg-gradient-to-br from-amber-400 to-orange-500 text-white rounded-full mb-3">
                                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                             </div>
                             <h4 class="text-base font-bold text-gray-800 mb-1">Incoming message is not configured</h4>
                             <p class="text-sm text-gray-500 max-w-md mx-auto mb-4">Setup now to get a full featured mailbox. This will automatically create the necessary S3 Bucket, SNS Topic, and SES Rules for you.</p>

                             <button @click="setupInbound" :disabled="loading" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-2.5 rounded-2xl font-bold transition-all inline-flex items-center disabled:opacity-70 disabled:cursor-not-allowed hover:-translate-y-0.5 active:translate-y-0 text-sm">
                                 <svg v-if="loading" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                 {{ loading ? 'Configuring...' : 'Setup Now' }}
                             </button>
                             <div v-if="error" class="mt-3 bg-red-50/80 backdrop-blur-sm text-red-600 p-3 rounded-xl max-w-lg mx-auto border border-red-200/50 text-xs">{{ error }}</div>
                        </div>

                        <div v-else class="p-4">
                            <div class="flex items-center p-3 bg-green-50/80 backdrop-blur-sm rounded-xl border border-green-200/50">
                                <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center mr-2">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-green-700 text-sm">Inbound handling is active</span>
                                    <p class="text-gray-500 text-xs mt-1">Emails sent to this domain will be processed via S3 and SNS Webhooks.</p>
                                </div>
                            </div>
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
