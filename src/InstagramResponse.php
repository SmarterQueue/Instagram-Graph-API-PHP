<?php

namespace SmarterQueue\Instagram;

class InstagramResponse
{
    public function __construct(
        public readonly int $httpCode,
        public readonly array $headers,
        public readonly array $decodedData,
    ) {}
}
