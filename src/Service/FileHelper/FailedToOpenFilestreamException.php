<?php

namespace App\Service\FileHelper;

class FailedToOpenFilestreamException extends \Exception
{
    private string $resource;

    /**
     * @param string $resource
     */
    public function __construct(string $resource)
    {
        $this->resource = $resource;
        parent::__construct(sprintf('Cannot open file stream for path "%s"', $resource));
    }

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

}