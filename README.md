# yii2-airbrake
Airbrake integration for Yii2, which wraps around official
[Airbrake PHP library (airbrake/phpbrake)](https://github.com/airbrake/phpbrake).

## Installation
    composer require enscope/yii2-airbrake

## Usage
While it is not explicitly required, it is recommended to configure the `AirbrakeService`
as a component in environment (or even common) configuration, so it is easily accessible
from the whole application (using i.e. `Yii::$app->get('airbrakeService')`).

    import enscope\Yii2\Ext\Airbrake\AirbrakeService;
    import enscope\Yii2\Ext\Airbrake\AirbrakeFilterFactory;

    return [
        // ...
        'components' => [
            // ...
            'airbrakeService' => [
                'class' => AirbrakeService::className(),
                
                'enabled' => true, // default TRUE
                
                'projectId' => [*your-project-id],
                'projectKey' => [*your-project-key],
                
                'environment' => YII_ENV, // default NULL
                'appVersion' => [your-app-version], // default NULL
                'rootDirectory' => [source-root-directory], // default NULL
                'host' => [api-endpoint-host], // default "api.airbrake.io"
                'httpClient' => [http-client-type], // default "default"
                
                'setGlobalInstance' => [boolean], // default TRUE
                'setErrorHandler' => [boolean], // default FALSE
                
                'filters' => [ // default NULL
                    // 'PHPSESSID' and '_csrf' parameters should not be transferred to airbrake
                    AirbrakeFilterFactory::createParamsFilter(['PHPSESSID', '_csrf']),
                ],
            ],
        ],
    ];

*Configuration options marked with asterisk are required, all other options are optional.*

* `rootDirectory`: should be set to your sources root to allow shortening of file paths
* `httpClient`: specifies type of HTTP client to use and can be configured as:
    * `AirbrakeService::CLIENT_DEFAULT`
    * `AirbrakeService::CLIENT_GUZZLE`
    * `AirbrakeService::CLIENT_CURL`
* `setGlobalInstance`: if set to `true`, current instance will be set as global instance
* `setErrorHandler`: if set to `true`, current instance will be set as PHP run-time unhandled exception handler
* `filters`: array of callables providing notice pre-processing

*For additional information about the API, please consult official Airbrake PHP library documentation.*

### AirbrakeFilterFactory
Factory class that can be used to create various filtering rules.

#### `AirbrakeFilterFactory::createParamsFilter(array $params, $replacement = 'FILTERED')`
Method will create filtering callable that filters parameters, specified by
`$params` and replaces it with specified `$replacement`. Example usage is
available above.

## License
Yii2 Airbrake integration is licensed under [The MIT License (MIT)](https://github.com/enscope/yii2-airbrake/blob/master/LICENSE)
as is the original PHP Airbrake library and follows the versioning of that library.
