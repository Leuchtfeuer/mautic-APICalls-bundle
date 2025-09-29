<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

class PropertySearchService
{

    public function getValue(mixed $content, string $valueKey, string $objectKey = ''): string
    {
        if ($objectKey !== '') {

            $result = $this->findByKey($content, $objectKey);

            if ($result === null) {
                return '';
            }

            $result = $this->findByKey($result, $valueKey);

        } else {
            $result = $this->findByKey($content, $valueKey);
        }

        return (is_scalar($result)) ? (string) $result : '';
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