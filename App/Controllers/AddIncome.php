<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\User;
use \App\Auth;

use \App\Flash;

/**
 * AddIncome controller
 */
class AddIncome extends \Core\Controller
{
    /**
     * Show the add income page
     */
    public function newAction()
    {
        $this->requireLogin(); //access to this page only with login status

        View::renderTemplate('AddIncome/new.html', [
            'category' => User::getCategories()
            ]);
      

    }

    public function createAction()
    {

    }

    public function destroyAction()
    {

    }
    /**
     * Show a "add income" flash message??? 
     */
    public function showAddIncomeMessageAction()
    {

    }

}