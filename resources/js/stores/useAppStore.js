import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAppStore = defineStore('app', () => {
    const isConfigured = ref(window.FluentMailbox?.is_configured || false);

    function setConfigured(status) {
        isConfigured.value = status;
    }

    return {
        isConfigured,
        setConfigured
    };
});
