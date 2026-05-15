# FCM Push — Developer Guide

## Overview

The `fcm-push` module adds **Firebase Cloud Messaging (FCM)** push notifications to HumHub. When a user receives a HumHub notification, this module dispatches it as a push message to all registered devices of that user.

It supports two delivery channels simultaneously:

| Channel | Driver class | Target |
|---|---|---|
| Browser / PWA | `drivers/Fcm.php` | Any browser or PWA that registered via the Firebase JS SDK |
| Official HumHub mobile app | `drivers/Proxy.php` | The community HumHub app (iOS/Android), relayed via `https://push.humhub.com` |
| Branded mobile app (custom FCM) | `drivers/Fcm.php` | A custom-branded app that uses the operator's own Firebase project |

---

## Architecture

```
HumHub Notification System
        │
        ▼
NotificationTargetProvider      (implements MobileTargetProvider, replaces it via DI)
        │
        ▼
MessagingService::processNotification()
        │
        ├──► Fcm driver ──────► Firebase API (kreait/firebase-php SDK)
        │       └─ uses service account JSON + Sender ID
        │
        └──► Proxy driver ────► https://push.humhub.com/api/v1/push
                └─ uses humhubApiKey
```

### Driver selection

`DriverService` initialises both drivers at boot and exposes two accessor methods:

- **`getWebDriver()`** — returns the `Fcm` driver if configured; never the Proxy (the Proxy is only for native apps).
- **`getMobileAppDriver()`** — returns `Fcm` when the request comes from a branded app (`DeviceDetectorHelper::isAppWithCustomFcm()`), otherwise returns `Proxy`.

Both drivers can be active at the same time. `MessagingService::processMessage()` iterates all configured drivers and dispatches to each independently.

---

## Configuration (`ConfigureForm`)

All settings are stored via `$module->settings` (HumHub key-value store).

| Setting | Driver | Description |
|---|---|---|
| `humhubApiKey` | Proxy | API key obtained from the HumHub push service portal |
| `senderId` | Fcm | Firebase Messaging Sender ID (numeric) |
| `firebaseApiKey` | Fcm | Firebase Web API Key |
| `firebaseAppId` | Fcm | Firebase Web App ID |
| `firebaseVapidKey` | Fcm | VAPID key pair from Firebase Web Push Certificates |
| `json` | Fcm | Full contents of the Firebase Service Account JSON file |

`ConfigureForm::getJsonAsArray()` / `getJsonParam()` parse the raw JSON on the fly. The `json` field is validated against the expected Google service account structure on save.

---

## Registered Devices — `FcmUser` model

Database table: `fcmpush_user`

| Column | Description |
|---|---|
| `id` | PK |
| `user_id` | HumHub user |
| `token` | FCM registration token (unique, issued by the browser/app) |
| `sender_id` | Identifies which Firebase project/driver owns this token |
| `created_at` / `updated_at` | Timestamps set in `beforeSave` |

One row = one registered device. A user can have many tokens (multiple browsers, multiple devices).
The `sender_id` column lets Proxy tokens and Fcm tokens coexist for the same user without collision.

### Token lifecycle

#### Created / updated

**Native apps (Flutter):**
1. User logs in → `Events::onAfterLogin()` sets `SESSION_VAR_REGISTER_NOTIFICATION` in the session.
2. On the next full page render → `Events::onLayoutAddonInit()` detects the flag and calls `MobileAppHelper::registerNotificationScript()`, which sends a JS bridge message `{type: 'registerFcmDevice', url: '…'}` to the Flutter app.
3. The app obtains an FCM token from Firebase and POSTs it to `/fcm-push/token/update-mobile-app`.

**Browsers / PWA (`humhub.firebase.js`):**

Token registration is always routed through `afterServiceWorkerRegistration()`, which is the single code path for calling `getToken`. It is triggered in two ways:

- **PWA service worker callback** — the `web/pwa` module calls the global `afterServiceWorkerRegistration(registration)` whenever the service worker registers or updates.
- **Proactive check in `init()`** — on every page load, `init()` checks whether `Notification.permission === 'granted'` AND no token is cached in localStorage. If so, it calls `afterServiceWorkerRegistration()` via `navigator.serviceWorker.ready`. This covers users who granted permission in browser settings after their initial login, without requiring a logout/login cycle.

`afterServiceWorkerRegistration()` itself is protected by two guards to prevent double execution — which would cause two different tokens if both callers fire on the same page load (e.g. when a service worker update changes the registration object):
- **`_tokenRegistrationPending` flag** — set synchronously at entry, reset in every `.then()`/`.catch()` branch. Whichever caller arrives second while the first is still awaiting its promise returns immediately.
- **`getTokenLocalStore()` check** — if a valid token is already cached in localStorage (from an earlier call that already succeeded), skip silently.

Once `getToken` returns a token, `sendTokenToServer()` POSTs it to `/fcm-push/token/update` and caches it in localStorage with a 24-hour expiry. On subsequent page loads within that window `isTokenSentToServer()` returns `true` and no AJAX call is made.

**Server-side (`TokenService::storeTokenForUser()`):**
- Token already exists for the same user + sender → `updated_at` is refreshed.
- Token exists but belongs to a **different user or sender** → old record deleted, new one created (handles device hand-offs).

#### Deleted
- **On logout:** `Events::onAfterLogout()` sets two distinct session flags — `WebAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION` (`'webAppUnregisterNotification'`) and `MobileAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION` (`'mobileAppUnregisterNotification'`). On the next page render `onLayoutAddonInit` consumes each flag independently: the web block injects JS calling `humhub.modules.firebase.unregisterNotification()`, and the mobile block sends an `{type: 'unregisterFcmDevice'}` Flutter bridge message. Both ultimately POST to `TokenController::actionDelete[MobileApp]()` → `TokenService::deleteToken()`.

  > ⚠️ The two constants **must** have different string values. If they share a value the first block removes the session key and the mobile unregister script never fires, leaving stale tokens in the database.

  > ℹ️ `unregisterNotification()` reads the token from **localStorage** and deletes that one token. Only the token cached in the current browser is removed on logout; tokens registered from other browsers or devices remain active in the database.

- **Auto-cleanup of permanently rejected tokens:** After every multicast send, `MessagingService::processMessage()` inspects `SendReport::$failedTokens`. Only tokens that Firebase has permanently invalidated (`unknownTokens` = app uninstalled / `invalidTokens` = malformed) are deleted via `TokenService::deleteToken()`. Tokens that failed due to transient errors (rate limit, server hiccup) or because a device was simply offline are **never deleted** — Firebase queues messages for offline devices and does not report them as failures.
- **Manually by an admin:** The debug page (`/fcm-push/admin/debug`) lists all tokens for the currently logged-in admin and provides a delete link.

---

## Notification dispatch flow

1. A HumHub notification is triggered anywhere in the application.
2. The core `notification` module iterates `MobileTargetProvider` implementations.
3. Because `onBeforeRequest` replaced `MobileTargetProvider::class` with `NotificationTargetProvider::class` in the DI container, `NotificationTargetProvider::handle()` is called.
4. `handle()` sets the locale to the target user's, then delegates to `MessagingService::processNotification()`.
5. `MessagingService` builds the message (title = site name, body = notification text, url = notification entry URL, icon = site icon at 180 px, badge count = unseen notifications count) and calls `processMessage()`.
6. For each configured driver, `TokenService::getTokensForUser()` fetches all tokens scoped to that driver's `sender_id`. If tokens exist, `driver->processCloudMessage()` is called.

### `Fcm` driver
Uses the `kreait/firebase-php` SDK (loaded via a dedicated `vendor/autoload.php` inside the module). Authenticates with the service account JSON. Sends a multicast message with `withWebPushConfig` (link) and `withData` (url + notification_count). The `imageUrl` is intentionally omitted from the notification payload to avoid displaying a duplicate of the logo on branded apps.

After each multicast, the driver inspects the `MulticastSendReport` for **permanently** invalid tokens only:
- `unknownTokens()` — Firebase returned `UNREGISTERED` (app was uninstalled, token expired). Safe to delete.
- `invalidTokens()` — token is structurally malformed. Safe to delete.

Transient failures (rate limit, server unavailable) appear in `failures()` but not in the two lists above and are **not** deleted. A device being off never generates a Firebase failure at all — Firebase queues the message and delivers it when the device reconnects — so offline devices are never at risk of having their token removed.

### `Proxy` driver
Makes an authenticated HTTP POST to `https://push.humhub.com/api/v1/push` using the `humhubApiKey` as a Bearer token. The HumHub service relays the message to FCM on behalf of the operator.

---

## Service Worker integration (PWA)

`Events::onServiceWorkerControllerInit()` hooks into the `web/pwa` module's service worker generation. If the `Fcm` driver is configured, it appends `importScripts` calls for the Firebase compat SDK and the `firebase.initializeApp()` + `firebase.messaging()` bootstrap to the service worker JS. This is required for background message reception in PWA mode.

---

## Event hooks summary

| Event | Handler | Purpose |
|---|---|---|
| `Application::EVENT_BEFORE_REQUEST` | `onBeforeRequest` | Registers `NotificationTargetProvider` in the DI container when a driver is configured |
| `ServiceWorkerController::EVENT_INIT` | `onServiceWorkerControllerInit` | Injects Firebase SDK bootstrap into the PWA service worker |
| `LayoutAddons::EVENT_INIT` | `onLayoutAddonInit` | Triggers token registration (after login) or unregistration (after logout) scripts |
| `User::EVENT_AFTER_LOGIN` | `onAfterLogin` | Sets the session flag to trigger token registration on next render |
| `User::EVENT_AFTER_LOGOUT` | `onAfterLogout` | Sets session flags to trigger token unregistration on next render |

---

## HTTP endpoints

| Route | Controller action | Description |
|---|---|---|
| `POST /fcm-push/token/update` | `TokenController::actionUpdate` | Register/refresh a browser FCM token |
| `POST /fcm-push/token/update-mobile-app` | `TokenController::actionUpdateMobileApp` | Register/refresh a mobile app FCM token |
| `POST /fcm-push/token/delete` | `TokenController::actionDelete` | Remove a browser FCM token |
| `POST /fcm-push/token/delete-mobile-app` | `TokenController::actionDeleteMobileApp` | Remove a mobile app FCM token |
| `GET /fcm-push/status` | `StatusController::actionIndex` | Returns HTTP 200 / 404 / 501 JSON to check module health |
| `GET /fcm-push/admin` | `AdminController::actionIndex` | Admin configuration page |
| `GET /fcm-push/admin/debug` | `AdminController::actionDebug` | Lists and allows deletion of tokens for the current user |

CSRF validation is disabled on `TokenController` because calls originate from the Firebase JS SDK or the Flutter app, which do not carry a CSRF token.

---

## Console command (testing)

```bash
php yii firebase/send-to-user <userId> "<title>" "<message>"
```

Sends a raw push message to all registered devices for `<userId>` via all configured drivers. Useful for verifying that configuration is correct without triggering a real notification.

---

## Key classes at a glance

| Class | Role |
|---|---|
| `Module` | Module entry point; lazy-loads `ConfigureForm` and `DriverService` |
| `ConfigureForm` | Loads/saves all settings; validates the service account JSON |
| `DriverService` | Instantiates configured drivers; routes to web vs. mobile driver |
| `DriverInterface` | Contract every driver must implement |
| `drivers/Fcm` | Direct Firebase delivery via `kreait/firebase-php` SDK |
| `drivers/Proxy` | Delivery via the HumHub push relay service |
| `TokenService` | CRUD for `FcmUser` records (device tokens) |
| `MessagingService` | Orchestrates notification → driver dispatch |
| `NotificationTargetProvider` | Bridge between HumHub's notification system and this module |
| `FcmUser` | ActiveRecord for the `fcmpush_user` table |
| `MobileAppHelper` | Emits Flutter JS bridge messages to register/unregister devices |
| `WebAppHelper` | Emits JS to unregister web browser tokens on logout |
