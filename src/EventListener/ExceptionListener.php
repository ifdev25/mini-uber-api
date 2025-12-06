<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle JSON responses for API calls
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        $response = [
            'error' => true,
            'message' => $exception->getMessage(),
            'code' => $statusCode,
        ];

        // Add validation errors if it's a validation exception
        if ($exception instanceof ValidationFailedException) {
            $violations = $exception->getViolations();
            $errors = [];

            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            $response['violations'] = $errors;
            $statusCode = 422; // Unprocessable Entity
        }

        // In development, add stack trace
        if ($_ENV['APP_ENV'] === 'dev') {
            $response['trace'] = $exception->getTraceAsString();
            $response['file'] = $exception->getFile();
            $response['line'] = $exception->getLine();
        }

        $event->setResponse(new JsonResponse($response, $statusCode));
    }
}
