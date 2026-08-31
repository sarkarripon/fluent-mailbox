<template>
  <div class="h-full flex flex-col">
      <header class="py-4 border-b border-gray-100/50 flex justify-between items-center bg-white/50 backdrop-blur-sm transition-all duration-300" :class="store.isCompact ? 'pl-16 pr-6' : 'px-6'">
          <div class="flex items-center space-x-3">
              <h1 class="text-xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Mailboxes</h1>
              <div v-if="store.mailboxes.length" class="text-sm text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">{{ store.mailboxes.length }} connected</div>
          </div>
          <button @click="openAdd" class="flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
              <span>Add Mailbox</span>
          </button>
      </header>

      <div class="flex-1 overflow-auto p-4">
          <div class="max-w-3xl mx-auto space-y-3">

              <div v-if="loading" class="flex justify-center items-center h-64">
                  <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
              </div>

              <!-- Empty state / first-run setup -->
              <div v-else-if="store.mailboxes.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                  <div class="w-28 h-28 mb-6 rounded-full bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                      <svg class="w-14 h-14 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                  </div>
                  <h3 class="text-xl font-semibold text-gray-700 mb-2">Connect your first mailbox</h3>
                  <p class="text-sm text-gray-500 mb-6 max-w-md">Connect an email account you already own — cPanel/hosting email, Zoho, Gmail (app password), Amazon SES, Mailgun or Postmark — to start sending and receiving mail here.</p>
                  <button @click="openAdd" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-2.5 rounded-2xl font-bold transition-all hover:-translate-y-0.5 active:translate-y-0 text-sm">
                      Add Mailbox
                  </button>
              </div>

              <!-- Mailbox list -->
              <div
                  v-for="mailbox in store.mailboxes"
                  :key="mailbox.id"
                  class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200/50 p-4 transition-all hover:shadow-sm"
                  :class="{ 'opacity-60': !mailbox.is_active }"
              >
                  <div class="flex items-start justify-between gap-3">
                      <div class="flex items-start gap-3 min-w-0">
                          <span class="w-3 h-3 rounded-full mt-1.5 flex-shrink-0" :style="{ backgroundColor: mailbox.color || '#9ca3af' }"></span>
                          <div class="min-w-0">
                              <div class="flex items-center gap-2 flex-wrap">
                                  <h3 class="font-semibold text-gray-800 truncate">{{ mailbox.name }}</h3>
                                  <span v-if="mailbox.is_default" class="text-[10px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Default</span>
                                  <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full uppercase tracking-wide" :class="categoryClass(mailbox.category)">{{ mailbox.category }}</span>
                                  <span v-if="!mailbox.is_active" class="text-[10px] font-semibold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Inactive</span>
                              </div>
                              <div class="text-sm text-gray-500 truncate">{{ mailbox.email }}</div>
                              <div class="text-xs text-gray-400 mt-1 flex items-center gap-2 flex-wrap">
                                  <span>{{ mailbox.driver_label }}</span>
                                  <span v-if="mailbox.unread > 0" class="text-blue-600 font-medium">{{ mailbox.unread }} unread</span>
                                  <span v-if="mailbox.last_synced_at">Last sync: {{ formatRelativeDate(mailbox.last_synced_at) }}</span>
                              </div>
                          </div>
                      </div>
                      <div class="flex items-center gap-1 flex-shrink-0">
                          <button @click="testMailbox(mailbox)" :disabled="rowBusy === mailbox.id" class="text-xs text-gray-600 hover:text-gray-800 px-2.5 py-1.5 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50" title="Verify the connection">
                              {{ rowBusy === mailbox.id && rowAction === 'test' ? 'Testing…' : 'Test' }}
                          </button>
                          <button v-if="mailbox.capabilities?.polling" @click="syncMailbox(mailbox)" :disabled="rowBusy === mailbox.id" class="text-xs text-gray-600 hover:text-gray-800 px-2.5 py-1.5 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50" title="Fetch new mail now">
                              {{ rowBusy === mailbox.id && rowAction === 'sync' ? 'Syncing…' : 'Sync' }}
                          </button>
                          <button @click="openEdit(mailbox)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                          </button>
                          <button @click="openDelete(mailbox)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                          </button>
                      </div>
                  </div>
                  <div v-if="rowMessage.id === mailbox.id" class="mt-2 text-xs px-3 py-2 rounded-lg" :class="rowMessage.error ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-700'">
                      {{ rowMessage.text }}
                  </div>
              </div>

              <p v-if="store.mailboxes.length" class="text-xs text-gray-400 text-center pt-2">
                  Polling mailboxes are checked every 5 minutes via WP-Cron. For reliable syncing, run a real system cron (<code class="font-mono">DISABLE_WP_CRON</code> + <code class="font-mono">wp cron event run --due-now</code>).
              </p>
          </div>
      </div>

      <!-- Add / Edit modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[calc(100vh-4rem)] flex flex-col">
              <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                  <h3 class="font-bold text-gray-800">{{ editing ? 'Edit Mailbox' : 'Add Mailbox' }}</h3>
                  <button @click="closeModal" class="text-gray-400 hover:text-gray-600 p-1 rounded transition-colors">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  </button>
              </div>

              <form @submit.prevent="save" class="flex-1 overflow-y-auto p-5 space-y-5">
                  <!-- Driver selection (create only) -->
                  <div v-if="!editing">
                      <label class="block text-sm font-semibold text-gray-700 mb-2">Provider</label>
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                          <button
                              v-for="driver in drivers"
                              :key="driver.slug"
                              type="button"
                              @click="selectDriver(driver.slug)"
                              class="text-left p-3 rounded-xl border transition-all"
                              :class="form.driver === driver.slug ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500' : 'border-gray-200 hover:border-blue-300'"
                          >
                              <div class="text-sm font-semibold text-gray-800">{{ driver.label }}</div>
                              <div class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ driver.description }}</div>
                          </button>
                      </div>
                  </div>
                  <div v-else class="text-sm text-gray-500">
                      Provider: <span class="font-semibold text-gray-700">{{ currentDriver?.label || form.driver }}</span>
                  </div>

                  <!-- Basics -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                          <input v-model="form.name" type="text" required placeholder="e.g. Support" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all text-sm">
                      </div>
                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                          <input v-model="form.email" type="email" required placeholder="support@example.com" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all text-sm">
                      </div>
                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">From name <span class="text-gray-400 font-normal">(optional)</span></label>
                          <input v-model="form.from_name" type="text" placeholder="Shown to recipients" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all text-sm">
                      </div>
                      <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                          <select v-model="form.category" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all text-sm">
                              <option value="marketing">Marketing</option>
                              <option value="business">Business</option>
                              <option value="support">Support</option>
                              <option value="other">Other</option>
                          </select>
                      </div>
                  </div>

                  <div class="flex items-center justify-between flex-wrap gap-3">
                      <div class="flex items-center gap-1.5">
                          <span class="text-sm font-medium text-gray-700 mr-1">Color</span>
                          <button
                              v-for="color in colorOptions"
                              :key="color"
                              type="button"
                              @click="form.color = color"
                              class="w-6 h-6 rounded-full transition-transform hover:scale-110"
                              :style="{ backgroundColor: color }"
                              :class="form.color === color ? 'ring-2 ring-offset-1 ring-gray-500' : ''"
                          ></button>
                      </div>
                      <div class="flex items-center gap-4">
                          <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                              <input v-model="form.is_default" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                              Default sender
                          </label>
                          <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                              <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                              Active
                          </label>
                      </div>
                  </div>

                  <!-- Driver settings, grouped -->
                  <div v-for="(fields, group) in groupedFields" :key="group" class="border border-gray-100 rounded-xl p-4 space-y-3 bg-gray-50/50">
                      <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ groupLabel(group) }}</h4>
                      <div v-for="field in fields" :key="field.key">
                          <template v-if="field.type === 'checkbox'">
                              <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                  <input type="checkbox" :checked="!!form.driver_settings[field.key]" @change="form.driver_settings[field.key] = $event.target.checked ? 1 : 0" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                  {{ field.label }}
                              </label>
                          </template>
                          <template v-else>
                              <label class="block text-sm font-medium text-gray-700 mb-1">{{ field.label }}</label>
                              <select
                                  v-if="field.type === 'select'"
                                  v-model="form.driver_settings[field.key]"
                                  @change="field.key === 'preset' && applyPreset()"
                                  class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all text-sm"
                              >
                                  <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                              </select>
                              <input
                                  v-else
                                  v-model="form.driver_settings[field.key]"
                                  :type="field.type === 'password' ? 'password' : field.type === 'number' ? 'number' : 'text'"
                                  :placeholder="field.placeholder || ''"
                                  class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all text-sm"
                                  :class="{ 'font-mono': field.type === 'password' }"
                              >
                          </template>
                          <p v-if="field.help" class="text-xs text-gray-500 mt-1">{{ field.help }}</p>
                          <p v-if="field.key === 'preset' && presetHelp" class="text-xs text-blue-700 bg-blue-50 rounded-lg p-2 mt-1.5">{{ presetHelp }}</p>
                      </div>
                  </div>

                  <!-- Webhook URLs for push drivers (available after creation) -->
                  <div v-if="editing && editing.webhook_url" class="border border-gray-100 rounded-xl p-4 space-y-3 bg-gray-50/50">
                      <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Inbound webhook</h4>
                      <p class="text-xs text-gray-500">Configure your provider to POST incoming mail to this URL{{ form.driver === 'mailgun' ? ' (use the MIME variant for Mailgun Routes so attachments and headers are preserved)' : '' }}.</p>
                      <div v-for="entry in webhookEntries" :key="entry.label" class="space-y-1">
                          <div class="text-xs font-medium text-gray-600">{{ entry.label }}</div>
                          <div class="flex items-center gap-2">
                              <input type="text" readonly :value="entry.url" class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-mono text-gray-600" @focus="$event.target.select()">
                              <button type="button" @click="copyToClipboard(entry.url)" class="text-xs text-blue-600 hover:text-blue-800 px-2.5 py-2 rounded-lg hover:bg-blue-50 transition-colors flex-shrink-0">
                                  {{ copied === entry.url ? 'Copied!' : 'Copy' }}
                              </button>
                          </div>
                      </div>
                  </div>

                  <div v-if="modalError" class="bg-red-50 text-red-600 p-3 rounded-xl text-sm border border-red-200/50">{{ modalError }}</div>
              </form>

              <div class="px-5 py-4 border-t border-gray-100 flex justify-end items-center gap-2">
                  <button type="button" @click="closeModal" class="text-sm text-gray-600 hover:text-gray-800 px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors">Cancel</button>
                  <button @click="save" :disabled="saving || !form.driver" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl text-sm font-medium transition-colors flex items-center disabled:opacity-60 disabled:cursor-not-allowed">
                      <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                      {{ saving ? 'Saving…' : (editing ? 'Save Changes' : 'Create Mailbox') }}
                  </button>
              </div>
          </div>
      </div>

      <!-- Delete confirmation modal -->
      <div v-if="deleting" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="deleting = null"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-5 space-y-4">
              <h3 class="font-bold text-gray-800">Delete "{{ deleting.name }}"?</h3>
              <p class="text-sm text-gray-500">The connection will be removed. Choose what happens to the emails already imported from this mailbox:</p>
              <div class="space-y-2">
                  <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer p-3 rounded-xl border transition-colors" :class="deleteEmailsMode === 'keep' ? 'border-blue-400 bg-blue-50/50' : 'border-gray-200'">
                      <input v-model="deleteEmailsMode" type="radio" value="keep" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                      <span><span class="font-medium">Keep the emails</span><br><span class="text-xs text-gray-500">They stay in your inbox, no longer assigned to a mailbox.</span></span>
                  </label>
                  <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer p-3 rounded-xl border transition-colors" :class="deleteEmailsMode === 'trash' ? 'border-blue-400 bg-blue-50/50' : 'border-gray-200'">
                      <input v-model="deleteEmailsMode" type="radio" value="trash" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                      <span><span class="font-medium">Move received mail to Trash</span><br><span class="text-xs text-gray-500">Drafts and sent copies are kept, unassigned. Emptying the Trash deletes the rest permanently.</span></span>
                  </label>
              </div>
              <div v-if="deleteError" class="bg-red-50 text-red-600 p-3 rounded-xl text-sm border border-red-200/50">{{ deleteError }}</div>
              <div class="flex justify-end gap-2 pt-1">
                  <button @click="deleting = null" class="text-sm text-gray-600 hover:text-gray-800 px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors">Cancel</button>
                  <button @click="confirmDelete" :disabled="deleteBusy" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-xl text-sm font-medium transition-colors disabled:opacity-60">
                      {{ deleteBusy ? 'Deleting…' : 'Delete Mailbox' }}
                  </button>
              </div>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import api from '../utils/api';
import { useAppStore } from '../stores/useAppStore';
import { formatRelativeDate } from '../utils/date';

const store = useAppStore();

// Mailbox rows render straight from the store — the sidebar and this
// page always show the same list, refreshed by store.loadMailboxes()
const loading = ref(true);
const drivers = ref([]);

const showModal = ref(false);
const editing = ref(null); // the mailbox row being edited, or null when creating
const saving = ref(false);
const modalError = ref('');
const presetHelp = ref('');
const copied = ref('');

const deleting = ref(null);
const deleteEmailsMode = ref('keep');
const deleteBusy = ref(false);
const deleteError = ref('');

const rowBusy = ref(null);
const rowAction = ref('');
const rowMessage = ref({ id: null, text: '', error: false });

const colorOptions = ['#3b82f6', '#8b5cf6', '#ec4899', '#ef4444', '#f59e0b', '#10b981', '#14b8a6', '#6b7280'];

const emptyForm = () => ({
    name: '',
    email: '',
    from_name: '',
    driver: '',
    category: 'other',
    color: colorOptions[0],
    is_default: false,
    is_active: true,
    driver_settings: {}
});

const form = reactive(emptyForm());

const currentDriver = computed(() => drivers.value.find(d => d.slug === form.driver) || null);

const groupedFields = computed(() => {
    const groups = {};
    for (const field of currentDriver.value?.fields || []) {
        const group = field.group || 'settings';
        (groups[group] = groups[group] || []).push(field);
    }
    return groups;
});

const webhookEntries = computed(() => {
    if (!editing.value) return [];
    const entries = [];
    if (editing.value.webhook_url_mime) {
        entries.push({ label: 'Webhook URL (raw MIME — recommended)', url: editing.value.webhook_url_mime });
    }
    if (editing.value.webhook_url) {
        entries.push({ label: editing.value.webhook_url_mime ? 'Webhook URL (parsed payload)' : 'Webhook URL', url: editing.value.webhook_url });
    }
    return entries;
});

const GROUP_LABELS = {
    imap: 'Incoming mail (IMAP)',
    smtp: 'Outgoing mail (SMTP)',
    options: 'Options',
    aws: 'AWS credentials',
    mailgun: 'Mailgun API',
    postmark: 'Postmark API',
    elasticemail: 'Elastic Email API',
    brevo: 'Brevo API'
};

const groupLabel = (group) => GROUP_LABELS[group] || (group.charAt(0).toUpperCase() + group.slice(1));

const categoryClass = (category) => {
    switch (category) {
        case 'marketing': return 'text-purple-700 bg-purple-100';
        case 'business': return 'text-blue-700 bg-blue-100';
        case 'support': return 'text-green-700 bg-green-100';
        default: return 'text-gray-600 bg-gray-100';
    }
};

const loadAll = async () => {
    try {
        // The driver list is static — fetched once here, mailboxes via the store
        const [driverRes] = await Promise.all([api.getDrivers(), store.loadMailboxes()]);
        drivers.value = driverRes.data || [];
    } catch (e) {
        console.error('Failed to load drivers', e);
    } finally {
        loading.value = false;
    }
};

// The app is "configured" exactly when an active mailbox exists —
// mirrors the backend's Mailbox::hasActive()
const syncConfiguredFlag = () => {
    store.setConfigured(store.activeMailboxes.length > 0);
};

const defaultSettingsFor = (slug) => {
    const driver = drivers.value.find(d => d.slug === slug);
    const settings = {};
    for (const field of driver?.fields || []) {
        settings[field.key] = field.default !== undefined ? field.default : '';
    }
    return settings;
};

const selectDriver = (slug) => {
    form.driver = slug;
    form.driver_settings = defaultSettingsFor(slug);
    presetHelp.value = '';
    applyPreset();
};

// Auto-fill IMAP/SMTP server fields from the chosen provider preset
const applyPreset = () => {
    const presets = currentDriver.value?.presets;
    const preset = presets && presets[form.driver_settings.preset];
    if (!preset) {
        presetHelp.value = '';
        return;
    }
    for (const key of ['imap_host', 'imap_port', 'imap_encryption', 'smtp_host', 'smtp_port', 'smtp_encryption']) {
        if (preset[key] !== undefined) {
            form.driver_settings[key] = preset[key];
        }
    }
    presetHelp.value = preset.help || '';
};

const openAdd = () => {
    editing.value = null;
    Object.assign(form, emptyForm());
    modalError.value = '';
    presetHelp.value = '';
    // Preselect the universal driver so the form is usable immediately
    if (drivers.value.length) {
        selectDriver(drivers.value[0].slug);
    }
    showModal.value = true;
};

const openEdit = (mailbox) => {
    editing.value = mailbox;
    Object.assign(form, {
        name: mailbox.name,
        email: mailbox.email,
        from_name: mailbox.from_name || '',
        driver: mailbox.driver,
        category: mailbox.category || 'other',
        color: mailbox.color || colorOptions[0],
        is_default: !!mailbox.is_default,
        is_active: !!mailbox.is_active,
        // Secrets arrive masked; sending a masked value back means "unchanged"
        driver_settings: { ...defaultSettingsFor(mailbox.driver), ...(mailbox.driver_settings || {}) }
    });
    modalError.value = '';
    presetHelp.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editing.value = null;
};

const save = async () => {
    if (!form.name || !form.email || !form.driver) {
        modalError.value = 'Name, email address and provider are required.';
        return;
    }
    saving.value = true;
    modalError.value = '';
    try {
        const payload = {
            name: form.name,
            email: form.email,
            from_name: form.from_name,
            driver: form.driver,
            category: form.category,
            color: form.color,
            is_default: form.is_default ? 1 : 0,
            is_active: form.is_active ? 1 : 0,
            driver_settings: form.driver_settings
        };

        if (editing.value) {
            await api.updateMailbox(editing.value.id, payload);
        } else {
            await api.createMailbox(payload);
        }

        closeModal();
        await store.loadMailboxes();
        syncConfiguredFlag();
    } catch (e) {
        modalError.value = e.response?.data?.message || 'Failed to save mailbox.';
    } finally {
        saving.value = false;
    }
};

const testMailbox = async (mailbox) => {
    rowBusy.value = mailbox.id;
    rowAction.value = 'test';
    rowMessage.value = { id: null, text: '', error: false };
    try {
        const { data } = await api.testMailbox(mailbox.id);
        rowMessage.value = { id: mailbox.id, text: data.message || 'Connection verified', error: false };
    } catch (e) {
        rowMessage.value = { id: mailbox.id, text: e.response?.data?.message || 'Connection failed', error: true };
    } finally {
        rowBusy.value = null;
    }
};

const syncMailbox = async (mailbox) => {
    rowBusy.value = mailbox.id;
    rowAction.value = 'sync';
    rowMessage.value = { id: null, text: '', error: false };
    try {
        const { data } = await api.syncMailbox(mailbox.id);
        rowMessage.value = { id: mailbox.id, text: data.message || 'Sync complete', error: false };
        await store.loadMailboxes();
    } catch (e) {
        rowMessage.value = { id: mailbox.id, text: e.response?.data?.message || 'Sync failed', error: true };
    } finally {
        rowBusy.value = null;
    }
};

const openDelete = (mailbox) => {
    deleting.value = mailbox;
    deleteEmailsMode.value = 'keep';
    deleteError.value = '';
};

const confirmDelete = async () => {
    deleteBusy.value = true;
    deleteError.value = '';
    try {
        const params = deleteEmailsMode.value === 'trash' ? { emails: 'trash' } : {};
        await api.deleteMailbox(deleting.value.id, params);
        deleting.value = null;
        await store.loadMailboxes();
        syncConfiguredFlag();
    } catch (e) {
        deleteError.value = e.response?.data?.message || 'Failed to delete mailbox.';
    } finally {
        deleteBusy.value = false;
    }
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
    } catch (e) {
        // Clipboard API can be unavailable over plain HTTP
        const input = document.createElement('textarea');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    }
    copied.value = text;
    setTimeout(() => { copied.value = ''; }, 2000);
};

onMounted(loadAll);
</script>
