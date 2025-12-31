import axios from 'axios';

// Defined in functions.php via wp_localize_script
const config = window.FluentMailbox || { root: '', nonce: '' };

const api = axios.create({
    baseURL: config.root,
    headers: {
        'X-WP-Nonce': config.nonce
    }
});

export default {
    getUsers() {
        return api.get('/users');
    },
    getEmails(page = 1, status = 'all') {
        return api.get('/emails', { params: { page, status } });
    },
    saveConnection(data) {
        return api.post('/settings/save-connection', data);
    },
    fetchEmails() {
        return api.post('/emails/fetch');
    },
    verifyCredentials(data) {
        return api.post('/settings/verify', data);
    },
    setupInbound() {
        return api.post('/settings/setup-inbound');
    },
    disconnect() {
        return api.post('/settings/disconnect');
    },
    sendEmail(data) {
        return api.post('/emails', data);
    },
    getSettings() {
        return api.get('/settings');
    },
    saveSettings(data) {
        return api.post('/settings', data);
    },
    getEmail(id) {
        return api.get(`/emails/${id}`);
    },
    getEmailWorkflow(id) {
        return api.get(`/emails/${id}/workflow`);
    },
    updateEmailWorkflow(id, data) {
        return api.put(`/emails/${id}/workflow`, data);
    },
    getEmailNotes(id) {
        return api.get(`/emails/${id}/notes`);
    },
    addEmailNote(id, data) {
        return api.post(`/emails/${id}/notes`, data);
    },
    deleteNote(id) {
        return api.delete(`/notes/${id}`);
    },
    deleteEmail(id, params = {}) {
        return api.delete(`/emails/${id}`, { params });
    },
    updateEmail(id, data) {
        return api.put(`/emails/${id}`, data);
    },
    emptyTrash() {
        return api.delete('/emails/trash');
    },
    uploadAttachment(file) {
        const formData = new FormData();
        formData.append('file', file);
        return api.post('/attachments/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },
    getDrafts(page = 1) {
        return api.get('/drafts', { params: { page } });
    },
    saveDraft(data) {
        return api.post('/drafts', data);
    },
    deleteDraft(id) {
        return api.delete(`/drafts/${id}`);
    },
    getSignatures() {
        return api.get('/signatures');
    },
    saveSignature(data) {
        return api.post('/signatures', data);
    },
    deleteSignature(id) {
        return api.delete(`/signatures/${id}`);
    },
    getTemplates() {
        return api.get('/templates');
    },
    saveTemplate(data) {
        return api.post('/templates', data);
    },
    deleteTemplate(id) {
        return api.delete(`/templates/${id}`);
    },
    simulateWebhook(type = 'content') {
        return api.post('/settings/simulate-webhook', { type });
    },
    getDebugLog() {
        return api.get('/settings/debug-log');
    },
    cleanDebugLog() {
        return api.post('/settings/debug-log/clean');
    }
};
