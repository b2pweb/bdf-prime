<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Hydrator\HydratorRegistry;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformTypesInterface;

if (!class_exists(Hydrator_Bdf_Prime_TestEntity::class)) {

    /**
     * Hydrator class for {@link \Bdf\Prime\TestEntity}
     */
    final class Hydrator_Bdf_Prime_TestEntity extends \Bdf\Prime\TestEntity implements \Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface
    {
        private $__instantiator;

        private $__Bdf_Prime_TestEmbeddedEntity_hydrator;


        public function __construct($__Bdf_Prime_TestEmbeddedEntity_hydrator)
        {
            $this->__Bdf_Prime_TestEmbeddedEntity_hydrator = $__Bdf_Prime_TestEmbeddedEntity_hydrator;
        }

        /**
         * {@inheritdoc}
         */
        final public function setPrimeInstantiator(InstantiatorInterface $instantiator)
        {
            $this->__instantiator = $instantiator;
        }

        /**
         * {@inheritdoc}
         */
        final public function setPrimeMetadata(Metadata $metadata)
        {
        }

        /**
         * {@inheritdoc}
         */
        final public function hydrate($object, array $data)
        {
            if (isset($data['id'])) {
                $object->id = $data['id'];
            }

            if (isset($data['name'])) {
                $object->name = $data['name'];
            }

            if (isset($data['foreign'])) {
                if (is_array($data['foreign'])) {
                    $__rel_foreign = $object->foreign;

                    if ($__rel_foreign instanceof \Bdf\Prime\TestEmbeddedEntity) {
                        $this->__Bdf_Prime_TestEmbeddedEntity_hydrator->hydrate($__rel_foreign, $data['foreign']);
                    }

                } else {
                    $object->foreign = $data['foreign'];
                }
            }

            if (isset($data['dateInsert'])) {
                $object->dateInsert = $data['dateInsert'];
            }


        }

        /**
         * {@inheritdoc}
         */
        final public function extract($object, array $attributes = [])
        {
            if (empty($attributes)) {
                return ['id' => ($object->id), 'name' => ($object->name), 'foreign' => (($__rel_foreign = $object->foreign) === null ? null : $__rel_foreign instanceof \Bdf\Prime\TestEmbeddedEntity ? $this->__Bdf_Prime_TestEmbeddedEntity_hydrator->extract($__rel_foreign) : $__rel_foreign), 'dateInsert' => ($object->dateInsert)];
            } else {
                $attributes = array_flip($attributes);
                $values = [];

                if (isset($attributes['id'])) {
                    $values['id'] = $object->id;
                }if (isset($attributes['name'])) {
                    $values['name'] = $object->name;
                }if (isset($attributes['foreign'])) {
                    $values['foreign'] = ($__rel_foreign = $object->foreign) === null ? null : $__rel_foreign instanceof \Bdf\Prime\TestEmbeddedEntity ? $this->__Bdf_Prime_TestEmbeddedEntity_hydrator->extract($__rel_foreign) : $__rel_foreign;
                }if (isset($attributes['dateInsert'])) {
                    $values['dateInsert'] = $object->dateInsert;
                }

                return $values;
            }
        }

        /**
         * {@inheritdoc}
         */
        final public function flatExtract($object, array $attributes = null)
        {
            if (empty($attributes)) {
                $data = ['id' => ($object->id), 'name' => ($object->name), 'dateInsert' => ($object->dateInsert)];
                { //START accessor for foreign
                    $__tmp_0 = $object->foreign;

                    if ($__tmp_0 === null) {
                        $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\TestEmbeddedEntity', 1);
                        $object->foreign = $__tmp_0;
                    }
                    $__embedded = $__tmp_0;
                } //END accessor for foreign

                $data['foreign.id'] = $__embedded->id;

                return $data;
            } else {
                $data = [];

                if (isset($attributes['id'])) {
                    $data['id'] = $object->id;
                }
                if (isset($attributes['name'])) {
                    $data['name'] = $object->name;
                }
                if (isset($attributes['foreign.id'])) {
                    { //START accessor for foreign
                        $__tmp_0 = $object->foreign;

                        if ($__tmp_0 === null) {
                            $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\TestEmbeddedEntity', 1);
                            $object->foreign = $__tmp_0;
                        }
                        $__embedded = $__tmp_0;
                    } //END accessor for foreign

                    $data['foreign.id'] = $__embedded->id;
                }
                if (isset($attributes['dateInsert'])) {
                    $data['dateInsert'] = $object->dateInsert;
                }


                return $data;
            }
        }

        /**
         * {@inheritdoc}
         */
        final public function flatHydrate($object, array $data, PlatformTypesInterface $types)
        {
            $typeinteger = $types->get('integer');
            $typestring = $types->get('string');
            $typedateTime = $types->get('dateTime');
            if (isset($data['id'])) {
                $value = $typeinteger->fromDatabase($data['id']);
                $object->id = $value;
            }

            if (isset($data['name'])) {
                $value = $typestring->fromDatabase($data['name']);
                $object->name = $value;
            }

            if (isset($data['foreign_key'])) {
                $value = $typeinteger->fromDatabase($data['foreign_key']);
                { //START accessor for foreign
                    $__tmp_0 = $object->foreign;

                    if ($__tmp_0 === null) {
                        $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\TestEmbeddedEntity', 1);
                        $object->foreign = $__tmp_0;
                    }
                    $__embedded = $__tmp_0;
                } //END accessor for foreign

                $__embedded->id = $value;
            }

            if (isset($data['date_insert'])) {
                $value = $typedateTime->fromDatabase($data['date_insert']);
                $object->dateInsert = $typedateTime->fromDatabase($value);
            }


        }

        /**
         * {@inheritdoc}
         */
        final public function extractOne($object, $attribute)
        {
            switch($attribute) {
                case 'id':
                    return $object->id;
                case 'name':
                    return $object->name;
                case 'foreign.id':
                { //START accessor for foreign
                    $__tmp_0 = $object->foreign;

                    if ($__tmp_0 === null) {
                        $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\TestEmbeddedEntity', 1);
                        $object->foreign = $__tmp_0;
                    }
                    $__embedded = $__tmp_0;
                } //END accessor for foreign

                    return $__embedded->id;
                case 'dateInsert':
                    return $object->dateInsert;
                case 'foreign':
                { //START accessor for foreign
                    $__tmp_0 = $object->foreign;

                    if ($__tmp_0 === null) {
                        $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\TestEmbeddedEntity', 1);
                        $object->foreign = $__tmp_0;
                    }
                    $__foreign = $__tmp_0;
                } //END accessor for foreign

                    return $__foreign;
                default:
                    return null;
            }
        }

        /**
         * {@inheritdoc}
         */
        final public function hydrateOne($object, $attribute, $value)
        {
            switch($attribute) {
                case 'id':
                    $object->id = $value;
                    break;
                case 'name':
                    $object->name = $value;
                    break;
                case 'foreign.id':
                { //START accessor for foreign
                    $__tmp_0 = $object->foreign;

                    if ($__tmp_0 === null) {
                        $__tmp_0 = $this->__instantiator->instantiate('Bdf\Prime\TestEmbeddedEntity', 1);
                        $object->foreign = $__tmp_0;
                    }
                    $__embedded = $__tmp_0;
                } //END accessor for foreign

                    $__embedded->id = $value;
                    break;
                case 'dateInsert':
                    $object->dateInsert = $value;
                    break;
                case 'foreign':
                    $object->foreign = $value;
                    break;
            }
        }



        /**
         * {@inheritdoc}
         */
        public static function supportedPrimeClassName()
        {
            return 'Bdf\Prime\TestEntity';
        }

        /**
         * {@inheritdoc}
         */
        public static function embeddedPrimeClasses()
        {
            return array (
                0 => 'Bdf\\Prime\\TestEmbeddedEntity',
            );
        }
    }
}

if (!class_exists(Hydrator_Bdf_Prime_EmbeddedEntity::class)) {

    /**
     * Hydrator class for {@link \Bdf\Prime\EmbeddedEntity}
     */
    final class Hydrator_Bdf_Prime_EmbeddedEntity extends \Bdf\Prime\EmbeddedEntity implements \Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface
    {
        private $__instantiator;



        public function __construct()
        {

        }

        /**
         * {@inheritdoc}
         */
        final public function setPrimeInstantiator(InstantiatorInterface $instantiator)
        {
            $this->__instantiator = $instantiator;
        }

        /**
         * {@inheritdoc}
         */
        final public function setPrimeMetadata(Metadata $metadata)
        {
        }

        /**
         * {@inheritdoc}
         */
        final public function hydrate($object, array $data)
        {
            if (isset($data['id'])) {
                $object->setId($data['id']);
            }


        }

        /**
         * {@inheritdoc}
         */
        final public function extract($object, array $attributes = [])
        {
            if (empty($attributes)) {
                return ['id' => ($object->getId())];
            } else {
                $attributes = array_flip($attributes);
                $values = [];

                if (isset($attributes['id'])) {
                    $values['id'] = $object->getId();
                }

                return $values;
            }
        }

        /**
         * {@inheritdoc}
         */
        final public function flatExtract($object, array $attributes = null)
        {
            if (empty($attributes)) {
                $data = ['id' => ($object->getId())];


                return $data;
            } else {
                $data = [];

                if (isset($attributes['id'])) {
                    $data['id'] = $object->getId();
                }


                return $data;
            }
        }

        /**
         * {@inheritdoc}
         */
        final public function flatHydrate($object, array $data, PlatformTypesInterface $types)
        {
            $typeinteger = $types->get('integer');
            if (isset($data['id'])) {
                $object->setId($typeinteger->fromDatabase($data['id']));
            }

        }

        /**
         * {@inheritdoc}
         */
        final public function extractOne($object, $attribute)
        {
            switch($attribute) {
                case 'id':
                    return $object->getId();
                default:
                    return null;
            }
        }

        /**
         * {@inheritdoc}
         */
        final public function hydrateOne($object, $attribute, $value)
        {
            switch($attribute) {
                case 'id':
                    $object->setId($value);
                    break;
            }
        }



        /**
         * {@inheritdoc}
         */
        public static function supportedPrimeClassName()
        {
            return 'Bdf\Prime\EmbeddedEntity';
        }

        /**
         * {@inheritdoc}
         */
        public static function embeddedPrimeClasses()
        {
            return array (
            );
        }
    }
}

/** @var HydratorRegistry $registry */

$registry->add('Bdf\Prime\EmbeddedEntity', new Hydrator_Bdf_Prime_EmbeddedEntity());
$registry->factory('Bdf\Prime\TestEntity', function($registry) {
    return new Hydrator_Bdf_Prime_TestEntity(
        $registry->get('Bdf\Prime\EmbeddedEntity')
    );
});