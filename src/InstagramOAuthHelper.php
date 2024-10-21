<?php

namespace SmarterQueue\Instagram;

class InstagramOAuthHelper
{
    public function __construct(protected InstagramApi $instagramApi) {}

    public function getLoginUrl(array $scopes, string $redirectUri, ?string $state = null): string
    {
        $credentials = $this->instagramApi->getAppCredentials();
        $options = [
            'client_id' => $credentials->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
        ];
        if (null !== $state) {
            $options['state'] = $state;
        }

        return sprintf('https://www.instagram.com/oauth/authorize?%s', http_build_query($options));
    }

    public function getShortLivedAccessToken(string $code, string $redirectUri): InstagramResponse
    {
        $credentials = $this->instagramApi->getAppCredentials();
        $options = [
            'form_params' => [
                'client_id' => $credentials->clientId,
                'client_secret' => $credentials->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ],
        ];

        return $this->instagramApi->sendRequest('POST', 'oauth/access_token', $options, '', 'https://api.instagram.com');
    }

    public function getLongLivedAccessToken(string $accessToken): InstagramResponse
    {
        $credentials = $this->instagramApi->getAppCredentials();
        $params = [
            'client_secret' => $credentials->clientSecret,
            'access_token' => $accessToken,
            'grant_type' => 'ig_exchange_token',
        ];

        return $this->instagramApi->get('access_token', $params, '');
    }

    public function refreshLongLivedAccessToken(string $accessToken): InstagramResponse
    {
        $credentials = $this->instagramApi->getAppCredentials();
        $params = [
            'client_secret' => $credentials->clientSecret,
            'access_token' => $accessToken,
            'grant_type' => 'ig_refresh_token',
        ];

        return $this->instagramApi->get('refresh_access_token', $params, '');
    }
}
