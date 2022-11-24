<?php

namespace App\Service\FileHelper;

class FileDeleteException extends \Exception
{
    private string $filename;

    /**
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
        parent::__construct(sprintf('Error deleting file "%s"', $filename));
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

}