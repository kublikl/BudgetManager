<?php

namespace App\Controllers;

use \App\Auth;

use \Core\View;
use \App\Flash;
use \App\Models\Incomes;
use \App\Models\User;
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
           // 'user' => $_SESSION['user_id'],
            'user_incomes' => Incomes::getIncomeCategories(),
            'expense_category' => Expenses::getExpenseCategories(),
            'payment_method' => Expenses::getPaymentMethods()
            
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

				Flash::addMessage('Kategoria przychodów została zedytowana.');

				$this->redirect('/settings/show');

			} else {
					
				Flash::addMessage('Podana kategoria już istnieje.',Flash::DANGER);	
					
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

			Flash::addMessage('Kategoria przychodów została usunięta, a należące do niej transakcje przeniesiono do kategorii "Inne". Możesz edytować ich kategorię w przeglądzie bilansu.');

			$this->redirect('/settings/show');
			
		} else {
			$this->redirect('/settings/show');
		}

	}		
}