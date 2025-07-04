import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Pusher for Laravel Echo
window.Pusher = Pusher;

// Create Echo instance with Reverb configuration
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Auth endpoint for private channels
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    },
});

export default echo;

// Global window types for TypeScript
declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: typeof echo;
    }
}

// Make Echo available globally
window.Echo = echo;