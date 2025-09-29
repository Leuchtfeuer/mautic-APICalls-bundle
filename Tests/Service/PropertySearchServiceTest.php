<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\PropertySearchService;
use PHPUnit\Framework\TestCase;

class PropertySearchServiceTest extends TestCase
{
    private PropertySearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PropertySearchService();
    }

    public function testGetValueWithoutObjectKey(): void
    {
        $data = json_decode('{"orderId": "ORD-1001", "total": 89.48}');

        $result = $this->service->getValue($data, 'orderId');
        $this->assertEquals('ORD-1001', $result);

        $result = $this->service->getValue($data, 'total');
        $this->assertEquals('89.48', $result);
    }

    public function testGetValueWithObjectKey(): void
    {
        $jsonData = '{
            "orderId": "ORD-1001",
            "customer": {
                "id": 501,
                "name": "John Doe",
                "email": "john.doe@example.com"
            }
        }';

        $data = json_decode($jsonData);

        $result = $this->service->getValue($data, 'name', 'customer');
        $this->assertEquals('John Doe', $result);

        $result = $this->service->getValue($data, 'id', 'customer');
        $this->assertEquals('501', $result);
    }

    public function testGetValueWithNestedObjectKey(): void
    {
        $jsonData = '{
            "customer": {
                "address": {
                    "street": "123 Main St",
                    "city": "New York",
                    "zipCode": "10001"
                }
            }
        }';

        $data = json_decode($jsonData);

        $result = $this->service->getValue($data, 'city', 'address');
        $this->assertEquals('New York', $result);

        $result = $this->service->getValue($data, 'zipCode', 'address');
        $this->assertEquals('10001', $result);
    }

    public function testGetValueFromArrayElements(): void
    {
        $jsonData = '{
            "items": [
                {
                    "sku": "ABC123",
                    "quantity": 2,
                    "price": 19.99
                },
                {
                    "sku": "XYZ789",
                    "quantity": 1,
                    "price": 49.5
                }
            ]
        }';

        $data = json_decode($jsonData);

        // Should return first match
        $result = $this->service->getValue($data, 'sku');
        $this->assertEquals('ABC123', $result);

        $result = $this->service->getValue($data, 'price');
        $this->assertEquals('19.99', $result);
    }

    public function testGetValueWithComplexNestedStructure(): void
    {
        $jsonData = '{
            "orderId": "ORD-1001",
            "status": "shipped",
            "customer": {
                "id": 501,
                "name": "John Doe",
                "email": "john.doe@example.com",
                "address": {
                    "street": "123 Main St",
                    "city": "New York",
                    "zipCode": "10001",
                    "country": "USA"
                },
                "preferences": {
                    "newsletter": true,
                    "notifications": {
                        "email": true,
                        "sms": false
                    }
                }
            },
            "items": [
                {
                    "sku": "ABC123",
                    "name": "Widget A",
                    "quantity": 2,
                    "price": 19.99,
                    "category": {
                        "id": 100,
                        "name": "Electronics"
                    }
                },
                {
                    "sku": "XYZ789",
                    "name": "Gadget B",
                    "quantity": 1,
                    "price": 49.5,
                    "category": {
                        "id": 200,
                        "name": "Tools"
                    }
                }
            ],
            "shipping": {
                "method": "express",
                "cost": 9.99,
                "address": {
                    "street": "456 Oak Ave",
                    "city": "Boston",
                    "zipCode": "02101"
                }
            },
            "payment": {
                "method": "credit_card",
                "last4": "1234",
                "provider": {
                    "name": "Visa",
                    "code": "VI"
                }
            },
            "total": 89.48,
            "metadata": {
                "source": "web",
                "campaign": {
                    "id": "SUMMER2023",
                    "name": "Summer Sale"
                }
            }
        }';

        $data = json_decode($jsonData);

        // Test deep nested access
        $result = $this->service->getValue($data, 'email', 'customer');
        $this->assertEquals('john.doe@example.com', $result);

        $result = $this->service->getValue($data, 'city', 'address');
        $this->assertEquals('New York', $result);

        $result = $this->service->getValue($data, 'name', 'provider');
        $this->assertEquals('Visa', $result);

        $result = $this->service->getValue($data, 'cost', 'shipping');
        $this->assertEquals('9.99', $result);

        // Test array nested objects
        $result = $this->service->getValue($data, 'name', 'category');
        $this->assertEquals('Electronics', $result);
    }

    public function testGetValueReturnsEmptyStringWhenKeyNotFound(): void
    {
        $data = json_decode('{"orderId": "ORD-1001"}');

        $result = $this->service->getValue($data, 'nonexistent');
        $this->assertEquals('', $result);
    }

    public function testGetValueReturnsEmptyStringWhenObjectKeyNotFound(): void
    {
        $data = json_decode('{"orderId": "ORD-1001"}');

        $result = $this->service->getValue($data, 'name', 'nonexistent');
        $this->assertEquals('', $result);
    }

    public function testGetValueReturnsEmptyStringWhenValueKeyNotFoundInObject(): void
    {
        $data = json_decode('{"customer": {"id": 501}}');

        $result = $this->service->getValue($data, 'nonexistent', 'customer');
        $this->assertEquals('', $result);
    }

    public function testHandleArraysWithDirectKey(): void
    {
        $data = ['name' => 'John', 'age' => 30];

        $result = $this->service->handleArrays($data, 'name');
        $this->assertEquals('John', $result);

        $result = $this->service->handleArrays($data, 'age');
        $this->assertEquals(30, $result);
    }

    public function testHandleArraysWithNestedSearch(): void
    {
        $data = [
            'users' => [
                ['name' => 'John', 'id' => 1],
                ['name' => 'Jane', 'id' => 2]
            ]
        ];

        $result = $this->service->handleArrays($data, 'name');
        $this->assertEquals('John', $result);
    }

    public function testHandleArraysReturnsNullWhenNotFound(): void
    {
        $data = ['name' => 'John'];

        $result = $this->service->handleArrays($data, 'nonexistent');
        $this->assertNull($result);
    }

    public function testHandleObjectsWithDirectProperty(): void
    {
        $data = (object)['name' => 'John', 'age' => 30];

        $result = $this->service->handleObjects($data, 'name');
        $this->assertEquals('John', $result);

        $result = $this->service->handleObjects($data, 'age');
        $this->assertEquals(30, $result);
    }

    public function testHandleObjectsWithNestedSearch(): void
    {
        $data = (object)[
            'user' => (object)['name' => 'John', 'id' => 1],
            'settings' => (object)['theme' => 'dark']
        ];

        $result = $this->service->handleObjects($data, 'name');
        $this->assertEquals('John', $result);

        $result = $this->service->handleObjects($data, 'theme');
        $this->assertEquals('dark', $result);
    }

    public function testHandleObjectsReturnsNullWhenNotFound(): void
    {
        $data = (object)['name' => 'John'];

        $result = $this->service->handleObjects($data, 'nonexistent');
        $this->assertNull($result);
    }

    public function testGetValueWithBooleanAndNullValues(): void
    {
        $data = json_decode('{"active": true, "inactive": false, "empty": null}');

        // Boolean true becomes "1" when cast to string
        $result = $this->service->getValue($data, 'active');
        $this->assertEquals('1', $result);

        // Boolean false becomes empty string when cast to string
        $result = $this->service->getValue($data, 'inactive');
        $this->assertEquals('', $result);

        // null values return empty string since they're not scalar
        $result = $this->service->getValue($data, 'empty');
        $this->assertEquals('', $result);
    }

    public function testGetValueWithNumericValues(): void
    {
        $data = json_decode('{"integer": 42, "float": 3.14, "zero": 0, "negative": -25}');

        // Integer becomes string
        $result = $this->service->getValue($data, 'integer');
        $this->assertEquals('42', $result);

        // Float becomes string with decimal precision preserved
        $result = $this->service->getValue($data, 'float');
        $this->assertEquals('3.14', $result);

        // Zero integer becomes "0" string
        $result = $this->service->getValue($data, 'zero');
        $this->assertEquals('0', $result);

        // Negative integer becomes string
        $result = $this->service->getValue($data, 'negative');
        $this->assertEquals('-25', $result);
    }

    public function testGetValueWithObjectValueReturnsEmptyString(): void
    {
        $data = json_decode('{"user": {"name": "John"}, "id": 123}');

        // When trying to get an object value, should return empty string since objects are not scalar
        $result = $this->service->getValue($data, 'user');
        $this->assertEquals('', $result);
    }

    public function testGetValueWithEmptyString(): void
    {
        $data = json_decode('{"name": "", "title": "Manager"}');

        $result = $this->service->getValue($data, 'name');
        $this->assertEquals('', $result);
    }

    public function testFindByKeyWithPrimitiveTypes(): void
    {
        // Test with string - findByKey returns null for primitives, so result is empty
        $result = $this->service->getValue('simple string', 'any');
        $this->assertEquals('', $result);

        // Test with integer - findByKey returns null for primitives, so result is empty
        $result = $this->service->getValue(42, 'any');
        $this->assertEquals('', $result);

        // Test with boolean - findByKey returns null for primitives, so result is empty
        $result = $this->service->getValue(true, 'any');
        $this->assertEquals('', $result);
    }
}