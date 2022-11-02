<?php


namespace App\Http;


use Symfony\Component\HttpFoundation\JsonResponse;

class ApiExceptionResponse extends JsonResponse
{
    /**
     * ApiResponse constructor.
     */
    public function __construct(string $message, string $errorCode, array $errors = [], int $status = 422, array $headers = [], bool $json = false)
    {
        parent::__construct($this->format($message, $errorCode, $errors), $status, $headers, $json);
    }

    /**
     * Format the API exception response.
     */
    private function format(string $message, string $errorCode, array $errors = [])
    {

        $response = [
            'errorCode' => $errorCode,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}