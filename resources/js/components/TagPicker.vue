<template>
    <div class="relative" ref="pickerRef">
        <div class="flex flex-wrap gap-2 items-center">
            <!-- Existing tags -->
            <span
                v-for="tag in emailTags"
                :key="tag.id"
                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full text-white cursor-pointer hover:opacity-80"
                :style="{ backgroundColor: tag.color }"
            >
                {{ tag.name }}
                <button
                    @click.stop="removeTag(tag.id)"
                    class="hover:bg-white hover:bg-opacity-20 rounded-full w-4 h-4 flex items-center justify-center"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </span>

            <!-- Add tag button -->
            <button
                @click="toggleDropdown"
                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full"
                :class="appStore.isDarkTheme ? 'text-gray-200 bg-gray-800 hover:bg-gray-700' : 'text-gray-600 bg-gray-100 hover:bg-gray-200'"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Tag
            </button>
        </div>

        <!-- Dropdown menu -->
        <Teleport to="body">
            <div
                v-if="showDropdown"
                :style="dropdownStyle"
                class="fixed z-50 mt-1 rounded-md shadow-lg max-h-60 overflow-y-auto"
                style="min-width: 200px;"
                :class="appStore.isDarkTheme ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'"
            >
                <div v-if="loading" class="px-4 py-3 text-sm" :class="appStore.isDarkTheme ? 'text-gray-400' : 'text-gray-500'">
                    Loading tags...
                </div>
                <div v-else-if="availableTags.length === 0" class="px-4 py-3 text-sm" :class="appStore.isDarkTheme ? 'text-gray-400' : 'text-gray-500'">
                    <p class="mb-2">No tags available.</p>
                    <button
                        @click="$emit('manage-tags')"
                        class="text-xs underline"
                        :class="appStore.isDarkTheme ? 'text-blue-400 hover:text-blue-300' : 'text-blue-600 hover:text-blue-700'"
                    >
                        Create tags
                    </button>
                </div>
                <div v-else>
                    <button
                        v-for="tag in availableTags"
                        :key="tag.id"
                        @click="addTag(tag.id)"
                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-left"
                        :class="appStore.isDarkTheme ? 'hover:bg-gray-700 text-gray-200' : 'hover:bg-gray-50 text-gray-700'"
                    >
                        <span
                            class="inline-block w-3 h-3 rounded-full"
                            :style="{ backgroundColor: tag.color }"
                        ></span>
                        <span>{{ tag.name }}</span>
                    </button>
                </div>
                <div class="px-4 py-2 border-t" :class="appStore.isDarkTheme ? 'border-gray-700' : 'border-gray-200'">
                    <button
                        @click="$emit('manage-tags')"
                        class="w-full text-xs text-left underline"
                        :class="appStore.isDarkTheme ? 'text-blue-400 hover:text-blue-300' : 'text-blue-600 hover:text-blue-700'"
                    >
                        Manage tags...
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useAppStore } from '../stores/useAppStore';
import api from '../utils/api';

const props = defineProps({
    emailId: {
        type: [Number, String],
        required: true
    },
    initialTags: {
        type: Array,
        default: null // If provided, use these instead of loading
    }
});

const emit = defineEmits(['manage-tags', 'tags-updated']);

const appStore = useAppStore();
const pickerRef = ref(null);
const emailTags = ref([]);
const loading = ref(false);
const showDropdown = ref(false);
const dropdownStyle = ref({});

// Get tags that are not already assigned to this email
const availableTags = computed(() => {
    const emailTagIds = emailTags.value.map(t => t.id);
    return appStore.allTags.filter(tag => !emailTagIds.includes(tag.id));
});

onMounted(async () => {
    // Use initial tags if provided, otherwise load them
    if (props.initialTags) {
        emailTags.value = props.initialTags;
    } else {
        await loadEmailTags();
    }

    if (!appStore.tagsLoaded) {
        await appStore.loadTags();
    }
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

async function loadEmailTags() {
    loading.value = true;
    try {
        const response = await api.getEmailTags(props.emailId);
        emailTags.value = response.data || [];
    } catch (error) {
        console.error('Failed to load email tags:', error);
    } finally {
        loading.value = false;
    }
}

function toggleDropdown() {
    showDropdown.value = !showDropdown.value;
    if (showDropdown.value) {
        nextTick(() => {
            updateDropdownPosition();
        });
    }
}

function updateDropdownPosition() {
    if (!pickerRef.value) return;

    const rect = pickerRef.value.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    // Position below by default, above if not enough space
    if (spaceBelow < 250 && spaceAbove > spaceBelow) {
        dropdownStyle.value = {
            left: `${rect.left}px`,
            bottom: `${window.innerHeight - rect.top}px`,
            top: 'auto'
        };
    } else {
        dropdownStyle.value = {
            left: `${rect.left}px`,
            top: `${rect.bottom}px`
        };
    }
}

function handleClickOutside(event) {
    if (pickerRef.value && !pickerRef.value.contains(event.target)) {
        showDropdown.value = false;
    }
}

async function addTag(tagId) {
    try {
        const response = await api.addEmailTag(props.emailId, tagId);
        emailTags.value = response.data.tags || [];
        showDropdown.value = false;
        emit('tags-updated', emailTags.value);
    } catch (error) {
        alert('Failed to add tag');
    }
}

async function removeTag(tagId) {
    try {
        const response = await api.removeEmailTag(props.emailId, tagId);
        emailTags.value = response.data.tags || [];
        emit('tags-updated', emailTags.value);
    } catch (error) {
        alert('Failed to remove tag');
    }
}

// Watch for changes to reload tags
watch(() => appStore.allTags, () => {
    // Refresh email tags if all tags were updated
}, { deep: true });
</script>
