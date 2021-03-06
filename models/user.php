<?php
class User extends AppModel {
	var $name = 'Users';
	var $displayField = 'name';
	var $primaryKey = 'id';

	var $hasOne = array(
		'languages' => array(
			'className' => 'languages',
			'foreignKey' => 'id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
		/*'country' => array(
			'className' => 'country',
			'foreignKey' => 'id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)*/
	);

	var $hasMany = array(
		'Privilege' => array(
			'className' => 'Privilege',
			'foreignKey' => 'user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'Profile' => array(
			'className' => 'Profile',
			'foreignKey' => 'user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'groups_users' => array(
			'className' => 'groups_users',
			'foreignKey' => 'user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'Token' => array(
			'className' => 'Token',
			'foreignKey' => 'user_id',
			'dependent' => false
		)
	);


	var $hasAndBelongsToMany = array(
		'languages' => array(
			'className' => 'languages',
			'joinTable' => 'deny_translations',
			'foreignKey' => 'languages_id',
			'associationForeignKey' => 'users_id',
			'unique' => true,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'finderQuery' => '',
			'deleteQuery' => '',
			'insertQuery' => ''
		)
	);
	
	/**
	 * $validate
	 * 
	 * Contains validation rules.
	 * @see http://book.cakephp.org/view/1067/validate
	 * @var array
	 */
	var $validate = array(
		'username' => array(
			'usernameRule-1' => array( 
				'rule' => 'isUnique',
				'message' => 'Username already exists'
			),
			'usernameRule-2' => array(
				'rule' => array('between', 4, 16),
				'allowEmpty' => false,
				'message' => 'Must be from 4 to 16 characters long',
				'last' => true
			),
			'usernameRule-3' => array(
				'rule' => 'alphaNumeric',
				'message' => 'Only alphabets and numbers allowed'
			)
		),
		'password' => array(
			'rule' => array('between', 4, 32),
			'allowEmpty' => false,
			'message' => 'Must be from 4 to 32 characters long'
		),
		'password_confirm' => array(
			'rule' => '_comparePasswords',
			'message' => 'Passwords do not match'
		),
		'hometown' => array(
			'rule' => 'notEmpty',
			'message' => 'This field cannot be left blank'
		),
		'email' => array(
			'emailRule-1' => array(
				'rule' => 'email',
				'allowEmpty' => false,
				'message' => 'Must be a proper email address',
				'last' => true
			),
			'emailRule-2' => array(
				'rule' => 'isUnique',
				'message' => 'Email address exists'
			)
		),
		'status' => array(
			'rule' => 'notEmpty',
			'message' => 'This field cannot be left blank'
		),
		'agreement' => array(
			'rule' => array('equalTo', '1'),
			'message' => 'You must agree to the terms in order to sign up'
		),
		'recaptcha_response_field' => array(
			'checkRecaptcha' => array( 
				'rule' => array('checkRecaptcha', 'recaptcha_challenge_field'), 
				'message' => 'You did not enter the words correctly. Please try again.'
			)
		)
	);

	/**
	 * _comparePasswords
	 * 
	 * Verifies that the password and password_confirm are the same strings.
	 * Used by validation rules variable $validate.
	 * @return boolean
	 */
	protected function _comparePasswords() {
		return strcmp($this->data['User']['password'], $this->data['User']['password_confirm']) ? false:true;
	}
	
	/**
	 * hashOldPassword
	 * 
	 * Hash password with old hashing algorithm (used in previous version of Massidea)
	 * @param array $data
	 * @param string $password
	 * @return array
	 */
	function hashOldPassword($data, $password) {
		$data['User']['password'] = md5($data['User']['password_salt'].$password.$data['User']['password_salt']);
		return $data;
	}
	
	/**
	 * hashPasswords
	 * 
	 * Overrides CakePHP's password hashing. It passes the parameter straight through, 
	 * thus disabling the default hashing function.
	 * This is a workaround until a newer version supports this.
	 * @todo Replace this overriding function with a setting.
	 * @param array $data
	 * @return array $data
	 */
	function hashPasswords($data) { return $data; }

	/**
	 * customHashPasswords
	 * 
	 * Password hashing function.
	 * @param array $data
	 * @return array $data
	 */
	function customHashPasswords($data) {
		$data['User']['password'] = Security::hash($data['User']['password']);
		return $data;
	}
	
	function isOldUser($userName = null) {
		$return = $this->find(
			'count', array(
				'conditions' => array(
					'User.username' => $userName,
					'User.password_salt !=' => ''
				)
			)
		);
		return ($return > 0) ? true: false;
	}
	
	function getUserSalt($username = null) {
		$data = $this->find('list', array(
			'conditions' => array('User.username' => $username),
			'fields' => array('User.password_salt')
		));
		return current($data);
	}
	
	function getPublicUserdata($username = null) {
		$data = $this->findByUsername($username, array(
			'id', 'username', 'email', 'last_login', 'created'
		));
		
		if(!empty($data)) {
			unset($data['Privilege']);
			unset($data['groups_users']);
			unset($data['Token']);
			unset($data['languages']);
			
			foreach($data['Profile'] as $child => $arr) {
				if($arr['public'] == 0) unset($data['Profile'][$child]);
			}
		}
		
		return $data;
	}
		
	/**
	 * beforeSave
	 * 
	 * CakePHP model callback method, executes between validation and saving.
	 * Note that must return true in order to continue.
	 * @see http://book.cakephp.org/view/1052/beforeSave
	 */
	function beforeSave() {
		// Use default values for a few specific fields
		$this->data['User']['languages_id'] = 'my';
		$this->data['User']['country_id'] = 'GB';
		
		// Hash password before saving
		if(isset($this->data['User']['password'])) {
			$this->data = $this->customHashPasswords($this->data);
		}
		
		return true;
	}
	
}
?>