<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends AbstractController
{

    public function appInfo(): Response
    {
        return $this->response([
            'version' => '0.1',
            'name' => 'SGGW MeetApp REST API',
            'status' => 'WIP'
        ]);
    }

    protected int $statusCode = 200;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function response(array $data, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    public function respondWithError(string $errorCode, string $errorMessage, array $headers = []): JsonResponse
    {
        $data = [
            'errorCode' => $errorCode,
            'message' => $errorMessage
        ];
        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    public function respondWithSuccessMessage(string $message, array $headers = []): JsonResponse
    {
        $data = [
            'message' => $message
        ];
        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    public function respondUnauthenticated(string $message = 'Unauthenticated.'): JsonResponse
    {
        return $this->setStatusCode(401)->respondWithError('MISSING_AUTH', $message);
    }

    public function respondNotFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->setStatusCode(404)->response(['message' => $message]);
    }

    public function respondCreated($data = []): JsonResponse
    {
        return $this->setStatusCode(201)->response($data);
    }

    public function respondInternalServerError(\Throwable $exception): JsonResponse
    {
        $this->logger->error($exception->getMessage(), $exception->getTrace());
        return $this->setStatusCode(500)->respondWithError(
            'INTERNAL_SERVER_ERROR',
            'Internal server error. Please report it to app developers ASAP.'
        );
    }
}