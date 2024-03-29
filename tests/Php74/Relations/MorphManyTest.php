<?php

namespace Php74\Relations;

require_once __DIR__.'/../_files/relation.php';

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\TestPack;
use Php74\Admin;
use Php74\Customer;
use Php74\Document;
use Php74\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MorphManyTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->persist([
            'admin' => new Admin([
                'id'            => '10',
                'name'          => 'Admin User',
                'roles'         => [1],
            ]),

            'user' => new User([
                'id'            => '321',
                'name'          => 'Web User',
                'roles'         => [1],
                'customer'      => new Customer([
                    'id'            => '123',
                ]),
            ]),

            'customer' => new Customer([
                'id'            => '321',
                'name'          => 'Web Customer',
            ]),

            'document-admin' => new Document([
                'id'             => '10',
                'customerId'     => '123',
                'uploaderType'   => 'admin',
                'uploaderId'     => '10',
            ]),

            'document-user' => new Document([
                'id'             => '20',
                'customerId'     => '123',
                'uploaderType'   => 'user',
                'uploaderId'     => '321',
            ]),
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    /**
     *
     */
    public function test_one_morph()
    {
        $admin = Admin::with('documents')->get('10');
        
        $this->assertEquals(1, count($admin->documents));
        $this->assertEquals('10', $admin->documents[0]->id);
        $this->assertEquals('admin', $admin->documents[0]->uploaderType);
        $this->assertTrue($admin->relation('documents')->isLoaded());
    }

    /**
     *
     */
    public function test_one_morph_user()
    {
        $user = User::with('documents')->get('321');
        
        $this->assertEquals(1, count($user->documents));
        $this->assertEquals('20', $user->documents[0]->id);
        $this->assertEquals('user', $user->documents[0]->uploaderType);
        $this->assertTrue($user->relation('documents')->isLoaded());
    }

    /**
     *
     */
    public function test_dynamic_join_from_admin()
    {
        $user = Admin::where('documents.customerId', '123')->first();

        $this->assertEquals('10', $user->id);
    }

    /**
     *
     */
    public function test_dynamic_join_from_user()
    {
        $user = User::where('documents.customerId', '123')->first();

        $this->assertEquals('321', $user->id);
    }

    /**
     *
     */
    public function test_link_entity()
    {
        $admin = TestPack::pack()->get('admin');

        $document = $admin->relation('documents')->first();

        $this->assertEquals('10', $document->id);
    }

    /**
     *
     */
    public function test_create()
    {
        $admin = TestPack::pack()->get('admin');

        $document = $admin->relation('documents')->create([
            'customerId' => '132'
        ]);

        $this->assertEquals('admin', $document->uploaderType);
        $this->assertEquals('10', $document->uploaderId);
    }
}
