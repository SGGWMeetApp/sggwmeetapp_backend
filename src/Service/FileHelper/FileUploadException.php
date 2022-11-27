<?php

namespace App\Service\FileHelper;

class FileUploadException extends \Exception
{
    private string $uploadedFileName;

    /**
     * @param string $uploadedFileName
     */
    public function __construct(string $uploadedFileName)
    {
        $this->uploadedFileName = $uploadedFileName;
        parent::__construct(sprintf('Could not write uploaded file "%s"', $uploadedFileName));
    }

    /**
     * @return string
     */
    public function getUploadedFileName(): string
    {
        return $this->uploadedFileName;
    }

}