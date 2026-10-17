<?php

namespace humhub\modules\fcmPush\components;

use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\MessagingService;
use Yii;
use yii\base\Component;
use humhub\modules\notification\targets\DeliveryBatch;
use humhub\modules\notification\targets\MobileTargetProvider;
use humhub\modules\user\models\User;

/**
 * Bridges HumHub's core notification system with the FCM push module.
 *
 * This class implements MobileTargetProvider and is swapped in via the DI container
 * in Events::onBeforeRequest() when at least one push driver is configured. HumHub's
 * notification module resolves MobileTargetProvider from the container, so this
 * replacement is transparent to the rest of the core.
 *
 * deliver() is called once per recipient and delivery batch (one notification today, several
 * once the core batches the deliveries). It temporarily switches the application locale to the
 * recipient's locale so that the notification text is localised before being sent as the push body.
 */
class NotificationTargetProvider extends Component implements MobileTargetProvider
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        // Init module
        Yii::$app->getModule('fcm-push');
        parent::init();
    }


    /**
     * @inheritDoc
     */
    public function deliver(DeliveryBatch $batch): void
    {
        Yii::$app->i18n->setUserLocale($batch->recipient);

        try {
            /** @var Module $module */
            $module = Yii::$app->getModule('fcm-push');

            /** @var MessagingService $messagingService */
            $messagingService = Yii::createObject(MessagingService::class, [$module->getConfigureForm()]);
            $messagingService->processBatch($batch);
        } finally {
            Yii::$app->i18n->autosetLocale();
        }
    }


    /**
     * @inheritDoc
     */
    public function isActive(?User $user = null): bool
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if (!$module->getDriverService()->hasConfiguredDriver()) {
            return false;
        }

        // Check if user has at least one token
        #if ($user !== null && FcmUser::find()->where(['user_id' => $user->id])->count() === 0) {
        #    return false;
        #}

        return true;
    }
}
