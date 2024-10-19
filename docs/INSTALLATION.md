# Installation

**1.** Create a [Google Firebase](https://console.firebase.google.com/) account and start a new project.

![Firebase Setup - Step 1](images/firebase-step-1.png)

**2.** Navigate to **Project Overview > Project Settings**.

![Firebase Setup - Step 2](images/firebase-step-2.png)

**3.1.** Under the **General** tab, select the option to create a **Web App**.

![Firebase Setup - Step 3.1](images/firebase-step-3-1.png)

**3.2.** Name your app and click **Register App**.

![Firebase Setup - Step 3.2](images/firebase-step-3-2.png)

**3.3.** Copy the highlighted information from your **App’s Firebase Configuration** and paste it into the **Modules Configuration** as follows: `apiKey` = `Web API Key`, `messagingSenderId` = `Sender ID`, and `appId` = `Web App ID`.

![Firebase Setup - Step 3.3](images/firebase-step-3-3.png)

**4.1.** In the **Cloud Messaging** tab, select "**Generate Key Pair**".

![Firebase Setup - Step 4.1](images/firebase-step-4-1.png)

**4.2.** Copy the highlighted information from your **Web Push Certificates** and paste it into the **Modules Configuration** as follows: `Key pair` = `Key Pair from the Web Push certificates`

![Firebase Setup - Step 4.2](images/firebase-step-4-2.png)

**5.1.** In the **Cloud Messaging** tab, under **Firebase Cloud Messaging API**, click on "**Manage Service Accounts**".

![Firebase Setup - Step 5.1](images/firebase-step-5-1.png)

**5.2.** Under **Service Accounts**, select your account.\
_**Note:** In some instances, it may take longer for Google to generate the account automatically. In rare cases where the account is not generated, please create one by clicking on "Create Service Account"._

![Firebase Setup - Step 5.2](images/firebase-step-5-2.png)

**5.3.** Switch to the **Keys** tab and click **Create New Key**.

![Firebase Setup - Step 5.3](images/firebase-step-5-3.png)

**5.4.** Choose **JSON** as the Key type and click **Create**.

![Firebase Setup - Step 5.4](images/firebase-step-5-4.png)

**6.** Open your Service Account key JSON file, and copy its contents into the `Service Account (JSON file)` field in the **Modules Configuration**.

![Firebase Setup - Step 6](images/firebase-step-6.png)

**7.** When you are finished, click **Save** on the **Modules Configuration** page.

![Firebase Setup - Step 7](images/firebase-step-7.png)

----

## Custom CSP Configuration 

If you have customized your [CSP header](https://docs.humhub.org/docs/admin/security#web-security-configuration), make sure to allow the following URLs:

```PHP
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
