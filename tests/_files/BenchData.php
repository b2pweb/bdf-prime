<?php

namespace Bdf\Prime\Bench;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\ServiceLocator;

require_once 'bench.php';

/**
 * Permet d'initialiser la base de données
 *
 * @package Extensions
 */
class BenchData
{
    /**
     * @var ServiceLocator
     */
    protected $prime;

    /**
     * @var Model[]
     */
    protected $data = [];

    /**
     * BenchData constructor.
     * @param ServiceLocator $prime
     */
    public function __construct(ServiceLocator $prime)
    {
        $this->prime = $prime;
    }

    /**
     * Get the entity
     *
     * @param string $key
     *
     * @return Model
     */
    public function get($key)
    {
        return $this->data[$key];
    }

    /**
     * @param array $entityClassNames
     */
    public function register(array $entityClassNames)
    {
        foreach ($entityClassNames as $className) {
            $this->buildSchema($className);
        }
    }

    /**
     * Ajoute toutes les données commununes aux tests
     */
    public function addAllData()
    {
        $this->register([
            User::class, Pack::class, Customer::class, CustomerPack::class
        ]);

        $this->addPacks();
        $this->addComfortUsers();
        $this->addCommonUser();
        $this->addCommunityUsers();
        $this->addHistoricConfortUsers();
        $this->addReferencingLoaderUsers();
        $this->addReferencingLimitedUsers();
        $this->addReferencingUsers();
        $this->addSimplyUsers();
        $this->addSpotUsers();
    }

    public function clear()
    {
        foreach ($this->data as $entity) {
            $this->prime->repository($entity)->delete($entity);
        }

        $this->data = [];
    }

    /**
     * @param $entityClassName
     */
    protected function buildSchema($entityClassName)
    {
        $this->prime->repository($entityClassName)->schema(true)->migrate();
    }

    /**
     * Ajoute uniquement les customers dans le test pack
     */
    protected function addCustomers()
    {
        $this->addSimplyCustomer();
        $this->addComfortCustomer();
        $this->addSpotCustomer();
        $this->addReferencingLoaderCustomer();
        $this->addReferencingCustomer();
        $this->addCommunityCustomer();
        $this->addSpotUsers();
        $this->addReferencingLimitedCustomer();
        $this->addHistoricConfortCustomer();
    }

    /**
     * Ajoute le customer avec tous les packs
     */
    protected function addCommonCustomer()
    {
        $this->push([
            'CUSTOMER_COMMON' => new Customer([
                'id'                => '330000',
                'name'              => 'customer Common name',
            ]),

            'PACK_CUSTOMER_COMMON_2'  => new CustomerPack(['customerId' => '330000', 'packId' => 2, 'options' => ['autoAnswerNetworkRequest' => false, 'autoAnswerDocumentRequest' => false]]),
            'PACK_CUSTOMER_COMMON_3'  => new CustomerPack(['customerId' => '330000', 'packId' => 3]),
            'PACK_CUSTOMER_COMMON_7'  => new CustomerPack(['customerId' => '330000', 'packId' => 7]),
            'PACK_CUSTOMER_COMMON_5'  => new CustomerPack(['customerId' => '330000', 'packId' => 5]),
            'PACK_CUSTOMER_COMMON_15' => new CustomerPack(['customerId' => '330000', 'packId' => 15]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec tous les packs
     */
    protected function addCommonUser()
    {
        $this->addCommonCustomer();

        $this->push([
            'USER_COMMON' => new User([
                'id'                => '22000001',
                'name'              => 'user Common name',
                'roles'             => [1 => UserRole::ADMINISTRATIVE_MANAGER, 2 => UserRole::PROCUREMENT_MANAGER, 3 => UserRole::CHARTERER, 5 => UserRole::MULTISITE_MANAGER],
                'customer'          => $this->get('CUSTOMER_COMMON')
            ])
        ]);
    }

    /**
     * Ajoute le customer avec le pack simply
     */
    protected function addSimplyCustomer()
    {
        $this->push([
            'CUSTOMER_SIMPLY' => new Customer([
                'id'                => '331111',
                'name'              => 'customer Simply name',
            ]),
            'PACK_CUSTOMER_SIMPLY' => new CustomerPack(['customerId' => '331111', 'packId' => 1]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec le pack simply
     */
    protected function addSimplyUsers()
    {
        $this->addSimplyCustomer();

        $this->push([
            'USER_EXPLOIT_SIMPLY' => new User([
                'id'                => '22111101',
                'name'              => 'user Exploit Simply name',
                'roles'             => [1 => UserRole::CARRIER],
                'customer'          => $this->get('CUSTOMER_SIMPLY'),
            ]),
            'USER_OFFICE_MANAGER_SIMPLY' => new User([
                'id'                => '22111102',
                'name'              => 'user Office Manager Simply name',
                'roles'             => [1 => UserRole::ADMINISTRATIVE_MANAGER],
                'customer'          => $this->get('CUSTOMER_SIMPLY'),
            ])
        ]);
    }

    /**
     * Ajoute le customer avec le pack confort
     */
    protected function addComfortCustomer()
    {
        $this->push([
            'CUSTOMER_CONFORT' => new Customer([
                'id'                => '332222',
                'name'              => 'customer Confort name',
            ]),
            'PACK_CUSTOMER_CONFORT' => new CustomerPack(['customerId' => '332222', 'packId' => 2]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec le pack confort
     */
    protected function addComfortUsers()
    {
        $this->addComfortCustomer();

        $this->push([
            'USER_EXPLOIT_CONFORT' => new User([
                'id'                => '22222201',
                'name'              => 'user Exploit Confort name',
                'roles'             => [1 => UserRole::CARRIER],
                'customer'          => $this->get('CUSTOMER_CONFORT'),
            ]),
            'USER_OFFICE_MANAGER_CONFORT' => new User([
                'id'                => '22222202',
                'name'              => 'user Office Manager Confort name',
                'roles'             => [1 => UserRole::ADMINISTRATIVE_MANAGER],
                'customer'          => $this->get('CUSTOMER_CONFORT'),
            ])
        ]);
    }

    /**
     * Ajoute le customer avec le pack spot
     */
    protected function addSpotCustomer()
    {
        $this->push([
            'CUSTOMER_SPOT' => new Customer([
                'id'                => '333333',
                'name'              => 'customer Spot name',
            ]),
            'PACK_CUSTOMER_SPOT' => new CustomerPack(['customerId' => '333333', 'packId' => 3]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec le pack spot
     */
    protected function addSpotUsers()
    {
        $this->addSpotCustomer();

        $this->push([
            'USER_CHARTERER_SPOT' => new User([
                'id'                => '22333301',
                'name'              => 'user Charterer Spot name',
                'roles'             => [1 => UserRole::CHARTERER],
                'customer'          => $this->get('CUSTOMER_SPOT'),
            ]),
            'USER_PURCHASING_MANAGER_SPOT' => new User([
                'id'                => '22333302',
                'name'              => 'user Purchasing Manager Spot name',
                'roles'             => [1 => UserRole::PROCUREMENT_MANAGER],
                'customer'          => $this->get('CUSTOMER_SPOT'),
            ])
        ]);
    }

    /**
     * Ajoute le customer avec le pack référencement sans limite
     */
    protected function addReferencingCustomer()
    {
        $this->push([
            'CUSTOMER_REFERENCING' => new Customer([
                'id'                => '334444',
                'name'              => 'customer Referencing name',
            ]),
            'PACK_CUSTOMER_REFERENCING' => new CustomerPack(['customerId' => '334444', 'packId' => 5]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec le pack référencement sans limite
     */
    protected function addReferencingUsers()
    {
        $this->addReferencingCustomer();

        $this->push([
            'USER_CHARTERER_REFERENCING' => new User([
                'id'                => '22444401',
                'name'              => 'user Charterer Referencing name',
                'roles'             => [1 => UserRole::CHARTERER],
                'customer'          => $this->get('CUSTOMER_REFERENCING'),
            ]),
            'USER_PURCHASING_MANAGER_REFERENCING' => new User([
                'id'                => '22444402',
                'name'              => 'user Purchasing Manager Spot name',
                'roles'             => [1 => UserRole::PROCUREMENT_MANAGER],
                'customer'          => $this->get('CUSTOMER_REFERENCING'),
            ])
        ]);
    }

    /**
     * Ajoute le customer avec le pack référencement chargeur
     */
    protected function addReferencingLoaderCustomer()
    {
        $this->push([
            'CUSTOMER_REFERENCING_LOADER' => new Customer([
                'id'                => '335555',
                'name'              => 'customer Referencing Loader name',
            ]),
            'PACK_CUSTOMER_REFERENCING_LOADER' => new CustomerPack(['customerId' => '335555', 'packId' => 16]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec le pack référencement chargeur
     */
    protected function addReferencingLoaderUsers()
    {
        $this->addReferencingLoaderCustomer();

        $this->push([
            'USER_CHARTERER_REFERENCING_LOADER' => new User([
                'id'                => '22555501',
                'name'              => 'user Charterer Referencing Loader name',
                'roles'             => [1 => UserRole::CHARTERER],
                'customer'          => $this->get('CUSTOMER_REFERENCING_LOADER'),
            ]),
            'USER_PURCHASING_MANAGER_REFERENCING_LOADER' => new User([
                'id'                => '22555502',
                'name'              => 'user Purchasing Manager Referencing Loader name',
                'roles'             => [1 => UserRole::PROCUREMENT_MANAGER],
                'customer'          => $this->get('CUSTOMER_REFERENCING_LOADER'),
            ])
        ]);
    }

    /**
     * Ajoute le customer avec le pack communauté
     */
    protected function addCommunityCustomer()
    {
        $this->push([
            'CUSTOMER_COMMUNITY' => new Customer([
                'id'                => '336666',
                'name'              => 'customer Community name',
            ]),
            'PACK_CUSTOMER_COMMUNITY_SPOT' => new CustomerPack(['customerId' => '336666', 'packId' => 3]),
            'PACK_CUSTOMER_COMMUNITY' => new CustomerPack(['customerId' => '336666', 'packId' => 15]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec le pack communauté
     */
    protected function addCommunityUsers()
    {
        $this->addCommunityCustomer();

        $this->push([
            'USER_CHARTERER_COMMUNITY' => new User([
                'id'                => '22666601',
                'name'              => 'user Charterer Community name',
                'roles'             => [1 => UserRole::CHARTERER],
                'customer'          => $this->get('CUSTOMER_COMMUNITY'),
            ]),
            'USER_PURCHASING_MANAGER_COMMUNITY' => new User([
                'id'                => '22666602',
                'name'              => 'user Purchasing Manager Community name',
                'roles'             => [1 => UserRole::PROCUREMENT_MANAGER],
                'customer'          => $this->get('CUSTOMER_COMMUNITY'),
            ])
        ]);
    }

    /**
     * Ajoute le customer avec le pack référencement limité
     */
    protected function addReferencingLimitedCustomer()
    {
        $this->push([
            'CUSTOMER_REFERENCING_LIMIT' => new Customer([
                'id'                => '337777',
                'name'              => 'customer with Referencing Limit name',
            ]),
            'PACK_CUSTOMER_REFERENCING_LIMIT_BASE'  => new CustomerPack(['customerId' => '337777', 'packId' => 5]),
            'PACK_CUSTOMER_REFERENCING_LIMIT'       => new CustomerPack(['customerId' => '337777', 'packId' => 14, 'options' => ['invitationsLimit' => 25]]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec le pack référencement limité
     */
    protected function addReferencingLimitedUsers()
    {
        $this->addReferencingLimitedCustomer();

        $this->push([
            'USER_REFERENCING_LIMIT' => new User([
                'id'                => '22777701',
                'name'              => 'user Purchasing Manager Referencing Limit name',
                'roles'             => [1 => UserRole::PROCUREMENT_MANAGER],
                'customer'          => $this->get('CUSTOMER_REFERENCING_LIMIT'),
            ])
        ]);
    }

    /**
     * Ajoute le customer avec le pack référencement limité
     */
    protected function addHistoricConfortCustomer()
    {
        $this->push([
            'CUSTOMER_HISTORIC_CONFORT' => new Customer([
                'id'                => '338888',
                'name'              => 'customer Historic Confort name',
            ]),
            'PACK_CUSTOMER_HISTORIC_CONFORT_BASE'   => new CustomerPack(['customerId' => '338888', 'packId' => 2]),
            'PACK_CUSTOMER_HISTORIC_CONFORT'        => new CustomerPack(['customerId' => '338888', 'packId' => 7]),
        ]);
    }

    /**
     * Ajoute le customer et les users avec le pack référencement limité
     */
    protected function addHistoricConfortUsers()
    {
        $this->addHistoricConfortCustomer();

        $this->push([
            'USER_HISTORIC_EXPLOIT_CONFORT' => new User([
                'id'                => '22888801',
                'name'              => 'user Exploit Historic Confort name',
                'roles'             => [1 => UserRole::CARRIER],
                'customer'          => $this->get('CUSTOMER_HISTORIC_CONFORT'),
            ]),
            'USER_HISTORIC_OFFICE_MANAGER_CONFORT' => new User([
                'id'                => '22888802',
                'name'              => 'user Office Manager Historic Confort name',
                'roles'             => [1 => UserRole::ADMINISTRATIVE_MANAGER],
                'customer'          => $this->get('CUSTOMER_HISTORIC_CONFORT'),
            ]),
        ]);
    }

    /**
     * Ajoute les packs dans le test pack
     */
    protected function addPacks()
    {
        $this->push([
            'PACK_SIMPLY'               => new Pack(['id' => 1, 'label' => 'Simply']),
            'PACK_CONFORT'              => new Pack(['id' => 2, 'label' => 'Confort']),
            'PACK_SPOT'                 => new Pack(['id' => 3, 'label' => 'Spot']),
            'PACK_REFERENCING'          => new Pack(['id' => 5, 'label' => 'Premium']),
            'PACK_HISTORIC_CONFORT'     => new Pack(['id' => 7, 'label' => 'Historique Confort']),
            'PACK_REFERENCING_LIMIT'    => new Pack(['id' => 14, 'label' => 'Référencement']),
            'PACK_COMMUNITY'            => new Pack(['id' => 15, 'label' => 'Communauté']),
            'PACK_REFERENCING_LOADER'   => new Pack(['id' => 16, 'label' => 'Référencement chargeur']),
        ]);
    }

    /**
     * @param array $entities
     */
    public function push($entities = [])
    {
        foreach ($entities as $key => $entity) {
            $this->prime->repository($entity)->replace($entity);
            $this->data[$key] = $entity;
        }
    }
}