# Instagram Graph API Wrapper for PHP
This provides a PHP wrapper around [Meta's Instagram (with Instagram login) API](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/).

# Usage
There are 2 main API service classes - the `InstagramApi` and `InstagramOAuthHelper`
* `InstagramApi` - The base class for sending requests and parsing the response & handling errors
* `InstagramOAuthHelper` - A helper class with methods to:
  * Build a login URL
  * Exchange the authorization code for a short-lived token
  * Exchange a short-lived token for a long-lived token
  * Refresh a long-lived token

On successful responses, the API services will return a `InstagramResponse` object that will contain
the http code, headers, and decoded data.

On unsuccessful requests or PHP exceptions, the API services will throw a `InstagramException` object that will contain
the message, http code, status code (if any), sub code (if any), error type (if any), fb trace id (if any),
and the previous exception (if any).

## Create a link to the login page
```php
// Set up the API instances.
$instagramApi = new InstagramApi($clientId, $clientSecret);
$instagramOAuthHelper = new InstagramOAuthHelper($instagramApi);

// Setup CSRF protection.
$state = bin2hex(random_bytes(32));
$_SESSION['instagram_state'] = $state;

// Setup scopes and callback.
$scopes = ['instagram_business_basic'];
$redirectUri = 'https://my-site.com/oauth-callback';

// Generate login URL & redirect to it.
$loginUrl = $instagramOAuthHelper->getLoginUrl($scopes, $redirectUri, $state);
header('Location: ' . $loginUrl);
```

## Exchange code for access tokens
```php
// Set up the API instances.
$instagramApi = new InstagramApi($clientId, $clientSecret);
$instagramOAuthHelper = new InstagramOAuthHelper($instagramApi);

// GET params.
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

// CSRF Check.
if (!isset($_SESSION['instagram_state']) || $state === null || $state !== $_SESSION['instagram_state'])
{
	throw new \Exception('CSRF Error - State mismatch');
}

// Exchange authorization code for short-lived token.
$redirectUri = 'https://my-site.com/oauth-callback';
$response = $instagramOAuthHelper->getShortLivedAccessToken($code, $redirectUri);
$shortLivedToken = $response->decodedData['access_token'];

// Exchange short-lived token for long-lived token.
$response = $instagramOAuthHelper->getLongLivedAccessToken($shortLivedToken);
$longLivedToken = $response->decodedData['access_token'];
```

## Refresh the long-lived access token
```php
// Set up the API instances.
$instagramApi = new InstagramApi($clientId, $clientSecret);
$instagramOAuthHelper = new InstagramOAuthHelper($instagramApi);
$longLivedToken = 'Replace with your token here';
$instagramApi->setAccessToken($longLivedToken);

// Refresh the token.
$response = $instagramOAuthHelper->refreshLongLivedAccessToken($longLivedToken);
$refreshedToken = $response->decodedData['access_token'];
```

## General requests
```php
// Set up the API instance.
$instagramApi = new InstagramApi($clientId, $clientSecret);
$longLivedToken = 'Replace with your token here';
$instagramApi->setAccessToken($longLivedToken);

// Get my details
$response = $instagramApi->get('me', ['fields' => 'id,username,profile_picture_url']);
```

## Handling errors
```php
try {
  $containerResponse = $instagramApi->post('me/doesntexist');
} catch (InstagramException $e) {
  $logger->log('Error getting my details', [
    'message' => $e->getMessage(),
    'code' => $e->getCode(),
    'subCode' => $e->subcode,
    'httpCode' => $e->httpCode,
  ]);
}
```
-----
By [SmarterQueue](https://smarterqueue.com)

