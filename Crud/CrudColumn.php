<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Crud;

class CrudColumn
{
    public $id;
    public $alias;
    public $alias_search;
    public $label;
    public $sortable;
    public $default_displayed;
    
    /**
     * Constructor
     * 
     * @param string $id   Column id (used everywhere inside the crud)
     * @param string $alias   Column SQL alias
     * @param string $label   Column label (used in the header table)
     * @param bool $sortable   If the column is sortable
     * @param bool $default_displayed   If the column is displayed, by default
     * @param string $alias_search    Column SQL alias, used during searchs
     */
    public function __construct($id, $alias, $label, $sortable, $default_displayed, $alias_search)
    {
        $this->id = $id;
        $this->alias = $alias;
        $this->label = $label;
        $this->sortable = $sortable;
        $this->default_displayed = $default_displayed;
        $this->alias_search = $alias_search;
    }
}