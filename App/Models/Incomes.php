<?php

namespace App\Models;

use PDO;
use \Core\View;
use \App\Auth;


class Incomes extends \Core\Model
{
  public $category;
  public $amount;
  public $date;
  public $comment;
  public $user_id;



     // Error messages

    public $errors = [];

    /**
     * Class constructor
     * @param array $data  Initial property values
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Insert new income*/
    public function save()
    {
        $this->validate();
        $user_id = $_SESSION['user_id']; 
       // $user['id'] = Auth::getUser();
       // $user_id = $user->id; 
        $category_id = $this->category;

        if(empty($this->errors)){
            $sql = 'INSERT INTO incomes (user_id, income_category_assigned_to_user_id, amount, date_of_income, income_comment)
            VALUES (:user_id, :income_category_assigned_to_user_id, :amount, :date_of_income, :income_comment)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':income_category_assigned_to_user_id', $category_id, PDO::PARAM_STR);
            $stmt->bindValue(':amount', $this->amount, PDO::PARAM_STR);
            $stmt->bindValue(':date_of_income', $this->date, PDO::PARAM_STR);
            $stmt->bindValue(':income_comment', $this->comment, PDO::PARAM_STR);

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
      $this->errors[] = 'Amount sohuld be less than 1000000 PLN';

      //date
      if ($this->date == '') {
          $this->errors[] = 'Date is required';
      }/*else if((int)($this->date<wartosc))
      $this->errors[] = 'Date should be after 01.01.2022';*/

      //category
      if ($this->category == '') {
          $this->errors[] = 'Choose category';
      }
      //comment
      if(strlen($this->comment)>100)
      $this->errors[] = 'Comment should be shorter than 100 chars';
      }

      public static function getIncomeCategories()
      {
         $user = Auth::getUser();
 
         if ($user) {
             $user_id = $user->id;
     
             $sql = 'SELECT * FROM incomes_category_assigned_to_users WHERE user_id=:user_id';
             $db = static::getDB();
             $incomeCategories = $db->prepare($sql);
             $incomeCategories->bindValue(':user_id', $user_id, PDO::PARAM_INT);
             $incomeCategories->execute();
     
             return $incomeCategories->fetchAll(PDO::FETCH_ASSOC);
         }
     
         // WHEN THE USER IS NOT LOGGED IN, IT RETURN THE ARRAY
         return [];
     }

     public static function incomesBalance($user_id, $minDate, $maxDate) {

        $sql = 'SELECT `name`, SUM(`amount`) AS sumOfIncome FROM `incomes`, `incomes_category_assigned_to_users`
        WHERE `incomes`.`income_category_assigned_to_user_id`=`incomes_category_assigned_to_users`.`id` AND `incomes`.`user_id` = :user_id
        AND `incomes`.`date_of_income`
        BETWEEN :minDate AND :maxDate GROUP BY `income_category_assigned_to_user_id` ORDER BY sumOfIncome DESC';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':minDate', $minDate, PDO::PARAM_STR);
        $stmt->bindValue(':maxDate', $maxDate, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
     }

     public static function getdataPointsIncomes($user_incomes){

        $dataPointsIncomes = array();
        foreach ($user_incomes as $income) {
            $dataPointsIncomes[] = array("label" => $income['name'], "y" => $income['sumOfIncome']);
        }
        return $dataPointsIncomes;
    }
    public static function getSumOfIncomes($user_incomes){
        $sumIncomes = 0;
        foreach ($user_incomes as $income) {
            $sumIncomes += $income['sumOfIncome'];
        }

        return number_format($sumIncomes, 2, '.', '');
    }

}