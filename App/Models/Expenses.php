<?php

namespace App\Models;

use PDO;
use \Core\View;
use \App\Auth;
use \Exception;

class Expenses extends \Core\Model
{
  public $category;
  public $amount;
  public $date;
  public $comment;
  public $user_id;
  public $payment;
 // public $paymentMethod;
  public $payment_method;
  public $newExpenseCategory;
  public $expenseCategory;
  public $expenseCategoryId;
  public $paymentCategory;
  public $paymentId;
  
  
  
     // Error messages

    public $errors = []; //tablica z błędami

    /**
     * Class constructor
     * @param array $data  Initial property values
     */
    public function __construct($data = []) //during creating object, assign values from form
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Insert new expense to DB*/
    public function save()
    {
        $this->validate();
        $user_id = $_SESSION['user_id'];  
        $category_id = $this->category;
        $payment_method = $this->payment_method;

        if(empty($this->errors)){
            $sql = 'INSERT INTO expenses (user_id, expense_category_assigned_to_user_id, payment_method_assigned_to_user_id, amount, date_of_expense, expense_comment) 
            VALUES (:user_id, :expense_category_assigned_to_user_id, :payment_method_assigned_to_user_id, :amount, :date_of_expense, :expense_comment)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':expense_category_assigned_to_user_id', $category_id, PDO::PARAM_STR);
            $stmt->bindValue(':payment_method_assigned_to_user_id', $payment_method, PDO::PARAM_STR);
            $stmt->bindValue(':amount', $this->amount, PDO::PARAM_STR);
            $stmt->bindValue(':date_of_expense', $this->date, PDO::PARAM_STR);
            $stmt->bindValue(':expense_comment', $this->comment, PDO::PARAM_STR);

            return $stmt->execute();
        }else{
            return false;
        }
    }

    public function validate(){
         //amount
         if ($this->amount == '') {
            $this->errors[] = 'Amount is required';
        }else if((int)($this->amount>=1000000))
        $this->errors[] = 'Amount sohuld be less than 1000000PLN';

        //date
        if ($this->date == '') {
            $this->errors[] = 'Date is required';
        }

        //category
        if ($this->category == '') {
            $this->errors[] = 'Choose category';
        }
        //payment
        if ($this->payment_method == '') {
            $this->errors[] = 'Payment method is required';
        }
        //comment
        if(strlen($this->comment)>100)
            $this->errors[] = 'Comment should be shorter than 100 chars';
    }
    public static function getExpenseCategories()
    {
        /*
        $user_id['id'] = Auth::getUser();
        $sql = 'SELECT * FROM expenses_category_assigned_to_users WHERE user_id=:user_id';
        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
        */
        
            $user = Auth::getUser();
    
            if ($user) {
                $user_id = $user->id;
        
                $sql = 'SELECT * FROM expenses_category_assigned_to_users WHERE user_id=:user_id';
                $db = static::getDB();
                $expenseCategories = $db->prepare($sql);
                $expenseCategories->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $expenseCategories->execute();
        
                return $expenseCategories->fetchAll(PDO::FETCH_ASSOC);
            }
        
            // WHEN THE USER IS NOT LOGGED IN, IT RETURN THE ARRAY
            return [];
        
    }

    public static function getPaymentMethods()
    {
        $user = Auth::getUser();
    
        if ($user) {
            $user_id = $user->id;
    
            $sql = 'SELECT * FROM payment_methods_assigned_to_users WHERE user_id=:user_id';
            $db = static::getDB();
            $expenseCategories = $db->prepare($sql);
            $expenseCategories->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $expenseCategories->execute();
    
            return $expenseCategories->fetchAll(PDO::FETCH_ASSOC);
        }
    
        // WHEN THE USER IS NOT LOGGED IN, IT RETURN THE ARRAY
        return [];

    }
    public static function expensesBalance($user_id, $minDate, $maxDate) {
        $sql = 'SELECT `name`, SUM(`amount`) AS sumOfExpense FROM `expenses`, `expenses_category_assigned_to_users`
        WHERE `expenses`.`expense_category_assigned_to_user_id`=`expenses_category_assigned_to_users`.`id` AND `expenses`.`user_id` = :user_id
        AND `expenses`.`date_of_expense`
        BETWEEN :minDate AND :maxDate GROUP BY `expense_category_assigned_to_user_id` ORDER BY sumOfExpense DESC';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':minDate', $minDate, PDO::PARAM_STR);
        $stmt->bindValue(':maxDate', $maxDate, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }
    public static function getdataPointsExpenses($user_expenses){

        $dataPointsExpenses = array();
        foreach ($user_expenses as $expense) {
            $dataPointsExpenses[] = array("label" => $expense['name'], "y" => $expense['sumOfExpense']);
        }
        return $dataPointsExpenses;
    }
    public static function getSumOfExpenses($user_expenses){
        $sumExpenses = 0;
        foreach ($user_expenses as $expense) {
            $sumExpenses += $expense['sumOfExpense'];
        }

        return number_format($sumExpenses, 2, '.', '');
    }
    public function addExpenseCategory()
	{		
        $this->validateNewCategoryName();

		if (empty($this->errors)) {
			
			$sql = "INSERT INTO expenses_category_assigned_to_users VALUES (NULL, :user_id, :name)";
									
			$db = static::getDB();
            $stmt = $db->prepare($sql);
	
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':name', $this->newExpenseCategory, PDO::PARAM_STR);

            return $stmt->execute();		
			
		}
		return false;
	}	
    
    protected function validateNewCategoryName()
	{
        
		if(strlen($this->newExpenseCategory)<1 || strlen($this->newExpenseCategory)>40) {
		$this->errors['expenseCategory'] = "The expense category must be between 1 and 40 characters long.";
		}

		$sql = "SELECT * FROM expenses_category_assigned_to_users WHERE user_id = :user_id AND name = :expenseName";
		
		$db = static::getDB();

		$stmt = $db->prepare($sql);

		$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(':expenseName', $this->newExpenseCategory, PDO::PARAM_STR);

		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
		
		if(count($result) >= 1){
		$this->errors['newExpenseCategory'] = "Category already exists.";	
		}
			
	}
    public function updateCategory() 
	{	
        if(strlen($this->expenseCategory)<1 || strlen($this->expenseCategory)>40) {
		$this->errors['expenseCategory'] = "The expense category must be between 1 and 40 characters long.";
		}
		
		$sql = "SELECT * FROM expenses_category_assigned_to_users WHERE user_id = :user_id AND name = :expenseName AND id <> :id";
		
		$db = static::getDB();

		$stmt = $db->prepare($sql);


		$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(':id', $this->expenseCategoryId, PDO::PARAM_INT);
		$stmt->bindValue(':expenseName', $this->expenseCategory, PDO::PARAM_STR);

		$stmt->execute();

      
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if(count($result)==1){
		$this->errors['newExpenseCategory'] = "Category already exists.";	
		}

        if (empty($this->errors)) {
          
			$sql = "UPDATE expenses_category_assigned_to_users SET name = :name WHERE id = :id";
			
			$db = static::getDB();
            $stmt = $db->prepare($sql);
			
			$stmt->bindValue(':id', $this->expenseCategoryId, PDO::PARAM_INT);
            $stmt->bindValue(':name', $this->expenseCategory, PDO::PARAM_STR);

            return $stmt->execute();
          
		}
		return false;
        
    }

    	
    public function deleteCategory()
	{	
  
			$this->updateCategoryToOther();
	
			$sql = "DELETE FROM expenses_category_assigned_to_users WHERE id = :id";
									
			$db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $this->expenseCategoryId, PDO::PARAM_INT);

            return $stmt->execute();
            		
	}
    protected function updateCategoryToOther()
	{
		$sql = "UPDATE expenses
				SET expense_category_assigned_to_user_id = :otherCategoryId 
				WHERE expense_category_assigned_to_user_id = :categoryId";
		
		$db = static::getDB();
		$stmt = $db->prepare($sql);		
						
		$stmt->bindValue(':categoryId', $this->expenseCategoryId, PDO::PARAM_INT);
		$stmt->bindValue(':otherCategoryId', $this->getOtherCategoryId(), PDO::PARAM_INT);
		
		return	$stmt->execute();			
	}

    protected function getOtherCategoryId() 
	{
		
		$sql = "SELECT id FROM expenses_category_assigned_to_users WHERE user_id = :user_id AND name = :name";
		
		$db = static::getDB();
		
		$stmt = $db->prepare($sql);
		
		
		$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(':name', 'Another', PDO::PARAM_STR);
		
		$stmt->execute();
		
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
	
        return $result['id'];
	
	}

    public function addPaymentMethod()
	{	
		if ($this->validateNewPaymentMethodName()) {
			
			$sql = "INSERT INTO payment_methods_assigned_to_users VALUES (NULL, :user_id, :name)";
									
			$db = static::getDB();
            $stmt = $db->prepare($sql);
	
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':name', $this->paymentCategory, PDO::PARAM_STR);

            return $stmt->execute();		
			
		}
		return false;
	}
    
    protected function validateNewPaymentMethodName()
	{
		if(strlen($this->paymentCategory)<1 || strlen($this->paymentCategory)>30) {
			return false;	
		}

		$sql = "SELECT * FROM payment_methods_assigned_to_users WHERE user_id = :user_id AND name = :paymentCategoryName";
		
		$db = static::getDB();

		$stmt = $db->prepare($sql);


		$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(':paymentCategoryName', $this->paymentCategory, PDO::PARAM_STR);

		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if(count($result) >= 1){
            $this->errors['newExpenseCategory'] = "Category already exists.";	
            }
		
		return true;			
	}

    
/*
    public static function deletePaymentMethod($payment_id)
	{	
        $sql = 'DELETE FROM payment_methods_assigned_to_users
            WHERE id = :payment_id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':payment_id', $payment_id, PDO::PARAM_INT);
      
        return $stmt->execute();
        
    }
        */

    public function deletePaymentMethod()
    {
			$this->updateMethodToOther();
	
			$sql = "DELETE FROM payment_methods_assigned_to_users WHERE id = :id";
									
			$db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $this->paymentId, PDO::PARAM_INT);

            return $stmt->execute();	   	
		
    }
    protected function updateMethodToOther()
	{
		$sql = "UPDATE expenses
				SET payment_method_assigned_to_user_id = :otherMethodId 
				WHERE payment_method_assigned_to_user_id = :methodId";
		
		$db = static::getDB();
		$stmt = $db->prepare($sql);		
						
		$stmt->bindValue(':methodId', $this->paymentId, PDO::PARAM_INT);
		$stmt->bindValue(':otherMethodId', $this->getOtherMethodId(), PDO::PARAM_INT);
		
		return	$stmt->execute();			
	}

    protected function getOtherMethodId() 
	{
		
		$sql = "SELECT id FROM payment_methods_assigned_to_users WHERE user_id = :user_id AND name = :name";
		
		$db = static::getDB();
		
		$stmt = $db->prepare($sql);
		
		
		$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(':name', 'Another', PDO::PARAM_STR);
		
		$stmt->execute();
		
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return $result['id'];	
	}
    
    public function updatePaymentMethod() 
	{	
        if(strlen($this->paymentCategory)<1 || strlen($this->paymentCategory)>30) {
			return false;
		}

		$sql = "SELECT * FROM payment_methods_assigned_to_users WHERE user_id = :user_id AND name = :paymentName AND id <> :id";
		
		$db = static::getDB();
		$stmt = $db->prepare($sql);
		
		$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(':id', $this->paymentId, PDO::PARAM_INT);
		$stmt->bindValue(':paymentName', $this->paymentCategory, PDO::PARAM_STR);

		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        if(count($result)>=1){
			return false;
		}

		$sql = "UPDATE payment_methods_assigned_to_users SET name = :name WHERE id = :id";
		
		$db = static::getDB();
		$stmt = $db->prepare($sql);	
		
		$stmt->bindValue(':id', $this->paymentId, PDO::PARAM_INT);
		$stmt->bindValue(':name', $this->paymentCategory, PDO::PARAM_STR);

		return $stmt->execute();
        
	}
    
}