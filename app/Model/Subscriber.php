<?php
App::uses('AppModel', 'Model');
/**
 * Subscriber Model
 *
 */
class Subscriber extends AppModel {
/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'email';
        
	/**
	 * validate form elements
	 * @var array
	 */
	public $validate = array(
            'email'=>'email'
        );
    
	
    
	/**
	 * validate the form elements 
	 * @param array $data
	 * @return array
	 */
        public function myValidate($data){
            $error = '';
            $this->set($data);
            if(!$this->validates()){
                $error = $this->invalidFields();
            }
            return $error;
        }
}
