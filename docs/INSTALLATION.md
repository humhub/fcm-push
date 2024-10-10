Installation
============

1. Create a Google Firebase Account [Google Firebase Messaging](https://firebase.google.com/)
2. Add a new project in the Firebase Console
3. Go to: `Project Overview` -> `Project settings` -> `General`
4. Click the "Add app" button and select the "</>" icon (Web platform)
5. Give your app a nickname and click the "Register app" button
6. Firebase will provide you with a configuration object
7. Copy the `messagingSenderId` into the `Sender ID` field in HumHub
8. Copy the `projectId` into the `Project ID` field in HumHub
9. Copy the `apiKey` into the `API Key` field in HumHub
10. Copy the `appId` into the `Project ID` field in HumHub
11. Go to: `Cloud Messaging` tab -> `Web configuration` section
12. Generate a new key pair if you haven't already and copy the `Key pair` into the `VAPID key` field in HumHub
13. Click on `Manage Service Accounts`  and select the `firebase` Service Account or create a new one
14. In the Service Acccount, click on the `Keys` tab, Click `Add Key` -> `Create New Key` -> `Key type: JSON`
15. Download the created JSON key file, open it, copy it and paste the file content into the `Service Account (JSON file)` file in the HumHub module configuration

## Custom CSP Configuration 

In case you have customized your [csp header](https://docs.humhub.org/docs/admin/security#web-security-configuration).
Make sure to allow the following urls:

```
"script-src" => [
    "self" => true,
    "allow" => [
        "https://www.gstatic.com/firebasejs/10.6.0-20231107192534/firebase-app.js",
        "https://www.gstatic.com/firebasejs/10.6.0-20231107192534/firebase-messaging.js"
    ]
],
 "connect-src" => [
    "self" => true,
    "allow" => [
        "https://fcm.googleapis.com/"
    ]
],
``` 
