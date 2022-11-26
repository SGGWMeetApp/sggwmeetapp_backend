<?php

namespace App\Service\FileHelper;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File as FileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class FileUploadHelper
{
    const USER_AVATAR_DIR = 'user_avatar';

    private Filesystem $filesystem;

    private RequestStackContext $requestStackContext;

    private LoggerInterface $logger;

    private string $publicAssetBaseUrl;

    private string $uploadsFilestream;

    public function __construct(
        Filesystem          $uploadsFilesystem,
        RequestStackContext $requestStackContext,
        LoggerInterface     $logger,
        string              $uploadedAssetsBaseUrl,
        string              $uploadsFilestream
    )
    {
        $this->filesystem = $uploadsFilesystem;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
        $this->uploadsFilestream = $uploadsFilestream;
    }

    /**
     * @throws FileUploadException
     */
    public function uploadUserAvatarImage(FileObject $file, int $userId): string
    {
        return $this->uploadFile($file, self::USER_AVATAR_DIR, true, sprintf('user_avatar%d', $userId));
    }

    public function findUploadedFilesByPattern(string $pattern, string $directory): array
    {
        $finder = new Finder();
        $finder
            ->name($pattern)
            ->in($this->uploadsFilestream.'/'.$directory);
        $filesInfo = [];
        if($finder->hasResults()) {
            $filenameIterator = $finder->getIterator();
            foreach ($filenameIterator as $fileInfo) {
                $filesInfo [] = $fileInfo;
            }
        }
        return $filesInfo;
    }

    public static function getConstraintsForAvatar(): array
    {
        return [
            new NotBlank([
                'message' => 'Please select a valid avatar image to upload and upload it under "avatar" key.'
            ]),
            new File([
                'maxSize' => '5M',
                'mimeTypes' => [
                    'image/*'
                ],
            ])
        ];
    }

    public function getPublicPath(string $path): string
    {
        $fullPath = $this->publicAssetBaseUrl.'/'.$path;
        // if it's already absolute, just return
        if (str_contains($fullPath, '://')) {
            return $fullPath;
        }
        // needed if you deploy under a subdirectory
        return $this->requestStackContext->getBasePath().$fullPath;
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
        } catch (FileNotFoundException) {
            $this->logger->alert(sprintf('File "%s" was missing when trying to delete.', $path));
        }
    }

    /**
     * @throws FileUploadException
     */
    public function uploadFile(FileObject $file, string $directory, bool $isPublic, ?string $fileName = null): string
    {
        if($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }
        $newFilename = $fileName !== null ? $fileName.'.'.$file->guessExtension() :
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