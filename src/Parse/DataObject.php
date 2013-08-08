<?php

namespace Parse;
class DataObject extends \Parse {
	public $_includes = array();
	private $_className = '';
        protected $_url = 'classes';
        protected $_clauses = array();

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

    public function get($id = false) {
        if (!$id && $this->objectId) {
            $id = $this->objectId;
        }
        
        if ($this->_className != '') {
            if (!empty($id)) {
                $requestUrl = $this->_url . '/' . $this->_className . '/' . $id;
            } else {
                $requestUrl = $this->_url . '/' . $this->_className;
            }
            
            $args = array(
                'method' => 'GET',
                'requestUrl' => $requestUrl
            );

            if (count($this->_clauses)) {
                $args['where'] = $this->_clauses;
            }

            $request = $this->request($args);

            if (!empty($this->_includes)) {
                $request->include = implode(',', $this->_includes);
            }
            
            if (is_array($request->results) && count($request->results)) {
                if (count($request->results) > 1) {
                    $return = array();
                    foreach ($request->results as $obj) {
                        $return[] = $this->classMapper($obj, $this->_classNmae);
                    }
                    return $return;
                } else {
                    foreach ($request->results[0] as $key => $value) {
                        $this->$key = $value;
                    }
                    return $this;
                }
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
        
        private function classMapper($instance, $classNmae) {
            return unserialize(sprintf(
                'O:%d:"%s"%s',
                strlen($className),
                $className,
                strstr(strstr(serialize($instance), '"'), ':')
            ));
        }
}

?>
