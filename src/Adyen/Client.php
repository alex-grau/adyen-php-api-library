<?php

namespace Adyen;

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Client
{
    const LIB_VERSION = "2.1.0";
    const LIB_NAME = "adyen-php-api-library";
    const USER_AGENT_SUFFIX = "adyen-php-api-library/";
    const ENDPOINT_TEST = "https://pal-test.adyen.com";
    const ENDPOINT_LIVE = "https://pal-live.adyen.com";
    const ENDPOINT_LIVE_SUFFIX = "-pal-live.adyenpayments.com";
    const ENDPOINT_TEST_DIRECTORY_LOOKUP = "https://test.adyen.com/hpp/directory/v2.shtml";
    const ENDPOINT_LIVE_DIRECTORY_LOOKUP = "https://live.adyen.com/hpp/directory/v2.shtml";
    const API_PAYMENT_VERSION = "v40";
    const API_BIN_LOOKUP_VERSION = "v40";
    const API_PAYOUT_VERSION = "v30";
    const API_RECURRING_VERSION = "v25";
    const API_CHECKOUT_VERSION = "v41";
    const API_CHECKOUT_UTILITY_VERSION = "v1";
    const API_NOTIFICATION_VERSION = "v1";
    const API_ACCOUNT_VERSION = "v4";
    const API_FUND_VERSION = "v3";
    const ENDPOINT_TERMINAL_CLOUD_TEST = "https://terminal-api-test.adyen.com";
    const ENDPOINT_TERMINAL_CLOUD_LIVE = "https://terminal-api-live.adyen.com";
    const ENDPOINT_CHECKOUT_TEST = "https://checkout-test.adyen.com/checkout";
    const ENDPOINT_CHECKOUT_LIVE_SUFFIX = "-checkout-live.adyenpayments.com/checkout";
    const ENDPOINT_PROTOCOL = "https://";
    const ENDPOINT_NOTIFICATION_TEST = "https://cal-test.adyen.com/cal/services/Notification";
    const ENDPOINT_NOTIFICATION_LIVE = "https://cal-live.adyen.com/cal/services/Notification";
    const ENDPOINT_ACCOUNT_TEST = "https://cal-test.adyen.com/cal/services/Account";
    const ENDPOINT_ACCOUNT_LIVE = "https://cal-live.adyen.com/cal/services/Account";
    const ENDPOINT_FUND_TEST = "https://cal-test.adyen.com/cal/services/Fund";
    const ENDPOINT_FUND_LIVE = "https://cal-live.adyen.com/cal/services/Fund";


    /**
     * @var \Adyen\Config $config
     */
    private $config;

    /**
     * @var
     */
    private $httpClient;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * Client constructor.
     *
     * @param null $config
     * @throws AdyenException
     */
    public function __construct($config = null)
    {
        if (!$config) {
            // create config
            $this->config = new \Adyen\Config();
        } elseif ($config instanceof \Adyen\ConfigInterface) {
            $this->config = $config;
        } else {
            throw new \Adyen\AdyenException("This config object is not supported, you need to implement the ConfigInterface");
        }
    }

    /**
     * @return Config|ConfigInterface|null
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set Username of Web Service User
     *
     * @param $username
     */
    public function setUsername($username)
    {
        $this->config->set('username', $username);
    }

    /**
     * Set Password of Web Service User
     *
     * @param $password
     */
    public function setPassword($password)
    {
        $this->config->set('password', $password);
    }

    /**
     * Set x-api-key for Web Service Client
     *
     * @param $xApiKey
     */
    public function setXApiKey($xApiKey)
    {
        $this->config->set('x-api-key', $xApiKey);
    }

    /**
     * Set HTTP proxy information
     *
     * @param string $proxy
     */
    public function setHttpProxy($proxy)
    {
        $this->config->set('http-proxy', $proxy);
    }

    /**
     * Set environment to connect to test or live platform of Adyen
     * For live please specify the unique identifier.
     *
     * @param string $environment
     * @param null $liveEndpointUrlPrefix Provide the unique live url prefix from the "API URLs and Response" menu in the Adyen Customer Area
     * @throws AdyenException
     */
    public function setEnvironment($environment, $liveEndpointUrlPrefix = null)
    {
        if ($environment == \Adyen\Environment::TEST) {
            $this->config->set('environment', \Adyen\Environment::TEST);
            $this->config->set('endpoint', self::ENDPOINT_TEST);
            $this->config->set('endpointDirectorylookup', self::ENDPOINT_TEST_DIRECTORY_LOOKUP);
            $this->config->set('endpointTerminalCloud', self::ENDPOINT_TERMINAL_CLOUD_TEST);
            $this->config->set('endpointCheckout', self::ENDPOINT_CHECKOUT_TEST);
            $this->config->set('endpointNotification', self::ENDPOINT_NOTIFICATION_TEST);
            $this->config->set('endpointAccount', self::ENDPOINT_ACCOUNT_TEST);
            $this->config->set('endpointFund', self::ENDPOINT_FUND_TEST);
        } elseif ($environment == \Adyen\Environment::LIVE) {
            $this->config->set('environment', \Adyen\Environment::LIVE);
            $this->config->set('endpointDirectorylookup', self::ENDPOINT_LIVE_DIRECTORY_LOOKUP);
            $this->config->set('endpointTerminalCloud', self::ENDPOINT_TERMINAL_CLOUD_LIVE);
            $this->config->set('endpointNotification', self::ENDPOINT_NOTIFICATION_LIVE);
            $this->config->set('endpointAccount', self::ENDPOINT_ACCOUNT_LIVE);
            $this->config->set('endpointFund', self::ENDPOINT_FUND_LIVE);

            if ($liveEndpointUrlPrefix) {
                $this->config->set('endpoint',
                    self::ENDPOINT_PROTOCOL . $liveEndpointUrlPrefix . self::ENDPOINT_LIVE_SUFFIX);
                $this->config->set('endpointCheckout',
                    self::ENDPOINT_PROTOCOL . $liveEndpointUrlPrefix . self::ENDPOINT_CHECKOUT_LIVE_SUFFIX);
            } else {
                $this->config->set('endpoint', self::ENDPOINT_LIVE);
                $this->config->set('endpointCheckout', null); // not supported please specify unique identifier
            }
        } else {
            // environment does not exist
            $msg = "This environment does not exist, use " . \Adyen\Environment::TEST . ' or ' . \Adyen\Environment::LIVE;
            throw new \Adyen\AdyenException($msg);
        }
    }

    /**
     * Set Request URl
     *
     * @param $url
     */
    public function setRequestUrl($url)
    {
        $this->config->set('endpoint', $url);
    }

    /**
     * Set directory lookup URL
     *
     * @param $url
     */
    public function setDirectoryLookupUrl($url)
    {
        $this->config->set('endpointDirectorylookup', $url);
    }

    /**
     * @param $merchantAccount
     */
    public function setMerchantAccount($merchantAccount)
    {
        $this->config->set('merchantAccount', $merchantAccount);
    }

    /**
     * @param $applicationName
     */
    public function setApplicationName($applicationName)
    {
        $this->config->set('applicationName', $applicationName);
    }

    /**
     * Set external platform name, version and integrator
     *
     * @param string $name
     * @param string $version
     * @param string $integrator
     */
    public function setExternalPlatform($name, $version, $integrator = "")
    {
        $this->config->set('externalPlatform',
            array('name' => $name, 'version' => $version, 'integrator' => $integrator));
    }

    /**
     * Set Adyen payment source name and version
     *
     * @param string $name
     * @param string $version
     */
    public function setAdyenPaymentSource($name, $version)
    {
        $this->config->set('adyenPaymentSource', array('name' => $name, 'version' => $version));
    }

    /**
     * Type can be json or array
     *
     * @param $value
     */
    public function setInputType($value)
    {
        $this->config->set('inputType', $value);
    }

    /**
     * Type can be json or array
     *
     * @param $value
     */
    public function setOutputType($value)
    {
        $this->config->set('outputType', $value);
    }

    /**
     * @param $value
     */
    public function setTimeout($value)
    {
        $this->config->set('timeout', $value);
    }

    /**
     * Get the library name
     *
     * @return string
     */
    public function getLibraryName()
    {
        return self::LIB_NAME;
    }

    /**
     * Get the library version
     *
     * @return string
     */
    public function getLibraryVersion()
    {
        return self::LIB_VERSION;
    }

    /**
     * Get the version of the API Payment endpoint
     *
     * @return string
     */
    public function getApiPaymentVersion()
    {
        return self::API_PAYMENT_VERSION;
    }

    /**
     * Get the version of the API BinLookUp endpoint
     *
     * @return string
     */
    public function getApiBinLookupVersion()
    {
        return self::API_BIN_LOOKUP_VERSION;
    }

    /**
     * Get the version of the API Payout endpoint
     *
     * @return string
     */
    public function getApiPayoutVersion()
    {
        return self::API_PAYOUT_VERSION;
    }

    /**
     * Get the version of the Recurring API endpoint
     *
     * @return string
     */
    public function getApiRecurringVersion()
    {
        return self::API_RECURRING_VERSION;
    }

    /**
     * Get the version of the Checkout API endpoint
     *
     * @return string
     */
    public function getApiCheckoutVersion()
    {
        return self::API_CHECKOUT_VERSION;
    }

    /**
     * Get the version of the Checkout Utility API endpoint
     *
     * @return string
     */
    public function getApiCheckoutUtilityVersion()
    {
        return self::API_CHECKOUT_UTILITY_VERSION;
    }

    /**
     * Get the version of the Notification API endpoint
     *
     * @return string
     */
    public function getApiNotificationVersion()
    {
        return self::API_NOTIFICATION_VERSION;
    }

    /**
     * Get the version of the Account API endpoint
     *
     * @return string
     */
    public function getApiAccountVersion()
    {
        return self::API_ACCOUNT_VERSION;
    }

    /**
     * Get the version of the Fund API endpoint
     *
     * @return string
     */
    public function getApiFundVersion()
    {
        return self::API_FUND_VERSION;
    }

    /**
     * @param HttpClient\ClientInterface $httpClient
     */
    public function setHttpClient(\Adyen\HttpClient\ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return mixed
     */
    public function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = $this->createDefaultHttpClient();
        }
        return $this->httpClient;
    }

    /**
     * @return HttpClient\CurlClient
     */
    protected function createDefaultHttpClient()
    {
        return new \Adyen\HttpClient\CurlClient();
    }

    /**
     * Set the Logger object
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return Logger
     * @throws \Exception
     */
    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->logger = $this->createDefaultLogger();
        }

        return $this->logger;
    }

    /**
     * @return Logger
     * @throws \Exception
     */
    protected function createDefaultLogger()
    {
        $logger = new Logger('adyen-php-api-library');
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::NOTICE));

        return $logger;
    }
}
