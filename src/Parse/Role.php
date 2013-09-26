<?php

namespace Parse;
class Role extends DataObject {

    protected $_url = '';

    public function __construct($name = null, $acl = null) {
        parent::__construct('roles');

        if (is_string($name)) {
            $this->name = $name;
        }
        if ($acl instanceOf ACL) {
            $this->acl = $acl;
        } else {
            $this->acl = new ACL();
        }
    }

    public function __sleep() {
        $transients = array('name', 'ACL');
        if ($this->objectId) {
            array_push($transients, 'objectId');
        }
        return $transients;
    }
}
