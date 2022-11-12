<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\RegistrationType;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
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
            $registrationRequest->userData['firstName'],
            $registrationRequest->userData['lastName'],
            $registrationRequest->email,
            $registrationRequest->password,
            $registrationRequest->userData['phoneNumberPrefix'],
            $registrationRequest->userData['phoneNumber'],
            $registrationRequest->userData['description'],
            ['ROLE_USER']
        );
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
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
}