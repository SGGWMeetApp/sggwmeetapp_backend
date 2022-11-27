<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Filter\UserFilters;
use App\Form\SearchUsersForGroupType;
use App\Form\UpdateUserType;
use App\Repository\EntityNotFoundException;
use App\Repository\UserRepositoryInterface;
use App\Request\GetUsersEligibleForGroupRequest;
use App\Request\UpdateUserRequest;
use App\Security\User;
use App\Serializer\UserNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class UserController extends ApiController
{
    /**
     * @throws SerializerExceptionInterface
     */
    public function getUsersEligibleForGroupAction(
        int $group_id,
        Request $request,
        UserRepositoryInterface $userRepository,
        UserNormalizer $userNormalizer
    ): JsonResponse
    {
        $requestParameters = $request->query->all();
        $eligibleUsersRequest = new GetUsersEligibleForGroupRequest();
        $form = $this->createForm(SearchUsersForGroupType::class, $eligibleUsersRequest);
        $form->submit($requestParameters);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
        $filters = new UserFilters();
        $filters->setFullName($eligibleUsersRequest->namePhrase);
        $filters->setDisallowedGroups([$group_id]);
        $users = $userRepository->findAll($filters);
        $normalizedUsers = [];
        foreach ($users as $user) {
            $normalizedUsers [] = $userNormalizer->normalize($user, null, ['modelProperties' => [
                'id',
                'firstName',
                'lastName',
                'email',
                'avatarUrl'
            ]]);
        }
        return $this->response(["users" => $normalizedUsers]);
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function getUserData(
        int $user_id,
        UserRepositoryInterface $userRepository,
        UserNormalizer $userNormalizer
    ): JsonResponse
    {
        try {
            $user = $userRepository->findByIdOrFail($user_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        return $this->response([
            "email" => $user->getEmail(),
            "userData" => $userNormalizer->normalize($user, null, ['modelProperties' => [
                'firstName',
                'lastName',
                'phoneNumberPrefix',
                'phoneNumber',
                'description',
                'avatarUrl'
            ]])
        ]);
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function editUserData(
        Request $request,
        int $user_id,
        UserRepositoryInterface $userRepository,
        UserNormalizer $userNormalizer
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $updateUserRequest = new UpdateUserRequest();
        $form = $this->createForm(UpdateUserType::class, $updateUserRequest);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
        $jwtUser = $this->getUser();
        try {
            $currentUser = $userRepository->findOrFail($jwtUser->getUserIdentifier());
            $userToUpdate = $userRepository->findByIdOrFail($user_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        if(!$currentUser->isEqualTo($userToUpdate)) {
            return $this->respondUnauthorized();
        }
        $this->updateUserWithRequestData($userToUpdate, $updateUserRequest);
        $userRepository->update($userToUpdate);
        return $this->response([
            "email" => $userToUpdate->getEmail(),
            "userData" => $userNormalizer->normalize($userToUpdate, null, ['modelProperties' => [
                'firstName',
                'lastName',
                'phoneNumberPrefix',
                'phoneNumber',
                'description',
                'avatarUrl'
            ]])
        ]);
    }

    private function updateUserWithRequestData(User $userToUpdate, UpdateUserRequest $updateUserRequest)
    {
        $userData = $updateUserRequest->userData;
        if ($firstName = $userData['firstName'] ?? null) {
            $userToUpdate->setFirstName($firstName);
        }
        if ($lastName = $userData['lastName'] ?? null) {
            $userToUpdate->setLastName($lastName);
        }
        if ($phonePrefix = $userData['phoneNumberPrefix'] ?? null) {
            $userToUpdate->setPhonePrefix($phonePrefix);
        }
        if ($phone = $userData['phoneNumber'] ?? null) {
            $userToUpdate->setPhone($phone);
        }
        if (array_key_exists('description', $userData)) {
            $userToUpdate->setDescription($userData['description']);
        }
    }

}

