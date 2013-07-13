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

class CrudSessionManager
{
    /**
     * Search's object (used by "setData" inside the form). Used to 
     * save the data of the form of search 
     * 
     * @var Object
     */
    public $form_filter_values_object = null;
    
    /**
     * Number of results, in one page
     * 
     * @var int   
     */
    public $number_results_displayed = null;
    
    /**
     * Displayed colums
     * 
     * @var type 
     */
    public $columns_diplayed = array();
    
    /**
     * Sortable: Sort (Column id)
     * 
     * @var type 
     */
    public $sort = null;
    
    /**
     * Sortable: Sens (ASC / DESC)
     * 
     * @var type 
     */
    public $sense = null;
    
    /**
     * Page number
     * 
     * @var int 
     */
    public $page = 1;
}