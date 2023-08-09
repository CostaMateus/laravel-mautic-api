<?php

namespace Triibo\Mautic\Factories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Arr;
use Mautic\Auth\ApiAuth;
use Triibo\Mautic\Models\MauticConsumer;

class MauticFactory
{
    public function getDefaultConnection()
    {
        $connectionName = config('mautic.default');

        return config("mautic.connections.$connectionName");
    }

    /**
     * Make a new Mautic url.
     *
     * @param  string  $endpoints
     * @return url
     */
    protected function getMauticUrl($endpoints = null)
    {
        $conn = $this->getDefaultConnection();
        $url = $conn['baseUrl'].'/';

        return (! empty($endpoints)) ? $url.$endpoints : $url;
    }

    /**
     * Check AccessToken Expiration Time
     *
     * @return bool
     */
    public function checkExpirationTime($expireTimestamp)
    {
        $now = time();

        return ($now > $expireTimestamp) ? true : false;
    }

    /**
     * Make a new Mautic client.
     *
     * @return \Mautic\Config
     */
    public function make(array $config)
    {
        $config = $this->getConfig($config);

        return $this->getClient($config);
    }

    /**
     * Get the configuration data.
     *
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getConfig(array $config)
    {
        $keys = ['clientKey', 'clientSecret'];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $config)) {
                throw new \InvalidArgumentException('The Mautic client requires configuration.');
            }
        }

        return Arr::only($config, ['version', 'baseUrl', 'clientKey', 'clientSecret', 'callback']);
    }

    /**
     * Get the Mautic client.
     *
     *
     * @return \Mautic\MauticConsumer
     */
    protected function getClient(array $setting)
    {
        session_name('mauticOAuth');
        session_start();

        // Initiate the auth object
        $initAuth = new ApiAuth();
        $auth = $initAuth->newAuth($setting);

        // Initiate process for obtaining an access token; this will redirect the user to the $authorizationUrl and/or
        // set the access_tokens when the user is redirected back after granting authorization

        if ($auth->validateAccessToken()) {
            if ($auth->accessTokenUpdated()) {
                $accessTokenData = $auth->getAccessTokenData();

                return MauticConsumer::create($accessTokenData);
            }
        }
    }

    /**
     * Call Mautic Api
     *
     * @return mixed
     *
     * @throws \ClientException
     */
    public function callMautic($method, $endpoints, $body, $token)
    {
        $mauticURL = $this->getMauticUrl("api/$endpoints");
        $conn = $this->getDefaultConnection();

        $params = [];

        if (! empty($body)) {
            foreach ($body as $key => $item) {
                $params['form_params'][$key] = $item;
            }
        }

        if ($conn['version'] == 'BasicAuth') {
            $user = $conn['username'];
            $pass = $conn['password'];
            $b64 = base64_encode("$user:$pass");
            $auth = "Basic $b64";
        } else {
            $auth = "Bearer $token";
        }

        $headers = [
            'headers' => [
                'Authorization' => $auth,
            ],
        ];

        $client = new Client($headers);

        try {
            $response = $client->request($method, $mauticURL, $params);
            $responseBodyAsString = $response->getBody();

            return json_decode($responseBodyAsString, true);
        } catch (ClientException $e) {
            $exceptionResponse = $e->getResponse();

            return $statusCode = $exceptionResponse->getStatusCode();
        }
    }

    /**
     * Generate new token once old one expire
     * and store in consumer table.
     *
     * @return MauticConsumer
     *
     * @throws \ClientException
     */
    public function refreshToken($refreshToken)
    {
        $mauticURL = $this->getMauticUrl('oauth/v2/token');

        $config = config('mautic.connections.main');

        $client = new Client();

        try {
            $response = $client->request('POST', $mauticURL, [
                'form_params' => [
                    'client_id' => $config['clientKey'],
                    'client_secret' => $config['clientSecret'],
                    'redirect_uri' => $config['callback'],
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $responseBodyAsString = $response->getBody();
            $responseBodyAsString = json_decode($responseBodyAsString, true);

            return MauticConsumer::create([
                'access_token' => $responseBodyAsString['access_token'],
                'expires' => time() + $responseBodyAsString['expires_in'],
                'token_type' => $responseBodyAsString['token_type'],
                'refresh_token' => $responseBodyAsString['refresh_token'],
            ]);
        } catch (ClientException $e) {
            return $exceptionResponse = $e->getResponse();
        }
    }
}
