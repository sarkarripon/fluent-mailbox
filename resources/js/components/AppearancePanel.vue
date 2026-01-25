<template>
    <Teleport to="body">
        <!-- Slide Panel - No backdrop, allows interaction with app -->
        <Transition name="slide-right">
            <div
                v-if="isOpen"
                class="fixed right-0 w-80 max-w-[85vw] z-[9999] flex flex-col shadow-2xl border-l rounded-l-2xl m-2"
                :class="isDark ? 'bg-gray-900/95 backdrop-blur-xl text-gray-100 border-gray-700' : 'bg-white/95 backdrop-blur-xl text-gray-900 border-gray-200'"
                :style="{ top: adminBarHeight + 'px', height: `calc(100vh - ${adminBarHeight + 16}px)` }"
            >
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b" :class="isDark ? 'border-gray-700' : 'border-gray-200'">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold">Appearance</h2>
                            <p class="text-xs" :class="isDark ? 'text-gray-400' : 'text-gray-500'">Customize your experience</p>
                        </div>
                    </div>
                    <button
                        @click="$emit('close')"
                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                        :class="isDark ? 'hover:bg-gray-800 text-gray-400 hover:text-gray-200' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-700'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="space-y-6">
                        <!-- Theme Selection -->
                        <section>
                            <h3 class="text-sm font-semibold mb-3" :class="isDark ? 'text-gray-200' : 'text-gray-800'">Theme</h3>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="theme in store.themePresets"
                                    :key="theme.id"
                                    @click="store.setTheme(theme.id)"
                                    class="group relative rounded-lg overflow-hidden aspect-[4/3] border-2 transition-all duration-200 hover:scale-105"
                                    :class="store.themeSettings.theme === theme.id
                                        ? 'border-blue-500 ring-2 ring-blue-500/30'
                                        : isDark ? 'border-gray-700 hover:border-gray-600' : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <div class="absolute inset-0 bg-gradient-to-br" :class="theme.gradient"></div>
                                    <div class="absolute inset-1 flex gap-0.5">
                                        <div class="w-1/4 rounded-sm" :class="theme.dark ? 'bg-gray-700/50' : 'bg-white/60'"></div>
                                        <div class="flex-1 rounded-sm" :class="theme.dark ? 'bg-gray-600/50' : 'bg-white/40'"></div>
                                    </div>
                                    <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/50 to-transparent p-1">
                                        <span class="text-[10px] font-medium text-white">{{ theme.name }}</span>
                                    </div>
                                    <div
                                        v-if="store.themeSettings.theme === theme.id"
                                        class="absolute top-1 right-1 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center"
                                    >
                                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                        </section>

                        <!-- Accent Color -->
                        <section>
                            <h3 class="text-sm font-semibold mb-3" :class="isDark ? 'text-gray-200' : 'text-gray-800'">Accent Color</h3>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="color in store.accentColors"
                                    :key="color.id"
                                    @click="store.setAccentColor(color.id)"
                                    class="w-8 h-8 rounded-full transition-all duration-200 hover:scale-110 relative"
                                    :style="{ backgroundColor: color.primary }"
                                    :title="color.name"
                                >
                                    <span
                                        v-if="store.themeSettings.accentColor === color.id"
                                        class="absolute inset-0.5 rounded-full border-2 border-white"
                                    ></span>
                                    <span
                                        v-if="store.themeSettings.accentColor === color.id"
                                        class="absolute inset-0 flex items-center justify-center"
                                    >
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </section>

                        <!-- Density -->
                        <section>
                            <h3 class="text-sm font-semibold mb-3" :class="isDark ? 'text-gray-200' : 'text-gray-800'">Density</h3>
                            <div class="flex gap-2">
                                <button
                                    v-for="density in store.densityOptions"
                                    :key="density.id"
                                    @click="store.setDensity(density.id)"
                                    class="flex-1 p-2.5 rounded-lg border-2 transition-all duration-200 text-center"
                                    :class="store.themeSettings.density === density.id
                                        ? 'border-blue-500 bg-blue-500/10'
                                        : isDark ? 'border-gray-700 hover:border-gray-600 hover:bg-gray-800' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                                >
                                    <div class="space-y-0.5 mb-1.5 mx-auto w-12">
                                        <div class="h-1 rounded-full w-full" :class="isDark ? 'bg-gray-600' : 'bg-gray-300'" :style="{ marginBottom: density.id === 'comfortable' ? '6px' : density.id === 'compact' ? '2px' : '4px' }"></div>
                                        <div class="h-1 rounded-full w-3/4" :class="isDark ? 'bg-gray-600' : 'bg-gray-300'" :style="{ marginBottom: density.id === 'comfortable' ? '6px' : density.id === 'compact' ? '2px' : '4px' }"></div>
                                        <div class="h-1 rounded-full w-5/6" :class="isDark ? 'bg-gray-600' : 'bg-gray-300'"></div>
                                    </div>
                                    <span class="text-xs font-medium" :class="store.themeSettings.density === density.id ? 'text-blue-500' : isDark ? 'text-gray-400' : 'text-gray-600'">
                                        {{ density.name }}
                                    </span>
                                </button>
                            </div>
                        </section>

                        <!-- Dark Mode -->
                        <section>
                            <h3 class="text-sm font-semibold mb-3" :class="isDark ? 'text-gray-200' : 'text-gray-800'">Mode</h3>
                            <div class="flex gap-2">
                                <button
                                    v-for="mode in darkModeOptions"
                                    :key="mode.id"
                                    @click="store.setDarkMode(mode.id)"
                                    class="flex-1 p-2.5 rounded-lg border-2 transition-all duration-200 flex flex-col items-center gap-1.5"
                                    :class="store.themeSettings.darkMode === mode.id
                                        ? 'border-blue-500 bg-blue-500/10'
                                        : isDark ? 'border-gray-700 hover:border-gray-600 hover:bg-gray-800' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                                >
                                    <svg class="w-5 h-5" :class="store.themeSettings.darkMode === mode.id ? 'text-blue-500' : isDark ? 'text-gray-400' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mode.icon"></path>
                                    </svg>
                                    <span class="text-xs font-medium" :class="store.themeSettings.darkMode === mode.id ? 'text-blue-500' : isDark ? 'text-gray-400' : 'text-gray-600'">
                                        {{ mode.name }}
                                    </span>
                                </button>
                            </div>
                        </section>

                        <!-- Background Image -->
                        <section>
                            <h3 class="text-sm font-semibold mb-3" :class="isDark ? 'text-gray-200' : 'text-gray-800'">Background</h3>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    @click="store.setBackgroundImage(null)"
                                    class="aspect-[4/3] rounded-lg border-2 transition-all duration-200 flex items-center justify-center"
                                    :class="!store.themeSettings.backgroundImage
                                        ? 'border-blue-500 bg-blue-500/10'
                                        : isDark ? 'border-gray-700 hover:border-gray-600 bg-gray-800' : 'border-gray-200 hover:border-gray-300 bg-gray-100'"
                                >
                                    <svg class="w-5 h-5" :class="!store.themeSettings.backgroundImage ? 'text-blue-500' : isDark ? 'text-gray-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                    </svg>
                                </button>
                                <button
                                    v-for="bg in backgroundPresets"
                                    :key="bg.id"
                                    @click="store.setBackgroundImage(bg.url)"
                                    class="aspect-[4/3] rounded-lg border-2 transition-all duration-200 bg-cover bg-center overflow-hidden relative"
                                    :class="store.themeSettings.backgroundImage === bg.url
                                        ? 'border-blue-500 ring-2 ring-blue-500/30'
                                        : isDark ? 'border-gray-700 hover:border-gray-600' : 'border-gray-200 hover:border-gray-300'"
                                    :style="{ backgroundImage: `url(${bg.url})` }"
                                >
                                    <div
                                        v-if="store.themeSettings.backgroundImage === bg.url"
                                        class="absolute top-0.5 right-0.5 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center"
                                    >
                                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-4 border-t" :class="isDark ? 'border-gray-700' : 'border-gray-200'">
                    <button
                        @click="resetToDefaults"
                        class="w-full text-sm py-2 px-4 rounded-lg transition-colors"
                        :class="isDark ? 'text-gray-400 hover:text-gray-200 hover:bg-gray-800' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-100'"
                    >
                        Reset to defaults
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useAppStore } from '../stores/useAppStore';

defineProps({
    isOpen: {
        type: Boolean,
        default: false
    }
});

defineEmits(['close']);

const store = useAppStore();

const isDark = computed(() => store.isDarkTheme);

// Get WordPress admin bar height
const adminBarHeight = ref(0);

const getAdminBarHeight = () => {
    const adminBar = document.getElementById('wpadminbar');
    if (adminBar) {
        const height = adminBar.offsetHeight;
        if (height > 0) return height;
    }
    const body = document.body;
    if (body && body.classList.contains('admin-bar')) {
        return window.innerWidth <= 782 ? 46 : 32;
    }
    return 0;
};

onMounted(() => {
    adminBarHeight.value = getAdminBarHeight();
});

const darkModeOptions = [
    { id: 'light', name: 'Light', icon: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z' },
    { id: 'dark', name: 'Dark', icon: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z' },
    { id: 'system', name: 'System', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' }
];

const backgroundPresets = [
    { id: 'mountains', url: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&q=80' },
    { id: 'ocean', url: 'https://images.unsplash.com/photo-1505142468610-359e7d316be0?w=800&q=80' },
    { id: 'forest', url: 'https://images.unsplash.com/photo-1448375240586-882707db888b?w=800&q=80' },
];

const resetToDefaults = () => {
    store.setTheme('default');
    store.setAccentColor('blue');
    store.setDensity('default');
    store.setDarkMode('light');
    store.setBackgroundImage(null);
};
</script>

<style scoped>
.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.3s ease;
}

.slide-right-enter-from,
.slide-right-leave-to {
    transform: translateX(100%);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
