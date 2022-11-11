<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\PlaceReviewType;
use App\Form\ReviewAssessmentType;
use App\Model\PlaceReview;
use App\Repository\EntityNotFoundException;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\PlaceReviewRepositoryInterface;
use App\Repository\ReviewAssessmentRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Request\ReviewAssessmentRequest;
use App\Request\ReviewPlaceRequest;
use App\Response\PlaceReviewResponse;
use App\Serializer\PlaceNormalizer;
use App\Serializer\PlaceReviewNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class PlaceController extends ApiController
{
    /**
     * @throws SerializerExceptionInterface
     */
    public function getPlaceDetailsAction(
        int $place_id,
        PlaceRepositoryInterface $placeRepository,
        PlaceReviewRepositoryInterface $placeReviewRepository
    ): JsonResponse
    {
        try {
            $place = $placeRepository->findOrFail($place_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        $placeNormalizer = new PlaceNormalizer();
        $normalizedPlace = $placeNormalizer->normalize($place);
        $placeReviews = $placeReviewRepository->findAllForPlace($place_id);
        $placeReviewsNormalizer = new PlaceReviewNormalizer();
        $normalizedReviews = [];
        foreach($placeReviews as $placeReview) {
            $normalizedReviews [] = $placeReviewsNormalizer->normalize($placeReview);
        }

        return $this->response([
            ...$normalizedPlace,
            "rating" => [
                "positivePercent" => $place->getRatingPercent(),
                "reviews" => $normalizedReviews
            ]
        ]);
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function getPlacesAction(Request $request, PlaceRepositoryInterface $placeRepository): JsonResponse
    {
        // TODO: Get filters from request (converted to filters object)
        // TODO: Get filtered places from database
        $places = $placeRepository->findAll();
        $placeNormalizer = new PlaceNormalizer();
        $normalizedPlaces = [];
        foreach($places as $place) {
            $normalizedPlace = $placeNormalizer->normalize($place);
            $normalizedPlaces [] = [
                ...$normalizedPlace,
                "reviewSummary" => [
                    "positivePercent" => $place->getRatingPercent(),
                    "reviewsCount" => $place->getReviewsCount()
                ]
            ];
        }
        return $this->response(['places' => $normalizedPlaces]);
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
        PlaceRepositoryInterface $placeRepository,
        PlaceReviewRepositoryInterface $placeReviewRepository,
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $addReviewRequest = new ReviewPlaceRequest();
        $this->handleAddPlaceReviewRequest($addReviewRequest, $requestData);
        try {
            $placeRepository->findOrFail($place_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        $placeReview = new PlaceReview($place_id, $user, $addReviewRequest->isPositive, $addReviewRequest->comment);
        $placeReviewRepository->add($placeReview);
        return new PlaceReviewResponse($placeReview);
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
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        $placeReview
            ->setIsPositive($updateReviewRequest->isPositive)
            ->setComment($updateReviewRequest->comment);
        $placeReviewRepository->update($placeReview);
        return new PlaceReviewResponse($placeReview);
    }

    public function reviewAssessment(
        Request $request,
        int $place_id,
        int $author_id,
        PlaceReviewRepositoryInterface $placeReviewRepository,
        ReviewAssessmentRepositoryInterface $reviewAssessmentRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $reviewAssessmentRequest = new ReviewAssessmentRequest();
        $form = $this->createForm(ReviewAssessmentType::class, $reviewAssessmentRequest);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
        try {
            $placeReview = $placeReviewRepository->findOrFail($place_id, $author_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        // Below TODOs will be feasible when db schema gets updated
        // TODO: Check if user already reviewed (update his review if necessary)
        // TODO: if user did not review add his review
        return $this->response([]);
    }
}