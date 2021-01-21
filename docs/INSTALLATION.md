Installation
============

1. Create an Google Firebase Account [Google Firebase Messaging](https://firebase.google.com/)
2. Add a new project in the Firebase Console
3. Install and enable this module
4. Copy **Project ID**, **Cloud messaging Sender ID** & **Server key** from Google Firebase Console into the module configuration.
5. Enable "Mobile" column in the Notification settings

## CSP

In case you have customized your [csp header](https://docs.humhub.org/docs/admin/security#web-security-configuration).
Make sure to allow the following urls:

```
"script-src" => [
    "self" => true,
    "allow" => [
        "https://www.gstatic.com/firebasejs/6.3.3/firebase-app.js",
        "https://www.gstatic.com/firebasejs/6.3.3/firebase-messaging.js"
    ]
],
 "connect-src" => [
    "self" => true,
    "allow" => [
        "https://fcm.googleapis.com/"
    ]
],
``` 