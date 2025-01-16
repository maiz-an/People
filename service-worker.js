// service-worker.js
self.addEventListener('push', function(event) {
    const data = event.data.json();
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: 'people.png', 
        data: { url: data.url }
    });
});
navigator.serviceWorker.ready.then((registration) => {
    console.log("Service Worker ready:", registration);
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    if (event.notification.data && event.notification.data.url) {
        clients.openWindow(event.notification.data.url);
    }
});

// Public Key: BC-7WsmMZx3cQ_wfByFsOq4nD8uIJu7Nz-CuAVbSgT8dAQRATGqBp_w9Mp5pTFOCTlkTDL0VuSfKCoMcev7K14U
// Private Key: BhhQq-SapNlz6otMb-jVWMRe6qjAb_IPo1G9uGiiYkA

const publicVapidKey = 'BC-7WsmMZx3cQ_wfByFsOq4nD8uIJu7Nz-CuAVbSgT8dAQRATGqBp_w9Mp5pTFOCTlkTDL0VuSfKCoMcev7K14U'; // public key

if ('serviceWorker' in navigator && 'PushManager' in window) {
    navigator.serviceWorker.register('/service-worker.js')
        .then(registration => {
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicVapidKey)
            });
        })
        .then(subscription => {
            return fetch('/save_subscription.php', {
                method: 'POST',
                body: JSON.stringify(subscription),
                headers: { 'Content-Type': 'application/json' }
            });
        })
        .catch(error => console.error('Push subscription failed:', error));
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}
