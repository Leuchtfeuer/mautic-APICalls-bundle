<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

class PropertySearchService
{

    public function getValue(mixed $content, string $valueKey, string $objectKey = ''): string
    {
        $data = $content;

        if ('' !== $objectKey) {
            $data = $this->resolveKeyOrPath($content, $objectKey);

            if (null === $data) {
                return '';
            }
        }

        if ('' === $valueKey) {
            return is_scalar($data) ? (string) $data : '';
        }

        $result = $this->resolveKeyOrPath($data, $valueKey);

        return is_scalar($result) ? (string) $result : '';
    }

    private function resolveKeyOrPath(mixed $data, string $key): mixed
    {
        if ($this->isPathExpression($key)) {
            return $this->resolvePath($data, $key);
        }

        return $this->findByKey($data, $key);
    }

    private function isPathExpression(string $key): bool
    {
        return str_contains($key, '.') || str_contains($key, '[');
    }

    private function resolvePath(mixed $data, string $path): mixed
    {
        $current = $data;

        foreach ($this->parsePath($path) as $segment) {
            if (null === $current) {
                return null;
            }

            $current = $this->getSegmentValue($current, $segment);

            if (null === $current) {
                return null;
            }
        }

        return $current;
    }

    /**
     * @return list<string>
     */
    private function parsePath(string $path): array
    {
        $normalized = preg_replace('/\[(\d+)\]/', '.$1', $path) ?? $path;
        $normalized = ltrim($normalized, '.');

        if ('' === $normalized) {
            return [];
        }

        return explode('.', $normalized);
    }

    private function getSegmentValue(mixed $data, string $segment): mixed
    {
        if (is_array($data)) {
            if (array_key_exists($segment, $data)) {
                return $data[$segment];
            }

            if (ctype_digit($segment) && array_key_exists((int) $segment, $data)) {
                return $data[(int) $segment];
            }

            return null;
        }

        if (is_object($data)) {
            if (property_exists($data, $segment) || isset($data->$segment)) {
                return $data->$segment;
            }

            return null;
        }

        return null;
    }

    private function findByKey(mixed $data, string $key): mixed
    {
        if (is_array($data)) {
            return $this->handleArrays($data, $key);
        }

        if (is_object($data)) {
            return $this->handleObjects($data, $key);
        }

        return null;
    }



    public function handleObjects(mixed $data, string $key):mixed
    {
        if (is_object($data)) {

            if (property_exists($data, $key)) {
                return $data->$key;
            }

            foreach (get_object_vars($data) as $value) {
                $result = $this->findByKey($value, $key);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }


    public function handleArrays(mixed $data, string $key):mixed
    {
        if (is_array($data)) {

            if (array_key_exists($key, $data)) {
                return $data[$key];
            }

            foreach ($data as $item) {
                $result = $this->findByKey($item, $key);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }




}