<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Serializer\Metadata\Builder\ClassMetadataBuilder;
use DateTimeImmutable;
use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\Relations\Builder\RelationBuilder;

class Document extends Model
{
    public $id;
    public $customerId;
    public $uploaderType;
    public $uploaderId;
    public $uploader;
    public $contact;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class DocumentMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'document_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
                ->autoincrement()->alias('id_')
                
            ->bigint('customerId')
                ->alias('customer_id')
                
            ->string('uploaderType', 60)
                ->alias('uploader_type')
                
            ->bigint('uploaderId')
                ->alias('uploader_id')

            ->embedded('contact', Contact::class, function($builder) {
                $builder
                    ->string('name')->alias('contact_name')->nillable()
                    ->embedded('location', Location::class, function($builder) {
                        $builder
                            ->string('address')->alias('contact_address')->nillable()
                            ->string('city')->alias('contact_city')->nillable();
                    });
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('uploader')
            ->morphTo('uploaderId', 'uploaderType', [
                'admin' => Admin::class.'::id',
                'user' => [
                    'entity'           => User::class,
                    'distantKey'       => 'id',
                ],
            ]);
        
        $builder->on('customer')
            ->belongsTo(Customer::class, 'customerId')
            ->detached();
    }
}

class DocumentEager extends Model
{
    public $id;
    public $customerId;
    public $uploaderType;
    public $uploaderId;
    public $uploader;
    public $contact;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class DocumentEagerMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'document_eager_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
            ->autoincrement()->alias('id_')

            ->bigint('customerId')
            ->alias('customer_id')

            ->string('uploaderType', 60)
            ->alias('uploader_type')

            ->bigint('uploaderId')
            ->alias('uploader_id')

            ->embedded('contact', Contact::class, function($builder) {
                $builder
                    ->string('name')->alias('contact_name')->nillable()
                    ->embedded('location', Location::class, function($builder) {
                        $builder
                            ->string('address')->alias('contact_address')->nillable()
                            ->string('city')->alias('contact_city')->nillable();
                    });
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('uploader')
            ->morphTo('uploaderId', 'uploaderType', [
                'admin' => Admin::class.'::id',
                'user' => [
                    'entity'           => User::class,
                    'distantKey'       => 'id',
                ],
            ])
            ->mode(RelationBuilder::MODE_EAGER);

        $builder->on('customer')
            ->belongsTo(Customer::class, 'customerId')
            ->detached();
    }
}

class Contact
{
    use ArrayInjector;

    public $name;
    public $location;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class User extends Model
{
    public $id;
    public $name;
    public $customer;
    public $roles;
    public $faction;
    public $documents;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class UserMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'user_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
                ->primary()->alias('id_')
                
            ->string('name')
                ->alias('name_')
                
            ->searchableArray('roles')
                ->alias('roles_')
                
            ->embedded('customer', Customer::class, function($builder) {
                $builder->bigint('id')->alias('customer_id');
            })
            
            ->embedded('faction', Faction::class, function($builder) {
                $builder->bigint('id')->nillable()->alias('faction_id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('customer')
            ->belongsTo(Customer::class, 'customer.id');

        $builder->on('faction')
            ->belongsTo(Faction::class, 'faction.id')
            ->constraints(['domain' => 'user']);

        $builder->on('documents')
            ->morphMany(Document::class.'::uploaderId', 'uploaderType=user');
    }
    
    /**
     * {@inheritdoc}
     */
    public function customEvents(RepositoryEventsSubscriberInterface $notifier): void
    {
        $notifier->listen('afterLoad', function($entity) {
            if ($entity->name === 'TEST1 to check event') {
                $entity->name = 'TEST1 afterLoad';
            }
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function filters(): array
    {
        return [
            'nameLike' => function($query, $value) {
                $query->where(['name :like' => '%' . $value]);
            },
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function scopes(): array
    {
        return [
            'testScope' => function($query, $value) {
                return $query->limit(1)->execute(['test' => $value])->all();
            }
        ];
    }
}


class Admin extends Model
{
    public $id;
    public $name;
    public $roles;
    public $faction;
    public $documents;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class AdminMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'adminuser_',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
                ->autoincrement()->alias('id_')
                
            ->string('name')
                ->alias('name_')
                
            ->searchableArray('roles')
                ->alias('roles_')
                
            ->embedded('faction', Faction::class, function($builder) {
                $builder->bigint('id')->nillable()->alias('faction_id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('faction')
                ->belongsTo(Faction::class, 'faction.id')
                ->constraints(['domain' => 'admin']);

        $builder->on('documents')
            ->morphMany(Document::class.'::uploaderId', 'uploaderType=admin');
    }
}

//------- Faction

class Faction extends Model
{
    public $id;
    public $name;
    public $domain;
    public $enabled = true;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class FactionMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'faction_',
        ];
    }

    
    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
                ->autoincrement()->alias('id_')
                
            ->string('name')
                ->alias('name_')
                
            ->boolean('enabled', true)
                ->alias('enabled_')
                
            ->string('domain')
                ->nillable()->alias('domain_')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function customConstraints(): array
    {
        return [
            'enabled' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('adminFaction')
            ->hasMany(Admin::class.'::faction.id')
            ->constraints(['domain' => 'admin'])
            ->detached();

        $builder->on('userFaction')
            ->hasMany(User::class.'::faction.id')
            ->constraints(['domain' => 'user'])
            ->detached();
    }
}

class Customer extends Model
{
    public $id;
    public $name;
    public $packs;
    public $documents;
    public $location;
    public $parentId;
    public $parent;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class CustomerMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'customer_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sequence(): array
    {
        return [
            'connection'   => 'test',
            'table'        => 'customer_seq_',
            'column'       => 'id',
            'tableOptions' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
                ->sequence()->alias('id_')
                
            ->bigint('parentId')->nillable()->alias('parent_id')
                
            ->string('name')
                ->alias('name_')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('packs')
            ->belongsToMany(Pack::class)
            ->through(CustomerPack::class, 'customerId', 'packId');
        
        $builder->on('location')
            ->hasOne(Location::class);

        $builder->on('documents')
            ->hasMany(Document::class.'::customerId');

        $builder->on('users')
            ->hasMany(User::class.'::customer.id')
            ->detached();

        $builder->on('parent')
            ->belongsTo(Customer::class, 'parentId');

        $builder->on('children')
            ->hasMany(Customer::class.'::parentId')
            ->detached();

        $builder->on('webUsers')
            ->hasMany(User::class.'::customer.id')
            ->constraints(['faction.domain' => 'user'])
            ->detached();
    }
}

class Location extends Model
{
    public $id;
    public $address;
    public $city;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class LocationMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'location_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
            ->primary()->alias('id_')

            ->string('address')
            ->alias('address_')

            ->string('city')
            ->alias('city_')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('customer')
            ->belongsTo(Customer::class, 'id')
            ->detached();
    }
}

class Pack extends Model
{
    public $id;
    public $label;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class PackMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'pack_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')
                ->primary()->alias('id_')
                
            ->string('label')
                ->alias('name_')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('customers')
            ->belongsToMany(Customer::class)
            ->through(CustomerPack::class, 'packId', 'customerId')
            ->detached();
    }
}

class CustomerPack extends Model
{
    public $customerId;
    public $packId;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class CustomerPackMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'customer_pack_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('customerId')
                ->primary()->alias('customer_id')
                
            ->integer('packId')
                ->primary()->alias('pack_id')
        ;
    }
}

class Project extends Model
{
    public $id;
    public $name;
    public $developers;
    public $leadDevelopers;
    public $commits;
    public $creator;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class ProjectMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'project_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')
            ->primary()

            ->string('name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('developers')
            ->hasMany(Developer::class.'::project.id')
            ->mode(RelationBuilder::MODE_EAGER);

        $builder->on('leadDevelopers')
            ->hasMany(Developer::class.'::project.id')
            ->constraints(['lead' => true])
            ->mode(RelationBuilder::MODE_EAGER);

        $builder->on('commits')
            ->hasMany(Commit::class.'::project.id')
            ->mode(RelationBuilder::MODE_EAGER);

        $builder->on('creator')
            ->hasOne(Developer::class.'::project.id')
            ->mode(RelationBuilder::MODE_EAGER);
    }
}

class Commit extends Model
{
    public $id;
    public $message;
    public $author;
    public $authorType;
    public $authorId;
    public $project;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class CommitMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'commit_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')
            ->primary()

            ->text('message')

            ->integer('authorId')->alias('author_id')

            ->string('authorType')->alias('author_type')

            ->embedded('project', Project::class, function($builder) {
                $builder->integer('id')->alias('project_id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('project')
            ->belongsTo(Project::class, 'project.id');

        $builder->on('author')
            ->morphTo('authorId', 'authorType', [
                'integrator' => Integrator::class.'::id',
                'developer' => Developer::class.'::id'
            ])
            ->mode(RelationBuilder::MODE_EAGER);
    }
}

class Developer extends Model
{
    public $id;
    public $name;
    public $commits;
    public $project;
    public $lead = false;
    public $company;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class DeveloperMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'developer_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')
            ->primary()

            ->string('name')

            ->embedded('project', Project::class, function($builder) {
                $builder->integer('id')->alias('project_id');
            })

            ->embedded('company', Company::class, function($builder) {
                $builder->integer('id')->alias('company_id');
            })

            ->boolean('lead')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('commits')
            ->hasMany(Commit::class.'::authorId');

        $builder->on('project')
            ->belongsTo(Project::class, 'project.id');

        $builder->on('company')
            ->belongsTo(Company::class, 'company.id')
            ->mode(RelationBuilder::MODE_EAGER)
        ;
    }
}

class Integrator extends Model
{
    public $id;
    public $name;
    public $company;
    public $projects;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class IntegratorMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'integrator_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->primary()
            ->string('name')
            ->embedded('company', Company::class, function($builder) {
                $builder->integer('id')->alias('company_id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('company')
            ->belongsTo(Company::class, 'company.id')
            ->mode(RelationBuilder::MODE_EAGER)
        ;

        $builder->on('projects')
            ->belongsToMany(Project::class)
            ->through(ProjectIntegrator::class, 'integratorId', 'projectId');
    }
}

class ProjectIntegrator extends Model
{
    public $projectId;
    public $integratorId;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}


class ProjectIntegratorMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'project_integrator_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('projectId')->primary()
            ->integer('integratorId')->primary()
        ;
    }
}

class Company extends Model
{
    public $id;
    public $name;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class CompanyMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'company_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->primary()
            ->string('name')
        ;
    }
}

//-------------- single inheritance

class Task extends Model
{
    public $id;
    public $name;
    public $targetId;
    public $target;
    public $targetEager;
    public $type;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    protected $overridenProperty;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }

    /**
     * @return mixed
     */
    public function overridenProperty()
    {
        return $this->overridenProperty;
    }

    /**
     * @param mixed $overridenProperty
     *
     * @return $this
     */
    public function setOverridenProperty($overridenProperty)
    {
        $this->overridenProperty = $overridenProperty;

        return $this;
    }
}
class DocumentControlTask extends Task
{
    public $type = 'DocumentControl';

    protected $overridenProperty = 'my-value';
}
class CustomerControlTask extends Task
{
    public $type = 'CustomerControl';

    protected $overridenProperty = 'my-value';
}

class TaskMapper extends SingleTableInheritanceMapper
{
    protected $discriminatorColumn = 'type';

    protected $discriminatorMap = [
        'DocumentControl' => DocumentControlTaskMapper::class,
        'CustomerControl' => CustomerControlTaskMapper::class,
    ];


    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'task_'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
            ->string('type')
            ->bigint('targetId', 0)
            ->string('overridenProperty')->nillable()
            ->add('createdAt', 'date_utc', 'CURRENT_DATE')->nillable()
            ->dateTime('updatedAt', 'CURRENT_DATE')->timezone('UTC')->phpClass(DateTimeImmutable::class)->nillable()
            ->dateTime('deletedAt', 'CURRENT_DATE')->nillable()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('target')->inherit('targetId');
        $builder->on('targetEager')->inherit('targetId')->mode(RelationBuilder::MODE_EAGER);
    }
}
class DocumentControlTaskMapper extends TaskMapper
{
    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        parent::buildRelations($builder);

        $builder->on('target')
            ->belongsTo(Document::class, 'targetId');

        $builder->on('targetEager')
            ->belongsTo(DocumentEager::class, 'targetId');
    }
}
class CustomerControlTaskMapper extends TaskMapper
{
    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        parent::buildRelations($builder);

        $builder->on('target')
            ->belongsTo(Customer::class, 'targetId');

        $builder->on('targetEager')
            ->belongsTo(Customer::class, 'targetId');
    }
}

/**
 * Class UserCustomMetadata
 * @package Bdf\Prime
 */
class UserCustomMetadata extends Model
{
    public $id;
    public $name;
    public $customer;

    /**
     * {@inheritdoc}
     */
    public static function loadSerializerMetadata(ClassMetadataBuilder $metadata): void
    {
        $metadata->property('id')->configure([
            'type' => 'string',
            'group' => ['all', 'identifier'],
        ]);
        $metadata->property('name')->configure([
            'type' => 'string',
            'group' => ['all'],
        ]);
    }
}
class UserCustomMetadataMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'webuser_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
            ->primary()->alias('id_')

            ->string('name')
            ->alias('name_')

            ->embedded('customer', Customer::class, function($builder) {
                $builder->bigint('id')->alias('customer_id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('customer')
            ->belongsTo(Customer::class, 'customer.id');
    }
}

/**
 * Class WebUser
 * @package Bdf\Prime
 */
class UserInheritedMetadata extends Model
{
    public $id;
    public $name;
    public $customer;
    public $rights;

    /**
     * UserInheritedMetadata constructor.
     */
    public function __construct()
    {
        $this->customer = new Customer();
    }


}
class UserInheritedMetadataMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'webuser_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
            ->primary()->alias('id_')

            ->string('name')
            ->alias('name_')

            ->embedded('customer', Customer::class, function($builder) {
                $builder->bigint('id')->alias('customer_id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('customer')
            ->belongsTo(Customer::class, 'customer.id');

        $builder->on('rights')
            ->hasMany(Right::class.'::userId');
    }
}

/**
 * Class Right
 * @package Bdf\Prime
 */
class Right extends Model
{
    public $id;
    public $userId;
    public $name;
}
class RightMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this->setQuoteIdentifier(true);
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'rights_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')->primary()->alias('id_')
            ->bigint('userId')->primary()->alias('user_id')
            ->string('name')->alias('name_')
        ;
    }
}
