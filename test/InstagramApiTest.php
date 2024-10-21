<?php

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SmarterQueue\Instagram\InstagramApi;
use SmarterQueue\Instagram\InstagramException;
use SmarterQueue\Instagram\InstagramResponse;

/**
 * @internal
 *
 * @coversNothing
 */
class InstagramApiTest extends TestCase
{
    protected string $clientId = 'test-client-id';
    protected string $clientSecret = 'test-client-secret';
    protected InstagramApi $instagramApi;
    protected $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(ClientInterface::class);
        $this->instagramApi = new InstagramApi($this->clientId, $this->clientSecret, $this->mockClient);
    }

    public function testSetVersionCode()
    {
        $this->instagramApi->setVersionCode('v2.0');
        $this->assertSame('v2.0', $this->getPrivateProperty($this->instagramApi, 'versionCode'));
    }

    public function testSetAccessToken()
    {
        $this->instagramApi->setAccessToken('test-token');
        $this->assertSame('test-token', $this->getPrivateProperty($this->instagramApi, 'accessToken'));
    }

    public function testGetCredentials()
    {
        $credentials = $this->instagramApi->getAppCredentials();
        $this->assertSame($this->clientId, $credentials->clientId);
        $this->assertSame($this->clientSecret, $credentials->clientSecret);
    }

    public function testGetRequest()
    {
        $endpoint = 'test-endpoint';
        $params = ['param1' => 'value1'];
        $responseBody = ['key' => 'value'];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')->willReturn(json_encode($responseBody));
        $mockResponse->method('getBody')->willReturn($mockStream);
        $this->mockClient->method('request')->willReturn($mockResponse);

        $result = $this->instagramApi->get($endpoint, $params);
        $this->assertInstanceOf(InstagramResponse::class, $result);
        $this->assertSame($responseBody, $result->decodedData);
    }

    public function testPostRequest()
    {
        $endpoint = 'test-endpoint';
        $params = ['param1' => 'value1'];
        $responseBody = ['key' => 'value'];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')->willReturn(json_encode($responseBody));
        $mockResponse->method('getBody')->willReturn($mockStream);
        $this->mockClient->method('request')->willReturn($mockResponse);

        $result = $this->instagramApi->post($endpoint, $params);
        $this->assertInstanceOf(InstagramResponse::class, $result);
        $this->assertSame($responseBody, $result->decodedData);
    }

    public function testSendRequestWithException()
    {
        $this->expectException(InstagramException::class);

        $endpoint = 'test-endpoint';

        $this->mockClient->method('request')->willThrowException(new RequestException('Error', $this->createMock(RequestInterface::class)));

        $this->instagramApi->setAccessToken('test-token');
        $this->instagramApi->get($endpoint);
    }

    protected function getPrivateProperty($object, $property)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
