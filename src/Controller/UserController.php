<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Filter\UserFilters;
use App\Form\SearchUsersForGroupType;
use App\Repository\EntityNotFoundException;
use App\Repository\UserRepositoryInterface;
use App\Request\GetUsersEligibleForGroupRequest;
use App\Serializer\UserNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class UserController extends ApiController
{
    /**
     * @throws SerializerExceptionInterface
     */
    public function getUsersEligibleForGroupAction(int $group_id, Request $request, UserRepositoryInterface $userRepository): JsonResponse
    {
        $requestData = $request->query->all();
        $eligibleUsersRequest = new GetUsersEligibleForGroupRequest();
        $form = $this->createForm(SearchUsersForGroupType::class, $eligibleUsersRequest);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
        $filters = new UserFilters();
        $filters->setFullName($eligibleUsersRequest->namePhrase);
        $filters->setDisallowedGroups([$group_id]);
        $users = $userRepository->findAll($filters);
        $userNormalizer = new UserNormalizer();
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
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        try {
            $user = $userRepository->findByIdOrFail($user_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        $userNormalizer = new UserNormalizer();
        return $this->response([
            "email" => $user->getEmail(),
            "userData" => $userNormalizer->normalize($user)
        ]);
    }

    public function editUserData(Request $request, int $user_id): JsonResponse
    {
        return $this->response([
            "email" => "jan_kowalski@example.com",
            "userData" => [
                "firstName" => "Jan",
                "lastName" => "Kowalski",
                "phoneNumber" => "123456789",
                "description" => null,
                "avatarUrl" => ""
            ]
        ]);
    }

}

