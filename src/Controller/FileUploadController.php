<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\AvatarUploadType;
use App\Form\Base64FileUploadType;
use App\Repository\UserRepositoryInterface;
use App\Request\UserAvatarUploadRequest;
use App\Service\FileHelper\FileUploadException;
use App\Service\FileHelper\FileUploadHelper;
use App\Service\SecurityHelper\JWTIdentityHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileUploadController extends ApiController
{
    public function uploadUserAvatar(
        int $user_id,
        Request $request,
        FileUploadHelper $uploadHelper,
        UserRepositoryInterface $userRepository,
        JWTIdentityHelper $identityHelper
    ): JsonResponse
    {
        $user = $identityHelper->getUser();
        if($user->getId() !== $user_id) {
            return $this->respondUnauthorized();
        }
        if($request->headers->get('Content-Type') === 'application/json') {
            $decodedImage = $this->retrieveAvatarImageDataFromJsonRequest($request);
            $uploadedFile = $uploadHelper->saveFileContentsToTemp($decodedImage);
        } else {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('base64file');
        }
        $fileValidationForm = $this->createForm(AvatarUploadType::class);
        $fileValidationForm->submit(['avatar' => $uploadedFile]);
        if(!$fileValidationForm->isValid()) {
            throw new FormException($fileValidationForm);
        }
        try {
            $filename = $uploadHelper->uploadUserAvatarImage($uploadedFile, $user_id);
        } catch (FileUploadException $e) {
            return $this->respondInternalServerError($e);
        }
        if (is_file($uploadedFile->getPathname())) {
            unlink($uploadedFile->getPathname());
        }
        $relativeAvatarPath = FileUploadHelper::USER_AVATAR_DIR.'/'.$filename;
        $user->getUserData()->setAvatarUrl($relativeAvatarPath);
        try {
            $userRepository->update($user);
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return $this->response([
            'avatarUrl' => $uploadHelper->getPublicPath(FileUploadHelper::USER_AVATAR_DIR.'/'.$filename)
        ]);
    }

    private function retrieveAvatarImageDataFromJsonRequest(Request $request): string
    {
        $uploadRequest = new UserAvatarUploadRequest();
        $requestData = json_decode($request->getContent(), true);
        $form = $this->createForm(Base64FileUploadType::class, $uploadRequest);
        $form->submit($requestData);
        if(!$form->isValid()) {
            throw new FormException($form);
        }
        return $uploadRequest->getDecodedData();
    }
}