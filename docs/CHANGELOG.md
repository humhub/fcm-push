Changelog
=========

2.1.0 (October 21, 2024)
------------------------
- Fix #51: Firebase not working anymore for web push notifications
- Fix #54: Remove obsolete driver FcmLegacy
- Enh #52: Use PHP CS Fixer

**This version requires extra settings in the module configuration.**

Please read [the Installation page](https://marketplace.humhub.com/module/fcm-push/installation) and update the Firebase version in the CSP configuration.

2.0.7 (September 2, 2024)
-------------------------
- Fix #48: Fix go service for multiline links

2.0.6 (August 25, 2024)
-----------------------
- Fix #41: Fix go service for multiline links
- Enh #47: Updated `kreait/firebase-php` to version 7.13+

2.0.3 (June 24, 2024)
---------------------
- Enh: Endpoint to test push status
- Enh: Add possibility to profile `.well-known` files

2.0.2 (May 28, 2024)
-----------------------
- Enh: Branded App Firebase Support

2.0.1 (January 8, 2024)
-----------------------
- Enh: Improve Mobile Page Debug Page with POST Debug

2.0.0 (October 17, 2023)
------------------------------
- Enh: Translations & Stable Release

2.0.0-beta.9 (October 6, 2023)
------------------------------
- Enh: Do not require valid session for unregister FCM tokens

2.0.0-beta.8 (October 4, 2023)
-------------------------------
- Enh #29: Wrap URLs in email messages to "go" app
 
2.0.0-beta.7 (August 21, 2023)
------------------------------
- Enh: Added Token Unregister on App Logout 

2.0.0-beta.6 (August 1, 2023)
-----------------------------
- Fix #25: Notification is not sent in user's language setting
- Chg #26: Module configuration titles rewording and changing links to buttons

2.0.0-beta.5 (July 14, 2023)
----------------------------

- Fix: Error when no Web Driver is configured.

2.0.0-beta.4 (July 13, 2023)
----------------------------

- Fix #22: `send-to-user` CLI command fails because mismatched arguments

2.0.0-beta.3 (July 4, 2023)
---------------------------

- Enh: Validator for GoogleService Account JSON
- Enh: Show/Hide Mobile App Opener Dialog after Login/Logout
- Fix #18: Do not pass `SiteIcon` as notification image
- Enh: Added Mobile App Page

2.0.0-beta.1 (March 28, 2023)
-----------------------------

:warning: This major release an updated module configuration!

- Enh: Switched to new Google Firebase API
- Enh: Added CLI Test Command
- Enh: Bundle JS File instead of use Google CDN
- Enh: Migrated Javascript code into HumHub module logic

1.0.1  (January 21, 2021)
-------------------------
- Fix: Initialize firebase only if module was configured
- Enh: Updated CHANGELOG

1.0.0  (December 17, 2019)
-------------------------
Initial release
