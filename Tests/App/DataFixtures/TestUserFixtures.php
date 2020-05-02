<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ecommit\CrudBundle\Tests\App\Entity\TestUser;

class TestUserFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $dataSet = [
            ['Eve', 'Reste'],
            ['Henri', 'Poste'],
            ['Henri', 'Plait'],
            ['Jean', 'Serrien'],
            ['Clément', 'Tine'],
            ['Aude', 'Javel'],
            ['Yvon', 'Embavé'],
            ['Judie', 'Cieux'],
            ['Paul', 'Ochon'],
            ['Sarah', 'Pelle'],
            ['Thierry', 'Gollo'],
        ];

        foreach ($dataSet as $data) {
            $user = new TestUser();
            $user->setUsername($data[0].$data[1])
                ->setFirstName($data[0])
                ->setLastName($data[1]);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
