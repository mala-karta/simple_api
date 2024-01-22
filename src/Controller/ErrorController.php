<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class ErrorController extends AbstractController
{
    public function show(\Exception $exception): JsonResponse
    {
        if($exception instanceof MethodNotAllowedException) {
            return $this->json([
                'error' => $exception->getMessage(),
            ])->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
        }
        if($exception instanceof BadRequestException) {
            return $this->json([
                'error' => $exception->getMessage(),
            ])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
        return $this->json([
            'error' => $exception->getMessage(),
        ])->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}