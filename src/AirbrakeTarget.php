<?php

namespace juanisorondo\phpbrake;

use Airbrake\Errors\Error;
use Airbrake\Errors\Notice;
use Airbrake\Errors\Warning;
use Throwable;
use Yii;
use yii\base\InvalidParamException;
use yii\log\Logger;
use yii\log\Target;

/**
 * Class AirbrakeTarget
 *
 * Airbrake logging target for Yii2 logger.
 *
 * @package juanisorondo\yii2-phpbrake
 */
class AirbrakeTarget extends Target {

    /** @var AirbrakeService|string Name of a component providing AirbrakeService or component instance */
    public $airbrakeService = 'airbrakeService';

    /**
     * Exports log [[messages]] to a specific destination.
     * Child classes must implement this method.
     */
    public function export() {
        $this->assertServiceAvailable();

        foreach ($this->messages as list($content, $level, $category, $timestamp, $traces)) {
            /** @var array|null $airbrakeNotice */
            $airbrakeNotice = null;

            if ($content instanceof Throwable) {
                // if exception (throwable) is provided, build
                // notice using default facilities
                $airbrakeNotice = $this->airbrakeService->buildNotice($content);
            } else {
                // for other information, custom notice will be created
                // based on severity supplied to logger
                $customError = $this->buildCustomError($content, $level, $traces);
                $airbrakeNotice = $this->airbrakeService->buildNotice($customError);
            }

            // add additional information available from logger
            $airbrakeNotice['context']['severity'] = Logger::getLevelName($level);
            $airbrakeNotice['context']['category'] = $category;
            $airbrakeNotice['context']['timestamp'] = date('Y-m-d H:i:s', $timestamp);

            if ($airbrakeNotice !== null) {
                $this->airbrakeService->sendNotice($airbrakeNotice);
            }
        }
    }

    protected function buildCustomError($content, $level, array $traces) {
        switch ($level) {
            case Logger::LEVEL_ERROR:
                return new Error($content, $traces);

            case Logger::LEVEL_WARNING:
                return new Warning($content, $traces);

            case Logger::LEVEL_INFO:
            case Logger::LEVEL_PROFILE:
            case Logger::LEVEL_TRACE:
                return new Notice($content, $traces);
        }

        return new Notice($content, $traces);
    }

    /**
     * Generates the context information to be logged.
     * The default implementation will dump user information, system variables, etc.
     *
     * @return string the context information. If an empty string, it means no context information.
     */
    protected function getContextMessage() {
        // no context is returned for logging, it is handled internally
        // and added to notices automatically by Airbrake
        return '';
    }

    protected function assertServiceAvailable() {
        if (!$this->airbrakeService instanceof AirbrakeService) {
            if (!is_string($this->airbrakeService)) {
                throw new InvalidParamException("AirbrakeService instance not available. Set 'airbrakeService' property.");
            }

            $this->airbrakeService = Yii::$app->get($this->airbrakeService);
            $this->assertServiceAvailable();
        }
    }

}
