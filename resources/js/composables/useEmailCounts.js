import { computed } from 'vue';
import { useAppStore } from '../stores/useAppStore';

// Sidebar unread counts now come from GET /mailboxes (one aggregated
// query server-side) instead of fetching email lists and counting
// client-side. This composable stays as a thin wrapper so existing
// callers keep working.

// Debounce timer for fetchCounts
let fetchCountsTimer = null;

export function useEmailCounts() {
  const store = useAppStore();

  const fetchCounts = (immediate = false) => {
    if (fetchCountsTimer) {
      clearTimeout(fetchCountsTimer);
      fetchCountsTimer = null;
    }

    if (immediate) {
      store.loadMailboxes();
      return;
    }

    fetchCountsTimer = setTimeout(() => {
      store.loadMailboxes();
    }, 500); // 500ms debounce
  };

  const inboxUnreadCount = computed(() => store.inboxUnreadCount);

  return {
    inboxUnreadCount,
    fetchCounts
  };
}
