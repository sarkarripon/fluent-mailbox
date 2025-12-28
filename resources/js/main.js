import { createApp } from 'vue';
import App from './App.vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import '../css/style.css';

// Placeholder views
import Inbox from './views/Inbox.vue';
import Sent from './views/Sent.vue';
import Settings from './views/Settings.vue';
import EmailDetail from './views/EmailDetail.vue';
import Trash from './views/Trash.vue';

const routes = [
    { path: '/', redirect: '/inbox' },
    { path: '/inbox', component: Inbox, name: 'Inbox' },
    { path: '/sent', component: Sent, name: 'Sent' },
    { path: '/trash', component: Trash, name: 'Trash' },
    { path: '/settings', component: Settings, name: 'Settings' },
    { path: '/emails/:id', component: EmailDetail, name: 'EmailDetail' },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

const app = createApp(App);
app.use(router);
app.mount('#fluent-mailbox-app');
