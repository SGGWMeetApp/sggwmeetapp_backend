<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PlaceController extends ApiController
{
    public function getPlaceDetails(int $place_id): JsonResponse //bylo getPlaceDetailsAction ale mi nie działało w postmanie wiec zmieniłem nazwe funkcji
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


    public function editReview(Request $request, int $place_id, int $review_id): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);


        return $this -> response([
            
                "id" => $review_id,
                "comment" => $requestData["comment"],
                "author" => [
                    "firstName" => "Jan",
                    "lastName" => "Kowalski",
                    "email" => "email@example.com",
                    "avatarUrl" => ""
                ],
                "upvoteCount" => 100,
                "downvoteCount" => 5,
                "publicationDate" => "2022-11-02T12:34:56.500Z",
                "isPositive" => $requestData["isPositive"]
            
        ]);
    }

    public function reviewAssessment(Request $request, int $place_id, int $review_id): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        //TODO porpawic
        if($review_id==1)
            return $this->response(["code"=>"200"]);
        else
        return $this->respondNotFound();
    }
}