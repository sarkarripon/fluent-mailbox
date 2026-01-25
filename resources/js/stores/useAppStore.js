import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import api from '../utils/api';

export const useAppStore = defineStore('app', () => {
    const isConfigured = ref(window.FluentMailbox?.is_configured || false);
    const isFrontendMode = ref(window.FluentMailbox?.isFrontend || false);
    const STORAGE_KEY = 'fluent-mailbox-compact-mode';

    // Load compact state from localStorage
    const loadCompactState = () => {
        const saved = localStorage.getItem(STORAGE_KEY);
        return saved === 'true';
    };

    // Default theme settings
    const getDefaultThemeSettings = () => ({
        theme: 'default',
        accentColor: 'blue',
        density: 'comfortable',
        darkMode: 'light',
        backgroundImage: null
    });

    const isCompact = ref(loadCompactState());
    const themeSettingsLoaded = ref(false);

    // Theme state - start with defaults, will be loaded from server
    const themeSettings = ref(getDefaultThemeSettings());

    // Theme presets
    const themePresets = [
        { id: 'default', name: 'Default', gradient: 'from-slate-50 via-blue-50 to-indigo-50', sidebar: 'bg-white/80 backdrop-blur-sm', surface: 'bg-white/70' },
        { id: 'ocean', name: 'Ocean', gradient: 'from-cyan-50 via-sky-50 to-blue-50', sidebar: 'bg-white/80 backdrop-blur-sm', surface: 'bg-white/70' },
        { id: 'sunset', name: 'Sunset', gradient: 'from-orange-50 via-rose-50 to-pink-50', sidebar: 'bg-white/80 backdrop-blur-sm', surface: 'bg-white/70' },
        { id: 'forest', name: 'Forest', gradient: 'from-emerald-50 via-green-50 to-teal-50', sidebar: 'bg-white/80 backdrop-blur-sm', surface: 'bg-white/70' },
        { id: 'lavender', name: 'Lavender', gradient: 'from-purple-50 via-violet-50 to-fuchsia-50', sidebar: 'bg-white/80 backdrop-blur-sm', surface: 'bg-white/70' },
        { id: 'midnight', name: 'Midnight', gradient: 'from-slate-900 via-gray-900 to-zinc-900', sidebar: 'bg-gray-800/90 backdrop-blur-sm', surface: 'bg-gray-800/90', dark: true },
        { id: 'charcoal', name: 'Charcoal', gradient: 'from-gray-800 via-gray-900 to-black', sidebar: 'bg-gray-900/90 backdrop-blur-sm', surface: 'bg-gray-700/90', dark: true },
    ];

    // Accent color presets
    const accentColors = [
        { id: 'blue', name: 'Blue', primary: '#2563eb', hover: '#1d4ed8', light: '#dbeafe' },
        { id: 'indigo', name: 'Indigo', primary: '#4f46e5', hover: '#4338ca', light: '#e0e7ff' },
        { id: 'violet', name: 'Violet', primary: '#7c3aed', hover: '#6d28d9', light: '#ede9fe' },
        { id: 'pink', name: 'Pink', primary: '#db2777', hover: '#be185d', light: '#fce7f3' },
        { id: 'rose', name: 'Rose', primary: '#e11d48', hover: '#be123c', light: '#ffe4e6' },
        { id: 'orange', name: 'Orange', primary: '#ea580c', hover: '#c2410c', light: '#ffedd5' },
        { id: 'amber', name: 'Amber', primary: '#d97706', hover: '#b45309', light: '#fef3c7' },
        { id: 'emerald', name: 'Emerald', primary: '#059669', hover: '#047857', light: '#d1fae5' },
        { id: 'teal', name: 'Teal', primary: '#0d9488', hover: '#0f766e', light: '#ccfbf1' },
        { id: 'cyan', name: 'Cyan', primary: '#0891b2', hover: '#0e7490', light: '#cffafe' },
    ];

    // Density options
    const densityOptions = [
        { id: 'comfortable', name: 'Comfortable', description: 'More spacing for easier reading', py: 'py-3', text: 'text-sm' },
        { id: 'default', name: 'Default', description: 'Balanced spacing', py: 'py-2.5', text: 'text-sm' },
        { id: 'compact', name: 'Compact', description: 'Fit more content on screen', py: 'py-2', text: 'text-xs' },
    ];

    // Computed theme values
    const currentTheme = computed(() => {
        return themePresets.find(t => t.id === themeSettings.value.theme) || themePresets[0];
    });

    const currentAccent = computed(() => {
        return accentColors.find(c => c.id === themeSettings.value.accentColor) || accentColors[0];
    });

    const currentDensity = computed(() => {
        return densityOptions.find(d => d.id === themeSettings.value.density) || densityOptions[1];
    });

    const isDarkTheme = computed(() => {
        if (themeSettings.value.darkMode === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        return themeSettings.value.darkMode === 'dark' || currentTheme.value.dark;
    });

    // Load theme settings from server (user meta)
    const loadThemeSettings = async () => {
        try {
            const response = await api.getThemeSettings();
            if (response.data) {
                themeSettings.value = { ...getDefaultThemeSettings(), ...response.data };
            }
            themeSettingsLoaded.value = true;
            applyThemeToDOM();
        } catch (e) {
            console.error('Failed to load theme settings from server:', e);
            themeSettingsLoaded.value = true;
        }
    };

    // Save theme settings to server (user meta)
    const saveThemeSettings = async () => {
        applyThemeToDOM();
        try {
            await api.saveThemeSettings(themeSettings.value);
        } catch (e) {
            console.error('Failed to save theme settings to server:', e);
        }
    };

    // Apply CSS variables to DOM
    const applyThemeToDOM = () => {
        const root = document.documentElement;
        const accent = currentAccent.value;

        root.style.setProperty('--fm-primary', accent.primary);
        root.style.setProperty('--fm-primary-hover', accent.hover);
        root.style.setProperty('--fm-primary-light', accent.light);

        // Apply dark mode class
        if (isDarkTheme.value) {
            root.classList.add('fm-dark');
        } else {
            root.classList.remove('fm-dark');
        }
    };

    // Theme actions
    const setTheme = (themeId) => {
        const theme = themePresets.find(t => t.id === themeId);
        themeSettings.value.theme = themeId;
        // Sync dark mode with theme type
        if (theme?.dark) {
            themeSettings.value.darkMode = 'dark';
        } else {
            themeSettings.value.darkMode = 'light';
        }
        saveThemeSettings();
    };

    const setAccentColor = (colorId) => {
        themeSettings.value.accentColor = colorId;
        saveThemeSettings();
    };

    const setDensity = (densityId) => {
        themeSettings.value.density = densityId;
        saveThemeSettings();
    };

    const setDarkMode = (mode) => {
        themeSettings.value.darkMode = mode;
        // Sync theme with dark mode
        const currentThemeIsDark = themePresets.find(t => t.id === themeSettings.value.theme)?.dark;
        if (mode === 'dark' && !currentThemeIsDark) {
            // Switch to midnight (first dark theme)
            themeSettings.value.theme = 'midnight';
        } else if (mode === 'light' && currentThemeIsDark) {
            // Switch to default (first light theme)
            themeSettings.value.theme = 'default';
        }
        saveThemeSettings();
    };

    const setBackgroundImage = (url) => {
        themeSettings.value.backgroundImage = url;
        saveThemeSettings();
    };

    // Watch for system dark mode changes
    if (typeof window !== 'undefined') {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (themeSettings.value.darkMode === 'system') {
                applyThemeToDOM();
            }
        });
    }

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
        isFrontendMode,
        isCompact,
        showCompose,
        composeMode,
        composeEmailData,
        allTags,
        selectedTagIds,
        tagsLoaded,
        // Theme exports
        themeSettings,
        themePresets,
        accentColors,
        densityOptions,
        currentTheme,
        currentAccent,
        currentDensity,
        isDarkTheme,
        setConfigured,
        toggleCompact,
        setCompact,
        openCompose,
        closeCompose,
        loadTags,
        setTags,
        toggleTagFilter,
        clearTagFilter,
        setTagFilter,
        // Theme actions
        setTheme,
        setAccentColor,
        setDensity,
        setDarkMode,
        setBackgroundImage,
        applyThemeToDOM,
        loadThemeSettings,
        themeSettingsLoaded
    };
});
