<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\PlaceReviewType;
use App\Model\PlaceReview;
use App\Repository\EntityNotFoundException;
use App\Repository\PlaceReviewRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Request\ReviewPlaceRequest;
use App\Response\PlaceReviewResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PlaceController extends ApiController
{
    public function getPlaceDetailsAction(int $place_id): JsonResponse
    {
        // 1. Try to get place by id from db
        // 2. If null return 404 not found
        // 3. Return transformed place
        return $this->response([
            "id" => $place_id,
            "name" => "Kartka",
            "description" => "Bufet w budynku 34",
            "photoPath" => "",
            "textLocation" => "Nowoursynowska 161, budynek 34",
            "rating" => [
                "positivePercent" => 67.12,
                "reviews" => [
                    [
                        "id" => 1,
                        "comment" => "Bardzo dobre jedzenie",
                        "author" => [
                            "firstName" => "Jan",
                            "lastName" => "Kowalski",
                            "email" => "email@example.com",
                            "avatarUrl" => ""
                        ],
                        "upvoteCount" => 100,
                        "downvoteCount" => 5,
                        "publicationDate" => "2022-11-01T12:34:56.500Z",
                        "isPositive" => true
                    ],
                    [
                        "id" => 2,
                        "comment" => "Automat z kawą mnie oszukał!",
                        "author" => [
                            "firstName" => "Piotr",
                            "lastName" => "Czarny",
                            "email" => "czarny@email.com",
                            "avatarUrl" => ""
                        ],
                        "upvoteCount" => 80,
                        "downvoteCount" => 35,
                        "publicationDate" => "2022-08-13T15:34:26.130Z",
                        "isPositive" => false
                    ],
                ]
            ]
        ]);
    }

    public function getPlacesAction(Request $request): JsonResponse
    {
        // 1. Get filters from request (converted to filters object)

        // 2. Get filtered places from database
        return $this->response(['places' => [
            [
                "id" => 1,
                "name" => "Kartka",
                "geolocation" => [
                    "latitude" => 12.345,
                    "longitude" => 12.345
                ],
                "locationCategoryCodes" => [
                    "RESTAURANT"
                ],
                "photoPath" => "",
                "reviewSummary" => [
                    "positivePercent" => 67.12,
                    "reviewsCount" => 12
                ]
            ],
            [
                "id" => 2,
                "name" => "Dziekanat 161",
                "geolocation" => [
                    "latitude" => 34.152,
                    "longitude" => 12.345
                ],
                "locationCategoryCodes" => [
                    "RESTAURANT",
                    "BAR"
                ],
                "photoPath" => "",
                "reviewSummary" => [
                    "positivePercent" => 67.12,
                    "reviewsCount" => 12
                ]
            ],
        ]]);
    }

    public function getPlaceEventsAction(int $place_id): JsonResponse {
        return $this->response(["events" => [
            [
                "id" => 1,
                "name" => "Planszówki",
                "description" => "Zapraszamy na świąteczną edycję planszówek! Wybierz jedną z setek gier i baw się razem z nami!",
                "startDate" => "2022-12-23T18:30:00.000Z",
                "locationData" => [
                    "name" => "Dziekanat 161"
                ],
                "author" => [
                    "firstName" => "Joanna",
                    "lastName" => "Nowak",
                    "email" => "joanna.nowak@email.com"
                ],
                "canEdit" => true
            ],
            [
                "id" => 2,
                "name" => "Środowe Disco",
                "description" => "Już w tą środę widzimy się na parkiecie w Dziekanacie! Dobra zabawa gwarantowana! Do 22:00 bilet 10 zł, Po 22:00 15 zł.",
                "startDate" => "2022-11-06T21:00:00.000Z",
                "locationData" => [
                    "name" => "Dziekanat 161"
                ],
                "author" => [
                    "firstName" => "Jerzy",
                    "lastName" => "Dudek",
                    "email" => "jerzy.dudek@example.com"
                ],
                "canEdit" => false
            ]
        ]]);
    }

    public function addReview(
        Request $request,
        int $place_id,
        PlaceReviewRepositoryInterface $placeReviewRepository,
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $addReviewRequest = new ReviewPlaceRequest();
        $this->handleAddPlaceReviewRequest($addReviewRequest, $requestData);
        // TODO: Find out if the place with given id exists
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        $placeReview = new PlaceReview($place_id, $user->getId(), $addReviewRequest->isPositive, $addReviewRequest->comment);
        $placeReview->setReviewId(1);
        $placeReviewRepository->add($placeReview);
        return new PlaceReviewResponse($placeReview, $user);
    }

    private function handleAddPlaceReviewRequest(ReviewPlaceRequest $request, mixed $requestData): void
    {
        $form = $this->createForm(PlaceReviewType::class, $request);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
    }

    public function editReview (
        Request $request,
        int $place_id,
        PlaceReviewRepositoryInterface $placeReviewRepository,
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $updateReviewRequest = new ReviewPlaceRequest();
        $this->handleAddPlaceReviewRequest($updateReviewRequest, $requestData);
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $placeReview = $placeReviewRepository->findOrFail($place_id, $user->getId());
        } catch (EntityNotFoundException $e) {
            return $this->respondNotFound();
        }
        $placeReview
            ->setIsPositive($updateReviewRequest->isPositive)
            ->setComment($updateReviewRequest->comment);
        $placeReviewRepository->update($placeReview);
        return new PlaceReviewResponse($placeReview, $user);
    }

    public function reviewAssessment(Request $request, int $place_id, int $review_id): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        //TODO poprawic
        if($review_id==1)
            return $this->response([]);
        else
            return $this->respondNotFound();
    }
}