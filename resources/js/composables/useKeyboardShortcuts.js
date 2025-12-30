import { onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../stores/useAppStore';

export function useKeyboardShortcuts() {
  const router = useRouter();
  const store = useAppStore();

  const handleKeyPress = (e) => {
    // Don't trigger shortcuts when typing in inputs, textareas, or contenteditable
    if (
      e.target.tagName === 'INPUT' ||
      e.target.tagName === 'TEXTAREA' ||
      e.target.isContentEditable ||
      e.target.closest('[contenteditable="true"]')
    ) {
      return;
    }

    // Check for modifier keys
    const isCtrl = e.ctrlKey || e.metaKey;
    const isShift = e.shiftKey;
    const key = e.key.toLowerCase();

    // Compose (C)
    if (key === 'c' && !isCtrl && !isShift) {
      e.preventDefault();
      if (store.isConfigured) {
        store.openCompose('new');
      }
      return;
    }

    // Keyboard shortcuts with Ctrl/Cmd
    if (isCtrl) {
      // Compose (Ctrl/Cmd + N)
      if (key === 'n') {
        e.preventDefault();
        if (store.isConfigured) {
          store.openCompose('new');
        }
        return;
      }

      // Search (Ctrl/Cmd + K)
      if (key === 'k') {
        e.preventDefault();
        // Focus search input if on inbox page
        const searchInput = document.querySelector('input[placeholder*="Search"]');
        if (searchInput) {
          searchInput.focus();
        }
        return;
      }

      // Settings (Ctrl/Cmd + ,)
      if (key === ',') {
        e.preventDefault();
        router.push('/settings');
        return;
      }
    }

    // Navigation shortcuts (only when not in input)
    if (!isCtrl && !isShift) {
      // Inbox (G then I)
      if (key === 'g') {
        e.preventDefault();
        const handler = (e2) => {
          if (e2.key.toLowerCase() === 'i') {
            router.push('/inbox');
            document.removeEventListener('keydown', handler);
          } else if (e2.key.toLowerCase() !== 'g') {
            document.removeEventListener('keydown', handler);
          }
        };
        document.addEventListener('keydown', handler);
        return;
      }

      // Sent (G then E)
      if (key === 'e' && router.currentRoute.value.path !== '/sent') {
        // Only if we're in a navigation context
        return;
      }
    }
  };

  onMounted(() => {
    document.addEventListener('keydown', handleKeyPress);
  });

  onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyPress);
  });

  return {};
}

