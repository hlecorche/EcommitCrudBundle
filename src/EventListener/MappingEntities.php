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

namespace Ecommit\CrudBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

final class MappingEntities
{
    protected bool $isLoad = false;

    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->isMappedSuperclass) {
            return;
        }

        /** @var string $className */
        $className = $metadata->getName();
        if (!$this->isLoad && is_subclass_of($className, 'Ecommit\CrudBundle\Entity\UserCrudInterface')) {
            $this->isLoad = true;
            $userCrudSettingsMetadata = $eventArgs->getEntityManager()->getMetadataFactory()->getMetadataFor('Ecommit\CrudBundle\Entity\UserCrudSettings');
            $this->mappUserCrudSettings($userCrudSettingsMetadata, $metadata);
        }
        if (!$this->isLoad && 'Ecommit\CrudBundle\Entity\UserCrudSettings' === $className) {
            $this->isLoad = true;
            $userMetadata = $eventArgs->getEntityManager()->getMetadataFactory()->getMetadataFor('Ecommit\CrudBundle\Entity\UserCrudInterface');
            $this->mappUserCrudSettings($metadata, $userMetadata);
        }
    }

    protected function mappUserCrudSettings(ClassMetadataInfo|ClassMetadata $userCrudSettingsMetadata, ClassMetadataInfo|ClassMetadata $userMetadata): void
    {
        $userCrudSettingsMetadata->setAssociationOverride(
            'user',
            [
                'joinColumns' => [[
                    'name' => 'user_id',
                    'referencedColumnName' => $userMetadata->getSingleIdentifierColumnName(),
                    'onDelete' => 'CASCADE',
                ]],
            ]
        );
    }
}
