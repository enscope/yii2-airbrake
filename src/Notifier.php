<?php

namespace biller\phpbrake;

use Airbrake\Notifier as AirbrakeNotifier;
use Yii;

class Notifier extends AirbrakeNotifier
{
    private $user;

    public function __construct($opt)
    {
        parent::__construct($opt);

        $this->user = $opt['user'];
    }

    public function buildNotice($exc)
    {
        $notice = parent::buildNotice($exc);

        if (isset(Yii::$app->user)) {
            $user = Yii::$app->user;
            if (isset($user->id)) {
                $notice['context']['user']['id'] = $user->id;
            }
            if (isset($user->identity)) {
                $notice['context']['user'] = $this->user->call($this, $user->identity);
            }
        }

        return $notice;
    }
}