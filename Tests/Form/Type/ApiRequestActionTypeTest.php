<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Form\Type;

use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\ApiCallsPreSubmitFormListener;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class ApiRequestActionTypeTest extends TestCase
{
    /** @var ExecutionContextInterface&MockObject */
    private ExecutionContextInterface $context;

    private ApiRequestActionType $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context  = $this->createMock(ExecutionContextInterface::class);
        $this->formType = new ApiRequestActionType(
            $this->createMock(FieldModel::class),
            new ApiCallsPreSubmitFormListener($this->createMock(CampaignActionSecretService::class)),
        );
    }

    public function testValidateRegexAcceptsValidPattern(): void
    {
        $this->context->expects(self::never())->method('buildViolation');

        $this->formType->validateRegex('/[\w\.-]+@[\w\.-]+\.\w+/', $this->context);
    }

    public function testValidateRegexAcceptsEmptyValue(): void
    {
        $this->context->expects(self::never())->method('buildViolation');

        $this->formType->validateRegex(null, $this->context);
        $this->formType->validateRegex('', $this->context);
    }

    public function testValidateRegexRejectsInvalidPattern(): void
    {
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects(self::once())->method('addViolation');

        $this->context->expects(self::once())
            ->method('buildViolation')
            ->with('leuchtfeuer.mautic-apicalls-bundle.regex.invalid')
            ->willReturn($violationBuilder);

        $this->formType->validateRegex('[invalid', $this->context);
    }

    public function testValidateUrlParametersUsesFlatFormData(): void
    {
        $this->mockRootFormData([
            'method' => 'POST',
        ]);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects(self::once())->method('addViolation');

        $this->context->expects(self::once())
            ->method('buildViolation')
            ->with('leuchtfeuer.mautic-apicalls-bundle.get.method.required')
            ->willReturn($violationBuilder);

        $this->formType->validateUrlParameters('email=test@example.com', $this->context);
    }

    public function testValidateUrlParametersUsesNestedCampaignFormData(): void
    {
        $this->mockRootFormData([
            'properties' => [
                'method' => 'POST',
            ],
        ]);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects(self::once())->method('addViolation');

        $this->context->expects(self::once())
            ->method('buildViolation')
            ->with('leuchtfeuer.mautic-apicalls-bundle.get.method.required')
            ->willReturn($violationBuilder);

        $this->formType->validateUrlParameters('email=test@example.com', $this->context);
    }

    public function testValidateBodyByContentTypeUsesFlatFormData(): void
    {
        $this->mockRootFormData([
            'method'      => 'GET',
            'contentType' => 'application/json',
        ]);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects(self::once())->method('addViolation');

        $this->context->expects(self::once())
            ->method('buildViolation')
            ->with('leuchtfeuer.mautic-apicalls-bundle.method.body.must.be.empty')
            ->willReturn($violationBuilder);

        $this->formType->validateBodyByContentType('{"test": true}', $this->context);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mockRootFormData(array $data): void
    {
        $root = $this->createMock(FormInterface::class);
        $root->expects(self::once())->method('getData')->willReturn($data);

        $this->context->expects(self::once())->method('getRoot')->willReturn($root);
    }
}
