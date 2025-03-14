humhub.module('firebase', function (module, require, $) {
    let messaging;

    const defaultConfig = {
        statusTexts: {
            granted: '',
            denied: '',
            default: '',
            not_supported: ''
        }
    };

    module.config = {...defaultConfig, ...module.config};

    const init = function () {
        if (!firebase.apps.length) {
            firebase.initializeApp({
                messagingSenderId: module.senderId(),
                projectId: module.config.projectId,
                apiKey: module.config.apiKey,
                appId: module.config.appId,
            });
            module.messaging = firebase.messaging();

            module.messaging.onMessage((payload) => {
            });
        }

        initDom();
    };

    const initDom = function() {
        module.updatePushStatus();

        $(document).off('click', '#enablePushBtn');

        $(document).on('click', '#enablePushBtn', () => {
            Notification.requestPermission().then((permission) => {
                module.updatePushStatus();
            });
        });
    };

    const afterServiceWorkerRegistration = function (registration) {
        const that = module;
        module.messaging.swRegistration = registration;

        Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') {
                that.updatePushStatus();
                return;
            }

            that.messaging.getToken({
                vapidKey: module.config.vapidKey,
                serviceWorkerRegistration: registration,
            }).then(function (currentToken) {
                if (currentToken) {
                    that.sendTokenToServer(currentToken);
                } else {
                    that.deleteTokenLocalStore();
                }
                that.updatePushStatus();
            }).catch(function (err) {
                that.deleteTokenLocalStore();
                that.updatePushStatus();
            });
        }).catch(function (err) {
            that.updatePushStatus();
        });
    };

    const sendTokenToServer = function (token) {
        if (!module.isTokenSentToServer(token)) {
            $.ajax({
                method: "POST",
                url: module.tokenUpdateUrl(),
                data: {token: token},
                success: function (data) {
                    module.setTokenLocalStore(token);
                    module.updatePushStatus();
                }
            });
        }
    };

    const isTokenSentToServer = function (token) {
        return (module.getTokenLocalStore() === token);
    };

    const deleteTokenLocalStore = function () {
        window.localStorage.removeItem('fcmPushToken_' + module.senderId());
    };

    const setTokenLocalStore = function (token) {
        const item = {
            value: token,
            expiry: (Date.now() / 1000) + (24 * 60 * 60),
        };
        window.localStorage.setItem('fcmPushToken_' + module.senderId(), JSON.stringify(item));
    };

    const getTokenLocalStore = function () {
        const itemStr = window.localStorage.getItem('fcmPushToken_' + module.senderId());

        if (!itemStr) {
            return null;
        }
        const item = JSON.parse(itemStr);
        const now = (Date.now() / 1000);
        if (now > item.expiry) {
            module.deleteTokenLocalStore();
            return null;
        }
        return item.value;
    };

    const tokenUpdateUrl = function () {
        return module.config.tokenUpdateUrl;
    };

    const senderId = function () {
        return module.config.senderId;
    };

    const isGranted = function() {
        return ("Notification" in window) && Notification.permission === "granted";
    };

    const isDenied = function() {
        return ("Notification" in window) && Notification.permission === "denied";
    };

    const isDefault = function() {
        return ("Notification" in window) && Notification.permission === "default";
    };

    const updatePushStatus = function() {
        const $status = $('#pushNotificationStatus');
        if (!$status.length) {
            return;
        }

        if (!("Notification" in window)) {
            $status.html(module.config.statusTexts['not-supported']);
            return;
        }

        const status = Notification.permission;
        const statusMessage = module.config.statusTexts[status] || 
            module.config.statusTexts['default'];
        $status.html(statusMessage);
    };

    module.export({
        init,
        initDom,
        isTokenSentToServer,
        sendTokenToServer,
        afterServiceWorkerRegistration,

        senderId,
        tokenUpdateUrl,

        setTokenLocalStore,
        getTokenLocalStore,
        deleteTokenLocalStore,

        updatePushStatus,
        isGranted,
        isDenied,
        isDefault
    });

    $(document).on('humhub:ready', function() {
        module.initDom();
    });
});

function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.afterServiceWorkerRegistration(registration);
}
