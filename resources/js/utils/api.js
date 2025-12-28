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
    getEmails(page = 1, status = 'all') {
        return api.get('/emails', { params: { page, status } });
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
    deleteEmail(id) {
        return api.delete(`/emails/${id}`);
    }
};
