<?php

namespace Bdf\Prime\Serializer;

use Bdf\Serializer\Metadata\Builder\ClassMetadataBuilder;

class User
{
    private $id;
    private $name;

    public function __construct($id = null, $name = null)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}

class UserWithLoader
{
    private $id;
    private $name;

    public function __construct($id = null, $name = null)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    /**
     * @param ClassMetadataBuilder $metadata
     */
    public static function loadSerializerMetadata($metadata)
    {
        $metadata->integer('id', [
            'group' => ['all', 'identifier'],
        ]);
        $metadata->string('name', [
            'group' => 'all',
            'serializedName' => 'testname',
        ]);
    }
}
class UserParentWithAnnotations
{
    /**
     * @var null|int
     */
    private $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }
    public function id()
    {
        return $this->id;
    }
}

class UserWithAnnotations extends UserParentWithAnnotations
{
    /**
     * @var null|string
     */
    private $name;
    /**
     * @var null|Customer
     */
    private $customer;

    public function __construct($id = null, $name = null, ?Customer $customer = null)
    {
        parent::__construct($id);

        $this->name = $name;
        $this->customer = $customer;
    }

    public function name()
    {
        return $this->name;
    }
    public function customer()
    {
        return $this->customer;
    }
}

class UserWithoutAnnotations
{
    private $id;
    private $name;
    private $customer;

    public function __construct($id = null, $name = null, ?Customer $customer = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->customer = $customer;
    }

    public function id()
    {
        return $this->id;
    }
    public function name()
    {
        return $this->name;
    }
    public function customer()
    {
        return $this->customer;
    }
}

class UserWithCustomer
{
    private $id;
    private $name;
    private $customer;

    public function __construct($id = null, $name = null, ?Customer $customer = null)
    {
        $this->id   = $id;
        $this->name = $name;
        $this->customer = $customer;
    }

    public function customer()
    {
        return $this->customer;
    }

    /**
     * @param ClassMetadataBuilder $metadata
     */
    public static function loadSerializerMetadata($metadata)
    {
        $metadata->integer('id')
            ->groups(['all', 'identifier']);

        $metadata->string('name')
            ->groups(['all'])
            ->alias('testname')
            ->since('1.0.0')
            ->until('2.0.0');

        $metadata->add('customer', Customer::class)
            ->groups(['all']);
    }
}

class AbstractCustomer
{
    public $email;

    /**
     * @param ClassMetadataBuilder $metadata
     */
    public static function loadSerializerMetadata($metadata)
    {
        $metadata->string('email', [
            'group' => ['all'],
        ]);
    }
}
class Customer extends AbstractCustomer
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;

    public function __construct($id = null, $name = null)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function id()
    {
        return $this->id;
    }
    public function name()
    {
        return $this->name;
    }
    /**
     * @param ClassMetadataBuilder $metadata
     */
    public static function loadSerializerMetadata($metadata)
    {
        $metadata->integer('id', [
            'group' => ['all', 'identifier'],
        ]);
        $metadata->string('name', [
            'group' => 'all',
            'since' => '1.0.0',
        ]);
    }
}
class CustomerChild extends Customer
{
    public $contact;

    /**
     * @param ClassMetadataBuilder $metadata
     */
    public static function loadSerializerMetadata($metadata)
    {
        parent::loadSerializerMetadata($metadata);

        $metadata->string('contact', [
            'group' => ['all'],
        ]);
    }
}
class CustomerChildChangeInheritance extends Customer
{
    /**
     * @param ClassMetadataBuilder $metadata
     */
    public static function loadSerializerMetadata($metadata)
    {
        parent::loadSerializerMetadata($metadata);

        $metadata->property('name')->groups(['none']);
    }
}
class CustomerChildWithoutMeta extends Customer
{
    public $contact;
}
class DateCollection
{
    /**
     * @var \DateTime[]
     */
    public $date = [];
}

class ReadOnlyEntity
{
    /**
     * @var string
     */
    private $data = 'entity';

    public function __construct($data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        }
    }

    public function data()
    {
        return $this->data;
    }

    /**
     * @param ClassMetadataBuilder $metadata
     */
    public static function loadSerializerMetadata($metadata)
    {
        $metadata->string('data')->readOnly();
    }
}

class ObjectWithUndefinedPropertyType
{
    public $attr;

    public function __construct($attr = null)
    {
        $this->attr = $attr;
    }
}
