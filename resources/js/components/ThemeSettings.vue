<template>
    <div class="space-y-8">
        <!-- Theme Selection -->
        <section>
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Theme</h3>
                    <p class="text-xs text-gray-500">Choose your preferred background theme</p>
                </div>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                <button
                    v-for="theme in store.themePresets"
                    :key="theme.id"
                    @click="store.setTheme(theme.id)"
                    class="group relative rounded-xl overflow-hidden aspect-[4/3] border-2 transition-all duration-200 hover:scale-105"
                    :class="store.themeSettings.theme === theme.id ? 'border-blue-500 ring-2 ring-blue-500/30' : 'border-gray-200 hover:border-gray-300'"
                >
                    <!-- Theme Preview -->
                    <div class="absolute inset-0 bg-gradient-to-br" :class="theme.gradient"></div>

                    <!-- Mini Layout Preview -->
                    <div class="absolute inset-1.5 flex gap-1">
                        <div class="w-1/4 rounded-md" :class="theme.dark ? 'bg-gray-700/50' : 'bg-white/60'"></div>
                        <div class="flex-1 rounded-md" :class="theme.dark ? 'bg-gray-600/50' : 'bg-white/40'"></div>
                    </div>

                    <!-- Theme Name -->
                    <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/40 to-transparent p-1.5">
                        <span class="text-xs font-medium text-white">{{ theme.name }}</span>
                    </div>

                    <!-- Check Mark -->
                    <div
                        v-if="store.themeSettings.theme === theme.id"
                        class="absolute top-1.5 right-1.5 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center"
                    >
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </section>

        <!-- Accent Color -->
        <section>
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="{ backgroundColor: store.currentAccent.primary }">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Accent Color</h3>
                    <p class="text-xs text-gray-500">Customize buttons, links, and highlights</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="color in store.accentColors"
                    :key="color.id"
                    @click="store.setAccentColor(color.id)"
                    class="w-10 h-10 rounded-full transition-all duration-200 hover:scale-110 relative group"
                    :style="{ backgroundColor: color.primary }"
                    :title="color.name"
                >
                    <!-- Inner Ring -->
                    <span
                        v-if="store.themeSettings.accentColor === color.id"
                        class="absolute inset-1 rounded-full border-2 border-white"
                    ></span>

                    <!-- Check Mark -->
                    <span
                        v-if="store.themeSettings.accentColor === color.id"
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </span>

                    <!-- Tooltip -->
                    <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 text-xs text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                        {{ color.name }}
                    </span>
                </button>
            </div>
        </section>

        <!-- Density -->
        <section>
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Density</h3>
                    <p class="text-xs text-gray-500">Adjust spacing between items</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <button
                    v-for="density in store.densityOptions"
                    :key="density.id"
                    @click="store.setDensity(density.id)"
                    class="p-3 rounded-xl border-2 transition-all duration-200 text-left"
                    :class="store.themeSettings.density === density.id
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                >
                    <!-- Preview Lines -->
                    <div class="space-y-1 mb-2">
                        <div class="h-1.5 bg-gray-300 rounded-full w-full" :class="density.id === 'comfortable' ? 'mb-2' : density.id === 'compact' ? 'mb-0.5' : 'mb-1'"></div>
                        <div class="h-1.5 bg-gray-300 rounded-full w-3/4" :class="density.id === 'comfortable' ? 'mb-2' : density.id === 'compact' ? 'mb-0.5' : 'mb-1'"></div>
                        <div class="h-1.5 bg-gray-300 rounded-full w-5/6"></div>
                    </div>
                    <span class="text-sm font-medium" :class="store.themeSettings.density === density.id ? 'text-blue-700' : 'text-gray-700'">
                        {{ density.name }}
                    </span>
                </button>
            </div>
        </section>

        <!-- Dark Mode -->
        <section>
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Dark Mode</h3>
                    <p class="text-xs text-gray-500">Choose light, dark, or system preference</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <button
                    v-for="mode in darkModeOptions"
                    :key="mode.id"
                    @click="store.setDarkMode(mode.id)"
                    class="p-3 rounded-xl border-2 transition-all duration-200 flex flex-col items-center gap-2"
                    :class="store.themeSettings.darkMode === mode.id
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                >
                    <component :is="mode.icon" class="w-6 h-6" :class="store.themeSettings.darkMode === mode.id ? 'text-blue-600' : 'text-gray-500'" />
                    <span class="text-sm font-medium" :class="store.themeSettings.darkMode === mode.id ? 'text-blue-700' : 'text-gray-700'">
                        {{ mode.name }}
                    </span>
                </button>
            </div>
        </section>

        <!-- Background Image (Optional) -->
        <section>
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Background Image</h3>
                    <p class="text-xs text-gray-500">Add a custom background image (optional)</p>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-3">
                <!-- No Image Option -->
                <button
                    @click="store.setBackgroundImage(null)"
                    class="aspect-[4/3] rounded-xl border-2 transition-all duration-200 flex items-center justify-center"
                    :class="!store.themeSettings.backgroundImage
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300 bg-gray-100'"
                >
                    <svg class="w-6 h-6" :class="!store.themeSettings.backgroundImage ? 'text-blue-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </button>

                <!-- Preset Images -->
                <button
                    v-for="bg in backgroundPresets"
                    :key="bg.id"
                    @click="store.setBackgroundImage(bg.url)"
                    class="aspect-[4/3] rounded-xl border-2 transition-all duration-200 bg-cover bg-center overflow-hidden relative group"
                    :class="store.themeSettings.backgroundImage === bg.url
                        ? 'border-blue-500 ring-2 ring-blue-500/30'
                        : 'border-gray-200 hover:border-gray-300'"
                    :style="{ backgroundImage: `url(${bg.url})` }"
                >
                    <!-- Check Mark -->
                    <div
                        v-if="store.themeSettings.backgroundImage === bg.url"
                        class="absolute top-1 right-1 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center"
                    >
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </section>

        <!-- Reset Button -->
        <div class="pt-4 border-t border-gray-200">
            <button
                @click="resetToDefaults"
                class="text-sm text-gray-600 hover:text-gray-800 hover:underline transition-colors"
            >
                Reset to defaults
            </button>
        </div>
    </div>
</template>

<script setup>
import { h } from 'vue';
import { useAppStore } from '../stores/useAppStore';

const store = useAppStore();

// Dark mode options with inline SVG components
const darkModeOptions = [
    {
        id: 'light',
        name: 'Light',
        icon: {
            render() {
                return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z' })
                ]);
            }
        }
    },
    {
        id: 'dark',
        name: 'Dark',
        icon: {
            render() {
                return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z' })
                ]);
            }
        }
    },
    {
        id: 'system',
        name: 'System',
        icon: {
            render() {
                return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' })
                ]);
            }
        }
    }
];

// Sample background presets (using placeholder gradients as URLs)
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
