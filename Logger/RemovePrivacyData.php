<?php

namespace Mollie\Payment\Logger;

use Exception;

class RemovePrivacyData
{
    public function __construct(
        private readonly array $fieldsToRedact
    ) {}

    public function execute(array|object $data): array|object
    {
        if (is_array($data)) {
            return $this->removeFromArray($data);
        }

        if (is_object($data)) {
            return $this->removeFromObject($data);
        }

        throw new Exception('Invalid data type');
    }

    private function removeFromArray(array $data): array|object
    {
        foreach ($this->fieldsToRedact as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $data[$field] = '********';
            }
        }

        foreach ($data as $key => $value) {
            if (is_array($data[$key])) {
                $data[$key] = $this->removeFromArray($data[$key]);
            }

            if (is_object($data[$key])) {
                $data[$key] = $this->removeFromObject($data[$key]);
            }
        }

        return $data;
    }

    private function removeFromObject(object $data): array|object
    {
        $data = clone $data;

        foreach ($this->fieldsToRedact as $field) {
            if (property_exists($data, $field) && is_string($data->$field)) {
                $data->$field = '********';
            }
        }

        foreach (get_object_vars($data) as $key => $value) {
            if (is_array($value)) {
                $data->$key = $this->removeFromArray($value);
            }

            if (is_object($value)) {
                $data->$key = $this->removeFromObject($value);
            }
        }

        return $data;
    }
}
