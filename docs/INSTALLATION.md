Installation
============

1. Create an Google Firebase Account [Google Firebase Messaging](https://firebase.google.com/)
2. Add a new project in the Firebase Console
3. Go to: `Project Overview` -> `Project settings` -> `Cloud Messaging`
4. Copy the `Sender Id` number and paste it into the `Sender ID` field in the HumHub module configuration
4. Click on `Manage Service Accounts`  and select the `firebase` Service Account or create a new one
5. In the Service Acccount, click on the `Keys` tab, Click `Add Key` -> `Create New Key` -> `Key type: JSON`
6. Download the created JSON key file, open it, copy it and paste the file content into the `Service Account (JSON file)` file in the HumHub module configuration

## Custom CSP Configuration 

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
