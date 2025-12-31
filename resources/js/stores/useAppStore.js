import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../utils/api';

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

    // Tags state
    const allTags = ref([]);
    const selectedTagIds = ref([]);
    const tagsLoaded = ref(false);

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
