<?php

namespace App\Models;

use PDO;
use \App\Token;
use \App\Mail;
use \Core\View;

/**
 * User model
 */
class User extends \Core\Model
{
    public $id;
    public $name;
    public $password;
    public $email;
    public $password_hash;
    public $remember_token;
    public $expiry_timestamp;
    public $password_reset_token;
    public $password_reset_hash;
    public $password_reset_expires_at;
    public $activation_token;
    public $activation_hash;
    public $is_active;
    public $oldPassword;
   
    
   

    /**
     * Error messages
     *
     * @var array
     */
    public $errors = [];

    /**
     * Class constructor
     *
     * @param array $data  Initial property values (optional)
     *
     * @return void
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        };
    }

    /**
     * Save the user model with the current property values
     *
     * @return boolean  True if the user was saved, false otherwise
     */
    public function save()
    {
        $this->validate();

        if (empty($this->errors)) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $token = new Token();
            $hashed_token = $token->getHash();
            $this->activation_token = $token->getValue();

            $sql = 'INSERT INTO users (name, email, password_hash, activation_hash)
                    VALUES (:name, :email, :password_hash, :activation_hash)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindValue(':activation_hash', $hashed_token, PDO::PARAM_STR);

            return $stmt->execute();
        }

        return false;
    }

    /**
     * Validate current property values, adding valiation error messages to the errors array property
     *
     * @return void
     */
    public function validate()
    {
        // Name
        if ($this->name == '') {
            $this->errors[] = 'Name is required';
        }

        // email address
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = 'Invalid email';
        }
        if (static::emailExists($this->email, $this->id ?? null)) {
            $this->errors[] = 'email already taken';
        }

        // Password
        if (isset($this->password)) {

            if (strlen($this->password) < 6) {
                $this->errors[] = 'Please enter at least 6 characters for the password';
            }

            if (preg_match('/.*[a-z]+.*/i', $this->password) == 0) {
                $this->errors[] = 'Password needs at least one letter';
            }

            if (preg_match('/.*\d+.*/i', $this->password) == 0) {
                $this->errors[] = 'Password needs at least one number';
            }

        }
    }

    /**
     * See if a user record already exists with the specified email
     *
     * @param string $email email address to search for
     * @param string $ignore_id Return false anyway if the record found has this ID
     *
     * @return boolean  True if a record already exists with the specified email, false otherwise
     */
    public static function emailExists($email, $ignore_id = null)
    {
        $user = static::findByEmail($email);

        if ($user) {
            if ($user->id != $ignore_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find a user model by email address
     *
     * @param string $email email address to search for
     *
     * @return mixed User object if found, false otherwise
     */
    public static function findByEmail($email)
    {
        $sql = 'SELECT * FROM users WHERE email = :email';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     //* Authenticate a user by email and password.
     * Authenticate a user by email and password. User account has to be active.
     *
     * @param string $email email address
     * @param string $password password
     *
     * @return mixed  The user object or false if authentication fails
     */
    public static function authenticate($email, $password)
    {
        $user = static::findByEmail($email);

        //if ($user) {
        if ($user && $user->is_active) {
            if (password_verify($password, $user->password_hash)) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Find a user model by ID
     *
     * @param string $id The user ID
     *
     * @return mixed User object if found, false otherwise
     */
    public static function findByID($id)
    {
        $sql = 'SELECT * FROM users WHERE id = :id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Remember the login by inserting a new unique token into the remembered_logins table
     * for this user record
     *
     * @return boolean  True if the login was remembered successfully, false otherwise
     */
    public function rememberLogin()
    {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->remember_token = $token->getValue();

        $this->expiry_timestamp = time() + 60 * 60 * 24 * 30;  // 30 days from now

        $sql = 'INSERT INTO remembered_logins (token_hash, user_id, expires_at)
                VALUES (:token_hash, :user_id, :expires_at)';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $this->expiry_timestamp), PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Send password reset instructions to the user specified
     *
     * @param string $email The email address
     *
     * @return void
     */
    public static function sendPasswordReset($email)
    {
        $user = static::findByEmail($email);

        if ($user) {

            if ($user->startPasswordReset()) {

                $user->sendPasswordResetEmail();

            }
        }
    }

    /**
     * Start the password reset process by generating a new token and expiry
     *
     * @return void
     */
    protected function startPasswordReset()
    {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->password_reset_token = $token->getValue();

        $expiry_timestamp = time() + 60 * 60 * 2;  // 2 hours from now

        $sql = 'UPDATE users
                SET password_reset_hash = :token_hash,
                    password_reset_expires_at = :expires_at
                WHERE id = :id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $expiry_timestamp), PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Send password reset instructions in an email to the user
     *
     * @return void
     */
    protected function sendPasswordResetEmail()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/password/reset/' . $this->password_reset_token;

        $text = View::getTemplate('Password/reset_email.txt', ['url' => $url]);
        $html = View::getTemplate('Password/reset_email.html', ['url' => $url]);

        Mail::send($this->email, 'Password reset', $text, $html);
    }

    /**
     * Find a user model by password reset token and expiry
     *
     * @param string $token Password reset token sent to user
     *
     * @return mixed User object if found and the token hasn't expired, null otherwise
     */
    public static function findByPasswordReset($token)
    {
        $token = new Token($token);
        $hashed_token = $token->getHash();

        $sql = 'SELECT * FROM users
                WHERE password_reset_hash = :token_hash';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        $user = $stmt->fetch();

        if ($user) {
            
            // Check password reset token hasn't expired
            if (strtotime($user->password_reset_expires_at) > time()) {

                return $user;
            }
        }
    }

    /**
     * Reset the password
     *
     * @param string $password The new password
     *
     * @return boolean  True if the password was updated successfully, false otherwise
     */
    public function resetPassword($password)
    {
        $this->password = $password;

        $this->validate();

        //return empty($this->errors);
        if (empty($this->errors)) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $sql = 'UPDATE users
                    SET password_hash = :password_hash,
                        password_reset_hash = NULL,
                        password_reset_expires_at = NULL
                    WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);
                                                  
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
                                          
            return $stmt->execute();
        }

        return false;
    }

    /**
     * Send an email to the user containing the activation link
     *
     * @return void
     */
    public function sendActivationEmail()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/signup/activate/' . $this->activation_token;

        $text = View::getTemplate('Signup/activation_email.txt', ['url' => $url]);
        $html = View::getTemplate('Signup/activation_email.html', ['url' => $url]);

        Mail::send($this->email, 'Account activation', $text, $html);
    }

    /**
     * Activate the user account with the specified activation token
     *
     * @param string $value Activation token from the URL
     *
     * @return void
     */
    public static function activate($value)
    {
        $token = new Token($value);
        $hashed_token = $token->getHash();

        $sql = 'UPDATE users
                SET is_active = 1,
                    activation_hash = null
                WHERE activation_hash = :hashed_token';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':hashed_token', $hashed_token, PDO::PARAM_STR);

        $stmt->execute();
    }
    

     public static function getNewUserId()
     {
         $sql = 'SELECT id FROM users ORDER BY id DESC LIMIT 1';
         $db = static::getDB();
         $stmt = $db->prepare($sql);
         $stmt->execute();
        
          $user = $stmt->fetch();
          $user_id = $user['id'];
          return $user_id;
     }

     public static function copyIncomesCategories($user_id)
     {

        $sqlInsert = 'INSERT INTO incomes_category_assigned_to_users (user_id, name) SELECT :user_id, name FROM incomes_category_default';
        $db = static::getDB();
        $stmtInsert = $db->prepare($sqlInsert);
        $stmtInsert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmtInsert->execute();
    
        $sqlUpdate = 'UPDATE incomes_category_assigned_to_users SET user_id = :user_id ORDER BY id DESC LIMIT 4';
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmtUpdate->execute();
     }

     public static function copyExpensesCategories($user_id)
     {
         $sqlInsert = 'INSERT INTO expenses_category_assigned_to_users (user_id, name) SELECT :user_id, name FROM expenses_category_default';
         $db = static::getDB();
         $stmtInsert = $db->prepare($sqlInsert);
         $stmtInsert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
         $stmtInsert->execute();
     
         $sqlUpdate = 'UPDATE expenses_category_assigned_to_users SET user_id = :user_id ORDER BY id DESC LIMIT 16';
         $stmtUpdate = $db->prepare($sqlUpdate);
         $stmtUpdate->bindParam(':user_id', $user_id, PDO::PARAM_INT);
         $stmtUpdate->execute();
     }

     public static function copyPaymentMethods($user_id)
     {
        $sqlInsert = 'INSERT INTO payment_methods_assigned_to_users (user_id, name) SELECT :user_id, name FROM payment_methods_default';
        $db = static::getDB();
        $stmtInsert = $db->prepare($sqlInsert);
        $stmtInsert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmtInsert->execute();
    
        $sqlUpdate = 'UPDATE payment_methods_assigned_to_users SET user_id = :user_id ORDER BY id DESC LIMIT 3';
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmtUpdate->execute();
     }

     public function updateProfile()
     {
         $this->validateNameAndEmail();
         
 
         if (empty($this->errors)) {
             
 
             $sql = 'UPDATE users SET name = :name, email = :email WHERE id = :user_id';
 
             $db = static::getDB();
             $stmt = $db->prepare($sql);
 
             $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
             $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
             $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
             
             $results = $stmt->execute();
 
             return $results;
         }
 
         return false;
     }
     protected function validateNameAndEmail() 
     {
         // Name
         if ($this->name == '') {
             $this->errors['name'] = 'Wprowadź imię.';
         }
 
         // email address
         if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
             $this->errors['email'] = 'Podaj poprawny adres e-mail.';
         }
         if (static::emailExists($this->email, $this->id ?? null)) {
             $this->errors['email'] = 'Istnieje konto o podanym adresie e-mail.';
         }		
     }
     public function changeUserPassword()
    {
       // var_dump($_SESSION['user_id']);
       // var_dump($this->oldPassword);
        
        $this->validatePassword();
		
		$is_valid = static::validateOldPassword($this->oldPassword,$_SESSION['user_id']);
		

        if (empty($this->errors) && $is_valid) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $sql = 'UPDATE users SET password_hash = :new_password_hash WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);


            $stmt->bindValue(':new_password_hash', $password_hash, PDO::PARAM_STR);
			$stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
			
			$results = $stmt->execute();
			
            return $results;
        }

        return false;
    }
    public static function validateOldPassword($password,$userId)
    {
		$user = static::findByID($userId);

        if ($user) {
            if (password_verify($password, $user->password_hash)) {
                return true;
            }
        }
		
        return false;
    }
    protected function validatePassword() 
	{
		if (preg_match('/(?=.*?[0-9])(?=.*?[A-Za-z]).+/', $this->password) == 0) {
			$this->errors['password'] = 'Hasło musi posiadać przynajmniej 1 literę i 1 cyfrę.';
		}
		
		if (strlen($this->password) < 6 || strlen($this->password) > 20) {
			$this->errors['password'] = 'Hasło musi posiadać od 6 do 20 znaków.';
		}
	}

    public function deleteAccount()
    {
        	$sql = 'DELETE FROM users WHERE id = :user_id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

			$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
			
			$stmt->execute();
			
    }
}
