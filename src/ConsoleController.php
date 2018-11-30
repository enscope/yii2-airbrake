<?php

namespace biller\phpbrake;

use Yii;
use yii\base\InvalidArgumentException;
use yii\console\Controller;

/**
 * Airbrake Service support for Yii2.
 *
 * @package biller\yii2-phpbrake
 */
class ConsoleController extends Controller
{

    /** @var AirbrakeService|null */
    public $airbrakeService = 'airbrakeService';

    /** @var bool Try to infer revision and repository by calling 'git' command */
    public $inferParameters = true;

    /** @var string|null Currently deployed revision */
    public $revision;

    /** @var string Name of the user calling the tracking */
    public $username = 'system';

    /** @var string|null Tracked repository identifier */
    public $repository;

    public function options($actionId)
    {
        switch ($actionId) {
            case 'track-deploy':
                return ['inferParameters', 'revision', 'username', 'repository'];
        }

        return parent::options($actionId);
    }

    /**
     * Sends deploy tracking information to Airbrake service.
     *
     * @throws \Airbrake\Exception
     * @throws InvalidArgumentException
     */
    public function actionTrackDeploy()
    {
        $this->assertServiceAvailable();

        if ($this->inferParameters) {
            // if revision is not specified, try to get current using exec()
            @$this->revision = $this->revision ?: $this->getCurrentRevision();
            // if repository identifier is not specified, try to get url
            @$this->repository = $this->repository ?: $this->getRepositoryUrl();
        }

        if ($this->revision === null) {
            throw new InvalidArgumentException('revision must be specified when can not be inferred');
        }

        $this->stdout("*** Sending deploy tracker to Airbrake service...\n");
        $this->stdout("    Revision: {$this->revision}\n");
        $this->stdout("    Version:  {$this->repository}\n");

        $result = $this->airbrakeService->trackDeploy(
            $this->revision, $this->username, $this->repository);

        if ($result === true) {
            $this->stdout("  + Airbrake deploy tracker was sent successfully.\n");
        } else {
            $this->stderr("  + unable to send airbrake deploy tracker!\n");
        }
    }

    protected function getCurrentRevision()
    {
        return exec('git rev-parse HEAD');
    }

    protected function getRepositoryUrl()
    {
        return exec('git remote get-url origin');
    }

    protected function assertServiceAvailable()
    {
        if (!$this->airbrakeService instanceof AirbrakeService) {
            if (!is_string($this->airbrakeService)) {
                throw new InvalidArgumentException("AirbrakeService instance not available. Set 'airbrakeService' property.");
            }

            $this->airbrakeService = Yii::$app->get($this->airbrakeService);
            $this->assertServiceAvailable();
        }
    }

}
