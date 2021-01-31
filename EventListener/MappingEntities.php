<?php
/**
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class MappingEntities
{
    protected $isLoad = false;

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->isMappedSuperclass) {
            return;
        }

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

    protected function mappUserCrudSettings(ClassMetadataInfo $userCrudSettingsMetadata, ClassMetadataInfo $userMetadata)
    {
        $userCrudSettingsMetadata->setAssociationOverride(
            'user',
            array(
                'targetEntity' => $userMetadata->getName(),
                'fieldName' => 'user',
                'id' => true,
                'joinColumns' => array(array(
                    'name' => 'user_id',
                    'referencedColumnName' => $userMetadata->getSingleIdentifierColumnName(),
                    'onDelete' => 'CASCADE',
                ))
            )
        );
    }
} 
