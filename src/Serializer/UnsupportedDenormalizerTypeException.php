<?php

namespace App\Serializer;

class UnsupportedDenormalizerTypeException extends \Exception
{

    public function __construct(string $unsupportedType, array $supportedTypes)
    {
        parent::__construct("Type $unsupportedType is not supported for this normalizer. Supported types: " . implode(', ', $supportedTypes));
    }
}