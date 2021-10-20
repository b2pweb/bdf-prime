<?php

namespace Bdf\Prime;

use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

class Folder extends Model implements InitializableInterface
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var EntityCollection|TestFile[]
     */
    public $files;

    /**
     * @var int
     */
    public $parentId;

    /**
     * @var Folder|null
     */
    public $parent;

    public function __construct(array $data = [])
    {
        $this->initialize();
        $this->import($data);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        $this->files = TestFile::collection();
    }
}

/**
 * Class FolderMapper
 */
class FolderMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'test_folder'
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
            ->integer('parentId')->alias('parent_id')->nillable()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('files')
            ->hasMany(TestFile::class.'::folderId')
            ->wrapAs('collection')
        ;

        $builder->on('parent')
            ->belongsTo(Folder::class, 'parentId')
            ->wrapAs('collection')
        ;
    }
}

class TestFile extends Model implements InitializableInterface
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $folderId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var FileUser
     */
    public $owner;

    /**
     * @var Group
     */
    public $group;

    public function __construct(array $data = [])
    {
        $this->initialize();
        $this->import($data);
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->owner = new FileUser();
        $this->group = new Group();
    }


}

class TestFileMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'test_file'
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
            ->integer('folderId')->alias('folder_id')
            ->embedded('owner', FileUser::class, function (FieldBuilder $builder) {
                $builder->string('name')->alias('owner_name')->nillable();
            })
            ->embedded('group', Group::class, function (FieldBuilder $builder) {
                $builder->string('name')->alias('group_name')->nillable();
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('owner')
            ->belongsTo(FileUser::class, 'owner.name')
        ;

        $builder->on('group')
            ->belongsTo(Group::class, 'group.name')
        ;
    }
}

class FileUser extends Model
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var EntityCollection|Group[]
     */
    public $groups;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class FileUserMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'test_file_user'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->string('name')->primary()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('groups')
            ->belongsToMany(Group::class.'::name', 'name')
            ->through(UserGroup::class, 'userName', 'groupName')
            ->wrapAs('collection')
        ;
    }
}

class Group extends Model
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var FileUser[]
     */
    public $users;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}


class GroupMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'test_group'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->string('name')->primary()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('users')
            ->belongsToMany(FileUser::class, 'name')
            ->through(UserGroup::class, 'groupName', 'userName')
            ->wrapAs('collection')
        ;
    }
}


class UserGroup extends Model
{
    public $userName;
    public $groupName;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class UserGroupMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'test_user_group'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->string('userName')->primary()
            ->string('groupName')->primary()
        ;
    }
}
