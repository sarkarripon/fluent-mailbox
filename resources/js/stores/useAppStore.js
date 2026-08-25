import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../utils/api';

export const useAppStore = defineStore('app', () => {
    const isConfigured = ref(window.FluentMailbox?.is_configured || false);
    const STORAGE_KEY = 'fluent-mailbox-compact-mode';
    const MAILBOX_KEY = 'fluent-mailbox-selected-mailbox';

    // Load compact state from localStorage
    const loadCompactState = () => {
        const saved = localStorage.getItem(STORAGE_KEY);
        return saved === 'true';
    };

    const isCompact = ref(loadCompactState());

    // Compose modal state
    const showCompose = ref(false);
    const composeMode = ref('new'); // 'new', 'reply', 'forward'
    const composeEmailData = ref(null);

    // Tags state
    const allTags = ref([]);
    const selectedTagIds = ref([]);
    const tagsLoaded = ref(false);

    // Mailboxes state — the sidebar switcher filters every list view.
    // null = "All Inboxes"
    const mailboxes = ref([]);
    const unassignedUnread = ref(0);
    const mailboxesLoaded = ref(false);
    const selectedMailboxId = ref(loadSelectedMailbox());
    let loadingMailboxes = false;

    function loadSelectedMailbox() {
        const saved = localStorage.getItem(MAILBOX_KEY);
        const id = parseInt(saved, 10);
        return id > 0 ? id : null;
    }

    const activeMailboxes = computed(() => mailboxes.value.filter(m => m.is_active));

    const defaultMailbox = computed(() =>
        activeMailboxes.value.find(m => m.is_default) || activeMailboxes.value[0] || null
    );

    const selectedMailbox = computed(() =>
        selectedMailboxId.value
            ? mailboxes.value.find(m => m.id === selectedMailboxId.value) || null
            : null
    );

    // Unread badge for the Inbox nav item: the selected mailbox's count,
    // or everything (incl. unassigned rows) under "All Inboxes"
    const inboxUnreadCount = computed(() => {
        if (selectedMailboxId.value) {
            return selectedMailbox.value?.unread || 0;
        }
        return mailboxes.value.reduce((sum, m) => sum + (m.unread || 0), 0) + unassignedUnread.value;
    });

    async function loadMailboxes() {
        if (loadingMailboxes) return;
        loadingMailboxes = true;
        try {
            const { data } = await api.getMailboxes();
            mailboxes.value = data.mailboxes || [];
            unassignedUnread.value = data.unassigned_unread || 0;
            mailboxesLoaded.value = true;

            // Drop a stale selection (mailbox deleted or deactivated)
            if (selectedMailboxId.value && !activeMailboxes.value.some(m => m.id === selectedMailboxId.value)) {
                setSelectedMailbox(null);
            }
        } catch (error) {
            console.error('Failed to load mailboxes:', error);
        } finally {
            loadingMailboxes = false;
        }
    }

    function setSelectedMailbox(id) {
        selectedMailboxId.value = id || null;
        if (id) {
            localStorage.setItem(MAILBOX_KEY, String(id));
        } else {
            localStorage.removeItem(MAILBOX_KEY);
        }
    }

    function setConfigured(status) {
        isConfigured.value = status;
    }

    function toggleCompact() {
        isCompact.value = !isCompact.value;
        localStorage.setItem(STORAGE_KEY, isCompact.value.toString());
    }

    function setCompact(value) {
        isCompact.value = value;
        localStorage.setItem(STORAGE_KEY, value.toString());
    }

    function openCompose(mode = 'new', emailData = null) {
        composeMode.value = mode;
        composeEmailData.value = emailData;
        showCompose.value = true;
    }

    function closeCompose() {
        showCompose.value = false;
        composeMode.value = 'new';
        composeEmailData.value = null;
    }

    // Tag actions
    async function loadTags() {
        try {
            const response = await api.getTags();
            allTags.value = response.data || [];
            tagsLoaded.value = true;
        } catch (error) {
            console.error('Failed to load tags:', error);
        }
    }

    function setTags(tags) {
        allTags.value = tags;
    }

    function toggleTagFilter(tagId) {
        const index = selectedTagIds.value.indexOf(tagId);
        if (index > -1) {
            selectedTagIds.value.splice(index, 1);
        } else {
            selectedTagIds.value.push(tagId);
        }
    }

    function clearTagFilter() {
        selectedTagIds.value = [];
    }

    function setTagFilter(tagIds) {
        selectedTagIds.value = Array.isArray(tagIds) ? tagIds : [tagIds];
    }

    return {
        isConfigured,
        isCompact,
        showCompose,
        composeMode,
        composeEmailData,
        allTags,
        selectedTagIds,
        tagsLoaded,
        mailboxes,
        unassignedUnread,
        mailboxesLoaded,
        selectedMailboxId,
        activeMailboxes,
        defaultMailbox,
        selectedMailbox,
        inboxUnreadCount,
        loadMailboxes,
        setSelectedMailbox,
        setConfigured,
        toggleCompact,
        setCompact,
        openCompose,
        closeCompose,
        loadTags,
        setTags,
        toggleTagFilter,
        clearTagFilter,
        setTagFilter
    };
});
