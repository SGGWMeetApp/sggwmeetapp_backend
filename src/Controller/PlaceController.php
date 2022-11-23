<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\PlaceReviewType;
use App\Form\ReviewAssessmentType;
use App\Model\PlaceReview;
use App\Model\ReviewAssessment;
use App\Repository\EntityNotFoundException;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\PlaceReviewRepositoryInterface;
use App\Repository\PublicEventRepositoryInterface;
use App\Repository\ReviewAssessmentRepositoryInterface;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
use App\Request\ReviewAssessmentRequest;
use App\Request\ReviewPlaceRequest;
use App\Response\PlaceReviewResponse;
use App\Serializer\PlaceNormalizer;
use App\Serializer\PlaceReviewNormalizer;
use App\Serializer\PublicEventNormalizer;
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

    /**
     * @throws SerializerExceptionInterface
     */
    public function getPlaceEventsAction(
        int $place_id,
        PlaceRepositoryInterface $placeRepository,
        PublicEventRepositoryInterface $publicEventRepository
    ): JsonResponse
    {
        try {
            $place = $placeRepository->findOrFail($place_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        $placeEvents = $publicEventRepository->findAllForPlace($place);
        $publicEventNormalizer = new PublicEventNormalizer();
        $normalizedEvents = [];
        foreach ($placeEvents as $event) {
            $normalizedEvents [] = $publicEventNormalizer->normalize($event);
        }
        return $this->response(["events" => $normalizedEvents]);
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
        $placeReview = new PlaceReview(null, $place_id, $user, $addReviewRequest->isPositive, $addReviewRequest->comment);
        try {
            $placeReviewRepository->add($placeReview);
        } catch (UniqueConstraintViolationException $e) {
            return match ($e->getViolatedConstraint()) {
                'rating_unq_inx' => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', 'Rating by this user already exists for this place.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', $e->getMessage()),
            };
        }
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
        int $review_id,
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
            $placeReview = $placeReviewRepository->findOrFail($place_id, $review_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        if ($placeReview->getAuthor()->getId() != $user->getId()) {
            return $this->respondUnauthorized();
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
        int $review_id,
        UserRepositoryInterface $userRepository,
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
        if ($requestData['isPositive'] === null) {
            $reviewAssessmentRequest->isPositive = null;
        }
        $jwtUser = $this->getUser();
        try {
            $reviewer = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $placeReview = $placeReviewRepository->findOrFail($place_id, $review_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        try {
            // Update old assessment if exists
            $assessment = $reviewAssessmentRepository->findOrFail($place_id, $review_id, $reviewer->getId());
            if($reviewAssessmentRequest->isPositive !== null) {
                $assessment->setIsPositive($reviewAssessmentRequest->isPositive);
                $reviewAssessmentRepository->update($assessment);
            } else {
                $reviewAssessmentRepository->delete($assessment);
            }
        } catch (EntityNotFoundException) {
            // Otherwise, add new assessment
            if($reviewAssessmentRequest->isPositive !== null) {
                $assessment = new ReviewAssessment(
                    $review_id,
                    $placeReview->getAuthor()->getId(),
                    $reviewer->getId(),
                    $reviewAssessmentRequest->isPositive
                );
                $reviewAssessmentRepository->add($assessment);
            }
        }
        return $this->response([]);
    }
}