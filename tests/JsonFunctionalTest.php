<?php

namespace Bdf\Prime;

use Bdf\Prime\Query\Expression\Json\Json;
use Bdf\Prime\Query\Expression\Json\JsonContains;
use Bdf\Prime\Query\Expression\Json\JsonExtract;
use Bdf\Prime\Query\Expression\Json\JsonInsert;
use Bdf\Prime\Query\Expression\Json\JsonReplace;
use Bdf\Prime\Query\Expression\Json\JsonSet;
use Bdf\Prime\Query\Expression\Raw;
use PHPUnit\Framework\TestCase;

class JsonFunctionalTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();

        if (static::class === self::class) {
            $version = $this->prime()->connection('test')->executeQuery('select sqlite_version()')->fetchOne();

            if (version_compare($version, '3.38.0', '<')) {
                $this->markTestSkipped('JSON functions are not supported on SQLite < 3.38.0');
            }
        }
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    public function test_schema()
    {
        $this->assertSame([
            'CREATE TABLE test_json (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, data CLOB NOT NULL --(DC2Type:json)
, object CLOB DEFAULT NULL)'
        ], EntityWithJson::repository()->schema(true)->diff());
    }

    public function test_values()
    {
        $entity = new EntityWithJson();
        $entity->id = 1;
        $entity->data = ['foo' => 'bar'];
        $entity->object = (object) ['foo' => 'bar'];

        $this->pack()->nonPersist($entity);

        $this->assertSame(['foo' => 'bar'], EntityWithJson::repository()->get($entity->id)->data);
        $this->assertEquals((object) ['foo' => 'bar'], EntityWithJson::repository()->get($entity->id)->object);
    }

    public function test_extract_on_select()
    {
        $this->pack()->nonPersist([
            new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => [],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertSameWithJson([
            ['foo' => 'bar'],
            ['foo' => null],
            ['foo' => '[1,2,3]'],
        ], EntityWithJson::repository()->select(['foo' => new JsonExtract('data', '$.foo')])->execute()->all());

        $this->assertSameWithJson([
            ['foo' => '"bar"'],
            ['foo' => null],
            ['foo' => '[1,2,3]'],
        ], EntityWithJson::repository()->select(['foo' => new JsonExtract('data', '$.foo', false)])->execute()->all());

        $this->assertEquals([null, null, 2], EntityWithJson::repository()->inRows(Json::attr('data')->foo[1]));
    }

    public function test_extract_on_where()
    {
        $this->pack()->nonPersist([
            $e1 = new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            $e2 = new EntityWithJson([
                'id' => 2,
                'data' => [],
            ]),
            $e3 = new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertEquals([$e1], EntityWithJson::repository()->where(new JsonExtract('data', '$.foo'), 'bar')->all());
        $this->assertEquals([$e2], EntityWithJson::repository()->whereNull(new JsonExtract('data', '$.foo'))->all());
    }

    public function test_extract_using_json_facade()
    {
        $this->pack()->nonPersist([
            $e1 = new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            $e2 = new EntityWithJson([
                'id' => 2,
                'data' => [],
            ]),
            $e3 = new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertEquals([$e1], EntityWithJson::repository()->where(Json::attr('data')->foo, 'bar')->all());
        $this->assertEquals([$e2], EntityWithJson::repository()->whereNull(Json::attr('data')->foo)->all());
    }

    public function test_contains()
    {
        $this->pack()->nonPersist([
            $e1 = new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            $e2 = new EntityWithJson([
                'id' => 2,
                'data' => [],
            ]),
            $e3 = new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertEquals([$e3], EntityWithJson::repository()->whereRaw(new JsonContains(new JsonExtract('data', '$.foo', false), 2))->all());
        $this->assertEquals([$e1], EntityWithJson::repository()->whereRaw(new JsonContains(new JsonExtract('data', '$.foo', false), 'bar'))->all());
        $this->assertEmpty(EntityWithJson::repository()->whereRaw(new JsonContains(new JsonExtract('data', '$.foo', false), 404))->all());
    }

    public function test_contains_with_json_facade()
    {
        $this->pack()->nonPersist([
            $e1 = new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            $e2 = new EntityWithJson([
                'id' => 2,
                'data' => [],
            ]),
            $e3 = new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertEquals([$e3], EntityWithJson::repository()->whereRaw(Json::attr('data')->foo->contains(2))->all());
        $this->assertEquals([$e1], EntityWithJson::repository()->whereRaw(Json::attr('data')->foo->contains('bar'))->all());
        $this->assertEmpty(EntityWithJson::repository()->whereRaw(Json::attr('data')->foo->contains(404))->all());
    }

    public function test_json_set()
    {
        $this->pack()->nonPersist([
            new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => ['bar' => 123],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertSameWithJson([
            '{"foo":"bar","bar":"newbar"}',
            '{"bar":"newbar"}',
            '{"foo":[1,2,3],"bar":"newbar"}',
        ], EntityWithJson::repository()->inRows(new JsonSet('data', '$.bar', 'newbar')));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":"newbar"}',
            '{"bar":"newbar"}',
            '{"foo":[1,2,3],"bar":"newbar"}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->set('newbar')));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":true}',
            '{"bar":true}',
            '{"foo":[1,2,3],"bar":true}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->set(true)));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":123}',
            '{"bar":123}',
            '{"foo":[1,2,3],"bar":123}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->set(123)));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":12.3}',
            '{"bar":12.3}',
            '{"foo":[1,2,3],"bar":12.3}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->set(12.3)));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":[4,5,6]}',
            '{"bar":[4,5,6]}',
            '{"foo":[1,2,3],"bar":[4,5,6]}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->set([4, 5, 6])));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":{"aaa":true,"bbb":123}}',
            '{"bar":{"aaa":true,"bbb":123}}',
            '{"foo":[1,2,3],"bar":{"aaa":true,"bbb":123}}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->set(['aaa' => true, 'bbb' => 123])));

        EntityWithJson::repository()->builder()->set('data', Json::attr('data')->bar->set(true))->update();

        $this->assertEquals([
            new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar', 'bar' => true],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => ['bar' => true],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3], 'bar' => true],
            ]),
        ], EntityWithJson::all());
    }

    public function test_json_insert()
    {
        $this->pack()->nonPersist([
            new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => ['bar' => 123],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertSameWithJson([
            '{"foo":"bar","bar":"newbar"}',
            '{"bar":123}',
            '{"foo":[1,2,3],"bar":"newbar"}',
        ], EntityWithJson::repository()->inRows(new JsonInsert('data', '$.bar', 'newbar')));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":"newbar"}',
            '{"bar":123}',
            '{"foo":[1,2,3],"bar":"newbar"}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->insert('newbar')));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":true}',
            '{"bar":123}',
            '{"foo":[1,2,3],"bar":true}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->insert(true)));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":123}',
            '{"bar":123}',
            '{"foo":[1,2,3],"bar":123}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->insert(123)));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":12.3}',
            '{"bar":123}',
            '{"foo":[1,2,3],"bar":12.3}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->insert(12.3)));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":[4,5,6]}',
            '{"bar":123}',
            '{"foo":[1,2,3],"bar":[4,5,6]}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->insert([4, 5, 6])));

        $this->assertSameWithJson([
            '{"foo":"bar","bar":{"aaa":true,"bbb":123}}',
            '{"bar":123}',
            '{"foo":[1,2,3],"bar":{"aaa":true,"bbb":123}}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->insert(['aaa' => true, 'bbb' => 123])));

        EntityWithJson::repository()->builder()->set('data', Json::attr('data')->bar->insert(true))->update();

        $this->assertEquals([
            new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar', 'bar' => true],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => ['bar' => 123],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3], 'bar' => true],
            ]),
        ], EntityWithJson::all());
    }

    public function test_json_replace()
    {
        $this->pack()->nonPersist([
            new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => ['bar' => 123],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertSameWithJson([
            '{"foo":"bar"}',
            '{"bar":"newbar"}',
            '{"foo":[1,2,3]}',
        ], EntityWithJson::repository()->inRows(new JsonReplace('data', '$.bar', 'newbar')));

        $this->assertSameWithJson([
            '{"foo":"bar"}',
            '{"bar":"newbar"}',
            '{"foo":[1,2,3]}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->replace('newbar')));

        $this->assertSameWithJson([
            '{"foo":"bar"}',
            '{"bar":true}',
            '{"foo":[1,2,3]}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->replace(true)));

        $this->assertSameWithJson([
            '{"foo":"bar"}',
            '{"bar":123}',
            '{"foo":[1,2,3]}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->replace(123)));

        $this->assertSameWithJson([
            '{"foo":"bar"}',
            '{"bar":12.3}',
            '{"foo":[1,2,3]}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->replace(12.3)));

        $this->assertSameWithJson([
            '{"foo":"bar"}',
            '{"bar":[4,5,6]}',
            '{"foo":[1,2,3]}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->replace([4, 5, 6])));

        $this->assertSameWithJson([
            '{"foo":"bar"}',
            '{"bar":{"aaa":true,"bbb":123}}',
            '{"foo":[1,2,3]}',
        ], EntityWithJson::repository()->inRows(Json::attr('data')->bar->replace(['aaa' => true, 'bbb' => 123])));

        EntityWithJson::repository()->builder()->set('data', Json::attr('data')->bar->replace(true))->update();

        $this->assertEquals([
            new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => ['bar' => true],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ], EntityWithJson::all());
    }

    public function test_valid()
    {
        $this->pack()->nonPersist([
            new EntityWithJson([
                'id' => 1,
                'data' => ['foo' => 'bar'],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => ['bar' => 123],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => ['foo' => [1, 2, 3]],
            ]),
        ]);

        $this->assertEquals([1, 1, 1], EntityWithJson::repository()->inRows(Json::valid('data')));
        $this->assertEquals([null, null, null], EntityWithJson::repository()->inRows(Json::valid('object')));
        $this->assertEquals([0, 0, 0], EntityWithJson::repository()->inRows(Json::valid(new Raw('"invalid"'))));
    }

    public function test_hasPath()
    {
        $this->pack()->nonPersist([
            $e1 = new EntityWithJson([
                'id' => 1,
                'data' => [
                    'foo' => 'bar',
                    'baz' => [
                        'qux' => 123,
                    ],
                ],
            ]),
            new EntityWithJson([
                'id' => 2,
                'data' => [
                    'bar' => 123
                ],
            ]),
            new EntityWithJson([
                'id' => 3,
                'data' => [
                    'foo' => [1, 2, 3]
                ],
            ]),
        ]);

        $this->assertEquals([1, 0, 1], EntityWithJson::repository()->inRows(Json::attr('data')->hasPath('foo')));
        $this->assertEquals([1, 0, 1], EntityWithJson::repository()->inRows(Json::attr('data')->foo->hasPath()));
        $this->assertEquals([0, 0, 1], EntityWithJson::repository()->inRows(Json::attr('data')->foo->hasPath('[1]')));
        $this->assertEquals([0, 0, 1], EntityWithJson::repository()->inRows(Json::attr('data')->foo[1]->hasPath()));
        $this->assertEquals([1, 0, 0], EntityWithJson::repository()->inRows(Json::attr('data')->baz->qux->hasPath()));
        $this->assertEquals([$e1], EntityWithJson::repository()->whereRaw(Json::attr('data')->baz->qux->hasPath())->all());
    }

    protected function assertSameWithJson($expected, $actual)
    {
        $this->assertEqualsCanonicalizing($this->normalizeWithJson($expected), $this->normalizeWithJson($actual));
    }

    protected function normalizeWithJson($data)
    {
        if (is_array($data)) {
            ksort($data);
            $data = array_map([$this, 'normalizeWithJson'], $data);

            return $data;
        }

        if (!is_string($data)) {
            return $data;
        }

        $decoded = json_decode($data);

        // This is not JSON data
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $data;
        }

        return json_encode($decoded);
    }
}
