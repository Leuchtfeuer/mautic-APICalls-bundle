<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Form\Type;

use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\ApiCallsPreSubmitFormListener;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
            new ApiCallsPreSubmitFormListener(),
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
}
