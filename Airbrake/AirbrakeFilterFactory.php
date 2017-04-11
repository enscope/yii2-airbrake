<?php

namespace enscope\Yii2\Ext\Airbrake
{
    class AirbrakeFilterFactory
    {
        /**
         * Returns a callable that replaces occurrences of $params values
         * with value specified in $replacement.
         *
         * @param array  $params      Array of parameter names to replace
         * @param string $replacement Replacement string (default 'FILTERED')
         *
         * @return callable|\Closure Closure to set as filter
         */
        public static function createParamsFilter(array $params, $replacement = 'FILTERED')
        {
            return function ($notice) use ($params, $replacement)
            {
                foreach ($params as $param)
                {
                    if (isset($notice['params'][$param]))
                    {
                        $notice['params'][$param] = $replacement;
                    }
                }

                return $notice;
            };
        }
    }
}
