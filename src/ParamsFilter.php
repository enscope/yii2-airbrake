<?php

namespace juanisorondo\phpbrake;

use Closure;
use yii\base\BaseObject;

class ParamsFilter extends BaseObject
{

    /**
     * Array of parameter names to replace
     * @var array
     */
    public $params = [];

    /**
     * Replacement string (default 'FILTERED')
     * @var string
     */
    public $replacement = 'FILTERED';
    private $paramsFilter;

    public function init()
    {
        parent::init();

        $this->paramsFilter = function ($notice) {
            foreach ($this->params as $param) {
                if (isset($notice['params'][$param])) {
                    $notice['params'][$param] = $this->replacement;
                }
            }
            return $notice;
        };
    }

    /**
     * Returns a callable that replaces occurrences of $params values
     * with value specified in $replacement.
     * @return callable|Closure Closure to set as filter
     */
    public function getParamsFilter()
    {
        return $this->paramsFilter;
    }

}
