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

namespace Ecommit\CrudBundle\Tests\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Ecommit\CrudBundle\Tests\DoctrineHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataTransformerInterface;

abstract class AbstractEntityTransformerTest extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp(): void
    {
        $this->em = DoctrineHelper::createEntityManager();
        DoctrineHelper::loadTagsFixtures($this->em);
        DoctrineHelper::loadEntityManyToOneFixtures($this->em);
    }

    protected function tearDown(): void
    {
        $this->em = null;
    }

    abstract protected function createTransformer(...$args): DataTransformerInterface;
}
