<?php

namespace Parse;
class Role extends DataObject {

    public $objectId;    
    protected $_url = '';

    public function __construct() {
        parent::__construct('roles');
    }
}