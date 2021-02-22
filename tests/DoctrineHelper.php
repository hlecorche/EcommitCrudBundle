<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Ecommit\CrudBundle\Tests\Fixtures\EntityManyToOne;
use Ecommit\CrudBundle\Tests\Fixtures\Tag;

class DoctrineHelper
{
    public static function createEntityManager(bool $addSchema = true): EntityManager
    {
        $connection = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/Fixtures'], true, null, null, false);

        $entityManager = EntityManager::create($connection, $config);
        if (false === $addSchema) {
            return $entityManager;
        }

        $classes = [
            $entityManager->getClassMetadata(Tag::class),
            $entityManager->getClassMetadata(EntityManyToOne::class),
        ];

        $schemaTool = new SchemaTool($entityManager);
        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $e) {
        }

        try {
            $schemaTool->createSchema($classes);
        } catch (\Exception $e) {
        }

        return $entityManager;
    }

    public static function loadTagsFixtures(EntityManager $entityManager): void
    {
        $entityManager->persist(new Tag(1, 'tag1'));
        $entityManager->persist(new Tag(2, 'tag2'));
        $entityManager->persist(new Tag(3, '3'));
        $entityManager->persist(new Tag(4, 'tag_name'));
        $entityManager->persist(new Tag(5, 'tag_name'));
        $entityManager->flush();
        $entityManager->clear();
    }

    public static function loadEntityManyToOneFixtures(EntityManager $entityManager): void
    {
        $tagRepository = $entityManager->getRepository(Tag::class);

        $entityManager->persist(new EntityManyToOne(1, 'entity1', $tagRepository->find(2)));
        $entityManager->persist(new EntityManyToOne(2, 'entity2', $tagRepository->find(3)));
        $entityManager->persist(new EntityManyToOne(3, 'entity3', $tagRepository->find(4)));
        $entityManager->persist(new EntityManyToOne(4, 'entity4', $tagRepository->find(5)));
        $entityManager->persist(new EntityManyToOne(5, 'entity5', $tagRepository->find(5)));
        $entityManager->flush();
        $entityManager->clear();
    }
}
