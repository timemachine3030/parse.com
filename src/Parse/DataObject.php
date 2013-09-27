<?php

namespace Parse;
class DataObject extends \Parse {

    private $_className;
    private $_include = array();
    private $_select = array();
    private $_where;

    public $data = array();
    protected $_url = 'classes';
    
    private function addCondition($key, $condition, $value) {
        if (!array_key_exists($key, $this->_where)) {
            $this->_where->$key = new \StdClass();
        }
        $this->_where->$key->$condition = $value;
    }

    public function __construct($class = '') {
        if ($class != '') {
            $this->_className = $class;
        } else {
            $this->throwError('include the className when creating a parseObject');
        }

        //$this->acl = new ACL;

        $this->_where = new \StdClass();
        parent::__construct();
    }

    public function __set($name, $value) {
        if ($name != '_className') {
            $this->data[$name] = $value;
        }
    }

    public function __get($name) {
        if (strtolower($name) == 'acl') {
            if (!array_key_exists('acl', $this->data)) {
                $this->data['ACL'] = new ACL;
            }
            return $this->data['ACL'];
        }
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } else {
            return null;
        }
        
    }

    public function save() {
        if (count($this->data) > 0 && $this->_className != '') {
            $data = $this->data;
            if ($this->acl instanceOf ACL) {
                $data['ACL'] = $this->acl->toTransient();
            }
            $request = $this->request(array(
                'method' => 'POST',
                'requestUrl' => $this->_url . '/' . $this->_className,
                'data' => $data,
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
            

            if (count($this->_where)) {
                $args['where'] = $this->_where;
            }
            if (!empty($this->_include)) {
                $args['include'] = implode(',', $this->_include);
            }

            $request = $this->request($args);
            
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

    public function update($id = false) {
        if (!$id && $this->objectId) {
            $id = $this->objectId;
        }
        var_dump($id);
        if ($this->_className != '' || !empty($id)) {
            $data = $this->data;
            if ($this->acl instanceOf ACL) {
                $data['ACL'] = $this->acl->toTransient();
            }
            $request = $this->request(array(
                'method' => 'PUT',
                'requestUrl' => $this->_url . '/' . $this->_className . '/' . $id,
                'data' => $data,
            ));

            return $request;
        }
    }

	public function increment($field, $amount) {
        $this->data[$field] = $this->dataType('increment', $amount);
    }

    public function decrement($id) {
        $this->data[$field] = $this->dataType('decrement', $amount);
    }

    public function delete($id) {
        if ($this->_className != '' || !empty($id)) {
            $request = $this->request(array(
                'method' => 'DELETE',
                'requestUrl' => $this->_url . '/' . $this->_className . '/' . $id
            ));

            return $request;
        }
    }

    public function addInclude($name) {
        $this->_include[] = $name;
    }

    public function where($key, $value) {
        if (is_array($value)) {
            foreach ($value as $condition => $v) {
                $this->addCondition($key, $condition, $v);
            }
        }
        $this->_where->$key = $value;
        return $this;
    }

    public function matches($key, $regex, $modifiers = '') {
        $this->addCondition($key, '$regex', $regex);

        if ($modifiers && strlen($modifiers)) {
            $this->addCondition($key, '$options', $modifiers);
        }

        return $this;
    }
    public function matchesQuery($key, $query) {
        if (!$query instanceOf StdClass && method_exists($query, 'toTransient')) {
            $query = $query->toTransient();
        }

        $this->addCondition($key, '$inQuery', $query);
    }
    public function toTransient() {
        $transient = new \StdClass();

        foreach(array('className', 'include', 'select', 'limit', 'skip', 'order', 'where') as $key) {
            $prop = "_$key";
            if (is_array($this->$prop) && count($this->$prop)) {
                $transient->$key = implode(',', $this->$prop);
            } else if (!empty($this->$prop)) {
                $transient->$key = $this->$prop;
            }
        }
        return $transient;
    }

    public static function is_assoc($array) {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }

    private function classMapper($instance, $classNmae) {
        return unserialize(sprintf(
                        'O:%d:"%s"%s', strlen($className), $className, strstr(strstr(serialize($instance), '"'), ':')
        ));
    }
}
