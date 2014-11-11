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
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->isMappedSuperclass) {
            return;
        }

        $className = $metadata->getName();
        if (is_subclass_of($className, 'Ecommit\CrudBundle\Entity\UserCrudInterface')) {
            $this->mappUserCrudSettings($eventArgs, $metadata);
        }
    }

    protected function mappUserCrudSettings(LoadClassMetadataEventArgs $eventArgs, ClassMetadataInfo $userMetadata)
    {
        $metadata= $eventArgs->getEntityManager()->getMetadataFactory()->getMetadataFor('Ecommit\CrudBundle\Entity\UserCrudSettings');

        $metadata->setAssociationOverride(
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
