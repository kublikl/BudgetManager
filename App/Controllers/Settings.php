<?php

namespace App\Controllers;

use \App\Auth;

use \Core\View;
use \App\Flash;
use \App\Models\Incomes;
use \App\Models\Expenses;

/**
 * Settings controller
 */
class Settings extends Authenticated
{

    /**
     * Before filter - called before each action method
     *
     * @return void
     */

     private $user;

    protected function before()
    {
        parent::before();
        $this->user = Auth::getUser(); 
    }

    /**
     * Show settings sections
     *
     * @return void
     */
    public function showAction()
    {
        View::renderTemplate('Settings/settings.html', [
            'user_incomes' => Incomes::getIncomeCategories(),
            'user_expenses' => Expenses::getExpenseCategories(),
            'payment_methods' => Expenses::getPaymentMethods(),
						//'user' => $this->user
            
        ]);
    }


    /**
     * Show the form for editing the profile
     *
     * @return void
     */
    public function addIncomeCategory() 
	{	
		if(isset($_POST['newIncomeCategory'])) {
			
			$income = new Incomes($_POST);

			if($income->addIncomeCategory()) {

			Flash::addMessage('New category added successfully!');

			$this->redirect('/settings/show');
			} else {
				
				Flash::addMessage('Category already exists.',Flash::DANGER);	
					
				$this->redirect('/settings/show');	
			}
			
		} else {
			$this->redirect('/settings/show');
		}
	}	

    public function updateIncomeCategory() 
	{
		if(isset($_POST['incomeCategory'])) {
			
			$income = new Incomes($_POST);

			if ($income->updateCategory()) {

				Flash::addMessage('The income category has been edited.');

				$this->redirect('/settings/show');

			} else {
					
				Flash::addMessage('Category already exists.', Flash::DANGER);	
					
				$this->redirect('/settings/show');
			} 	
		} else {
			$this->redirect('/settings/show');
		}	
	}
    public function deleteIncomeCategory() 
	{	
		if(isset($_POST['incomeCategoryId'])) {
			
			$income = new Incomes($_POST);

			$income->deleteCategory();

			Flash::addMessage('The income category has been removed and its transactions have been moved to the "Another" category');

			$this->redirect('/settings/show');
			
		} else {
			$this->redirect('/settings/show');
		}

	}
	public function addExpenseCategory() 
	{	
		if(isset($_POST['newExpenseCategory'])) {
			
			$expense = new Expenses($_POST);

			if($expense->addExpenseCategory()) {

			Flash::addMessage('New category added successfully!');

			$this->redirect('/settings/show');
			} else {
				
				Flash::addMessage('Category already exists.', Flash::DANGER);	
					
				$this->redirect('/settings/show');	
			}
			
		} else {
			$this->redirect('/settings/show');
		}
	}	
	public function updateExpenseCategory() 
	{
		if(isset($_POST['expenseCategory'])) {

			
			$expense = new Expenses($_POST);

			if ($expense->updateCategory()) {

				Flash::addMessage('The expense category has been edited.');

				$this->redirect('/settings/show');

			} else {
					
				Flash::addMessage('Category already exists.',Flash::DANGER);	
					
				$this->redirect('/settings/show');
			} 	
		} else {
			$this->redirect('/settings/show');
		}
	}
	public function deleteExpenseCategory() 
	{	

		if(isset($_POST['expenseCategoryId'])) {
			
			$expense = new Expenses($_POST);

			$expense->deleteCategory();

			Flash::addMessage('The expense category has been removed and its transactions have been moved to the "Another" category');

			$this->redirect('/settings/show');
			
		} else {

			Flash::addMessage('Could not delete category!');

			$this->redirect('/settings/show');
		}
	}	
	
	public function addPaymentMethod() 
	{	
		if(isset($_POST['paymentId'])) {
			
			$expense = new Expenses($_POST);

			if($expense->addPaymentMethod()) {

			Flash::addMessage('A new payment method has been added.');

			$this->redirect('/settings/show');
			} else {
				
				Flash::addMessage('The payment method already exists.',Flash::DANGER);	
					
				$this->redirect('/settings/show');	
			}
			
		} else {
			$this->redirect('/settings/show');
		}
	}

	public function updatePaymentMethod() 
	{
		if(isset($_POST['paymentId'])) {
			
			$expense = new Expenses($_POST);

			if ($expense->updatePaymentMethod()) {

				Flash::addMessage('The payment method has been edited.');

				$this->redirect('/settings/show');

			} else {
					
				Flash::addMessage('The payment method already exists.',Flash::DANGER);	
					
				$this->redirect('/settings/show');
			} 	
		} else {
			$this->redirect('/settings/show');
		}
		
	}	

	public function deletePaymentMethod() 
	{	
		
		
		if(isset($_POST['paymentId'])) {
			
			$expense = new Expenses($_POST);

			$expense->deletePaymentMethod();

			Flash::addMessage('The payment method has been deleted and its transactions have been moved to the "Another" method');

			$this->redirect('/settings/show');
			
		} else {
			$this->redirect('/settings/show');
		}

	}
	
}