<?php

namespace biller\phpbrake;

use Airbrake\Notifier as AirbrakeNotifier;
use Yii;

class Notifier extends AirbrakeNotifier
{
    public function buildNotice($exc)
    {
        $notice = parent::buildNotice($exc);

        if (isset(Yii::$app->user)) {
            $user = Yii::$app->user;
            if (isset($user->id)) {
                $notice['context']['user']['id'] = $user->id;
            }
            if (isset($user->identity)) {
                $notice['context']['user']['name'] = $user->identity->nombre ?? null;
                $notice['context']['user']['email'] = $user->identity->email ?? null;
            }
        }

        return $notice;
    }

}