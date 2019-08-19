<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\fcmPush;


use humhub\modules\web\pwa\controllers\ManifestController;
use humhub\modules\web\pwa\controllers\ServiceWorkerController;
use Yii;

class Events
{

    public static function onManifestControllerInit($event)
    {
        /** @var ManifestController $controller */
        $controller = $event->sender;
        $controller->manifest['gcm_sender_id'] = (string) 103953800507;
    }

    public static function onServiceWorkerControllerInit($event)
    {
        /** @var ServiceWorkerController $controller */
        $controller = $event->sender;

        $controller->additionalJs .= <<<JS


            // Give the service worker access to Firebase Messaging.
            importScripts('https://www.gstatic.com/firebasejs/6.3.3/firebase-app.js')
            importScripts('https://www.gstatic.com/firebasejs/6.3.3/firebase-messaging.js')
            
            // Initialize the Firebase app in the service worker by passing in the messagingSenderId.
            var config = {
                messagingSenderId: "744948498049"
            };
            firebase.initializeApp(config);
            
            // Retrieve an instance of Firebase Data Messaging so that it can handle background messages.
            const messaging = firebase.messaging()
            messaging.setBackgroundMessageHandler(function(payload) {
              const notificationTitle = 'Data Message Title';
              const notificationOptions = {
                body: 'Data Message body',
                icon: 'alarm.png'
              };
              
              return self.registration.showNotification(notificationTitle,
                  notificationOptions);
            });
JS;
    }

    public static function onLayoutaddonInit($event) {

        $view = Yii::$app->view;

        $view->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js');
        $view->registerJsFile('https://www.gstatic.com/firebasejs/6.3.3/firebase-app.js');
        $view->registerJsFile('https://www.gstatic.com/firebasejs/6.3.3/firebase-messaging.js');

        $script <<<JS
            // Initialize the Firebase app by passing in the messagingSenderId
            var config = {
              messagingSenderId: "744948498049"
            };
            firebase.initializeApp(config);
            
            const messaging = firebase.messaging();
            
            navigator.serviceWorker.addEventListener('install', event => {
                console.log('skip waiting GOT EVEnt!!!!!!!!');
            });
            
            function afterServiceWorkerRegistration(registration) {
                messaging.useServiceWorker(registration);
                    
                // Request for permission
                messaging.requestPermission()
                .then(function() {
                  console.log('Notification permission granted.');
                  // TODO(developer): Retrieve an Instance ID token for use with FCM.
                  messaging.getToken()
                  .then(function(currentToken) {
                    if (currentToken) {
                      console.log('Token: ' + currentToken)
                      sendTokenToServer(currentToken);
                    } else {
                      console.log('No Instance ID token available. Request permission to generate one.');
                      setTokenSentToServer(false);
                    }
                  })
                  .catch(function(err) {
                    console.log('An error occurred while retrieving token. ', err);
                    setTokenSentToServer(false);
                  });
                })
                .catch(function(err) {
                  console.log('Unable to get permission to notify.', err);
                });
            }
            
            // Handle incoming messages
            messaging.onMessage(function(payload) {
              console.log("Notification received: ", payload);
              toastr["info"](payload.notification.body, payload.notification.title);
            });
            
            // Callback fired if Instance ID token is updated.
            messaging.onTokenRefresh(function() {
              messaging.getToken()
              .then(function(refreshedToken) {
                console.log('Token refreshed.');
                // Indicate that the new Instance ID token has not yet been sent 
                // to the app server.
                setTokenSentToServer(false);
                // Send Instance ID token to app server.
                sendTokenToServer(refreshedToken);
              })
              .catch(function(err) {
                console.log('Unable to retrieve refreshed token ', err);
              });
            });
            
            // Send the Instance ID token your application server, so that it can:
            // - send messages back to this app
            // - subscribe/unsubscribe the token from topics
            function sendTokenToServer(currentToken) {
              if (!isTokenSentToServer()) {
                console.log('Sending token to server...');
                // TODO(developer): Send the current token to your server.
                setTokenSentToServer(true);
              } else {
                console.log('Token already sent to server so won\'t send it again ' +
                    'unless it changes');
              }
            }
            
            function isTokenSentToServer() {
              return window.localStorage.getItem('sentToServer') == 1;
            }
            
            function setTokenSentToServer(sent) {
              window.localStorage.setItem('sentToServer', sent ? 1 : 0);
            }
JS;

        $view->registerJs($script);

    }

}