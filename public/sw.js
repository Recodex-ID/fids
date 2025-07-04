// Flight Information Display System - Service Worker
// Handles push notifications and offline caching

const CACHE_NAME = 'fids-v1';
const STATIC_CACHE_URLS = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/icons/flight-icon.png',
    '/icons/badge.png',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_CACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => cacheName !== CACHE_NAME)
                        .map((cacheName) => caches.delete(cacheName))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const data = event.data.json();
    const options = {
        body: data.body,
        icon: data.icon || '/icons/flight-icon.png',
        badge: data.badge || '/icons/badge.png',
        data: data.data || {},
        actions: data.actions || [],
        requireInteraction: data.requireInteraction || false,
        timestamp: data.timestamp,
        tag: `flight-${data.data?.flight_id || 'general'}`,
        renotify: true,
        vibrate: getVibrationPattern(data.data?.type),
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event - handle user interactions
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const action = event.action;
    const data = event.notification.data;

    event.waitUntil(
        handleNotificationAction(action, data)
    );
});

// Handle different notification actions
async function handleNotificationAction(action, data) {
    const flightId = data.flight_id;
    const notificationType = data.type;

    switch (action) {
        case 'view_gate':
            return openWindow(`/flights/${flightId}/gate-info`);
        
        case 'view_boarding_pass':
            return openWindow(`/passengers/boarding-pass`);
        
        case 'get_directions':
            return openWindow(`/airport/directions?gate=${data.gate}`);
        
        case 'view_details':
            return openWindow(`/flights/${flightId}`);
        
        case 'rebooking_options':
            return openWindow(`/passengers/rebooking`);
        
        case 'contact_support':
            return openWindow(`/support/contact`);
        
        case 'view_flight':
        default:
            return openWindow(`/flights/${flightId}`);
    }
}

// Open window with URL
async function openWindow(url) {
    const clients = await self.clients.matchAll({ type: 'window' });
    
    // Check if there's already a window/tab open with the target URL
    for (const client of clients) {
        if (client.url.includes(url.split('?')[0]) && 'focus' in client) {
            return client.focus();
        }
    }
    
    // If no existing window found, open a new one
    return self.clients.openWindow(url);
}

// Get vibration pattern based on notification type
function getVibrationPattern(type) {
    switch (type) {
        case 'boarding_call':
        case 'cancellation':
            return [200, 100, 200, 100, 200]; // Urgent pattern
        
        case 'gate_change':
        case 'delay':
            return [200, 100, 200]; // Important pattern
        
        default:
            return [100]; // Default pattern
    }
}

// Fetch event - serve cached content when offline
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            })
            .catch(() => {
                // Return offline page for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match('/offline.html');
                }
            })
    );
});

// Background sync for failed notification actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'notification-action-sync') {
        event.waitUntil(syncFailedActions());
    }
});

async function syncFailedActions() {
    // Sync any failed notification actions when back online
    console.log('Syncing failed notification actions...');
}