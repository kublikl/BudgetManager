<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\User;
use \App\Auth;
use \App\Flash;
use \App\Models\Incomes;
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

        View::renderTemplate('AddIncome/new.html');
       /* , [
            'category' => Incomes::getIncomeCategories()
            ]); 
 */

    }

    public function createAction()
    {
        $income = new Incomes($_POST);
        if($income->save()){
            Flash::addMessage('Income added successfully');
            View::renderTemplate('AddIncome/new.html' , [
                'income_category' => Incomes::getIncomeCategories()
            ]);
        
        
        
            /*}else{
            View::renderTemplate('AddIncome/new.html', [
                'incomes' => $income
              ]);*/
        }
    }
}