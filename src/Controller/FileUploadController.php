<?php

namespace App\Controller;

use App\Repository\EntityNotFoundException;
use App\Repository\UserRepositoryInterface;
use App\Request\UserAvatarUploadRequest;
use App\Service\FileHelper\FileDeleteException;
use App\Service\FileHelper\FileUploadException;
use App\Service\FileHelper\FileUploadHelper;
use Symfony\Component\HttpFoundation\File\File as FileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FileUploadController extends ApiController
{
    public function uploadUserAvatar(
        int $user_id,
        Request $request,
        FileUploadHelper $uploadHelper,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        if($user->getId() !== $user_id) {
            return $this->respondUnauthorized();
        }
        if($request->headers->get('Content-Type') === 'application/json') {
            $uploadRequest = $serializer->deserialize(
                $request->getContent(),
                UserAvatarUploadRequest::class,
                'json'
            );
            $violations = $validator->validate($uploadRequest);
            if($violations->count() > 0) {
                return $this->json($violations, 400);
            }
            $tmpPath = sys_get_temp_dir().'/sf_upload'.uniqid();
            file_put_contents($tmpPath, $uploadRequest->getDecodedData());
            $uploadedFile = new FileObject($tmpPath);
        } else {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('avatar');
        }
        $violations = $validator->validate($uploadedFile, FileUploadHelper::getConstraintsForAvatar());
        if($violations->count() > 0) {
            return $this->json($violations, 400);
        }
        try {
            $filename = $uploadHelper->uploadUserAvatarImage($uploadedFile, $user_id);
        } catch (FileDeleteException|FileUploadException $e) {
            return $this->respondInternalServerError($e);
        }
        if (is_file($uploadedFile->getPathname())) {
            unlink($uploadedFile->getPathname());
        }
        dd('Success', $uploadHelper->getPublicPath($filename));
    }
}