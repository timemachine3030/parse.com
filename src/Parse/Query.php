<?php

namespace Parse;
class Query extends \Parse {
	private $_qlimit = 100;
	private $_qskip = 0;
	private $_qcount = false;
	private $_qorder = array();
	private $_query = array();
	private $_include = array();

	public function __construct($class=''){
		if($class == 'users' || $class == 'installation'){
			$this->_requestUrl = $class;
		}
		elseif($class != ''){
			$this->_requestUrl = 'classes/'.$class;
		}
		else{
			$this->throwError('include the className when creating a parseQuery');
		}
		
		parent::__construct();

	}

	public function find(){
		if(empty($this->_query)){
			$request = $this->request(array(
				'method' => 'GET',
				'requestUrl' => $this->_requestUrl
			));

			return $request;

		}
		else{
			$urlParams = array(
				'where' => json_encode( $this->_query )
			);
			if(!empty($this->_include)){
				$urlParams['include'] = implode(',',$this->_include);
			}
			if(!empty($this->_qorder)){
				$urlParams['order'] = implode(',',$this->_qorder);
			}
			if(!empty($this->_qlimit) || $this->_qlimit == 0){
				$urlParams['limit'] = $this->_qlimit;
			}
			if(!empty($this->_qskip)){
				$urlParams['skip'] = $this->_qskip;
			}
			if($this->_qcount == 1){
				$urlParams['count'] = '1';
			}

			$request = $this->request(array(
				'method' => 'GET',
				'requestUrl' => $this->_requestUrl,
				'urlParams' => $urlParams,
			));
			$return->results = $request->results;
			
			if (property_exists($request, 'count')) {
			    $return->count = $request->count;
			} else {
			    $return->count = count($results);
			}
			
			// Page = floor(skip/limit)
			if (!$this->_qskip) {
			    $return->page = 1;
			    if (!$this->_qlimit) {
			        $return->perPage = 100;
			    } else {
			        $return->perPage = $this->_qlimit;
			    }
			} else {
			    if (!$this->_qlimit) {
			        $return->page = floor($this->_qskip / 100) + 1; // 100 it the Parse.com default limit.
			        $return->perPage = 100;
			    } else {
			        $return->page = floor($this->_qskip / $this->_qlimit) + 1;
			        $return->perPage = $this->_qlimit;
			    }
			}

			return $return;
		}
	}
	//setting this to 1 by default since you'd typically only call this function if you were wanting to turn it on
  public function setCount($bool=false){
  	if(is_bool($bool)){
  		$this->_qcount = $bool;
  	}
		else{
			$this->throwError('setCount requires a boolean paremeter');
		}		
  }

	public function getCount(){
		$this->_qcount = 1;
		$this->_qlimit = 0;
		return $this->find();
	}

	public function setLimit($int){
		if ($int >= 1 && $int <= 1000){
			$this->_qlimit = $int;
		}
		else{
			$this->throwError('parse requires the limit parameter be between 1 and 1000');
		}
	}

	public function setSkip($int){
		$this->_qskip = $int;
	}

	public function orderBy($field){
		if(!empty($field)){
			$this->_qorder[] = $field;
		}
	}

	public function orderByAscending($value){
		if(is_string($value)){
			$this->_qorder[] = $value;
		}
		else{
			$this->throwError('the order parameter on a query must be a string');
		}
	}

	public function orderByDescending($value){
		if(is_string($value)){
			$this->_qorder[] = '-'.$value;
		}
		else{
			$this->throwError('the order parameter on parseQuery must be a string');
		}
	}
	
	public function whereInclude($value){
		if(is_string($value)){
			$this->_include[] = $value;
		}
		else{
			$this->throwError('the include parameter on parseQuery must be a string');
		}
	}

	public function where($key,$value){
		$this->whereEqualTo($key,$value);
	}

	public function whereEqualTo($key,$value){
		if(isset($key) && isset($value)){
			$this->_query[$key] = $value;
		}
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	}

	public function whereNotEqualTo($key,$value){
		if(isset($key) && isset($value)){
			$this->_query[$key] = array(
				'$ne' => $value
			);
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	}


	public function whereGreaterThan($key,$value){
		if(isset($key) && isset($value)){
			$this->_query[$key] = array(
				'$gt' => $value
			);
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	
	}

	public function whereLessThan($key,$value){
		if(isset($key) && isset($value)){
			$this->_query[$key] = array(
				'$lt' => $value
			);
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	
	}

	public function whereGreaterThanOrEqualTo($key,$value){
		if(isset($key) && isset($value)){
			$this->_query[$key] = array(
				'$gte' => $value
			);
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	
	}

	public function whereLessThanOrEqualTo($key,$value){
		if(isset($key) && isset($value)){
			$this->_query[$key] = array(
				'$lte' => $value
			);
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	
	}

	public function whereAll($key,$value){
		if(isset($key) && isset($value)){
			if(is_array($value)){
				$this->_query[$key] = array(
					'$all' => $value
				);		
			}
			else{
				$this->throwError('$value must be an array to check through');		
			}
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	
	}


	public function whereContainedIn($key,$value){
		if(isset($key) && isset($value)){
			if(is_array($value)){
				$this->_query[$key] = array(
					'$in' => $value
				);		
			}
			else{
				$this->throwError('$value must be an array to check through');		
			}
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	
	}

	public function whereNotContainedIn($key,$value){
		if(isset($key) && isset($value)){
			if(is_array($value)){
				$this->_query[$key] = array(
					'$nin' => $value
				);		
			}
			else{
				$this->throwError('$value must be an array to check through');		
			}
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
	
	}

	public function whereExists($key){
		if(isset($key)){
			$this->_query[$key] = array(
				'$exists' => true
			);
		}
	}

	public function whereDoesNotExist($key){
		if(isset($key)){
			$this->_query[$key] = array(
				'$exists' => false
			);
		}
	}
	
	public function whereRegex($key,$value,$options=''){
		if(isset($key) && isset($value)){
			$this->_query[$key] = array(
				'$regex' => $value
			);

			if(!empty($options)){
				$this->_query[$key]['options'] = $options;
			}
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
		
	}

	public function wherePointer($key,$className,$objectId){
		if(isset($key) && isset($className)){
			$this->_query[$key] = $this->dataType('pointer', array($className,$objectId));
		}	
		else{
			$this->throwError('the $key and $className parameters must be set when setting a "where" pointer query method');		
		}
		
	}
	
	public function whereRelatedTo($key, $className, $objectId){
		if (isset($key) && isset($className)) {
			$this->_query = array(
				'$relatedTo' => array('object' => $this->dataType('pointer', array($className, $objectId)), 'key' => $key)
			);
		} else {
			$this->throwError('the $key and $className parameters must be set when setting a "where" pointer query method');
		}
	}
	
	public function whereInQuery($key,$className,$inQuery){
		if(isset($key) && isset($className)){
			$this->_query[$key] = array(
				'$inQuery' => $inQuery,
				'className' => $className
			);
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
		
	}

	public function whereNotInQuery($key,$className,$inQuery){
		if(isset($key) && isset($className)){
			$this->_query[$key] = array(
				'$notInQuery' => $inQuery,
				'className' => $className
			);
		}	
		else{
			$this->throwError('the $key and $value parameters must be set when setting a "where" query method');		
		}
		
	}
}

?>
