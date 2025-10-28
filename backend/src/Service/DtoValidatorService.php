<?php

namespace App\Service;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DtoValidatorService
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Désérialise et valide un DTO à partir du contenu JSON.
     *
     * @template T
     *
     * @param string $content Le contenu JSON
     * @param class-string<T> $dtoClass La classe du DTO à créer
     *
     * @return array{dto: T|null, errors: JsonResponse|null}
     */
    public function validateDto(string $content, string $dtoClass): array
    {
        try {
            // Désérialisation
            $dto = $this->serializer->deserialize(
                $content,
                $dtoClass,
                'json',
            );

            // Validation
            $errors = $this->validator->validate($dto);

            if (\count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return [
                    'dto' => null,
                    'errors' => new JsonResponse([
                        'error' => 'Validation failed',
                        'violations' => $errorMessages,
                    ], 400),
                ];
            }

            return ['dto' => $dto, 'errors' => null];
        }
        catch (Exception $e) {
            return [
                'dto' => null,
                'errors' => new JsonResponse([
                    'error' => 'Invalid JSON format',
                    'message' => $e->getMessage(),
                ], 400),
            ];
        }
    }
}
