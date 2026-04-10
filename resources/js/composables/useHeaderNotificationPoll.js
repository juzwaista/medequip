import { ref, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Single poll for /notifications/poll — bell count (non-chat) + messages badge (chat).
 * Also triggers a partial reload of the current page if counts change, 
 * to keep the UI in sync without manual refresh.
 */
export function useHeaderNotificationPoll(page) {
    const unreadNotifications = ref(page.props.unread_notifications_count ?? 0);
    const unreadChatMessages = ref(page.props.unread_chat_messages_count ?? 0);

    let timer = null;

    // Monitor for changes and trigger a partial data reload
    watch([unreadNotifications, unreadChatMessages], (newVals, oldVals) => {
        const [newNotif, newChat] = newVals;
        const [oldNotif, oldChat] = oldVals;

        // If counts increased, or transitioned from 0, refresh page data
        if (newNotif > oldNotif || newChat > oldChat) {
            router.reload({ 
                preserveScroll: true, 
                preserveState: true,
                only: ['flash', 'errors', 'auth', 'distributor', 'order', 'orders', 'inventory', 'stats'] 
            });
        }
    });

    watch(
        () => page.props.unread_notifications_count,
        (v) => {
            if (typeof v === 'number') {
                unreadNotifications.value = v;
            }
        }
    );

    watch(
        () => page.props.unread_chat_messages_count,
        (v) => {
            if (typeof v === 'number') {
                unreadChatMessages.value = v;
            }
        }
    );

    async function poll() {
        if (document.visibilityState !== 'visible' || !page.props.auth?.user) {
            return;
        }
        try {
            const { data } = await window.axios.get('/notifications/poll');
            
            // Only update if changed to avoid unnecessary re-renders
            if (unreadNotifications.value !== data.unread_count) {
                unreadNotifications.value = data.unread_count ?? 0;
            }
            if (unreadChatMessages.value !== data.unread_chat_count) {
                unreadChatMessages.value = data.unread_chat_count ?? 0;
            }
        } catch {
            /* silent */
        }
    }

    onMounted(() => {
        if (!page.props.auth?.user) {
            return;
        }
        poll();
        // Faster polling (15s instead of 45s) for more "real-time" feel
        timer = setInterval(poll, 15000);
    });

    onUnmounted(() => {
        if (timer) {
            clearInterval(timer);
        }
    });

    return { unreadNotifications, unreadChatMessages };
}
