<template>
  <div class="h-full flex flex-col">
      <header class="py-2 border-b border-gray-200 flex flex-col gap-2 bg-white/50 backdrop-blur-sm sticky top-0 z-10 transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
                   <div v-if="isSelectionMode" class="flex items-center gap-2">
                       <input
                           type="checkbox"
                           :checked="selectedEmails.length === filteredEmails.length && filteredEmails.length > 0"
                           :indeterminate="selectedEmails.length > 0 && selectedEmails.length < filteredEmails.length"
                           @change="toggleSelectAll"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                       >
                       <span class="text-xs text-gray-700 font-medium">
                           {{ selectedEmails.length }} selected
                       </span>
                   </div>
                   <div v-else class="flex items-center space-x-3">
                       <h1 class="text-lg font-semibold text-gray-800">Inbox</h1>
                       <div class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full" v-if="emails.length">
                           unread {{ unreadCount }} of {{ emails.length }}
                       </div>
                   </div>
              </div>

              <div class="flex items-center space-x-1.5">
                  <div v-if="isSelectionMode" class="flex items-center gap-1.5">
                      <Tooltip text="Mark all selected emails as read">
                          <button @click="bulkMarkAsRead" :disabled="selectedEmails.length === 0" class="px-2.5 py-1 text-xs text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                              Mark as read
                          </button>
                      </Tooltip>
                      <Tooltip text="Add tag to all selected emails">
                          <button @click="showBulkTagMenu = !showBulkTagMenu" :disabled="selectedEmails.length === 0" class="px-2.5 py-1 text-xs text-gray-700 hover:text-green-600 hover:bg-green-50 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed relative">
                              Add tag
                          </button>
                      </Tooltip>
                      <Tooltip text="Move all selected emails to trash">
                          <button @click="bulkDelete" :disabled="selectedEmails.length === 0" class="px-2.5 py-1 text-xs text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                              Delete
                          </button>
                      </Tooltip>
                      <button @click="exitSelectionMode" class="px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded-lg transition-all">
                          Cancel
                      </button>
                  </div>
                  <template v-else>
                      <Tooltip text="Select multiple emails to perform bulk actions">
                          <button @click="enterSelectionMode" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                          </button>
                      </Tooltip>
                  </template>
                  <Tooltip text="Sync emails from S3 bucket. This fetches any new emails that have been received.">
                      <button @click="handleRefresh" :disabled="isRefreshing" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                          <svg :class="{'animate-spin': isRefreshing}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                      </button>
                  </Tooltip>
              </div>
          </div>

          <!-- Search and Filters -->
          <div class="flex items-center gap-2">
              <!-- Search Bar -->
              <div class="relative flex-1">
                  <input
                      v-model="searchQuery"
                      type="text"
                      placeholder="Search emails..."
                      class="w-[300px] float-right pl-9 pr-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all"
                  >
              </div>

              <!-- Filter Toggle Button -->
              <button
                  @click="showFilters = !showFilters"
                  class="px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-1.5"
                  :class="hasActiveFilters ? 'border-blue-500 bg-blue-50' : ''"
              >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                  Filters
                  <span v-if="activeFilterCount > 0" class="bg-blue-600 text-white text-xs px-1 py-0.5 rounded-full">{{ activeFilterCount }}</span>
              </button>

              <!-- Sort Dropdown -->
              <div class="relative">
                  <button
                      @click="showSortMenu = !showSortMenu"
                      class="px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-1.5"
                  >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                      Sort
                  </button>
                  <div
                      v-if="showSortMenu"
                      v-click-outside="() => showSortMenu = false"
                      class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-20"
                  >
                      <button
                          v-for="option in sortOptions"
                          :key="option.value"
                          @click="setSort(option.value)"
                          class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center justify-between"
                          :class="sortBy === option.value ? 'bg-blue-50 text-blue-700' : 'text-gray-700'"
                      >
                          <span>{{ option.label }}</span>
                          <svg v-if="sortBy === option.value" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                      </button>
                  </div>
              </div>
          </div>
          
          <!-- Filter Panel -->
          <div v-if="showFilters" class="bg-white border border-gray-200 rounded-lg p-3 space-y-3">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <!-- Read Status Filter -->
                  <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1.5">Read Status</label>
                      <select v-model="filters.readStatus" class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none">
                          <option value="all">All</option>
                          <option value="unread">Unread</option>
                          <option value="read">Read</option>
                      </select>
                  </div>

                  <!-- Date Range Filter -->
                  <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1.5">Date Range</label>
                      <select v-model="filters.dateRange" class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none">
                          <option value="all">All Time</option>
                          <option value="today">Today</option>
                          <option value="week">This Week</option>
                          <option value="month">This Month</option>
                          <option value="year">This Year</option>
                      </select>
                  </div>

                  <!-- Attachments Filter -->
                  <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1.5">Attachments</label>
                      <select v-model="filters.hasAttachments" class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none">
                          <option value="all">All</option>
                          <option value="yes">With Attachments</option>
                          <option value="no">Without Attachments</option>
                      </select>
                  </div>
              </div>

              <!-- Sender Filter -->
              <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1.5">From</label>
                  <input
                      v-model="filters.sender"
                      type="text"
                      placeholder="Filter by sender email..."
                      class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none"
                  >
              </div>

              <!-- Tags Filter -->
              <div>
                  <div class="flex items-center justify-between mb-1.5">
                      <label class="block text-xs font-medium text-gray-700">Tags</label>
                      <button
                          @click="showTagManager = true"
                          class="text-xs text-blue-600 hover:text-blue-700 hover:underline"
                      >
                          Manage tags
                      </button>
                  </div>
                  <div v-if="store.allTags.length === 0" class="text-xs text-gray-500">
                      No tags available. <button @click="showTagManager = true" class="text-blue-600 hover:underline">Create tags</button>
                  </div>
                  <div v-else class="flex flex-wrap gap-2">
                      <button
                          v-for="tag in store.allTags"
                          :key="tag.id"
                          @click="toggleTagFilter(tag.id)"
                          class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full transition-all"
                          :class="filters.tags.includes(tag.id) ? 'text-white' : 'text-gray-700 bg-gray-100 hover:bg-gray-200'"
                          :style="filters.tags.includes(tag.id) ? { backgroundColor: tag.color } : {}"
                      >
                          {{ tag.name }}
                          <svg v-if="filters.tags.includes(tag.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                      </button>
                  </div>
              </div>

              <!-- Clear Filters Button -->
              <div class="flex justify-end">
                  <button
                      @click="clearFilters"
                      class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-lg transition-colors"
                      :disabled="!hasActiveFilters"
                  >
                      Clear Filters
          </button>
              </div>
          </div>
      </header>
      
      <div class="flex-1 overflow-auto p-0 scrollbar-hide">
          <div v-if="loading" class="flex justify-center items-center h-64">
              <div class="relative">
                  <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
              </div>
          </div>

          <div v-else-if="filteredEmails.length > 0" class="divide-y divide-gray-100">
              <div
                  v-for="email in filteredEmails"
                  :key="email.id"
                  @click="isSelectionMode ? toggleSelect(email) : openEmail(email.id)"
                  class="px-5 py-2.5 bg-white hover:shadow-sm cursor-pointer group transition-all relative border-l-4"
                  :class="[
                      !email.is_read ? 'bg-blue-50/20 border-l-blue-500' : 'border-l-transparent',
                      isSelected(email.id) ? 'bg-blue-50 border-l-blue-600' : ''
                  ]"
              >
                  <div class="flex items-center gap-3">
                      <!-- Selection Checkbox or Unread Indicator -->
                      <div class="flex-shrink-0">
                          <input
                              v-if="isSelectionMode"
                              type="checkbox"
                              :checked="isSelected(email.id)"
                              @click.stop="toggleSelect(email)"
                              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                          >
                          <div v-else-if="!email.is_read" class="w-2.5 h-2.5 bg-blue-600 rounded-full ring-2 ring-blue-100"></div>
                          <div v-else class="w-2.5 h-2.5"></div>
                      </div>

                      <!-- Avatar -->
                      <div class="flex-shrink-0">
                          <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                              {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                          </div>
                      </div>

                      <!-- Email Content -->
                      <div class="flex-1 min-w-0">
                          <div class="flex items-center gap-2 mb-0.5">
                              <!-- Starred Icon -->
                              <svg v-if="email.is_starred" class="w-3.5 h-3.5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                              </svg>

                              <!-- Sender -->
                              <span class="text-sm text-gray-900 truncate" :class="!email.is_read ? 'font-semibold' : 'font-medium'">
                                  {{ email.sender }}
                              </span>

                              <!-- Tags -->
                              <div v-if="getEmailTagsDisplay(email.id).length > 0" class="flex items-center gap-1">
                                  <span
                                      v-for="tag in getEmailTagsDisplay(email.id).slice(0, 2)"
                                      :key="tag.id"
                                      class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded text-white shadow-sm"
                                      :style="{ backgroundColor: tag.color }"
                                  >
                                      {{ tag.name }}
                                  </span>
                                  <span
                                      v-if="getEmailTagsDisplay(email.id).length > 2"
                                      class="text-xs text-gray-500 font-medium"
                                  >
                                      +{{ getEmailTagsDisplay(email.id).length - 2 }}
                                  </span>
                              </div>

                              <!-- Spacer -->
                              <div class="flex-1"></div>

                              <!-- Date -->
                              <span class="text-xs text-gray-500 flex-shrink-0 font-medium">
                                  {{ formatRelativeDate(email.created_at) }}
                              </span>
                          </div>

                          <!-- Subject and Snippet -->
                          <div class="flex items-baseline gap-2">
                              <h4 class="text-sm text-gray-900 truncate" :class="!email.is_read ? 'font-semibold' : 'font-normal'">
                                  {{ email.subject || '(No Subject)' }}
                              </h4>
                              <span class="text-sm text-gray-500 truncate flex-1">
                                  — {{ getEmailSnippet(email.body) }}
                              </span>
                          </div>
                      </div>

                      <!-- Quick Actions (shown on hover) -->
                      <div v-if="!isSelectionMode" class="flex-shrink-0 flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button
                              @click.stop="toggleStar(email)"
                              class="p-1.5 text-gray-400 hover:text-yellow-500 hover:bg-yellow-50 rounded transition-colors"
                              :title="email.is_starred ? 'Unstar' : 'Star'"
                          >
                              <svg v-if="email.is_starred" class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                              </svg>
                              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                              </svg>
                          </button>
                          <button
                              @click.stop="toggleRead(email)"
                              class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                              :title="email.is_read ? 'Mark as unread' : 'Mark as read'"
                          >
                              <svg v-if="email.is_read" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                              </svg>
                              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                              </svg>
                          </button>
                          <button
                              @click.stop="deleteEmail(email)"
                              class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                              title="Delete"
                          >
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                              </svg>
                          </button>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Pagination -->
          <div v-if="filteredEmails.length > 0 && totalPages > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-white">
              <div class="text-sm text-gray-600">
                  Showing {{ (currentPage - 1) * 20 + 1 }} to {{ Math.min(currentPage * 20, totalEmails) }} of {{ totalEmails }} emails
              </div>
              <div class="flex items-center gap-2">
                  <button
                      @click="fetchEmails(currentPage - 1)"
                      :disabled="currentPage === 1"
                      class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                      Previous
                  </button>
                  <span class="text-sm text-gray-600 px-2">
                      Page {{ currentPage }} of {{ totalPages }}
                  </span>
                  <button
                      @click="fetchEmails(currentPage + 1)"
                      :disabled="currentPage >= totalPages"
                      class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                      Next
                  </button>
              </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredEmails.length === 0" class="flex flex-col items-center justify-center h-full py-12">
              <div class="w-32 h-32 mb-6 rounded-full bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                  <svg class="w-16 h-16 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
              </div>
              <h3 class="text-xl font-semibold text-gray-700 mb-2">Your inbox is empty</h3>
              <p class="text-sm text-gray-500 mb-6 max-w-sm text-center">No emails here yet. New messages will appear in your inbox.</p>
              <button
                  v-if="store.isConfigured"
                  @click="store.openCompose('new')"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
              >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                  Compose Email
              </button>
          </div>
      </div>

      <!-- Bulk Tag Menu -->
      <div
          v-if="showBulkTagMenu && isSelectionMode"
          class="fixed inset-0 z-40"
          @click="showBulkTagMenu = false"
      >
          <div
              class="absolute top-16 right-8 bg-white border border-gray-200 rounded-lg shadow-lg p-3 min-w-[200px]"
              @click.stop
          >
              <div class="text-xs font-medium text-gray-700 mb-2">Add tag to {{ selectedEmails.length }} emails</div>
              <div v-if="store.allTags.length === 0" class="text-xs text-gray-500 py-2">
                  No tags available.
              </div>
              <div v-else class="space-y-1">
                  <button
                      v-for="tag in store.allTags"
                      :key="tag.id"
                      @click="bulkAddTag(tag.id)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 text-xs hover:bg-gray-50 rounded text-left"
                  >
                      <span
                          class="inline-block w-3 h-3 rounded-full"
                          :style="{ backgroundColor: tag.color }"
                      ></span>
                      <span>{{ tag.name }}</span>
                  </button>
              </div>
          </div>
      </div>

      <!-- Tag Manager Modal -->
      <TagManager v-model="showTagManager" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';
import { useEmailCounts } from '../composables/useEmailCounts';
import Tooltip from '../components/Tooltip.vue';
import TagManager from '../components/TagManager.vue';

const store = useAppStore();
const router = useRouter();
const emailCounts = useEmailCounts();

const emails = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const isRefreshing = ref(false);
const isSelectionMode = ref(false);
const selectedEmails = ref([]);
const currentPage = ref(1);
const totalPages = ref(1);
const totalEmails = ref(0);
const showFilters = ref(false);
const showSortMenu = ref(false);
const sortBy = ref('date-desc');
const sortOrder = ref('desc');

const filters = ref({
    readStatus: 'all',
    dateRange: 'all',
    hasAttachments: 'all',
    sender: '',
    tags: []
});

const showTagManager = ref(false);
const emailTags = ref({});
const showBulkTagMenu = ref(false);
const loadedEmailTagIds = ref(new Set()); // Track which email IDs we've loaded tags for

const sortOptions = [
    { label: 'Date (Newest)', value: 'date-desc' },
    { label: 'Date (Oldest)', value: 'date-asc' },
    { label: 'Sender (A-Z)', value: 'sender-asc' },
    { label: 'Sender (Z-A)', value: 'sender-desc' },
    { label: 'Subject (A-Z)', value: 'subject-asc' },
    { label: 'Subject (Z-A)', value: 'subject-desc' }
];
const pollingInterval = ref(null);

const fetchEmails = async (page = 1, silent = false) => {
    if (!silent) {
    loading.value = true;
    }
    try {
        // Get inbox emails - request with 'inbox' status
        let response = await api.getEmails(page, 'inbox');
        let allEmails = response.data.data || [];

        // If no emails found with 'inbox' status, try 'all' and filter manually
        if (allEmails.length === 0 && page === 1) {
            response = await api.getEmails(page, 'all');
            const allStatusEmails = response.data.data || [];

            // Filter for inbox: status = 'inbox', null, empty, or not sent/trash/draft
            allEmails = allStatusEmails.filter(email => {
                const status = email.status;
                return status === 'inbox' || !status || status === '' ||
                       (status !== 'sent' && status !== 'trash' && status !== 'draft');
            });
        }

        // Use all emails returned (backend already filtered for inbox)
        emails.value = allEmails;

        // Update pagination info
        currentPage.value = response.data.current_page || 1;
        totalPages.value = response.data.last_page || 1;
        totalEmails.value = response.data.total || 0;

        // Update counts from current emails (no need to fetch all emails again)
        // Only fetch counts if we're on page 1 and it's not a silent refresh
        // Use debounced version to avoid multiple rapid calls
        if (page === 1 && !silent) {
            emailCounts.fetchCounts(); // This is now debounced internally
        }
    } catch (e) {
        console.error('Error fetching emails:', e);
    } finally {
        if (!silent) {
        loading.value = false;
        }
    }
};

const handleRefresh = async () => {
    isRefreshing.value = true;
    try {
        await api.fetchEmails(); // Trigger S3 fetch
        await fetchEmails(); // Reload list
    } catch (e) {
        console.error('Failed to sync', e);
    } finally {
        isRefreshing.value = false;
    }
};

const unreadCount = computed(() => {
    return emails.value.filter(email => !email.is_read).length;
});

const hasActiveFilters = computed(() => {
    return filters.value.readStatus !== 'all' ||
           filters.value.dateRange !== 'all' ||
           filters.value.hasAttachments !== 'all' ||
           filters.value.sender.trim() !== '';
});

const activeFilterCount = computed(() => {
    let count = 0;
    if (filters.value.readStatus !== 'all') count++;
    if (filters.value.dateRange !== 'all') count++;
    if (filters.value.hasAttachments !== 'all') count++;
    if (filters.value.sender.trim() !== '') count++;
    if (filters.value.tags.length > 0) count++;
    return count;
});

const filteredEmails = computed(() => {
    let result = [...emails.value];

    // Apply search query
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(email => {
            return (
                email.subject?.toLowerCase().includes(query) ||
                email.sender?.toLowerCase().includes(query) ||
                email.body?.toLowerCase().includes(query)
            );
        });
    }

    // Apply read status filter
    if (filters.value.readStatus === 'read') {
        result = result.filter(email => email.is_read);
    } else if (filters.value.readStatus === 'unread') {
        result = result.filter(email => !email.is_read);
    }

    // Apply date range filter
    if (filters.value.dateRange !== 'all') {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        result = result.filter(email => {
            const emailDate = new Date(email.created_at);
            switch (filters.value.dateRange) {
                case 'today':
                    return emailDate >= today;
                case 'week':
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    return emailDate >= weekAgo;
                case 'month':
                    const monthAgo = new Date(today);
                    monthAgo.setMonth(monthAgo.getMonth() - 1);
                    return emailDate >= monthAgo;
                case 'year':
                    const yearAgo = new Date(today);
                    yearAgo.setFullYear(yearAgo.getFullYear() - 1);
                    return emailDate >= yearAgo;
                default:
                    return true;
            }
        });
    }

    // Apply attachments filter
    if (filters.value.hasAttachments === 'yes') {
        result = result.filter(email => {
            try {
                const atts = email.attachments ? JSON.parse(email.attachments) : [];
                return Array.isArray(atts) && atts.length > 0;
            } catch (e) {
                return false;
            }
        });
    } else if (filters.value.hasAttachments === 'no') {
        result = result.filter(email => {
            try {
                const atts = email.attachments ? JSON.parse(email.attachments) : [];
                return !Array.isArray(atts) || atts.length === 0;
            } catch (e) {
                return true;
            }
        });
    }

    // Apply sender filter
    if (filters.value.sender.trim()) {
        const senderQuery = filters.value.sender.toLowerCase().trim();
        result = result.filter(email =>
            email.sender?.toLowerCase().includes(senderQuery)
        );
    }

    // Apply tag filter
    if (filters.value.tags.length > 0) {
        result = result.filter(email => {
            const tags = emailTags.value[email.id] || [];
            const tagIds = tags.map(t => t.id);
            // Email must have at least one of the selected tags
            return filters.value.tags.some(filterTagId => tagIds.includes(filterTagId));
        });
    }

    // Apply sorting
    result.sort((a, b) => {
        switch (sortBy.value) {
            case 'date-desc':
                return new Date(b.created_at) - new Date(a.created_at);
            case 'date-asc':
                return new Date(a.created_at) - new Date(b.created_at);
            case 'sender-asc':
                return (a.sender || '').localeCompare(b.sender || '');
            case 'sender-desc':
                return (b.sender || '').localeCompare(a.sender || '');
            case 'subject-asc':
                return (a.subject || '').localeCompare(b.subject || '');
            case 'subject-desc':
                return (b.subject || '').localeCompare(a.subject || '');
            default:
                return 0;
        }
    });

    return result;
});

const setSort = (value) => {
    sortBy.value = value;
    showSortMenu.value = false;
};

const clearFilters = () => {
    filters.value = {
        readStatus: 'all',
        dateRange: 'all',
        hasAttachments: 'all',
        sender: '',
        tags: []
    };
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

const openEmail = (id) => {
    router.push(`/emails/${id}`);
};

const toggleRead = async (email) => {
    try {
        const newStatus = email.is_read ? 0 : 1;
        await api.updateEmail(email.id, { is_read: newStatus });
        email.is_read = newStatus;
        // fetchCounts is already debounced internally
        emailCounts.fetchCounts();
    } catch (e) {
        console.error('Failed to update read status', e);
    }
};

const deleteEmail = async (email) => {
    if (!confirm('Are you sure you want to delete this email?')) return;
    try {
        await api.deleteEmail(email.id);
        await fetchEmails(); // fetchEmails will handle counts refresh if on page 1
    } catch (e) {
        alert('Failed to delete email');
    }
};

// Bulk selection functions
const enterSelectionMode = () => {
    isSelectionMode.value = true;
    selectedEmails.value = [];
};

const exitSelectionMode = () => {
    isSelectionMode.value = false;
    selectedEmails.value = [];
};

const isSelected = (emailId) => {
    return selectedEmails.value.some(e => e.id === emailId);
};

const toggleSelect = (email) => {
    const index = selectedEmails.value.findIndex(e => e.id === email.id);
    if (index > -1) {
        selectedEmails.value.splice(index, 1);
    } else {
        selectedEmails.value.push(email);
    }
};

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedEmails.value = [...filteredEmails.value];
    } else {
        selectedEmails.value = [];
    }
};

const bulkMarkAsRead = async () => {
    try {
        const promises = selectedEmails.value.map(email =>
            api.updateEmail(email.id, { is_read: 1 })
        );
        await Promise.all(promises);
        await fetchEmails(); // fetchEmails will handle counts refresh if on page 1
        exitSelectionMode();
    } catch (e) {
        alert('Failed to mark emails as read');
    }
};

const bulkDelete = async () => {
    if (!confirm(`Are you sure you want to delete ${selectedEmails.value.length} email(s)?`)) return;
    try {
        const promises = selectedEmails.value.map(email =>
            api.deleteEmail(email.id)
        );
        await Promise.all(promises);
        await fetchEmails(); // fetchEmails will handle counts refresh if on page 1
        exitSelectionMode();
    } catch (e) {
        alert('Failed to delete emails');
    }
};

// Star functionality (using localStorage for now, can be moved to backend later)
const toggleStar = (email) => {
    if (!email.is_starred) {
        email.is_starred = 1;
        localStorage.setItem(`starred_${email.id}`, '1');
    } else {
        email.is_starred = 0;
        localStorage.removeItem(`starred_${email.id}`);
    }
};

// Initialize star status from localStorage
const initializeStars = () => {
    emails.value.forEach(email => {
        email.is_starred = localStorage.getItem(`starred_${email.id}`) === '1' ? 1 : 0;
    });
};

// Tag functionality
const loadEmailTags = async (emailIds) => {
    // Filter out email IDs we've already loaded tags for
    const newEmailIds = emailIds.filter(id => !loadedEmailTagIds.value.has(id));
    
    if (newEmailIds.length === 0) {
        return; // All tags already loaded
    }
    
    try {
        const promises = newEmailIds.map(id => api.getEmailTags(id));
        const responses = await Promise.all(promises);
        responses.forEach((response, index) => {
            const emailId = newEmailIds[index];
            emailTags.value[emailId] = response.data || [];
            loadedEmailTagIds.value.add(emailId); // Mark as loaded
        });
    } catch (error) {
        console.error('Failed to load email tags:', error);
    }
};

const getEmailTagsDisplay = (emailId) => {
    return emailTags.value[emailId] || [];
};

const toggleTagFilter = (tagId) => {
    const index = filters.value.tags.indexOf(tagId);
    if (index > -1) {
        filters.value.tags.splice(index, 1);
    } else {
        filters.value.tags.push(tagId);
    }
};

const bulkAddTag = async (tagId) => {
    try {
        const promises = selectedEmails.value.map(email =>
            api.addEmailTag(email.id, tagId)
        );
        await Promise.all(promises);

        // Reload tags for affected emails (clear cache first)
        const emailIds = selectedEmails.value.map(e => e.id);
        emailIds.forEach(id => loadedEmailTagIds.value.delete(id)); // Clear cache
        await loadEmailTags(emailIds);

        showBulkTagMenu.value = false;
    } catch (error) {
        alert('Failed to add tags to emails');
    }
};

onMounted(async () => {
    // Load global tags first if not loaded
    if (!store.tagsLoaded) {
        await store.loadTags();
    }
    
    await fetchEmails(1, false);
    initializeStars();

    // Load tags for current emails (only new ones)
    const emailIds = emails.value.map(e => e.id);
    if (emailIds.length > 0) {
        await loadEmailTags(emailIds);
    }

    // Auto-refresh every 15 seconds
    pollingInterval.value = setInterval(() => {
        // Only refresh if not already loading and valid page
        if (!loading.value && !isRefreshing.value) {
            fetchEmails(currentPage.value, true);
        }
    }, 15000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});

// Watch for route changes to exit selection mode
watch(() => router.currentRoute.value.path, () => {
    if (isSelectionMode.value) {
        exitSelectionMode();
    }
});

// Watch for emails changes to load tags - only when email IDs change, not deep watching
// Only load tags for new emails that we haven't loaded yet
watch(() => emails.value.map(e => e.id).join(','), async (newEmailIdsStr, oldEmailIdsStr) => {
    if (newEmailIdsStr !== oldEmailIdsStr && oldEmailIdsStr !== undefined) {
        // Only load if emails actually changed (not initial mount)
        const emailIds = emails.value.map(e => e.id);
        if (emailIds.length > 0) {
            await loadEmailTags(emailIds); // loadEmailTags now filters out already-loaded IDs
        }
    }
}, { immediate: false });
</script>
