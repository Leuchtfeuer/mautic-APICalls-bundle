<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ApiCallsPreSubmitFormListener implements EventSubscriberInterface
{
    private const SECRET_FIELDS = ['password', 'authorization_header'];

    public function __construct(
        private CampaignActionSecretService $secretService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmitData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $data = $event->getData();

        if (!is_array($data)) {
            return;
        }

        $changed = false;

        foreach (self::SECRET_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $sanitized = $this->secretService->sanitizeForFormDisplay($data[$field]);

            if ($sanitized !== $data[$field]) {
                $data[$field] = $sanitized;
                $changed      = true;
            }
        }

        if ($changed) {
            $event->setData($data);
        }
    }

    public function preSubmitData(FormEvent $event): void
    {
        $data = $event->getData();

        if (!is_array($data)) {
            return;
        }

        foreach (self::SECRET_FIELDS as $field) {
            $submitted = $data[$field] ?? '';

            if ('' !== $submitted && null !== $submitted) {
                $data[$field] = $this->secretService->encryptIfNeeded((string) $submitted);

                continue;
            }

            $original = $this->getOriginalPropertyValue($event, $field);

            if (null !== $original && '' !== $original) {
                $data[$field] = $this->secretService->encryptIfNeeded($original);
            }
        }

        $event->setData($data);
    }

    private function getOriginalPropertyValue(FormEvent $event, string $field): ?string
    {
        $originalData = $event->getForm()->getRoot()->getData();

        if (!is_array($originalData)) {
            return null;
        }

        $value = $originalData['properties'][$field] ?? $originalData[$field] ?? null;

        return is_string($value) ? $value : null;
    }
}
