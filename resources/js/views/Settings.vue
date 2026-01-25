<template>
  <div class="h-full flex flex-col">
      <header class="py-4 border-b flex justify-between items-center backdrop-blur-sm transition-all duration-300" :class="[store.isCompact ? 'pl-16 pr-6' : 'px-6', store.isDarkTheme ? 'border-gray-700/50 bg-gray-900/30' : 'border-gray-100/50 bg-white/50']">
          <h1 class="text-xl font-bold" :class="store.isDarkTheme ? 'text-gray-100' : 'bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent'">Settings</h1>
      </header>

      <div class="flex-1 overflow-auto p-4">
          <div class="max-w-xl mx-auto">

              <!-- Initial Loading Skeleton -->
              <div v-if="initialLoading" class="space-y-6">
                  <!-- Tab skeleton -->
                  <div class="flex space-x-1 rounded-xl p-1" :class="store.isDarkTheme ? 'bg-gray-800' : 'bg-gray-100'">
                      <div class="flex-1 h-10 rounded-lg skeleton-shimmer" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                      <div class="flex-1 h-10 rounded-lg skeleton-shimmer" style="animation-delay: 0.1s" :class="store.isDarkTheme ? 'bg-gray-700/50' : 'bg-gray-200/50'"></div>
                  </div>

                  <!-- Card skeleton -->
                  <div class="rounded-2xl p-6 border relative overflow-hidden" :class="store.isDarkTheme ? 'bg-gray-800/50 border-gray-700' : 'bg-white/70 border-gray-200/50'">
                      <!-- Header -->
                      <div class="flex items-center mb-6">
                          <div class="w-12 h-12 rounded-xl skeleton-shimmer" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                          <div class="ml-4 flex-1 space-y-2">
                              <div class="h-5 rounded-lg w-2/3 skeleton-shimmer" style="animation-delay: 0.1s" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                              <div class="h-3 rounded-lg w-1/2 skeleton-shimmer" style="animation-delay: 0.2s" :class="store.isDarkTheme ? 'bg-gray-700/60' : 'bg-gray-200/60'"></div>
                          </div>
                      </div>
                      <!-- Form fields -->
                      <div class="space-y-4">
                          <div class="space-y-2">
                              <div class="h-4 rounded w-24 skeleton-shimmer" style="animation-delay: 0.15s" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                              <div class="h-11 rounded-xl skeleton-shimmer" style="animation-delay: 0.2s" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                          </div>
                          <div class="space-y-2">
                              <div class="h-4 rounded w-32 skeleton-shimmer" style="animation-delay: 0.25s" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                              <div class="h-11 rounded-xl skeleton-shimmer" style="animation-delay: 0.3s" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                          </div>
                          <div class="space-y-2">
                              <div class="h-4 rounded w-28 skeleton-shimmer" style="animation-delay: 0.35s" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                              <div class="h-11 rounded-xl skeleton-shimmer" style="animation-delay: 0.4s" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                          </div>
                          <div class="h-12 rounded-2xl mt-6 skeleton-shimmer" style="animation-delay: 0.45s" :class="store.isDarkTheme ? 'bg-gray-700' : 'bg-gray-200'"></div>
                      </div>

                      <!-- Animated shimmer overlay -->
                      <div class="absolute inset-0 -translate-x-full animate-shimmer pointer-events-none"
                           :class="store.isDarkTheme ? 'bg-gradient-to-r from-transparent via-gray-600/10 to-transparent' : 'bg-gradient-to-r from-transparent via-white/40 to-transparent'">
                      </div>
                  </div>

                  <!-- Decorative loading indicator -->
                  <div class="flex justify-center items-center py-4">
                      <div class="flex items-center gap-3">
                          <div class="flex gap-1">
                              <span class="w-2 h-2 rounded-full animate-bounce fm-bg-primary" style="animation-delay: 0ms"></span>
                              <span class="w-2 h-2 rounded-full animate-bounce fm-bg-primary opacity-75" style="animation-delay: 150ms"></span>
                              <span class="w-2 h-2 rounded-full animate-bounce fm-bg-primary opacity-50" style="animation-delay: 300ms"></span>
                          </div>
                          <span class="text-sm font-medium" :class="store.isDarkTheme ? 'text-gray-400' : 'text-gray-500'">Loading settings...</span>
                      </div>
                  </div>
              </div>

              <!-- Main Content (shown after loading) -->
              <template v-else>

              <!-- Tab Navigation -->
              <div class="flex space-x-1 mb-6 rounded-xl p-1" :class="store.isDarkTheme ? 'bg-gray-800' : 'bg-gray-100'">
                  <button
                      @click="activeTab = 'aws'"
                      class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-medium transition-all duration-200"
                      :class="activeTab === 'aws'
                          ? (store.isDarkTheme ? 'bg-gray-700 text-gray-100 shadow-sm' : 'bg-white text-gray-900 shadow-sm')
                          : (store.isDarkTheme ? 'text-gray-400 hover:text-gray-200 hover:bg-gray-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50')"
                  >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                      </svg>
                      AWS Configuration
                  </button>
                  <button
                      @click="activeTab = 'appearance'"
                      class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-medium transition-all duration-200"
                      :class="activeTab === 'appearance'
                          ? (store.isDarkTheme ? 'bg-gray-700 text-gray-100 shadow-sm' : 'bg-white text-gray-900 shadow-sm')
                          : (store.isDarkTheme ? 'text-gray-400 hover:text-gray-200 hover:bg-gray-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50')"
                  >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                      </svg>
                      Appearance
                  </button>
              </div>

              <!-- AWS Configuration Tab -->
              <div v-show="activeTab === 'aws'">
              <!-- Step 1: Credentials configuration -->
              <div v-if="step === 'credentials'" class="bg-white/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-200/50 transition-all duration-300">
                  <div class="flex items-center mb-3">
                      <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mr-3">
                          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                      </div>
                      <div>
                          <h2 class="text-lg font-bold text-gray-800">Connect to AWS SES</h2>
                          <p class="text-xs text-gray-500 mt-0.5">Enter your AWS IAM credentials. Ensure the user has full access to SES, SNS, and S3.</p>
                      </div>
                  </div>

                  <form @submit.prevent="verifyCredentials" class="space-y-3">
                       <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1.5 flex items-center gap-1.5">
                              AWS Region
                              <Tooltip text="Select the AWS region where your SES service is configured. This should match the region where you verified your email/domain.">
                                  <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                              </Tooltip>
                          </label>
                          <select v-model="form.region" class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300">
                              <option value="us-east-1">US East (N. Virginia)</option>
                              <option value="us-west-2">US West (Oregon)</option>
                              <option value="eu-west-1">EU (Ireland)</option>
                              <option value="eu-central-1">EU (Frankfurt)</option>
                          </select>
                      </div>

                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1.5 flex items-center gap-1.5">
                              Access Key ID
                              <Tooltip text="Your AWS IAM user's Access Key ID. Create one in AWS Console > IAM > Users > Security credentials. The user needs SES, SNS, and S3 permissions.">
                                  <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                              </Tooltip>
                          </label>
                          <input v-model="form.key" type="text" required class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all font-mono hover:border-blue-300" placeholder="AKIA...">
                      </div>

                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1.5 flex items-center gap-1.5">
                              Secret Access Key
                              <Tooltip text="The secret key paired with your Access Key ID. Keep this secure and never share it. It's only shown once when created in AWS Console.">
                                  <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                              </Tooltip>
                          </label>
                          <input v-model="form.secret" type="password" required class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all font-mono hover:border-blue-300" placeholder="********">
                      </div>

                      <div v-if="error" class="bg-red-50/80 backdrop-blur-sm text-red-600 p-3 rounded-xl text-sm border border-red-200/50">
                         {{ error }}
                      </div>

                      <div class="pt-2">
                          <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-3 rounded-2xl font-bold transition-all flex justify-center items-center disabled:opacity-70 disabled:cursor-not-allowed hover:-translate-y-0.5 active:translate-y-0 relative overflow-hidden group">
                              <template v-if="loading">
                                  <span class="flex items-center gap-1">
                                      <span class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                      <span class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                      <span class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                  </span>
                                  <span class="ml-3">Verifying...</span>
                              </template>
                              <template v-else>
                                  <svg class="w-5 h-5 mr-2 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                  Connect to AWS
                              </template>
                          </button>
                      </div>
                  </form>
              </div>

              <!-- Step 2: Select Identity -->
              <div v-else-if="step === 'identity'" class="bg-white/70 backdrop-blur-sm p-4 rounded-xl border border-gray-200/50 transition-all duration-300">
                  <div class="flex items-center mb-3 bg-gradient-to-r from-green-50 to-emerald-50 p-2 rounded-lg border border-green-200/50">
                      <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center mr-2">
                          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                      </div>
                      <span class="font-semibold text-green-700 text-xs">Connected!</span>
                  </div>

                  <div class="mb-4">
                      <h2 class="text-base font-bold text-gray-800">Select Sender Identity</h2>
                  </div>

                   <form @submit.prevent="saveIdentity" class="space-y-3">
                       <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1.5 flex items-center gap-1.5">
                              Verify Identity
                              <Tooltip text="Select a verified email address or domain from AWS SES. You must verify identities in AWS Console first. This will be used as the 'From' address for outgoing emails.">
                                  <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                              </Tooltip>
                          </label>
                          <select v-model="form.from_email" required class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300">
                              <option value="" disabled>Select an identity</option>
                              <option v-for="id in identities" :key="id" :value="id">{{ id }}</option>
                          </select>
                           <p v-if="identities.length === 0" class="text-xs text-amber-600 mt-1.5 bg-amber-50/80 p-2 rounded-lg">No identities found in this region. Please verify an email in AWS Console.</p>
                      </div>

                      <div v-if="form.from_email && !form.from_email.includes('@')" class="space-y-3 p-4 bg-gray-50/80 rounded-xl">
                           <!-- Sender Name -->
                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1.5 flex items-center gap-1.5">
                                  Sender Name (Optional)
                                  <Tooltip text="The display name that recipients will see in their email client. If left empty, only the email address will be shown.">
                                      <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                  </Tooltip>
                              </label>
                              <input v-model="form.sender_name" type="text" class="w-full px-3 py-2.5 bg-white border border-gray-200/70 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all hover:border-blue-300" placeholder="e.g. Sarkar Ripon">
                              <p class="text-xs text-gray-500 mt-1.5">This name will be displayed to recipients.</p>
                          </div>

                          <!-- Email Prefix -->
                          <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1.5 flex items-center gap-1.5">
                                  Email Username
                                  <Tooltip text="When using a verified domain, specify the local part (username) of the email address. For example, 'contact' will create 'contact@yourdomain.com'.">
                                      <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                  </Tooltip>
                              </label>
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
                          <button type="submit" :disabled="loading || !form.from_email" class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white py-2.5 rounded-2xl font-bold transition-all flex justify-center items-center disabled:opacity-70 disabled:cursor-not-allowed hover:-translate-y-0.5 active:translate-y-0 relative overflow-hidden group">
                              <template v-if="loading">
                                  <span class="flex items-center gap-1">
                                      <span class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                      <span class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                      <span class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                  </span>
                                  <span class="ml-3">Saving...</span>
                              </template>
                              <template v-else>
                                  <svg class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                  Complete Setup
                              </template>
                          </button>
                      </div>
                   </form>
              </div>

              <!-- Step 3: Dashboard / Connected State -->
              <div v-else-if="step === 'dashboard' || true" class="space-y-4">
                  <!-- Success Banner -->
                   <div class="relative rounded-2xl p-5 overflow-hidden border" :class="store.isDarkTheme ? 'bg-gray-800/80 border-gray-700' : 'bg-white/80 border-gray-200/50'">
                       <!-- Accent color stripe on left -->
                       <div class="absolute left-0 top-0 bottom-0 w-1.5 fm-bg-primary"></div>

                       <div class="flex items-center justify-between pl-4">
                           <div class="flex items-center space-x-4">
                               <div class="w-12 h-12 fm-bg-primary-light rounded-2xl flex items-center justify-center">
                                   <svg class="w-6 h-6 fm-text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                               </div>
                               <div class="text-left">
                                   <div class="flex items-center gap-2 mb-1">
                                       <h2 class="text-lg font-bold" :class="store.isDarkTheme ? 'text-gray-100' : 'text-gray-800'">Connected & Ready</h2>
                                       <span class="relative flex h-2.5 w-2.5">
                                           <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                           <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                                       </span>
                                   </div>
                                   <p class="text-sm" :class="store.isDarkTheme ? 'text-gray-400' : 'text-gray-500'">
                                       Sending from
                                       <span class="font-medium fm-text-primary">{{ form.from_email }}</span>
                                   </p>
                               </div>
                           </div>
                           <div class="hidden sm:flex items-center gap-2">
                               <div class="text-right">
                                   <div class="text-xs font-medium uppercase tracking-wider" :class="store.isDarkTheme ? 'text-gray-500' : 'text-gray-400'">Region</div>
                                   <div class="text-sm font-semibold" :class="store.isDarkTheme ? 'text-gray-300' : 'text-gray-700'">{{ form.region }}</div>
                               </div>
                               <button @click="disconnect" class="ml-3 p-2 rounded-xl transition-all duration-200 hover:bg-red-50 group" title="Disconnect">
                                   <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                               </button>
                           </div>
                       </div>

                       <!-- Mobile disconnect button -->
                       <div class="sm:hidden mt-3 pl-4 flex items-center justify-between">
                           <div class="text-xs">
                               <span class="font-medium uppercase tracking-wider" :class="store.isDarkTheme ? 'text-gray-500' : 'text-gray-400'">Region:</span>
                               <span class="ml-1 font-semibold" :class="store.isDarkTheme ? 'text-gray-300' : 'text-gray-700'">{{ form.region }}</span>
                           </div>
                           <button @click="disconnect" class="text-red-500 hover:text-red-700 text-xs font-medium">
                               Disconnect
                           </button>
                       </div>
                   </div>

                   <!-- Incoming Config -->
                   <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden">
                        <div class="p-3 border-b border-gray-100/50 flex justify-between items-center">
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

                             <Tooltip text="This will automatically create an S3 bucket for storing emails, an SNS topic for notifications, and configure SES rules to forward incoming emails to your S3 bucket. All resources will be created in your selected AWS region.">
                                 <button @click="setupInbound" :disabled="loading" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-2.5 rounded-2xl font-bold transition-all inline-flex items-center disabled:opacity-70 disabled:cursor-not-allowed hover:-translate-y-0.5 active:translate-y-0 text-sm relative overflow-hidden">
                                     <template v-if="loading">
                                         <span class="flex items-center gap-1">
                                             <span class="w-1.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                             <span class="w-1.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                             <span class="w-1.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                         </span>
                                         <span class="ml-2">Configuring...</span>
                                     </template>
                                     <template v-else>
                                         <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                         Setup Now
                                     </template>
                                 </button>
                             </Tooltip>
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

                   <!-- Troubleshooting Section -->
                   <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 overflow-hidden mt-4">
                        <div class="p-3 border-b border-gray-100/50 flex justify-between items-center bg-gray-50/50">
                             <div class="flex items-center">
                                 <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center mr-2">
                                     <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                 </div>
                                 <h3 class="text-lg font-bold text-gray-800">Troubleshooting</h3>
                             </div>
                        </div>
                        <div class="p-4 space-y-4">
                            <p class="text-sm text-gray-500">Use these tools to debug webhook connectivity and processing issues.</p>

                            <div class="flex space-x-3">
                                <button @click="simulateWebhook" :disabled="simulating" class="flex-1 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 py-2.5 rounded-xl font-medium transition-all text-sm flex justify-center items-center shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                                    <template v-if="simulating">
                                        <span class="flex items-center gap-1 mr-2">
                                            <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                            <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                            <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                        </span>
                                        Simulating...
                                    </template>
                                    <template v-else>
                                        <span class="mr-2">⚡</span> Simulate Webhook
                                    </template>
                                </button>
                                <button @click="toggleLog" class="flex-1 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 py-2.5 rounded-xl font-medium transition-all text-sm flex justify-center items-center shadow-sm">
                                    <span class="mr-2">📄</span> View Debug Log
                                </button>
                            </div>

                            <div v-if="showLog" class="mt-4 border border-gray-200 rounded-xl overflow-hidden">
                                <div class="bg-gray-900 text-gray-300 px-4 py-2 text-xs font-mono flex justify-between items-center">
                                    <span>fluent-mailbox-debug.log</span>
                                    <div class="flex space-x-3">
                                        <button @click="refreshLog" :disabled="refreshingLog" class="hover:text-white transition-colors disabled:opacity-50 flex items-center gap-1">
                                            <template v-if="refreshingLog">
                                                <span class="flex items-center gap-0.5">
                                                    <span class="w-1 h-1 bg-gray-300 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                                    <span class="w-1 h-1 bg-gray-300 rounded-full animate-bounce" style="animation-delay: 100ms"></span>
                                                    <span class="w-1 h-1 bg-gray-300 rounded-full animate-bounce" style="animation-delay: 200ms"></span>
                                                </span>
                                            </template>
                                            <template v-else>Refresh</template>
                                        </button>
                                        <button @click="cleanLog" class="text-red-400 hover:text-red-300 transition-colors">Clear</button>
                                    </div>
                                </div>
                                <div class="bg-gray-800 p-4 overflow-x-auto max-h-64 scrollbar-thin scrollbar-thumb-gray-600">
                                    <pre class="text-xs font-mono text-gray-300 whitespace-pre-wrap font-ligatures-none">{{ logContent || 'Log is empty.' }}</pre>
                                </div>
                            </div>
                        </div>
                   </div>
              </div>
              </div><!-- End AWS Tab -->

              <!-- Appearance Tab -->
              <div v-show="activeTab === 'appearance'" class="bg-white/70 backdrop-blur-sm p-6 rounded-2xl border border-gray-200/50 transition-all duration-300">
                  <ThemeSettings />
              </div>

              </template><!-- End main content template -->

          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';
import Tooltip from '../components/Tooltip.vue';
import ThemeSettings from '../components/ThemeSettings.vue';
import { triggerConfetti } from '../utils/confetti';

const store = useAppStore();
const activeTab = ref('aws'); // 'aws' or 'appearance'
const step = ref('credentials'); // credentials, identity, dashboard
const loading = ref(false);
const initialLoading = ref(true);
const refreshingLog = ref(false);
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
    initialLoading.value = true;
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
        initialLoading.value = false;
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

        // Trigger confetti celebration!
        triggerConfetti();
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

// Troubleshooting Logic
const simulating = ref(false);
const showLog = ref(false);
const logContent = ref('');

const simulateWebhook = async () => {
    simulating.value = true;
    try {
        const { data } = await api.simulateWebhook();
        alert('Simulation Result: ' + data.message);
        // If simulation succeeded, maybe refresh inbox? But we are in settings.
        // Let's at least refresh the log if it's open
        if (showLog.value) {
            await refreshLog();
        }
    } catch (e) {
         alert('Simulation Failed: ' + (e.response?.data?.message || e.message));
    } finally {
        simulating.value = false;
    }
};

const toggleLog = async () => {
    showLog.value = !showLog.value;
    if (showLog.value) {
        await refreshLog();
    }
};

const refreshLog = async () => {
    refreshingLog.value = true;
    try {
        const { data } = await api.getDebugLog();
        logContent.value = data.log;
    } catch (e) {
        logContent.value = 'Failed to load log.';
    } finally {
        refreshingLog.value = false;
    }
};

const cleanLog = async () => {
    if(!confirm('Clear debug log?')) return;
    try {
        await api.cleanDebugLog();
        logContent.value = '';
        alert('Log cleared.');
    } catch (e) {
        alert('Failed to clear log.');
    }
};
</script>
