humhub.module('firebase', function (module, require, $) {
    let messaging;

    const init = function () {
        if (!firebase.apps.length) {
            firebase.initializeApp({messagingSenderId: this.senderId()});
            this.messaging = firebase.messaging();

            this.messaging.onMessage(function (payload) {
                module.log.info("Received FCM Push Notification", payload);
            });

            // Callback fired if Instance ID token is updated.
            this.messaging.onTokenRefresh(function () {
                this.messaging.getToken().then(function (refreshedToken) {
                    this.deleteTokenLocalStore();
                    this.sendTokenToServer(refreshedToken);
                }).catch(function (err) {
                    console.log('Unable to retrieve refreshed token ', err);
                });
            });
        }
    };

    const getNotificationPermissionContent = function () {
        if (!("Notification" in window)) {
            return 'This browser does not support notifications.';
        }
        console.log('Notification.permission:', Notification.permission);
        return Notification.permission === "granted"
            ? 'Push Notifications are active on this browser.<br>You can disable it in browser settings for this site.'
            : 'You have blocked Push Notifications.<br>' +
            (Notification.permission === 'denied'
                ? 'You can enable it in browser settings for this site.'
                : '<a href="#" id="enablePushBtn"><i class="fa fa-unlock"></i> Click here to enable</a>'); // 'default'
    }

    const showNotificationPermissionWindow = function () {
        function handlePermission(permission) {
            // set the button to shown or hidden, depending on what the user answers
            addPushNotificationPermissionsInfo(permission, true);
        }

        // Let's check if the browser supports notifications
        if (!("Notification" in window)) {
            console.log("This browser does not support notifications.");
        } else {
            Notification.requestPermission().then((permission) => {
                handlePermission(permission);
            });
        }
    }

    const addPushNotificationPermissionsInfo = function (permission, rewrite = false) {
        if (rewrite) {
            const contentContainer = document.getElementById('notificationPermissions');
            contentContainer.innerHTML = getNotificationPermissionContent()
        } else {
            const content = '<div class="panel panel-default panel-pn-permissions"><div class="panel-body" id="notificationPermissions">' + getNotificationPermissionContent() + '</div></div>';
            $('.layout-sidebar-container').prepend($(content));
        }

        $('#enablePushBtn').on('click', showNotificationPermissionWindow);
    }

    const afterServiceWorkerRegistration = function (registration) {
        //console.log("After Service Worker Registration");
        //console.log(registration);

        const that = this;

        this.messaging.useServiceWorker(registration);

        // Request for permission
        this.messaging.requestPermission().then(function () {
            //console.log('Notification permission granted.');
            addPushNotificationPermissionsInfo('granted');

            that.messaging.getToken().then(function (currentToken) {
                if (currentToken) {
                    //console.log('Token: ' + currentToken);
                    that.sendTokenToServer(currentToken);
                } else {
                    module.log.info('No Instance ID token available. Request permission to generate one.');
                    that.deleteTokenLocalStore();
                }
            }).catch(function (err) {
                module.log.error('An error occurred while retrieving token. ', err);
                that.deleteTokenLocalStore();
            });
        }).catch(function (err) {
            module.log.info('Could not get Push Notification permission!', err);
            addPushNotificationPermissionsInfo('denied');
        });
    };

    // Send the Instance ID token your application server, so that it can:
    // - send messages back to this app
    // - subscribe/unsubscribe the token from topics
    const sendTokenToServer = function (token) {
        const that = this;
        if (!that.isTokenSentToServer(token)) {
            module.log.info("Send FCM Push Token to Server");
            $.ajax({
                method: "POST",
                url: that.tokenUpdateUrl(),
                data: {token: token},
                success: function (data) {
                    that.setTokenLocalStore(token);
                }
            });
        } else {
            //console.log('Token already sent to server so won\'t send it again unless it changes');
        }
    };

    const isTokenSentToServer = function (token) {
        return (this.getTokenLocalStore() === token);
    };

    const deleteTokenLocalStore = function () {
        window.localStorage.removeItem('fcmPushToken_' + this.senderId())
    };

    const setTokenLocalStore = function (token) {
        const item = {
            value: token,
            expiry: (Date.now() / 1000) + (24 * 60 * 60),
        };
        window.localStorage.setItem('fcmPushToken_' + this.senderId(), JSON.stringify(item))
    };

    const getTokenLocalStore = function () {
        const itemStr = window.localStorage.getItem('fcmPushToken_' + this.senderId())

        // if the item doesn't exist, return null
        if (!itemStr) {
            return null
        }
        const item = JSON.parse(itemStr)
        const now = (Date.now() / 1000)
        if (now > item.expiry) {
            this.deleteTokenLocalStore();
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

    module.export({
        init: init,

        isTokenSentToServer: isTokenSentToServer,
        sendTokenToServer: sendTokenToServer,
        afterServiceWorkerRegistration: afterServiceWorkerRegistration,

        // Config Vars
        senderId: senderId,
        tokenUpdateUrl: tokenUpdateUrl,

        // LocalStore Helper
        setTokenLocalStore: setTokenLocalStore,
        getTokenLocalStore: getTokenLocalStore,
        deleteTokenLocalStore: deleteTokenLocalStore,
    });
});

function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.afterServiceWorkerRegistration(registration);
}
