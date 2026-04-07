import { ref, watch, onMounted, onUnmounted } from 'vue';

/**
 * Single poll for /notifications/poll — bell count (non-chat) + messages badge (chat).
 */
export function useHeaderNotificationPoll(page) {
    const unreadNotifications = ref(page.props.unread_notifications_count ?? 0);
    const unreadChatMessages = ref(page.props.unread_chat_messages_count ?? 0);

    let timer = null;

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
        if (document.visibilityState !== 'visible') {
            return;
        }
        try {
            const { data } = await window.axios.get('/notifications/poll');
            unreadNotifications.value = data.unread_count ?? 0;
            unreadChatMessages.value = data.unread_chat_count ?? 0;
        } catch {
            /* silent */
        }
    }

    onMounted(() => {
        if (!page.props.auth?.user) {
            return;
        }
        poll();
        timer = setInterval(poll, 45000);
    });

    onUnmounted(() => {
        if (timer) {
            clearInterval(timer);
        }
    });

    return { unreadNotifications, unreadChatMessages };
}
