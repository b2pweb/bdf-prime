<?php

namespace Php74;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

class SimpleEntity extends Model
{
    private ?int $id;
    protected string $firstName;
    public string $lastName;

    public function id(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): SimpleEntity
    {
        $this->id = $id;
        return $this;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): SimpleEntity
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): SimpleEntity
    {
        $this->lastName = $lastName;
        return $this;
    }
}

class SimpleEntityMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'simple_entity',
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('firstName')
            ->string('lastName')
        ;
    }
}

class EntityWithEmbedded extends Model
{
    private ?int $id = null;
    private ?SimpleEntity $embedded;

    /**
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return EntityWithEmbedded
     */
    public function setId(?int $id): EntityWithEmbedded
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return SimpleEntity|null
     */
    public function embedded(): ?SimpleEntity
    {
        return $this->embedded;
    }

    /**
     * @param SimpleEntity|null $embedded
     * @return EntityWithEmbedded
     */
    public function setEmbedded(?SimpleEntity $embedded): EntityWithEmbedded
    {
        $this->embedded = $embedded;
        return $this;
    }
}

class EntityWithEmbeddedMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'with_embedded',
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->embedded('embedded', SimpleEntity::class, fn (FieldBuilder $builder) => $builder
                ->string('firstName')->alias('emb_fn')
                ->string('lastName')->alias('emb_ln')
            )
        ;
    }
}
