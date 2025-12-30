import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAppStore = defineStore('app', () => {
    const isConfigured = ref(window.FluentMailbox?.is_configured || false);
    const STORAGE_KEY = 'fluent-mailbox-compact-mode';

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

    return {
        isConfigured,
        isCompact,
        showCompose,
        composeMode,
        composeEmailData,
        setConfigured,
        toggleCompact,
        setCompact,
        openCompose,
        closeCompose
    };
});
