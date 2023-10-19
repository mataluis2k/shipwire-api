<?php

namespace mataluis2k\shipwire;

use mataluis2k\shipwire\exceptions\InvalidAuthorizationException;
use mataluis2k\shipwire\exceptions\InvalidRequestException;
use mataluis2k\shipwire\exceptions\ShipwireConnectionException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;

class ShipwireConnector
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    /**
     * Environment method for integration. Possible values: 'live', 'sandbox'
     * @var string
     */
    static $environment = 'live';

    /**
     * Sandbox Base Url for Shipwire API
     * @var string
     */
    static $sandboxBaseUrl = 'https://api.beta.shipwire.com';

    /**
     * Live Base Url for Shipwire API
     * @var string
     */
    static $baseUrl = 'https://api.shipwire.com';

    /**
     * @var string
     */
    static $authorizationCode;

    /**
     * @var string
     */
    static $version = 'v3';

    /**
     * @var HandlerStack
     */
    static $handlerStack;

    private function __construct()
    {
    }

    /**
     * Generates the connection instance for Shipwire
     *
     * @param $username
     * @param $password
     * @param string $environment
     * @param HandlerStack $handlerStack
     */
    public static function init($username, $password, $environment = null, HandlerStack $handlerStack = null)
    {
        if( $username AND $password )
        {
            self::$authorizationCode = "Basic " . base64_encode($username . ':' . $password);
        }
        elseif( $username AND !$password )
        {
            self::$authorizationCode = "ShipwireKey " . $username;
        }
        else
        {
            self::$authorizationCode = "";
        }

        if (null !== $environment) {
            self::$environment = $environment;
        }
        if (null !== $handlerStack) {
            self::$handlerStack = $handlerStack;
        }

        self::$instance = null;
    }

    /**
     * @var ShipwireConnector
     */
    private static $instance = null;

    /**
     * @return ShipwireConnector
     * @throws \Exception
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
            self::$instance->getClient();
        }
        return self::$instance;
    }

    /**
     * Gets guzzle client to manage URL Connections
     *
     * @return Client
     * @throws \Exception
     */
    private function getClient()
    {
        if (!isset($this->client)) {
            if (!self::$authorizationCode) {
                throw new \Exception('Invalid authorization code');
            }
            $config = ['base_uri' => self::getEndpointUrl()];

            if (isset(self::$handlerStack)) {
                $config['handler'] = self::$handlerStack;
            }

            $this->client = new Client($config);
        }
        return $this->client;
    }

    /**
     * Send an api request to Shipwire Endpoint
     *
     * @param string $resource function to be called
     * @param array $params key value parameters
     * @param string $method
     * @param string $body
     * @param bool $onlyResource
     * @return mixed
     * @throws InvalidAuthorizationException
     * @throws InvalidRequestException
     * @throws ShipwireConnectionException
     * @throws \Exception
     */
    public function api($resource, $params = [], $method = "GET", $body = null, $onlyResource = false, $returnDespiteError = false, $version = null)
    {
        $client = self::getClient();

        try {
            $headers = [
                'User-Agent'    => 'mataluis2k-shipwireapi/1.0',
                'Accept'        => 'application/json',
                'Authorization' => self::$authorizationCode
            ];

            if ($body !== null) {
                $headers['content-type'] = 'application/json';
            }

            $version = $version ?? self::$version;
            $response = $client->request($method, "/api/{$version}/".$resource, [
                'headers' => $headers,
                'query' => $params,
                'body' => $body,
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] >= 300) {
                throw new ShipwireConnectionException($data['message'], $data['status']);
            }
            // while testing, finding SW is sending 200 OK but payload has errors.
            // this should capture those error items.
            if (! empty($data['errors'])) {
                $errors = [];
                foreach($data['errors'] as $error)
                {
                    $errors[] = "{$error['code']}: {$error['message']}";
                }
                throw new ShipwireConnectionException(implode("; ", $errors));
            }
            return $onlyResource?$data['resource']:$data;
        } catch (RequestException $e) {
            if ($responseBody = $e->getResponse()->getBody()) {
                $data = json_decode($responseBody, true);

                if($returnDespiteError) {
                    return $onlyResource?$data['resource']:$data;
                }
                
                switch ($data['status']) {
                    case 401:
                        throw new InvalidAuthorizationException($data['message'], $data['status']);
                        break;
                    case 400:
                        throw new InvalidRequestException($data['message'], $data['status']);
                        break;
                }
                throw new ShipwireConnectionException($data['message'], $data['status']);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Gets the endpoint URL based on
     * @return string
     */
    protected static function getEndpointUrl()
    {
        if (self::$environment == 'live') {
            return self::$baseUrl;
        }
        return self::$sandboxBaseUrl;
    }

    /**
     * This method is specific for downloading PDF.
     * Can modify this later to accept header for different file types
     * @param $resource
     * @param $fileResource
     * @return mixed
     * @throws InvalidAuthorizationException
     * @throws InvalidRequestException
     * @throws ShipwireConnectionException
     */
    public function download($resource)
    {
        try {
            $client = self::getClient();
            $headers = [
                'User-Agent'    => 'mataluis2k-shipwireapi/1.0',
                'Accept'        => 'application/pdf',
                'Authorization' => self::$authorizationCode,
            ];

            return $client->request("GET", '/api/' . self::$version . '/'.$resource, [
                'headers' => $headers,
            ]);

        } catch (RequestException $exception) {
            $code = $exception->getCode();
            switch ($exception->getCode()) {
                case 401:
                    throw new InvalidAuthorizationException($exception->getMessage(), $exception->getCode());
                    break;
                case 400:
                    throw new InvalidRequestException($exception->getMessage(), $exception->getCode());
                    break;
            }
            throw new ShipwireConnectionException($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
