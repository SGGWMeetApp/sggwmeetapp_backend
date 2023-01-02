<?php

namespace App\EventListener;

use App\Factory\NormalizerFactory;
use App\Http\ApiExceptionResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{

    private NormalizerFactory $normalizerFactory;

    /**
     * ExceptionListener constructor.
     */
    public function __construct(NormalizerFactory $normalizerFactory)
    {
        $this->normalizerFactory = $normalizerFactory;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request   = $event->getRequest();
        if (in_array('application/json', $request->getAcceptableContentTypes()) || in_array('*/*', $request->getAcceptableContentTypes())) {
            $response = $this->createApiExceptionResponse($exception);
            $event->setResponse($response);
        }
    }

    /**
     * Creates the ApiResponse from any Exception
     */
    private function createApiExceptionResponse(\Throwable $exception): ApiExceptionResponse
    {
        $normalizer = $this->normalizerFactory->getNormalizer($exception);
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        try {
            $errors = $normalizer ? $normalizer->normalize($exception) : [];
        } catch (\Throwable $e) {
            $errors = [];
        }

        return new ApiExceptionResponse($exception->getMessage(), 'API_EXCEPTION', $errors, $statusCode);
    }
}