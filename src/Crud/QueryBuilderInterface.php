<?php
/**
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Crud;

interface QueryBuilderInterface
{
    /**
     * @param string $sort
     * @param string $sense
     */
    public function addOrderBy($sort, $sense);

    /**
     * @param string $sort
     * @param string $sense
     */
    public function orderBy($sort, $sense);

    /**
     * @param QueryBuilderParameterInterface $parameter
     */
    public function addParameter(QueryBuilderParameterInterface $parameter);
}
