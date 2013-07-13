<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ecommit\CrudBundle\Crud\CrudSessionManager;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_crud_settings")
 */
class UserCrudSettings
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Ecommit\CrudBundle\Entity\UserCrudInterface")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=30)
     */
    protected $crud_name;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $results_displayed;
    
    /**
     * @ORM\Column(type="array")
     */
    protected $columns_diplayed = array();
    
    /**
     * @ORM\Column(type="string", length=30)
     */
    protected $sort;
    
    /**
     * @ORM\Column(type="string", length=4)
     */
    protected $sense;

    /**
     * Set crud_name
     *
     * @param string $crudName
     * @return UserCrudSettings
     */
    public function setCrudName($crudName)
    {
        $this->crud_name = $crudName;
    
        return $this;
    }

    /**
     * Get crud_name
     *
     * @return string 
     */
    public function getCrudName()
    {
        return $this->crud_name;
    }

    /**
     * Set results_displayed
     *
     * @param integer $resultsDisplayed
     * @return UserCrudSettings
     */
    public function setResultsDisplayed($resultsDisplayed)
    {
        $this->results_displayed = $resultsDisplayed;
    
        return $this;
    }

    /**
     * Get results_displayed
     *
     * @return integer 
     */
    public function getResultsDisplayed()
    {
        return $this->results_displayed;
    }

    /**
     * Set columns_diplayed
     *
     * @param array $columnsDiplayed
     * @return UserCrudSettings
     */
    public function setColumnsDiplayed($columnsDiplayed)
    {
        $this->columns_diplayed = $columnsDiplayed;
    
        return $this;
    }

    /**
     * Get columns_diplayed
     *
     * @return array 
     */
    public function getColumnsDiplayed()
    {
        return $this->columns_diplayed;
    }

    /**
     * Set sort
     *
     * @param string $sort
     * @return UserCrudSettings
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    
        return $this;
    }

    /**
     * Get sort
     *
     * @return string 
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set sense
     *
     * @param string $sense
     * @return UserCrudSettings
     */
    public function setSense($sense)
    {
        $this->sense = $sense;
    
        return $this;
    }

    /**
     * Get sense
     *
     * @return string 
     */
    public function getSense()
    {
        return $this->sense;
    }

    /**
     * Set user
     *
     * @param \Ecommit\CrudBundle\Entity\UserCrudInterface $user
     * @return UserCrudSettings
     */
    public function setUser(\Ecommit\CrudBundle\Entity\UserCrudInterface $user)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Ecommit\CrudBundle\Entity\UserCrudInterface 
     */
    public function getUser()
    {
        return $this->user;
    }
    
    /**
     * Create CrudSessionManager from this object
     * 
     * @param \Ecommit\CrudBundle\Crud\CrudSessionManager $crud_session_manager
     * @return \Ecommit\CrudBundle\Crud\CrudSessionManager
     */
    public function transformToCrudSessionManager(CrudSessionManager $crud_session_manager)
    {
        $crud_session_manager->columns_diplayed = $this->columns_diplayed;
        $crud_session_manager->number_results_displayed = $this->results_displayed;
        $crud_session_manager->sense = $this->sense;
        $crud_session_manager->sort = $this->sort;
        
        return $crud_session_manager;
    }
    
    /**
     * Update this object from CrudSessionManager
     * 
     * @param \Ecommit\CrudBundle\Crud\CrudSessionManager $crud_session_manager
     */
    public function updateFromSessionManager(CrudSessionManager $crud_session_manager)
    {
        $this->columns_diplayed = $crud_session_manager->columns_diplayed;
        $this->results_displayed = $crud_session_manager->number_results_displayed;
        $this->sense = $crud_session_manager->sense;
        $this->sort = $crud_session_manager->sort;
    }
}