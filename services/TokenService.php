<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\driver\DriverInterface;
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

        // Check if Token is already stored, but to another sender or user, then delete
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
