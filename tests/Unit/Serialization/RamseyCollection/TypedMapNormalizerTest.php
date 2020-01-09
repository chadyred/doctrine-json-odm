<?php
/*
 * This file is part of Goodwix Doctrine JSON ODM.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Goodwix\DoctrineJsonOdm\Unit\Serialization\RamseyCollection;

use Goodwix\DoctrineJsonOdm\Serialization\RamseyCollection\TypedMapNormalizer;
use Goodwix\DoctrineJsonOdm\Tests\Resources\DummyEntity;
use Goodwix\DoctrineJsonOdm\Tests\Resources\DummyEntityInterface;
use Goodwix\DoctrineJsonOdm\Tests\Resources\DummyEntityInterfaceMap;
use Goodwix\DoctrineJsonOdm\Tests\Resources\DummyEntityMap;
use Goodwix\DoctrineJsonOdm\Tests\Resources\DummyPrimitiveMap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class TypedMapNormalizerTest extends TestCase
{
    /** @var DenormalizerInterface */
    private $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = \Phake::mock(DenormalizerInterface::class);
    }

    /** @test */
    public function supportsDenormalization_arrayAndClassInheritsCollectionInterfaceType_trueReturned(): void
    {
        $normalizer = $this->createMapNormalizer();

        $supports = $normalizer->supportsDenormalization([], DummyEntityMap::class);

        $this->assertTrue($supports);
    }

    /** @test */
    public function supportsDenormalization_stringAndClassInheritsCollectionInterfaceType_trueReturned(): void
    {
        $normalizer = $this->createMapNormalizer();

        $supports = $normalizer->supportsDenormalization('', DummyEntityMap::class);

        $this->assertFalse($supports);
    }

    /** @test */
    public function supportsDenormalization_notAClassName_falseReturned(): void
    {
        $normalizer = $this->createMapNormalizer();

        $supports = $normalizer->supportsDenormalization([], 'Not\\A\\Class[]');

        $this->assertFalse($supports);
    }

    /** @test */
    public function denormalize_arrayOfClassMap_classMapReturned(): void
    {
        $normalizer = $this->createMapNormalizer();
        $data       = [
            'key' => [
                'id',
            ],
        ];
        $this->givenDenormalizer_denormalize_returnItem(new DummyEntity());

        $map = $normalizer->denormalize($data, DummyEntityMap::class, 'json');

        $this->assertCount(1, $map);
        $this->assertInstanceOf(DummyEntity::class, $map->get('key'));
        $this->assertDenormalizer_denormalize_wasCalledOnceWithDataAndType($data['key'], DummyEntity::class);
    }

    /** @test */
    public function denormalize_arrayOfInterfaceMap_interfaceMapReturned(): void
    {
        $normalizer = $this->createMapNormalizer();
        $data       = [
            'key' => [
                'id',
            ],
        ];
        $this->givenDenormalizer_denormalize_returnItem(\Phake::mock(DummyEntityInterface::class));

        $map = $normalizer->denormalize($data, DummyEntityInterfaceMap::class, 'json');

        $this->assertCount(1, $map);
        $this->assertInstanceOf(DummyEntityInterface::class, $map->get('key'));
        $this->assertDenormalizer_denormalize_wasCalledOnceWithDataAndType($data['key'], DummyEntityInterface::class);
    }

    /** @test */
    public function denormalize_arrayOfPrimitive_primitiveCollectionReturned(): void
    {
        $normalizer = $this->createMapNormalizer();
        $data       = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $collection = $normalizer->denormalize($data, DummyPrimitiveMap::class, 'json');

        $this->assertCount(3, $collection);
        $this->assertSame($data, $collection->toArray());
        $this->assertDenormalizer_denormalize_wasNeverCalled();
    }

    /** @test */
    public function denormalize_string_exceptionThrown(): void
    {
        $normalizer = $this->createMapNormalizer();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected value of type "array", value of type "string" is given.');

        $normalizer->denormalize('', DummyPrimitiveMap::class, 'json');
    }

    /** @test */
    public function denormalize_arrayOfClassCollectionAndInvalidItem_invalidArgumentException(): void
    {
        $normalizer = $this->createMapNormalizer();
        $data       = [
            'id' => 'id',
        ];
        $this->givenDenormalizer_denormalize_returnItem(new \stdClass());

        $this->expectException(InvalidArgumentException::class);

        $normalizer->denormalize($data, DummyEntityMap::class, 'json');
    }

    private function givenDenormalizer_denormalize_returnItem($item): void
    {
        \Phake::when($this->denormalizer)
            ->denormalize(\Phake::anyParameters())
            ->thenReturn($item);
    }

    private function assertDenormalizer_denormalize_wasCalledOnceWithDataAndType(array $data, string $type): void
    {
        \Phake::verify($this->denormalizer)
            ->denormalize($data, $type, 'json', []);
    }

    private function createMapNormalizer(): TypedMapNormalizer
    {
        $normalizer = new TypedMapNormalizer();
        $normalizer->setDenormalizer($this->denormalizer);

        return $normalizer;
    }

    private function assertDenormalizer_denormalize_wasNeverCalled(): void
    {
        \Phake::verify($this->denormalizer, \Phake::never())
            ->denormalize(\Phake::anyParameters());
    }
}
