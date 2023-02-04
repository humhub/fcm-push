<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\models\FcmUser;
use humhub\modules\user\models\User;
use yii\web\IdentityInterface;

class TokenService
{
    /**
     * @param IdentityInterface $user
     * @return string[]
     */
    public function getTokensForUser(IdentityInterface $user): array
    {
        $tokens = [];
        foreach (FcmUser::findAll(['user_id' => $user->id]) as $fcmUser) {
            $tokens[] = $fcmUser->token;
        }
        return $tokens;
    }

    public function storeTokenForUser(IdentityInterface $user, string $token): bool
    {
        $fcmUser = FcmUser::findOne(['token' => $token]);
        if ($fcmUser !== null && $fcmUser->user_id !== $user->id) {
            $fcmUser->delete();
            $fcmUser = null;
        }

        if ($fcmUser === null) {
            $fcmUser = new FcmUser();
            $fcmUser->user_id = $user->id;
            $fcmUser->token = $token;
        }

        return $fcmUser->save();
    }


}