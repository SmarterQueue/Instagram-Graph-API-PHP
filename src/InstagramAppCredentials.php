<?php

namespace SmarterQueue\Instagram;

class InstagramAppCredentials
{
    public function __construct(public readonly string $clientId, public readonly string $clientSecret) {}
}
