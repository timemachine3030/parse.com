<?php

namespace Parse;
class Role extends DataObject {

    protected $_url = '';

    public function __construct($name = null) {
        parent::__construct('roles');

        if (is_string($name)) {
            $this->name = $name;
        }
    }
}
