(function(window) {
    'use strict';

    var dbName = 'core_app_offline';
    var storeName = 'records';
    var memoryPrefix = 'core_offline:';

    function uuid(prefix) {
        var body = 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
        return (prefix || 'offline') + '_' + Date.now().toString(36) + '_' + body;
    }

    function fallbackKey(key) {
        return memoryPrefix + key;
    }

    function openDb() {
        return new Promise(function(resolve, reject) {
            if (!window.indexedDB) {
                reject(new Error('IndexedDB no disponible'));
                return;
            }
            var request = window.indexedDB.open(dbName, 1);
            request.onupgradeneeded = function(event) {
                var db = event.target.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    db.createObjectStore(storeName, { keyPath: 'key' });
                }
            };
            request.onsuccess = function(event) { resolve(event.target.result); };
            request.onerror = function() { reject(request.error); };
        });
    }

    function put(key, value) {
        var record = { key: key, value: value, updated_at: Date.now() };
        return openDb().then(function(db) {
            return new Promise(function(resolve, reject) {
                var tx = db.transaction(storeName, 'readwrite');
                tx.objectStore(storeName).put(record);
                tx.oncomplete = function() { resolve(record); };
                tx.onerror = function() { reject(tx.error); };
            });
        }).catch(function() {
            window.localStorage.setItem(fallbackKey(key), JSON.stringify(record));
            return record;
        });
    }

    function get(key) {
        return openDb().then(function(db) {
            return new Promise(function(resolve, reject) {
                var tx = db.transaction(storeName, 'readonly');
                var request = tx.objectStore(storeName).get(key);
                request.onsuccess = function() { resolve(request.result ? request.result.value : null); };
                request.onerror = function() { reject(request.error); };
            });
        }).catch(function() {
            var raw = window.localStorage.getItem(fallbackKey(key));
            if (!raw) return null;
            try { return JSON.parse(raw).value; } catch (e) { return null; }
        });
    }

    function remove(key) {
        return openDb().then(function(db) {
            return new Promise(function(resolve, reject) {
                var tx = db.transaction(storeName, 'readwrite');
                tx.objectStore(storeName).delete(key);
                tx.oncomplete = function() { resolve(true); };
                tx.onerror = function() { reject(tx.error); };
            });
        }).catch(function() {
            window.localStorage.removeItem(fallbackKey(key));
            return true;
        });
    }

    function list(prefix) {
        prefix = prefix || '';
        return openDb().then(function(db) {
            return new Promise(function(resolve, reject) {
                var items = [];
                var tx = db.transaction(storeName, 'readonly');
                var request = tx.objectStore(storeName).openCursor();
                request.onsuccess = function(event) {
                    var cursor = event.target.result;
                    if (!cursor) {
                        resolve(items);
                        return;
                    }
                    if (!prefix || cursor.value.key.indexOf(prefix) === 0) {
                        items.push(cursor.value);
                    }
                    cursor.continue();
                };
                request.onerror = function() { reject(request.error); };
            });
        }).catch(function() {
            var items = [];
            for (var i = 0; i < window.localStorage.length; i++) {
                var key = window.localStorage.key(i);
                if (key && key.indexOf(fallbackKey(prefix)) === 0) {
                    try { items.push(JSON.parse(window.localStorage.getItem(key))); } catch (e) {}
                }
            }
            return items;
        });
    }

    window.CoreOffline = {
        uuid: uuid,
        put: put,
        get: get,
        remove: remove,
        list: list,
        isOnline: function() { return window.navigator.onLine; }
    };
})(window);
