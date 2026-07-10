/**
 * Small IndexedDB key-value store shared between the page and the service
 * worker. Loaded in both contexts: as an asset before humhub.firebase.js (see
 * FcmPushAsset), and prepended to the service worker addon before
 * humhub.firebase.worker.js (see ServiceWorkerService).
 *
 * IndexedDB rather than the Cache API: iOS evicts/partitions Cache API storage
 * of home-screen web apps (observed: receiving a push wiped it), while
 * IndexedDB persists.
 */
const fcmPushStore = (function () {
    function db() {
        return new Promise(function (resolve, reject) {
            const req = indexedDB.open('fcm-push-store', 1);
            req.onupgradeneeded = function () { req.result.createObjectStore('kv'); };
            req.onsuccess = function () { resolve(req.result); };
            req.onerror = function () { reject(req.error); };
        });
    }

    return {
        get: function (key) {
            return db().then(function (db) {
                return new Promise(function (resolve, reject) {
                    const rq = db.transaction('kv', 'readonly').objectStore('kv').get(key);
                    rq.onsuccess = function () { resolve(rq.result); };
                    rq.onerror = function () { reject(rq.error); };
                });
            });
        },
        set: function (key, value) {
            return db().then(function (db) {
                return new Promise(function (resolve, reject) {
                    const tx = db.transaction('kv', 'readwrite');
                    tx.objectStore('kv').put(value, key);
                    tx.oncomplete = function () { resolve(); };
                    tx.onerror = function () { reject(tx.error); };
                });
            });
        },
    };
})();
