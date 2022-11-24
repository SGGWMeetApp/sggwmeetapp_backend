<?php

namespace App\Service\FileHelper;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadHelper
{
    const USER_AVATAR_DIR = 'user_avatar';

    private Filesystem $filesystem;

    private RequestStackContext $requestStackContext;

    private LoggerInterface $logger;

    private string $publicAssetBaseUrl;

    /**
     * @param Filesystem $uploadsFilesystem
     * @param RequestStackContext $requestStackContext
     * @param LoggerInterface $logger
     * @param string $uploadedAssetsBaseUrl
     */
    public function __construct(
        Filesystem          $uploadsFilesystem,
        RequestStackContext $requestStackContext,
        LoggerInterface     $logger,
        string              $uploadedAssetsBaseUrl)
    {
        $this->filesystem = $uploadsFilesystem;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
    }

    /**
     * @throws FileUploadException
     * @throws FileDeleteException
     */
    public function uploadUserAvatarImage(File $file, ?string $existingFileName = null): string
    {
        $newFilename = $this->uploadFile($file, self::USER_AVATAR_DIR, true);

        if($existingFileName !== null) {
            try {
                $this->filesystem->delete(self::USER_AVATAR_DIR.DIRECTORY_SEPARATOR.$existingFileName);
            } catch (FilesystemException) {
                throw new FileDeleteException($existingFileName);
            } catch (FileNotFoundException) {
                $this->logger->alert(sprintf('Old uploaded file "%s" was missing when trying to delete.', $existingFileName));
            }
        }

        return $newFilename;
    }

    public function getPublicPath(string $path): string
    {
        $fullPath = $this->publicAssetBaseUrl.'/'.$path;
        // if it's already absolute, just return
        if (str_contains($fullPath, '://')) {
            return $fullPath;
        }
        // needed if you deploy under a subdirectory
        return $this->requestStackContext
                ->getBasePath().$fullPath;
    }

    /**
     * @return resource
     * @throws FailedToOpenFilestreamException
     */
    public function readStream(string $path)
    {
        try {
            $resource = $this->filesystem->readStream($path);
        } catch (FilesystemException) {
            throw new FailedToOpenFilestreamException($path);
        }
        return $resource;
    }

    /**
     * @throws FileDeleteException
     */
    public function deleteFile(string $path): void
    {
        try {
            $this->filesystem->delete($path);
        } catch (FilesystemException) {
            throw new FileDeleteException($path);
        }
    }

    /**
     * @throws FileUploadException
     */
    public function uploadFile(File $file, string $directory, bool $isPublic, ?string $fileName = null): string
    {
        if($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }
        $newFilename = $fileName !== null ? $fileName.$file->guessExtension() :
            Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME)) .'-'.uniqid().'.'.$file->guessExtension();

        $stream = fopen($file->getPathname(), 'r');
        try {
            $this->filesystem->writeStream(
                $directory . DIRECTORY_SEPARATOR . $newFilename,
                $stream,
                [
                    'visibility' => $isPublic ? Visibility::PUBLIC : Visibility::PRIVATE
                ]
            );
        } catch (FilesystemException) {
            throw new FileUploadException($newFilename);
        }
        if(is_resource($stream)) {
            fclose($stream);
        }

        return $newFilename;
    }
}