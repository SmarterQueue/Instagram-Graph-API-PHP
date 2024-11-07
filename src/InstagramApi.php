<?php

namespace SmarterQueue\Instagram;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class InstagramApi
{
    private string $versionCode = 'v21.0';

    private ClientInterface $client;

    private ?string $accessToken = null;

    private InstagramAppCredentials $appCredentials;

    public function __construct(
        string $clientId,
        string $clientSecret,
        array|ClientInterface $clientOrConfig = [],
    ) {
        $this->appCredentials = new InstagramAppCredentials($clientId, $clientSecret);
        if ($clientOrConfig instanceof ClientInterface) {
            $this->client = $clientOrConfig;
        } else {
            $this->client = $this->buildClient($clientOrConfig);
        }
    }

    public function setVersionCode(string $versionCode): void
    {
        $this->versionCode = $versionCode;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAppCredentials(): InstagramAppCredentials
    {
        return $this->appCredentials;
    }

    /**
	 * Make a GET request.
	 * Allows two formats, either with query params inline within the endpoint, or as a separate array:
	 * $endpoint = /me/media?fields=media_url,permalink
	 * $endpoint = /me/media  with  $params = ['fields' => 'media_url,permalink']
	 *
	 * @param string $endpoint
	 * @param array $params
	 * @param string|null $versionCode
	 *
	 * @return InstagramResponse
	 * @throws InstagramException
	 */
    public function get(string $endpoint, array $params = [], ?string $versionCode = null): InstagramResponse
    {
        $options = [
            'query' => [
                'access_token' => $this->accessToken,
            ],
        ];
        $urlParts = parse_url($endpoint);

        $endpointPath = $urlParts['path'] ?? $endpoint;

        // Parse any existing query parameters from the endpoint
        $inlineParams = [];
        if (isset($urlParts['query']))
        {
            parse_str($urlParts['query'], $inlineParams);
        }

        // Combine existing inline query parameters, additional $params, and access_token
        $options['query'] = array_merge($options['query'], $inlineParams, $params);

        return $this->sendRequest('GET', $endpointPath, $options, $versionCode, null);
    }

    public function post(string $endpoint, array $params = [], ?string $versionCode = null): InstagramResponse
    {
        $options = [
            'json' => [
                'access_token' => $this->accessToken,
            ],
        ];
        $options['json'] = array_merge($options['json'], $params);

        return $this->sendRequest('POST', $endpoint, $options, $versionCode, null);
    }

	public function sendRequest(string $method, string $endpoint, array $options, ?string $versionCode = null, ?string $baseUrl = null): InstagramResponse
    {
        $versionCode = $versionCode ?? $this->versionCode;
        if (!empty($versionCode)) {
            $uri = sprintf('%s/%s/%s', $baseUrl ?? $this->getApiBaseUrl(), $versionCode, $endpoint);
        } else {
            $uri = sprintf('%s/%s', $baseUrl ?? $this->getApiBaseUrl(), $endpoint);
        }
        if ('GET' !== $method && !isset($options['headers']['Content-Type']) && isset($options['json'])) {
            $options['headers']['Content-Type'] = 'application/json';
        }

        try {
            $response = $this->client->request($method, $uri, $options);
            $content = $response->getBody()->getContents();
            $decodedData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return new InstagramResponse($response->getStatusCode(), $response->getHeaders(), $decodedData);
        } catch (\Throwable $e) {
            throw $this->mapException($e);
        }
    }

    protected function mapException(\Throwable $e): InstagramException
    {
        $response = $e instanceof RequestException ? $e->getResponse() : null;
        $errorMessage = $e->getMessage();
        $errorCode = $errorSubcode = $errorType = $errorFbTraceId = null;
        if ($response) {
            $content = $response->getBody()->getContents();
            if (str_contains($response->getHeaderLine('Content-Type'), 'application/json')) {
                $content = json_decode($content, true);
                if (JSON_ERROR_NONE === json_last_error()) {
                    $errorMessage = $content['error']['message'] ?? $e->getMessage();
                    $errorType = $content['error']['type'] ?? null;
                    $errorCode = $content['error']['code'] ?? null;
                    $errorSubcode = $content['error']['error_subcode'] ?? null;
                    $errorFbTraceId = $content['error']['fbtrace_id'] ?? null;
                }
            }
        }

        return new InstagramException($errorMessage, (int) $e->getCode(), $e, $errorType, $errorCode, $errorSubcode, $errorFbTraceId);
    }

    protected function getApiBaseUrl(): string
    {
        return 'https://graph.instagram.com';
    }

    protected function buildClient(array $clientConfig): ClientInterface
    {
        $config = array_merge([
            RequestOptions::TIMEOUT => 60,
            RequestOptions::CONNECT_TIMEOUT => 10,
        ], $clientConfig);

        return new Client($config);
    }
}
