import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import { useAppStore } from './stores/useAppStore';
import '../css/style.css';

// Remove all elements before the Fluent Mailbox app container
document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('fluent-mailbox-app');
    const container = document.getElementById('wpbody-content');

    if (!app || !container) return;

    // Find the direct child of wpbody-content that contains the app
    let target = app.closest('#wpbody-content > div');

    if (!target) return;

    // Remove all elements before the target
    let prev = target.previousElementSibling;
    while (prev) {
        const toRemove = prev;
        prev = prev.previousElementSibling;
        toRemove.remove();
    }
});

// Placeholder views
import Inbox from './views/Inbox.vue';
import Sent from './views/Sent.vue';
import Settings from './views/Settings.vue';
import EmailDetail from './views/EmailDetail.vue';
import Trash from './views/Trash.vue';
import Drafts from './views/Drafts.vue';

const routes = [
    { path: '/', redirect: '/inbox' },
    { path: '/inbox', component: Inbox, name: 'Inbox' },
    { path: '/sent', component: Sent, name: 'Sent' },
    { path: '/drafts', component: Drafts, name: 'Drafts' },
    { path: '/trash', component: Trash, name: 'Trash' },
    { path: '/settings', component: Settings, name: 'Settings' },
    { path: '/emails/:id', component: EmailDetail, name: 'EmailDetail' },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);

// Register global directives
import clickOutside from './directives/clickOutside';
app.directive('click-outside', clickOutside);

router.beforeEach((to, from, next) => {
    const store = useAppStore();

    if (to.path !== '/settings' && !store.isConfigured) {
        next('/settings');
    } else {
        next();
    }
});

app.use(router);

// Check for initial route from data attribute and navigate if needed
const appElement = document.getElementById('fluent-mailbox-app');
if (appElement) {
    const initialRoute = appElement.getAttribute('data-initial-route');
    if (initialRoute) {
        // Navigate to the initial route after a short delay to ensure router is ready
        router.isReady().then(() => {
            if (router.currentRoute.value.path !== initialRoute) {
                router.push(initialRoute);
            }
        });
    }
}

app.mount('#fluent-mailbox-app');
