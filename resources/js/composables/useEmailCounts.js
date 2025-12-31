import { ref, computed } from 'vue';
import api from '../utils/api';

const inboxUnreadCount = ref(0);
const sentCount = ref(0);
const trashCount = ref(0);

// Debounce timer for fetchCounts
let fetchCountsTimer = null;
let isFetchingCounts = false;

export function useEmailCounts() {
  const fetchCounts = async (immediate = false) => {
    // Clear any pending debounced call
    if (fetchCountsTimer) {
      clearTimeout(fetchCountsTimer);
      fetchCountsTimer = null;
    }

    // If immediate, fetch right away (but still check if already fetching)
    if (immediate) {
      if (isFetchingCounts) return;
      isFetchingCounts = true;
      try {
        // Fetch all emails and filter by status
        const allResponse = await api.getEmails(1, 'all');
        const allEmails = allResponse.data.data || [];
        
        // Count unread in inbox (status = 'inbox' or not 'sent' and not 'trash')
        const inboxEmails = allEmails.filter(email => 
          email.status === 'inbox' || (email.status !== 'sent' && email.status !== 'trash')
        );
        inboxUnreadCount.value = inboxEmails.filter(email => !email.is_read).length;

        // Count sent
        sentCount.value = allEmails.filter(email => email.status === 'sent').length;

        // Count trash
        trashCount.value = allEmails.filter(email => email.status === 'trash').length;
      } catch (e) {
        console.error('Failed to fetch email counts', e);
      } finally {
        isFetchingCounts = false;
      }
      return;
    }

    // Otherwise, debounce the call
    fetchCountsTimer = setTimeout(async () => {
      if (isFetchingCounts) return;
      isFetchingCounts = true;
      try {
        // Fetch all emails and filter by status
        const allResponse = await api.getEmails(1, 'all');
        const allEmails = allResponse.data.data || [];
        
        // Count unread in inbox (status = 'inbox' or not 'sent' and not 'trash')
        const inboxEmails = allEmails.filter(email => 
          email.status === 'inbox' || (email.status !== 'sent' && email.status !== 'trash')
        );
        inboxUnreadCount.value = inboxEmails.filter(email => !email.is_read).length;

        // Count sent
        sentCount.value = allEmails.filter(email => email.status === 'sent').length;

        // Count trash
        trashCount.value = allEmails.filter(email => email.status === 'trash').length;
      } catch (e) {
        console.error('Failed to fetch email counts', e);
      } finally {
        isFetchingCounts = false;
      }
    }, 500); // 500ms debounce
  };

  return {
    inboxUnreadCount,
    sentCount,
    trashCount,
    fetchCounts
  };
}

