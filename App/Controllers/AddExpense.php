<?php

namespace App\Controllers;

use \Core\View;
use \App\Flash;
use \App\Models\Expenses;
use \App\Auth;

/**
 * AddExpense controller
 */
class AddExpense extends \Core\Controller
{

    protected $user;
    /**
     * Show the add expense page
     */
    public function newAction()
    {
        $this->requireLogin(); //require login to access to this page!

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

    /*
    public function  checkLimitAction()
	{
        if(isset($_POST["expenseCategory"]))  
        {  $expense = new Expenses($_POST);
           $expense->showExpenseLimit();
        } else {
           $this->redirect('AddExpense/new.html');
        } 
	}

    public function  getFinalValueAction()
	{
		 if(isset($_POST["amount"]))  
		 { 	
			$expense = new Expenses($_POST);
			$value = $expense->getFinalValue();
        } else {
			$this->redirect('AddExpense/new.html');
		 }

	}
*/
    public function limitAction()
    {
        //$user_id = $this->user->id;

        $user_id = $_SESSION['user_id'];
        $id = $this->route_params['id'];

        echo json_encode(Expenses::getLimit($user_id, $id), JSON_UNESCAPED_UNICODE);
    }

    public function totalAction()
    {
        $user_id = $_SESSION['user_id'];
        $id = $this->route_params['id'];
        $date = $_GET['date'];

        $start_date = date('Y-m-01', strtotime($date));
        $end_date = date('Y-m-t', strtotime($date));

        echo json_encode(Expenses::getTotalExpensesForCategoryAndDate($user_id, $id, $start_date, $end_date), JSON_UNESCAPED_UNICODE);
    }

    
}