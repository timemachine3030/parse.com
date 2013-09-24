<?php

namespace Parse\Objects;

class Date {
    public $__type = 'Date';
    public $iso;
    public function __construct($time) {
        $this->setDate($time);
    }
    public function __sleep() {
        return array('__type', 'iso');
    }
    public function setDate($time) {
        $this->iso = date('c', $time);
    } 
}
