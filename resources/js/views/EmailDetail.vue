<template>
  <div class="h-full flex flex-col">
      <header class="py-4 border-b border-gray-200 flex justify-between items-center bg-white/50 backdrop-blur-sm sticky top-0 z-10 transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-8' : 'px-8'">
          <div class="flex items-center space-x-3">
              <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600 hover:text-gray-900 transition-all">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
              </button>
              <h1 class="text-lg font-semibold text-gray-800 truncate max-w-xl">{{ email ? email.subject : 'Loading...' }}</h1>
          </div>

          <div class="flex items-center space-x-1" v-if="email">
               <button @click="handleReply" class="px-3 py-1.5 text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                  Reply
               </button>
               <button @click="handleForward" class="px-3 py-1.5 text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                  Forward
               </button>
               <button @click="toggleRead" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" :title="email.is_read ? 'Mark as unread' : 'Mark as read'">
                  <svg v-if="email.is_read" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
               </button>
                    <button @click="openWorkflowModal" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Workflow">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h13M8 12h13M8 17h13M3 7h.01M3 12h.01M3 17h.01" />
                        </svg>
                    </button>
                    <div class="relative" ref="tagButtonRef">
                        <button @click="toggleTagDropdown" class="p-2 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-all" title="Tags">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span v-if="emailTags.length > 0" class="absolute -top-1 -right-1 bg-purple-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">{{ emailTags.length }}</span>
                        </button>

                        <!-- Tags Dropdown -->
                        <Teleport to="body">
                            <div
                                v-if="showTagDropdown"
                                :style="tagDropdownStyle"
                                class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-lg w-80"
                            >
                                <div class="p-3 border-b border-gray-200 flex justify-between items-center">
                                    <h3 class="text-sm font-semibold text-gray-700">Email Tags</h3>
                                    <button
                                        @click="showTagManager = true; showTagDropdown = false"
                                        class="text-xs text-blue-600 hover:text-blue-700 hover:underline"
                                    >
                                        Manage tags
                                    </button>
                                </div>
                                <div class="p-3">
                                    <TagPicker
                                        :email-id="email.id"
                                        @manage-tags="showTagManager = true; showTagDropdown = false"
                                        @tags-updated="loadEmailTags"
                                    />
                                </div>
                            </div>
                        </Teleport>
                    </div>
               <button @click="deleteEmail" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
               </button>
          </div>
      </header>

      <div class="flex-1 overflow-auto p-6 bg-white/50" v-if="!loading && email">
          <!-- Metadata -->
          <div class="bg-white rounded-lg p-5 mb-4 border border-gray-200">
              <div class="flex justify-between items-start mb-4">
              <div class="flex items-center space-x-3">
                      <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-lg">
                      {{ email.sender ? email.sender[0].toUpperCase() : '?' }}
                  </div>
                  <div>
                          <div class="font-semibold text-gray-900">{{ email.sender }}</div>
                          <div class="text-sm text-gray-500 mt-0.5 space-y-0.5">
                              <div>To: <span class="text-gray-700">{{ getRecipients(email.recipients) }}</span></div>
                              <div v-if="getRecipients(email.cc)">
                                  Cc: <span class="text-gray-700">{{ getRecipients(email.cc) }}</span>
                              </div>
                              <div v-if="getRecipients(email.bcc)">
                                  Bcc: <span class="text-gray-700">{{ getRecipients(email.bcc) }}</span>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="text-xs text-gray-500">
                      {{ formatRelativeDate(email.created_at) }}
                  </div>
              </div>
          </div>

          <!-- Attachments -->
          <div v-if="emailAttachments.length > 0" class="bg-white rounded-lg p-4 mb-4 border border-gray-200">
              <h3 class="text-sm font-semibold text-gray-700 mb-3">Attachments</h3>
              <div class="flex flex-wrap gap-2">
                  <a
                      v-for="(attachment, index) in emailAttachments"
                      :key="index"
                      :href="getAttachmentUrl(attachment)"
                      target="_blank"
                      class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 text-sm text-gray-700 transition-colors"
                  >
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                      <span>{{ attachment.filename || `Attachment ${index + 1}` }}</span>
                      <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                  </a>
              </div>
          </div>

          <!-- Workflow Modal -->
          <div
              v-if="showWorkflowModal"
              class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/30 p-3"
              @click.self="closeWorkflowModal"
          >
              <div class="w-full max-w-md bg-white rounded-lg border border-gray-200 p-3">
                  <div class="flex items-center justify-between mb-2">
                      <div class="text-xs font-semibold text-gray-800">Workflow</div>
                      <div class="flex items-center gap-3">
                          <div v-if="workflowSaving" class="text-xs text-gray-500">Saving...</div>
                          <button class="text-xs text-gray-600 hover:text-gray-900" @click="closeWorkflowModal">Close</button>
                      </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                          <label class="block text-xs font-medium text-gray-600 mb-0.5">Status</label>
                          <select
                              v-model="workflowDraft.workflow_status"
                              class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-300"
                          >
                              <option value="open">Open</option>
                              <option value="pending">Pending</option>
                              <option value="resolved">Resolved</option>
                          </select>
                      </div>

                      <div>
                          <label class="block text-xs font-medium text-gray-600 mb-0.5">Assigned To</label>
                          <select
                              v-model.number="workflowDraft.assigned_to"
                              class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-300"
                          >
                              <option :value="0">Unassigned</option>
                              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.display_name }}</option>
                          </select>
                      </div>
                  </div>

                  <div class="mt-3 flex justify-end gap-2">
                      <button
                          class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                          @click="closeWorkflowModal"
                          :disabled="workflowSaving"
                      >
                          Cancel
                      </button>
                      <button
                          class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                          @click="submitWorkflow"
                          :disabled="workflowSaving"
                      >
                          Save
                      </button>
                  </div>
              </div>
          </div>

          <!-- Body -->
          <div class="bg-white rounded-lg p-6 border border-gray-200">
              <div v-if="email.body && email.body.trim()" class="prose prose-sm max-w-none text-gray-800" v-html="email.body"></div>
              <div v-else class="text-sm text-gray-500 italic">No message content</div>
          </div>

          <!-- Internal Notes (bottom) -->
          <div class="bg-white rounded-lg p-3 mt-3 border border-gray-200">
              <div class="flex items-center justify-between mb-2">
                  <h3 class="text-xs font-semibold text-gray-800">Internal Notes</h3>
                  <div class="flex items-center gap-3">
                      <button
                          v-if="notes.length"
                          @click="refreshNotes"
                          class="text-xs text-gray-600 hover:text-blue-600 hover:underline"
                      >
                          Refresh
                      </button>
                      <button
                          @click="openAddNote"
                          class="text-xs text-gray-600 hover:text-blue-600 hover:underline"
                      >
                          Add note
                      </button>
                  </div>
              </div>

              <div class="space-y-1.5" v-if="notes.length">
                  <div v-for="n in notes" :key="n.id" class="p-2 rounded-lg border border-gray-200 bg-gray-50">
                      <div class="flex items-center justify-between mb-1">
                          <div class="text-xs text-gray-600">
                              <span class="font-medium text-gray-800">{{ n.user_name }}</span>
                              <span class="mx-1">•</span>
                              <span>{{ formatRelativeDate(n.created_at) }}</span>
                          </div>
                          <button
                              @click="deleteNote(n)"
                              class="text-xs text-gray-500 hover:text-red-600"
                              title="Delete note"
                          >
                              Delete
                          </button>
                      </div>
                      <div class="text-xs text-gray-800 truncate" :title="n.note">{{ n.note }}</div>
                  </div>
              </div>
              <div v-else class="text-xs text-gray-500 italic">No notes yet</div>

              <!-- Add Note Modal -->
              <div
                  v-if="showAddNoteModal"
                  class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/30 p-3"
                  @click.self="closeAddNote"
              >
                  <div class="w-full max-w-md bg-white rounded-lg border border-gray-200 p-3">
                      <div class="flex items-center justify-between mb-2">
                          <div class="text-xs font-semibold text-gray-800">Add internal note</div>
                          <button class="text-xs text-gray-600 hover:text-gray-900" @click="closeAddNote">Close</button>
                      </div>

                      <textarea
                          v-model="draftNote"
                          rows="4"
                          placeholder="Write a note..."
                          class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-300"
                      ></textarea>

                      <div class="mt-2 flex justify-end gap-2">
                          <button
                              class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                              @click="closeAddNote"
                              :disabled="notesSaving"
                          >
                              Cancel
                          </button>
                          <button
                              class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                              @click="submitAddNote"
                              :disabled="notesSaving || !draftNote.trim()"
                          >
                              {{ notesSaving ? 'Saving...' : 'Add' }}
                          </button>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div v-else-if="loading" class="flex-1 flex justify-center items-center">
           <div class="relative">
               <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
           </div>
      </div>

      <!-- Tag Manager Modal -->
      <TagManager v-model="showTagManager" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';
import { useEmailCounts } from '../composables/useEmailCounts';
import TagPicker from '../components/TagPicker.vue';
import TagManager from '../components/TagManager.vue';

const store = useAppStore();
const emailCounts = useEmailCounts();

const route = useRoute();
const router = useRouter();
const email = ref(null);
const loading = ref(true);

const users = ref([]);
const showTagManager = ref(false);
const showTagDropdown = ref(false);
const tagButtonRef = ref(null);
const tagDropdownStyle = ref({});
const emailTags = ref([]);
const workflow = ref({
    workflow_status: 'open',
    assigned_to: 0
});
const workflowSaving = ref(false);
const showWorkflowModal = ref(false);
const workflowDraft = ref({
    workflow_status: 'open',
    assigned_to: 0
});

const notes = ref([]);
const showAddNoteModal = ref(false);
const draftNote = ref('');
const notesSaving = ref(false);

onMounted(async () => {
    try {
        const { data } = await api.getEmail(route.params.id);
        email.value = data;
        // Email is automatically marked as read when fetched (in backend)
        // Load attachments if any
        await loadAttachments();
        await Promise.all([
            loadUsers(),
            loadWorkflow(),
            refreshNotes(),
            loadEmailTags()
        ]);
        // Load global tags if not loaded
        if (!store.tagsLoaded) {
            await store.loadTags();
        }
        // Refresh counts
        emailCounts.fetchCounts();
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }

    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

const toggleTagDropdown = () => {
    showTagDropdown.value = !showTagDropdown.value;
    if (showTagDropdown.value) {
        nextTick(() => {
            updateTagDropdownPosition();
        });
    }
};

const updateTagDropdownPosition = () => {
    if (!tagButtonRef.value) return;

    const rect = tagButtonRef.value.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    // Position below by default, above if not enough space
    if (spaceBelow < 200 && spaceAbove > spaceBelow) {
        tagDropdownStyle.value = {
            left: `${rect.right - 320}px`,
            bottom: `${window.innerHeight - rect.top}px`,
            top: 'auto'
        };
    } else {
        tagDropdownStyle.value = {
            left: `${rect.right - 320}px`,
            top: `${rect.bottom + 8}px`
        };
    }
};

const loadEmailTags = async () => {
    if (!email.value?.id) return;
    try {
        const response = await api.getEmailTags(email.value.id);
        emailTags.value = response.data || [];
    } catch (error) {
        console.error('Failed to load email tags:', error);
    }
};

const handleClickOutside = (event) => {
    if (showTagDropdown.value && tagButtonRef.value && !tagButtonRef.value.contains(event.target)) {
        const dropdowns = document.querySelectorAll('.fixed.z-50');
        let clickedInside = false;
        dropdowns.forEach(dropdown => {
            if (dropdown.contains(event.target)) {
                clickedInside = true;
            }
        });
        if (!clickedInside) {
            showTagDropdown.value = false;
        }
    }
};

const loadUsers = async () => {
    try {
        const { data } = await api.getUsers();
        users.value = Array.isArray(data) ? data : [];
    } catch (e) {
        users.value = [];
    }
};

const loadWorkflow = async () => {
    try {
        const { data } = await api.getEmailWorkflow(route.params.id);
        workflow.value = {
            workflow_status: data?.workflow_status || 'open',
            assigned_to: data?.assigned_to ? Number(data.assigned_to) : 0
        };
    } catch (e) {
        workflow.value = { workflow_status: 'open', assigned_to: 0 };
    }
};

const saveWorkflow = async () => {
    let success = false;
    try {
        workflowSaving.value = true;
        const payload = {
            workflow_status: workflow.value.workflow_status,
            assigned_to: workflow.value.assigned_to || null
        };
        const { data } = await api.updateEmailWorkflow(route.params.id, payload);
        workflow.value = {
            workflow_status: data?.workflow_status || workflow.value.workflow_status,
            assigned_to: data?.assigned_to ? Number(data.assigned_to) : 0
        };
        success = true;
    } catch (e) {
        console.error('Failed to save workflow', e);
        alert('Failed to save workflow');
    } finally {
        workflowSaving.value = false;
    }

    return success;
};

const openWorkflowModal = () => {
    workflowDraft.value = {
        workflow_status: workflow.value?.workflow_status || 'open',
        assigned_to: workflow.value?.assigned_to ? Number(workflow.value.assigned_to) : 0
    };
    showWorkflowModal.value = true;
};

const closeWorkflowModal = () => {
    showWorkflowModal.value = false;
};

const submitWorkflow = async () => {
    workflow.value = {
        workflow_status: workflowDraft.value.workflow_status,
        assigned_to: workflowDraft.value.assigned_to || 0
    };
    const ok = await saveWorkflow();
    if (ok) {
        closeWorkflowModal();
    }
};

const refreshNotes = async () => {
    try {
        const { data } = await api.getEmailNotes(route.params.id);
        notes.value = Array.isArray(data) ? data : [];
    } catch (e) {
        notes.value = [];
    }
};

const openAddNote = () => {
    showAddNoteModal.value = true;
};

const closeAddNote = () => {
    showAddNoteModal.value = false;
    draftNote.value = '';
};

const submitAddNote = async () => {
    const noteText = draftNote.value.trim();
    if (!noteText) return;

    try {
        notesSaving.value = true;
        await api.addEmailNote(route.params.id, { note: noteText });
        closeAddNote();
        await refreshNotes();
    } catch (e) {
        console.error('Failed to add note', e);
        alert('Failed to add note');
    } finally {
        notesSaving.value = false;
    }
};

const deleteNote = async (note) => {
    if (!note?.id) return;
    if (!confirm('Delete this note?')) return;

    try {
        await api.deleteNote(note.id);
        await refreshNotes();
    } catch (e) {
        alert('Failed to delete note');
    }
};

const toggleRead = async () => {
    try {
        const newStatus = email.value.is_read ? 0 : 1;
        await api.updateEmail(email.value.id, { is_read: newStatus });
        email.value.is_read = newStatus;
        emailCounts.fetchCounts();
    } catch (e) {
        console.error('Failed to update read status', e);
    }
};

const deleteEmail = async () => {
    if (!confirm('Are you sure you want to delete this email?')) return;
    try {
        await api.deleteEmail(email.value.id);
        emailCounts.fetchCounts();
        router.back();
    } catch (e) {
        alert('Failed to delete email');
    }
};

const formatRelativeDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;

    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const handleReply = () => {
    store.openCompose('reply', email.value);
};

const handleForward = () => {
    store.openCompose('forward', email.value);
};

const getRecipients = (json) => {
    if (!json) return '';
    try {
        const parsed = JSON.parse(json);
        if (Array.isArray(parsed)) return parsed.join(', ');
        return json;
    } catch (e) {
        return json;
    }
};

const attachments = ref([]);

const emailAttachments = computed(() => {
    return attachments.value;
});

const loadAttachments = async () => {
    if (!email.value || !email.value.attachments) {
        attachments.value = [];
        return;
    }

    try {
        const attIds = JSON.parse(email.value.attachments);
        if (!Array.isArray(attIds) || attIds.length === 0) {
            attachments.value = [];
            return;
        }

        // Fetch attachment details for each ID
        const attachmentPromises = attIds.map(async (id) => {
            try {
                const response = await api.get(`/attachments/${id}`);
                const data = response.data;
                return {
                    id: data.id,
                    url: data.url || wp_get_attachment_url(id),
                    filename: data.filename || `Attachment ${id}`,
                    filesize: data.filesize || '',
                    mime: data.mime || ''
                };
            } catch (e) {
                // Fallback: use WordPress function to get URL
                console.warn(`Failed to load attachment ${id}:`, e);
                return {
                    id: id,
                    url: '#',
                    filename: `Attachment ${id}`
                };
            }
        });

        attachments.value = await Promise.all(attachmentPromises);
    } catch (e) {
        console.error('Failed to load attachments', e);
        attachments.value = [];
    }
};

const getAttachmentUrl = (attachment) => {
    if (attachment.url && attachment.url !== '#') {
        return attachment.url;
    }
    // Fallback: try to construct URL from WordPress
    const root = window.FluentMailbox?.root || '';
    return `${root}/attachments/${attachment.id}/download`;
};
</script>
