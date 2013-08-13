<?php

namespace Parse;
class DataObject extends \Parse {
	public $_includes = array();
        public $data = array();
	private $_className = '';
        
        protected $_url = 'classes';
        protected $_clauses = array();
        protected $_sessionToken = false;
        

        public function __construct($class=''){
		if($class != ''){
			$this->_className = $class;
		}
		else{
			$this->throwError('include the className when creating a parseObject');
		}

		parent::__construct();
	}

	public function __set($name,$value){
		if($name != '_className'){
			$this->data[$name] = $value;
		}
	}
        
        public function __get($name) {
            return $this->data[$name];
        }

	public function save(){
		if(count($this->data) > 0 && $this->_className != ''){
			$request = $this->request(array(
				'method' => 'POST',
				'requestUrl' => $this->_url . '/' . $this->_className,
				'data' => $this->data,
			));
			return $request;
		}
	}

    public function get($limit = false, $skip = false, $order = false) {
        if (array_key_exists('objectId', $this->data)) {
            $id = $this->objectId;
        }
        
        if ($this->_className != '') {
            if (!empty($id)) {
                $requestUrl = $this->_url . '/' . $this->_className . '/' . $id;
            } else {
                $requestUrl = $this->_url . '/' . $this->_className;
            }
            
            $this->limit($limit);
            $this->skip($skip);
            $this->order($order);
            
            $args = array(
                'method' => 'GET',
                'requestUrl' => $requestUrl
            );
            
            if ($this->_sessionToken) {
                $args['sessionToken'] = $this->_sessionToken;
            }

            if (count($this->_clauses)) {
                $args['where'] = $this->_clauses;
            }

            $request = $this->request($args);

            if (!empty($this->_includes)) {
                $request->include = implode(',', $this->_includes);
            }
            
            if (property_exists($request, 'results') 
                && is_array($request->results) 
                && !self::is_assoc($request->results)
            ) {
                $return = new Results;
                if (class_exists('\Parse\\' . $this->_className)) {
                    
                    foreach ($request->results as $obj) {
                        $return->results[] = $this->classMapper($obj, $this->_className);
                    }
                } else {
                    $return->results = $request->results;
                }
                    
                if (property_exists($request, 'count')) {
                    $return->count = $request->count;
                } else {
                    $return->count = count($results);
                }
                
                // Page = floor(skip/limit)
                if (!$this->_skip) {
                    $return->page = 1;
                    if (!$this->_limit) {
                        $return->perPage = 100;
                    } else {
                        $return->perPage = $this->_limit;
                    }
                } else {
                    if (!$this->_limit) {
                        $return->page = floor($this->_skip / 100) + 1; // 100 it the Parse.com default limit.
                        $return->perPage = 100;
                    } else {
                        $return->page = floor($this->_skip / $this->_limit) + 1;
                        $return->perPage = $this->_limit;
                    }
                }
                
                
                return $return;    
            } else if (property_exists($request, 'objectId')) {
                foreach ($request as $key => $value) {
                    $this->$key = $value;
                }
                return $this;
            } else {
                return false;
            }
        } else {
            $this->throwError("need classname to 'get' a dataobject");
        }
    }

	public function update($id = false){
            if (!$id && $this->objectId) {
                $id = $this->objectId;
            }
		if($this->_className != '' || !empty($id)){
			$request = $this->request(array(
				'method' => 'PUT',
				'requestUrl' => $this->_url . '/' . $this->_className.'/'.$id,
				'data' => $this->data,
			));

			return $request;
		}
	}

	public function increment($field,$amount){
		$this->data[$field] = $this->dataType('increment', $amount);
	}

	public function decrement($id){
		$this->data[$field] = $this->dataType('decrement', $amount);
	}


	public function delete($id){
		if($this->_className != '' || !empty($id)){
			$request = $this->request(array(
				'method' => 'DELETE',
				'requestUrl' => $this->_url . '/' . $this->_className.'/'.$id
			));

			return $request;
		}		
	}

	public function addInclude($name){
		$this->_includes[] = $name;
	}
        
        public function where($key, $value) {
            $this->_clauses[$key] = $value;
            return $this;
        }
        
        public static function is_assoc($array) {
            return (bool)count(array_filter(array_keys($array), 'is_string'));
        }
        
        private function classMapper($instance, $classNmae) {
            return unserialize(sprintf(
                'O:%d:"%s"%s',
                strlen($className),
                $className,
                strstr(strstr(serialize($instance), '"'), ':')
            ));
        }
        
        public function setSessionToken($token) {
            $this->_sessionToken = $token;
        }
}

?>
