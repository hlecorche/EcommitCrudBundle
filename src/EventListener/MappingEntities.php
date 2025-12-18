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
use Ecommit\CrudBundle\Entity\UserCrudInterface;
use Ecommit\CrudBundle\Entity\UserCrudSettings;

final class MappingEntities
{
    private bool $isLoad = false;

    /** @var array<class-string, bool> */
    private array $inProgress = [];

    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        if ($this->isLoad) {
            return;
        }

        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->isMappedSuperclass) {
            return;
        }

        /** @var class-string $className */
        $className = $metadata->getName();

        if (isset($this->inProgress[$className])) {
            return;
        }
        $this->inProgress[$className] = true;

        if (is_subclass_of($className, UserCrudInterface::class)) {
            $userCrudSettingsMetadata = $eventArgs->getEntityManager()->getMetadataFactory()->getMetadataFor(UserCrudSettings::class);
            $this->mappUserCrudSettings($userCrudSettingsMetadata, $metadata);
        }
        if (UserCrudSettings::class === $className) {
            $userMetadata = $eventArgs->getEntityManager()->getMetadataFactory()->getMetadataFor(UserCrudInterface::class);
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
        $this->isLoad = true;
    }
}
