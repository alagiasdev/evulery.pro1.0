/**
 * Service Worker — Push Notifications
 */
self.addEventListener('push', function (event) {
    var data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'Evulery', body: event.data ? event.data.text() : '' };
    }

    var options = {
        body: data.body || '',
        icon: '/assets/img/logo-icon.png',
        badge: '/assets/img/badge-72.png',
        data: { url: data.url || '/dashboard' },
        tag: data.tag || 'evulery-notification',
        renotify: true
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Evulery', options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/dashboard';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            // Focus existing tab if open
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url.indexOf('/dashboard') !== -1 && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            // Open new window
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
