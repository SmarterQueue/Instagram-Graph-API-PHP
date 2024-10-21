<?php

use PHPUnit\Framework\TestCase;
use SmarterQueue\Instagram\InstagramAppCredentials;

/**
 * @internal
 *
 * @coversNothing
 */
class InstagramAppCredentialsTest extends TestCase
{
    public function testProperties()
    {
        $credentials = new InstagramAppCredentials('Test id', 'Test secret');

        $this->assertSame('Test id', $credentials->clientId);
        $this->assertSame('Test secret', $credentials->clientSecret);
    }
}
