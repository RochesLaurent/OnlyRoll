<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\DtoValidatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DtoValidatorServiceTest extends TestCase
{
    private SerializerInterface&MockObject $serializer;

    private ValidatorInterface&MockObject $validator;

    private DtoValidatorService $dtoValidatorService;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->dtoValidatorService = new DtoValidatorService(
            $this->serializer,
            $this->validator,
        );
    }

    public function testValidateDtoSuccess(): void
    {
        $content = '{"name":"Test","value":42}';
        $dto = new stdClass();
        $dto->name = 'Test';
        $dto->value = 42;

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($content, stdClass::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn(new ConstraintViolationList());

        $result = $this->dtoValidatorService->validateDto($content, stdClass::class);

        $this->assertSame($dto, $result['dto']);
        $this->assertNull($result['errors']);
    }

    public function testValidateDtoWithValidationErrors(): void
    {
        $content = '{"name":"","value":-1}';
        $dto = new stdClass();

        $violation1 = new ConstraintViolation(
            'Name cannot be blank',
            null,
            [],
            null,
            'name',
            '',
        );

        $violation2 = new ConstraintViolation(
            'Value must be positive',
            null,
            [],
            null,
            'value',
            -1,
        );

        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($violations);

        $result = $this->dtoValidatorService->validateDto($content, stdClass::class);

        $this->assertNull($result['dto']);
        $this->assertInstanceOf(JsonResponse::class, $result['errors']);

        $response = $result['errors'];
        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Validation failed', $data['error']);
        $this->assertArrayHasKey('violations', $data);
        $this->assertCount(2, $data['violations']);
    }

    public function testValidateDtoWithInvalidJson(): void
    {
        $content = '{invalid json}';

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willThrowException(new NotEncodableValueException('Invalid JSON'));

        $result = $this->dtoValidatorService->validateDto($content, stdClass::class);

        $this->assertNull($result['dto']);
        $this->assertInstanceOf(JsonResponse::class, $result['errors']);

        $response = $result['errors'];
        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid JSON format', $data['error']);
    }

    public function testValidateDtoWithEmptyViolationList(): void
    {
        $content = '{"valid":true}';
        $dto = new stdClass();

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $result = $this->dtoValidatorService->validateDto($content, stdClass::class);

        $this->assertNotNull($result['dto']);
        $this->assertNull($result['errors']);
    }

    public function testValidateDtoWithSingleViolation(): void
    {
        $content = '{"name":""}';
        $dto = new stdClass();

        $violation = new ConstraintViolation(
            'This value should not be blank',
            null,
            [],
            null,
            'name',
            '',
        );

        $violations = new ConstraintViolationList([$violation]);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $result = $this->dtoValidatorService->validateDto($content, stdClass::class);

        $this->assertNull($result['dto']);
        $this->assertInstanceOf(JsonResponse::class, $result['errors']);

        $data = json_decode($result['errors']->getContent(), true);
        $this->assertCount(1, $data['violations']);
        $this->assertEquals('This value should not be blank', $data['violations']['name']);
    }

    public function testValidateDtoPreservesPropertyPath(): void
    {
        $content = '{"nested":{"field":"invalid"}}';
        $dto = new stdClass();

        $violation = new ConstraintViolation(
            'Invalid value',
            null,
            [],
            null,
            'nested.field',
            'invalid',
        );

        $violations = new ConstraintViolationList([$violation]);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $result = $this->dtoValidatorService->validateDto($content, stdClass::class);

        $data = json_decode($result['errors']->getContent(), true);
        $this->assertArrayHasKey('nested.field', $data['violations']);
    }
}
