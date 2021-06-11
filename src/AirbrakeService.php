<?php

namespace biller\phpbrake;

use Airbrake\ErrorHandler;
use Airbrake\Errors\Base;
use Airbrake\Exception;
use Airbrake\Instance;
use Throwable;
use Yii;
use yii\base\Component;
use yii\base\InvalidArgumentException;

class AirbrakeService extends Component
{
    /** @var bool Enabled flag to allow simpler configuration */
    public $enabled = true;

    /** @var string Airbrake project identifier */
    public $projectId;

    /** @var string Airbrake project key */
    public $projectKey;

    /** @var string|null Application version (optional) */
    public $appVersion;

    /** @var string|null Run-time environment (optional) */
    public $environment;

    /** @var mixed Run-time user (optional) */
    public $user;

    /** @var ParamsFilter[] Items of the array will be added as filters */
    public $filters;

    /** @var string|null Root directory of application (optional) */
    public $rootDirectory;

    /** @var string Service server host */
    public $host = 'api.airbrake.io';

    /** @var bool If true, global instance is set on init() */
    public $setGlobalInstance = true;

    /** @var bool If true, global error handler is set on init() */
    public $setErrorHandler = false;

    /** @var Notifier */
    private $_notifier;

    /** @var ErrorHandler */
    private $_handler;

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object
     * is initialized with the given configuration.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function init()
    {
        parent::init();

        if (!$this->enabled) {
            // if the service should not be enabled,
            // no other initialization is done
            return;
        }

        // convert Yii2 environment name to Airbrake compatible
        $this->environment = self::convertEnvironmentName($this->environment);

        $this->_notifier = new Notifier([
            'projectId' => $this->projectId,
            'projectKey' => $this->projectKey,
            'appVersion' => $this->appVersion,
            'environment' => $this->environment,
            'rootDirectory' => Yii::getAlias($this->rootDirectory),
            'host' => $this->host,
            'user' => $this->user,
        ]);

        if (is_array($this->filters)) {
            foreach ($this->filters as $filterConfig) {
                $filter = Yii::createObject($filterConfig);
                $this->addFilter($filter->getParamsFilter());
            }

            unset($this->filters);
        }

        if ($this->setGlobalInstance) {
            Instance::set($this->_notifier);
        }

        if ($this->setErrorHandler) {
            $this->_handler = new ErrorHandler($this->_notifier);
            $this->_handler->register();
        }
    }

    /**
     * Shorthand for buildNotice and sendNotice.
     *
     * @param Throwable|Base $throwable Throwable to be notified
     *
     * @return array|int|mixed Result of the call
     */
    public function notify($throwable)
    {
        return $this->enabled ? $this->_notifier->notify($throwable) : true;
    }

    /**
     * Shortcut delegating addFilter() call to notifier.
     *
     * @param callable $filter Callable performing the filtering
     */
    public function addFilter(callable $filter)
    {
        if ($this->enabled) {
            $this->_notifier->addFilter($filter);
        }
    }

    /**
     * Shortcut delegating buildNotice() call to notifier.
     *
     * @param Throwable|Base $throwable Throwable to notify
     *
     * @return array Built notification
     */
    public function buildNotice($throwable)
    {
        if (!$this->enabled) {
            return [];
        }

        $notice = $this->_notifier->buildNotice($throwable);
        if (property_exists($throwable, 'statusCode') && $throwable->statusCode != 500) {
            $notice['context']['severity'] = 'warning';
        } elseif (method_exists($throwable, 'getStatusCode') && $throwable->getStatusCode() != 500) {
            $notice['context']['severity'] = 'warning';
        }
        return $notice;
    }

    /**
     * Shortcut delegating sendNotice() call to notifier.
     *
     * @param array $notice Notice built by buildNotice()
     *
     * @return array|int|mixed Result of the call
     */
    public function sendNotice(array $notice)
    {
        return $this->enabled ? $this->_notifier->sendNotice($notice) : true;
    }

    /**
     * Creates and returns API endpoint for specified version
     * of Airbrake API with hostname specified in parameter.
     *
     * @param int $apiVersion Version of the API to use (default 4)
     *
     * @return string URL of the API endpoint
     */
    protected function getAirbrakeApiUrl($apiVersion = 4)
    {
        $schemeAndHost = !preg_match('~^https?://~i', $this->host) ? "https://{$this->host}" : $this->host;

        return sprintf('%s/api/v%d', $schemeAndHost, $apiVersion);
    }

    /**
     * Converts Yii2 environment names to those compatible with Airbrake.
     * That is, 'dev' -> 'development' and 'prod' -> 'production'.
     * Other environment names are left untouched.
     *
     * @param string $env Yii2 environment name
     *
     * @return string Converted environment name
     */
    protected static function convertEnvironmentName($env)
    {
        switch ($env) {
            case 'prod':
                return 'production';
            case 'stag':
                return 'staging';
            case 'test':
                return 'test';
            case 'dev':
                return 'development';
        }

        return $env;
    }

    /**
     * Returns current Airbrake notifier instance.
     *
     * @return Notifier
     */
    public function getNotifier()
    {
        return $this->_notifier;
    }
}
