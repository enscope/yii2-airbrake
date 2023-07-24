<?php

namespace biller\phpbrake;

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
                $paramsToReplace = $this->explodeParams($param);

                if ((count($paramsToReplace) === 1) && isset($notice['params'][$paramsToReplace[0]])) {
                    $notice['params'][$paramsToReplace[0]] = $this->replacement;
                }
                if ((count($paramsToReplace) === 2) && isset($notice['params'][$paramsToReplace[0]][$paramsToReplace[1]])) {
                    $notice['params'][$paramsToReplace[0]][$paramsToReplace[1]] = $this->replacement;
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

    /**
     * @param string $param
     * @return string[]
     */
    private function explodeParams(string $param): array
    {
        $superglobals = ['_GET.', '_POST.', '_COOKIE.', '_SERVER.', '_ENV.', '_FILES.'];
        foreach ($superglobals as $superglobal) {
            if (strpos($param, $superglobal) === 0) {
                $param = str_replace($superglobal, "", $param);
                return explode(".", $param);
            }
        }
        return [$param];
    }

}
