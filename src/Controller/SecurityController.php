<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\ChangePasswordType;
use App\Form\RegistrationType;
use App\Model\AccountData;
use App\Model\PhoneNumber;
use App\Model\UserData;
use App\Repository\EntityNotFoundException;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
use App\Request\ChangePasswordRequest;
use App\Request\RegisterUserRequest;
use App\Security\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends ApiController
{
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $JWTManager,
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $registrationRequest = new RegisterUserRequest();
        $form = $this->createForm(RegistrationType::class, $registrationRequest);
        $form->submit($requestData);

        if (!$form->isValid()) {
            throw new FormException($form);
        }
        $user = new User(
            null,
            new UserData(
                $registrationRequest->userData['firstName'],
                $registrationRequest->userData['lastName'],
                $registrationRequest->userData['description'],
                new PhoneNumber(
                    $registrationRequest->userData['phoneNumberPrefix'],
                    $registrationRequest->userData['phoneNumber']
                )
            ),
            new AccountData(
                $registrationRequest->email,
                $registrationRequest->password,
                ['ROLE_USER']
            ),
            new \DateTime('now')
        );
        $user->getAccountData()->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        try {
            $userRepository->add($user);
        } catch (UniqueConstraintViolationException $e) {
            return match ($e->getViolatedConstraint()) {
                'username_inx', 'email_inx' =>
                    $this->setStatusCode(409)
                        ->respondWithError('INVALID_ENTITY', 'Email is not unique.'),
                'users_phone_number_prefix_phone_number_key' =>
                $this->setStatusCode(409)
                    ->respondWithError('INVALID_ENTITY', 'User with this data already exists.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('INVALID_ENTITY', $e->getMessage()),
            };
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return $this->response(['token' => $JWTManager->create($user)]);
    }

    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $changePasswordRequest = new ChangePasswordRequest();
        $form = $this->createForm(ChangePasswordType::class, $changePasswordRequest);
        $form->submit($requestData);

        if (!$form->isValid()) {
            throw new FormException($form);
        }
        if ($changePasswordRequest->newPassword === $changePasswordRequest->oldPassword) {
            return $this
                ->setStatusCode(400)
                ->respondWithError('BAD_REQUEST', 'New password cannot be the same as old password.');
        }
        $jwtUser = $this->getUser();
        try {
            $currentUser = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        if(!$passwordHasher->isPasswordValid($currentUser, $changePasswordRequest->oldPassword)) {
            return $this->setStatusCode(409)->respondWithError('WRONG_PASSWORD', 'Old password is incorrect.');
        }
        $newPasswordEncoded = $passwordHasher->hashPassword($currentUser, $changePasswordRequest->newPassword);
        $userRepository->updateUserPassword($currentUser, $newPasswordEncoded);
        return $this->respondWithSuccessMessage('Password changed successfully.');
    }
}