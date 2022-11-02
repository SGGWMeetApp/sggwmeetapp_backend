<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\RegistrationType;
use App\Request\RegisterUserRequest;
use App\Security\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends ApiController
{
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $registrationRequest = new RegisterUserRequest();
        $form = $this->createForm(RegistrationType::class, $registrationRequest);
        $form->submit($requestData);

        if (!$form->isValid()) {
            throw new FormException($form);
        }
        $user = new User(
            $registrationRequest->userData['firstName'],
            $registrationRequest->userData['lastName'],
            $registrationRequest->email,
            $registrationRequest->password,
            ['ROLE_USER']
        );
        //TODO: insert new user to database
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        return $this->response(['token' => $JWTManager->create($user)]);
    }
}