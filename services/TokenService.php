<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\drivers\DriverInterface;
use humhub\modules\fcmPush\models\FcmUser;
use yii\web\IdentityInterface;

class TokenService
{
    /**
     * @param IdentityInterface $user
     * @param DriverInterface $driver
     * @return string[]
     */
    public function getTokensForUser(IdentityInterface $user, DriverInterface $driver): array
    {
        $tokens = [];
        foreach (FcmUser::findAll(['user_id' => $user->id, 'sender_id' => $driver->getSenderId()]) as $fcmUser) {
            $tokens[] = $fcmUser->token;
        }
        return $tokens;
    }

    public function storeTokenForUser(IdentityInterface $user, DriverInterface $driver, string $token): bool
    {
        $fcmUser = FcmUser::findOne(['token' => $token]);

        // A token that already exists but is associated with a different user or a different
        // Firebase sender (e.g. the device was handed to another person, or the module was
        // reconfigured) must be re-owned. Delete the stale record so a fresh one can be created.
        if ($fcmUser !== null && ($fcmUser->user_id !== $user->id || $fcmUser->sender_id !== $driver->getSenderId())) {
            $fcmUser->delete();
            $fcmUser = null;
        }

        if (!$fcmUser) {
            $fcmUser = new FcmUser();
            $fcmUser->user_id = $user->id;
            $fcmUser->token = $token;
            $fcmUser->sender_id = $driver->getSenderId();
        }

        // If the token already belongs to this user+sender, save() just updates updated_at (via beforeSave).
        return $fcmUser->save();
    }

    public function deleteToken(string $token): bool
    {
        $fcmUser = FcmUser::findOne(['token' => $token]);
        if ($fcmUser) {
            return $fcmUser->delete();
        }

        return false;
    }

}
