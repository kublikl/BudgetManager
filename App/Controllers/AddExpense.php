<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\User;
use \App\Flash;
use \App\Models\Expenses;
use \App\Auth;

/**
 * AddExpense controller
 */
class AddExpense extends \Core\Controller
{
    /**
     * Show the add expense page
     */
    public function newAction()
    {
        $this->requireLogin(); //require login to acces to this page!

        View::renderTemplate('AddExpense/new.html');
       

    }

    public function createAction()
    {
        $expense = new Expenses($_POST);
        if($expense->save()){
            Flash::addMessage('Expense added successfully');
            //View::renderTemplate('AddExpense/new.html');
            View::renderTemplate('AddExpense/new.html', [
                'expense_category' => Expenses::getExpenseCategories(),
                'payment_method' => Expenses::getPaymentMethods()

                ]);
       /* }else{
            View::renderTemplate('AddExpense/new.html', [
                'expenses' => $expense
              ]); */
        }


    }
}