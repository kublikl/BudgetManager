<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Flash;
use \App\Models\Incomes;
use \App\Models\Expenses;
use \App\Models\User;

/**
 * Profile controller
 */
class Profile extends Authenticated
{

    public $user;
    /**
     * Before filter - called before each action method
     *
     * @return void
     */
    protected function before()
    {
        parent::before();

        $this->user = Auth::getUser();
    }

    /**
     * Show the profile
     *
     * @return void
     */
    public function showAction()
    {
        View::renderTemplate('/Profile/show.html', [
            'user' => $this->user
        ]);
    }

    public function updateProfileData()
	{
		if(isset($_POST['name'])) {
			
			$user = new User($_POST);

			if ($user->updateProfile()) {

				Flash::addMessage('The profile has been edited.');

				$this->redirect('/profile/show');

			} else {
					
				Flash::addMessage('The email address you entered is already taken or is in an incorrect format.',Flash::DANGER);	
					
				View::renderTemplate('/Profile/show', [
				'userIncomes' => Incomes::getIncomeCategories(),
				'userExpenses' => Expenses::getExpenseCategories(),
				'paymentMethods' => Expenses::getPaymentMethods(),
				'user' => $user
				]);
					
			} 	
		} else {
			$this->redirect('/Profile/show');
		}
	}
    
    public function resetAccountTransactions()
	{
		if(isset($_POST['resetAccount'])) {
			
		Incomes::deleteAllUserIncomes();
		Expenses::deleteAllUserExpenses();
		
		Flash::addMessage('All your transactions have been deleted.');

		$this->redirect('/Profile/show');
		} else {
			$this->redirect('/Profile/show');
		}
	
	}

    public function changePassword()
	{
		if(isset($_POST['password'])) {
			
			$user = new User($_POST);

			if ($user->changeUserPassword()) {

				Flash::addMessage('Your password has been changed.');

				$this->redirect('/Profile/show');

			} else {
					
				Flash::addMessage('The password could not be changed.',Flash::DANGER);	
					
				$this->redirect('/Profile/show');	
			} 	
		} else {
			$this->redirect('/Profile/show');
		}
	
	}
	public function deleteAccount()
	{
		if(isset($_POST['deleteAccount'])) {
			
		$user = new User();
		$user->deleteAccount();

		Auth::logout();
		
		$this->redirect('/home/index');
		} else {
			$this->redirect('/Profile/show');
		}
	
	}	
    
}
