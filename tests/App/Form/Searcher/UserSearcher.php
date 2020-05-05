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

namespace Ecommit\CrudBundle\Tests\App\Form\Searcher;

use Ecommit\CrudBundle\Form\Filter as Filter;
use Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher;

class UserSearcher extends AbstractFormSearcher
{
    public $username;

    public $firstName;

    public $lastName;

    public function configureFieldsFilter()
    {
        return [
            new Filter\FieldFilterText('username', 'username', []),
            new Filter\FieldFilterText('firstName', 'firstName', []),
            new Filter\FieldFilterText('lastName', 'lastName', []),
        ];
    }
}
