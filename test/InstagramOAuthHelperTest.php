<?php

use PHPUnit\Framework\TestCase;
use SmarterQueue\Instagram\InstagramApi;
use SmarterQueue\Instagram\InstagramAppCredentials;
use SmarterQueue\Instagram\InstagramOAuthHelper;
use SmarterQueue\Instagram\InstagramResponse;

/**
 * @internal
 *
 * @coversNothing
 */
class InstagramOAuthHelperTest extends TestCase
{
    protected $mockInstagramApi;
    protected InstagramOAuthHelper $oAuthHelper;

    protected function setUp(): void
    {
        $appCredentials = new InstagramAppCredentials('test-client-id', 'test-client-secret');
        $this->mockInstagramApi = $this->createMock(InstagramApi::class);
        $this->mockInstagramApi->method('getAppCredentials')->willReturn($appCredentials);
        $this->oAuthHelper = new InstagramOAuthHelper($this->mockInstagramApi);
    }

    public function testGetLoginUrl()
    {
        $scopes = ['scope1', 'scope2'];
        $redirectUri = 'https://example.com/redirect';
        $state = 'test-state';

        $loginUrl = $this->oAuthHelper->getLoginUrl($scopes, $redirectUri, $state);
        $expectedUrl = 'https://www.instagram.com/oauth/authorize?client_id=test-client-id&redirect_uri=https%3A%2F%2Fexample.com%2Fredirect&scope=scope1%2Cscope2&response_type=code&state=test-state';

        $this->assertSame($expectedUrl, $loginUrl);
    }

    public function testGetShortLivedAccessToken()
    {
        $code = 'test-code';
        $redirectUri = 'https://example.com/redirect';
        $responseBody = ['access_token' => 'short-lived-token'];

        $this->mockInstagramApi->method('sendRequest')->willReturn(new InstagramResponse(200, [], $responseBody));

        $result = $this->oAuthHelper->getShortLivedAccessToken($code, $redirectUri);
        $this->assertInstanceOf(InstagramResponse::class, $result);
        $this->assertSame($responseBody, $result->decodedData);
    }

    public function testGetLongLivedAccessToken()
    {
        $accessToken = 'short-lived-token';
        $responseBody = ['access_token' => 'long-lived-token'];

        $this->mockInstagramApi->method('get')->willReturn(new InstagramResponse(200, [], $responseBody));

        $result = $this->oAuthHelper->getLongLivedAccessToken($accessToken);
        $this->assertInstanceOf(InstagramResponse::class, $result);
        $this->assertSame($responseBody, $result->decodedData);
    }

    public function testRefreshLongLivedAccessToken()
    {
        $accessToken = 'long-lived-token';
        $responseBody = ['access_token' => 'refreshed-long-lived-token'];

        $this->mockInstagramApi->method('get')->willReturn(new InstagramResponse(200, [], $responseBody));

        $result = $this->oAuthHelper->refreshLongLivedAccessToken($accessToken);
        $this->assertInstanceOf(InstagramResponse::class, $result);
        $this->assertSame($responseBody, $result->decodedData);
    }
}
