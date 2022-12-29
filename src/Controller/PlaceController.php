<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Factory\NormalizerFactory;
use App\Filter\PlaceFilters;
use App\Form\PlaceFiltersType;
use App\Form\PlaceReviewType;
use App\Form\ReviewAssessmentType;
use App\Model\Place;
use App\Model\PlaceReview;
use App\Model\ReviewAssessment;
use App\Repository\EntityNotFoundException;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\PlaceReviewRepositoryInterface;
use App\Repository\ReviewAssessmentRepositoryInterface;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\EventRepositoryInterface;
use App\Request\PlaceFiltersRequest;
use App\Request\ReviewAssessmentRequest;
use App\Request\ReviewPlaceRequest;
use App\Response\PlaceReviewResponse;
use App\Serializer\PlaceNormalizer;
use App\Serializer\PlaceReviewNormalizer;
use App\Serializer\EventNormalizer;
use App\Service\SecurityHelper\JWTIdentityHelper;
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
        PlaceReviewRepositoryInterface $placeReviewRepository,
        PlaceNormalizer $placeNormalizer,
        PlaceReviewNormalizer $placeReviewsNormalizer,
        ReviewAssessmentRepositoryInterface $reviewAssessmentRepository
    ): JsonResponse
    {
        try {
            $place = $placeRepository->findOrFail($place_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        $normalizedPlace = $placeNormalizer->normalize($place);
        $placeReviews = $placeReviewRepository->findAllForPlace($place_id);
        $reviewIds = array_map(fn($value) => $value->getReviewId(), $placeReviews);
        $userAssessments = $reviewAssessmentRepository->findUserAssessmentsForReviews(11, $reviewIds);
        $normalizedReviews = [];
        foreach($placeReviews as $placeReview) {
            $normalizedReview = $placeReviewsNormalizer->normalize($placeReview);
            $normalizedReview['userVote'] = $userAssessments[$normalizedReview['id']]['isPositive'];
            $normalizedReviews [] = $normalizedReview;
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
    public function getPlacesAction(
        Request $request,
        PlaceRepositoryInterface $placeRepository,
        PlaceNormalizer $placeNormalizer
    ): JsonResponse
    {
        $placeFilters = $this->createPlaceFiltersFromRequest($request);
        $places = $placeRepository->findAll($placeFilters);
        $normalizedPlaces = [];
        /** @var Place $place */
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

    private function createPlaceFiltersFromRequest(Request $request): PlaceFilters
    {
        $requestParameters = $request->query->all();
        $placeFiltersRequest = new PlaceFiltersRequest();
        $form = $this->createForm(PlaceFiltersType::class, $placeFiltersRequest);
        $form->submit($requestParameters);
        if(!$form->isValid()) {
            throw new FormException($form);
        }
        $placeFilters = new PlaceFilters();
        $placeFilters->setName($placeFiltersRequest->name);
        $placeFilters->setCategoryCodes($placeFiltersRequest->categoryCodes);
        return $placeFilters;
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function getPlaceEventsAction(
        int $place_id,
        PlaceRepositoryInterface        $placeRepository,
        EventRepositoryInterface        $eventRepository,
        EventNormalizer                 $eventNormalizer
    ): JsonResponse
    {
        try {
            $place = $placeRepository->findOrFail($place_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        $placeEvents = $eventRepository->findAllPublicEventsForPlace($place);
        $normalizedEvents = [];
        foreach ($placeEvents as $event) {
            $normalizedEvents [] = $eventNormalizer->normalize($event);
        }
        return $this->response(["events" => $normalizedEvents]);
    }

    public function addReview(
        Request $request,
        int $place_id,
        PlaceRepositoryInterface $placeRepository,
        PlaceReviewRepositoryInterface $placeReviewRepository,
        NormalizerFactory $normalizerFactory,
        JWTIdentityHelper $identityHelper
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
        $user = $identityHelper->getUser();
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
        try {
            return new PlaceReviewResponse($placeReview, $normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
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
        NormalizerFactory $normalizerFactory,
        JWTIdentityHelper $identityHelper
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $updateReviewRequest = new ReviewPlaceRequest();
        $this->handleAddPlaceReviewRequest($updateReviewRequest, $requestData);
        $user = $identityHelper->getUser();
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
        try {
            return new PlaceReviewResponse($placeReview, $normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function reviewAssessment(
        Request $request,
        int $place_id,
        int $review_id,
        JWTIdentityHelper $identityHelper,
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
        $reviewer = $identityHelper->getUser();
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