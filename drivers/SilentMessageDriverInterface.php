<?php

namespace humhub\modules\fcmPush\drivers;

use humhub\modules\fcmPush\components\SendReport;

/**
 * Implemented by {@see DriverInterface} drivers that support sending silent (data-only)
 * messages - i.e. a push that carries only the unread notification count and updates the
 * app badge without displaying a visible notification.
 *
 * Not every driver is a suitable target for silent pushes (e.g. web/PWA tokens have no
 * background badge handler and iOS Safari revokes subscriptions on repeated silent pushes),
 * so this capability is opt-in per driver instead of being part of {@see DriverInterface}.
 *
 * @since 2.2.9
 */
interface SilentMessageDriverInterface
{
    /**
     * Sends a silent (data-only) message that only carries the unread notification count,
     * without a visible notification. Used to keep the app badge count in sync.
     */
    public function processSilentCloudMessage(array $tokens, ?int $notificationCount): SendReport;
}
