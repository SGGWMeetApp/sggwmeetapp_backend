<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Filter\UserFilters;
use App\Form\SearchUsersForGroupType;
use App\Form\UpdateUserNotificationsType;
use App\Form\UpdateUserType;
use App\Repository\EntityNotFoundException;
use App\Repository\UserRepositoryInterface;
use App\Request\GetUsersEligibleForGroupRequest;
use App\Request\UpdateUserNotificationsRequest;
use App\Request\UpdateUserRequest;
use App\Security\User;
use App\Serializer\UserNormalizer;
use App\Serializer\UserNotificationSettingsNormalizer;
use App\Service\SecurityHelper\JWTIdentityHelper;
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
            "email" => $user->getAccountData()->getEmail(),
            "userData" => $userNormalizer->normalize($user, null, ['modelProperties' => [
                'id',
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
        UserNormalizer $userNormalizer,
        JWTIdentityHelper $identityHelper
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $updateUserRequest = new UpdateUserRequest();
        $form = $this->createForm(UpdateUserType::class, $updateUserRequest);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
        $currentUser = $identityHelper->getUser();
        try {
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
            "email" => $userToUpdate->getAccountData()->getEmail(),
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
            $userToUpdate->getUserData()->setFirstName($firstName);
        }
        if ($lastName = $userData['lastName'] ?? null) {
            $userToUpdate->getUserData()->setLastName($lastName);
        }
        if ($phonePrefix = $userData['phoneNumberPrefix'] ?? null) {
            $userToUpdate->getUserData()->getPhoneNumber()->setPrefix($phonePrefix);
        }
        if ($phone = $userData['phoneNumber'] ?? null) {
            $userToUpdate->getUserData()->getPhoneNumber()->setNumber($phone);
        }
        if (array_key_exists('description', $userData)) {
            $userToUpdate->getUserData()->setDescription($userData['description']);
        }
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function updateUserNotificationSettings(
        Request $request,
        int $user_id,
        UserRepositoryInterface $userRepository,
        UserNotificationSettingsNormalizer $settingsNormalizer,
        JWTIdentityHelper $identityHelper
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $updateUserNotificationsRequest = new UpdateUserNotificationsRequest();
        $form = $this->createForm(UpdateUserNotificationsType::class, $updateUserNotificationsRequest);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }

        $currentUser = $identityHelper->getUser();
        try {
            $userToUpdate = $userRepository->findByIdOrFail($user_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        if(!$currentUser->isEqualTo($userToUpdate)) {
            return $this->respondUnauthorized();
        }
        $changedSettings = array_filter($requestData, fn ($val) => $val !== null);
        $preparedSettings = [];
        foreach ($changedSettings as $settingName => $settingEnabled) {
            $preparedSettings [strtolower(preg_replace("/[A-Z]/","_$0", lcfirst($settingName)))] = $settingEnabled;
        }
        $currentUser->setNotificationSettings($preparedSettings);
        try {
            $userRepository->updateUserNotificationSettings($currentUser, $currentUser->getNotificationSettings());
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return $this->response($settingsNormalizer->normalize($currentUser->getNotificationSettings()));
    }

    public function getUserNotificationSettings(
        int $user_id,
        UserRepositoryInterface $userRepository,
        UserNotificationSettingsNormalizer $settingsNormalizer,
        JWTIdentityHelper $identityHelper
    ): JsonResponse
    {
        $currentUser = $identityHelper->getUser();
        try {
            $userToUpdate = $userRepository->findByIdOrFail($user_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        if(!$currentUser->isEqualTo($userToUpdate)) {
            return $this->respondUnauthorized();
        }
        try {
            return $this->response($settingsNormalizer->normalize($currentUser->getNotificationSettings()));
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

}

