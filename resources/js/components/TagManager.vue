<template>
    <Teleport to="body">
        <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50" @click.self="close">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Manage Tags</h2>
                    <button @click="close" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                        &times;
                    </button>
                </div>

                <div class="p-6">
                    <!-- Create new tag form -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Create New Tag</h3>
                        <div class="flex gap-3">
                            <input
                                v-model="newTag.name"
                                type="text"
                                placeholder="Tag name"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                @keyup.enter="createTag"
                            />
                            <select
                                v-model="newTag.color"
                                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option v-for="color in availableColors" :key="color.value" :value="color.value">
                                    {{ color.name }}
                                </option>
                            </select>
                            <button
                                @click="createTag"
                                :disabled="!newTag.name.trim() || creating"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ creating ? 'Creating...' : 'Create' }}
                            </button>
                        </div>
                        <p v-if="createError" class="mt-2 text-sm text-red-600">{{ createError }}</p>
                    </div>

                    <!-- Existing tags list -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Existing Tags</h3>
                        <div v-if="loading" class="text-center py-8 text-gray-500">
                            Loading tags...
                        </div>
                        <div v-else-if="tags.length === 0" class="text-center py-8 text-gray-500">
                            No tags yet. Create your first tag above.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="tag in tags"
                                :key="tag.id"
                                class="flex items-center gap-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50"
                            >
                                <span
                                    class="inline-block w-4 h-4 rounded-full"
                                    :style="{ backgroundColor: tag.color }"
                                ></span>

                                <template v-if="editingTag?.id === tag.id">
                                    <input
                                        v-model="editingTag.name"
                                        type="text"
                                        class="flex-1 px-2 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        @keyup.enter="saveEdit(tag.id)"
                                        @keyup.escape="cancelEdit"
                                    />
                                    <select
                                        v-model="editingTag.color"
                                        class="px-2 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option v-for="color in availableColors" :key="color.value" :value="color.value">
                                            {{ color.name }}
                                        </option>
                                    </select>
                                    <button
                                        @click="saveEdit(tag.id)"
                                        class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600"
                                    >
                                        Save
                                    </button>
                                    <button
                                        @click="cancelEdit"
                                        class="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                                    >
                                        Cancel
                                    </button>
                                </template>

                                <template v-else>
                                    <span class="flex-1 font-medium">{{ tag.name }}</span>
                                    <button
                                        @click="startEdit(tag)"
                                        class="px-3 py-1 text-sm text-blue-600 hover:bg-blue-50 rounded"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteTag(tag.id)"
                                        :disabled="deleting === tag.id"
                                        class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded disabled:opacity-50"
                                    >
                                        {{ deleting === tag.id ? 'Deleting...' : 'Delete' }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t flex justify-end">
                    <button
                        @click="close"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useAppStore } from '../stores/useAppStore';
import api from '../utils/api';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const appStore = useAppStore();
const tags = computed(() => appStore.allTags);

const loading = ref(false);
const creating = ref(false);
const deleting = ref(null);
const createError = ref('');

const newTag = ref({
    name: '',
    color: '#3B82F6'
});

const editingTag = ref(null);

const availableColors = [
    { name: 'Blue', value: '#3B82F6' },
    { name: 'Green', value: '#10B981' },
    { name: 'Yellow', value: '#F59E0B' },
    { name: 'Red', value: '#EF4444' },
    { name: 'Purple', value: '#8B5CF6' },
    { name: 'Pink', value: '#EC4899' },
    { name: 'Indigo', value: '#6366F1' },
    { name: 'Gray', value: '#6B7280' },
    { name: 'Orange', value: '#F97316' },
    { name: 'Teal', value: '#14B8A6' }
];

watch(() => props.modelValue, async (value) => {
    if (value) {
        await loadTags();
    }
});

async function loadTags() {
    loading.value = true;
    try {
        await appStore.loadTags();
    } catch (error) {
        console.error('Failed to load tags:', error);
    } finally {
        loading.value = false;
    }
}

async function createTag() {
    if (!newTag.value.name.trim()) return;

    creating.value = true;
    createError.value = '';

    try {
        const response = await api.createTag({
            name: newTag.value.name.trim(),
            color: newTag.value.color
        });

        appStore.setTags(response.data.tags);
        newTag.value.name = '';
        newTag.value.color = '#3B82F6';
    } catch (error) {
        createError.value = error.response?.data?.message || 'Failed to create tag';
    } finally {
        creating.value = false;
    }
}

function startEdit(tag) {
    editingTag.value = { ...tag };
}

function cancelEdit() {
    editingTag.value = null;
}

async function saveEdit(tagId) {
    if (!editingTag.value.name.trim()) return;

    try {
        const response = await api.updateTag(tagId, {
            name: editingTag.value.name.trim(),
            color: editingTag.value.color
        });

        appStore.setTags(response.data.tags);
        editingTag.value = null;
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to update tag');
    }
}

async function deleteTag(tagId) {
    if (!confirm('Are you sure you want to delete this tag? It will be removed from all emails.')) {
        return;
    }

    deleting.value = tagId;

    try {
        const response = await api.deleteTag(tagId);
        appStore.setTags(response.data.tags);
    } catch (error) {
        alert('Failed to delete tag');
    } finally {
        deleting.value = null;
    }
}

function close() {
    emit('update:modelValue', false);
    editingTag.value = null;
    createError.value = '';
}
</script>
