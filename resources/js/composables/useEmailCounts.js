import { ref, computed } from 'vue';
import api from '../utils/api';

const inboxUnreadCount = ref(0);
const sentCount = ref(0);
const trashCount = ref(0);

export function useEmailCounts() {
  const fetchCounts = async () => {
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
    }
  };

  return {
    inboxUnreadCount,
    sentCount,
    trashCount,
    fetchCounts
  };
}

