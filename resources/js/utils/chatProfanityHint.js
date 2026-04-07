/**
 * Client-side hint only — server enforces moderation. Keep roughly in sync with config/profanity.php censored list.
 */
const HINT_WORDS = ['damn', 'hell', 'crap', 'stupid', 'idiot', 'moron'];

export function chatProfanityHint(text) {
    const t = String(text || '').toLowerCase();
    for (const w of HINT_WORDS) {
        const re = new RegExp(`\\b${w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'i');
        if (re.test(t)) {
            return true;
        }
    }
    return false;
}
