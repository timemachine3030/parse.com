<?php
/* 
// example: 
$object = new parseObject();
$object->__set('hello','world');

// This instantiates a ACL object with NO rights! 
$acl = new parseACL();
$acl->setPublicReadAccess(false);
$acl->setReadAccessForId('user_id',true);
$acl->setWriteAccessForRole('role_name',true);

$object->ACL($acl);
$object->save();
*/
namespace Parse;
class ACL {
	private $permission;

        private function getAccess($type, $userId) {
            if ($userId instanceOf Parse\User) {
                $userId = $userId->objectId;
            } else if ($userId instanceOf Parse\Role) {
                $userId = 'role:' . $userId->name;
            }
            $permissions = $this->permissions[$userId];
            if (!$permissions) {
                return false;
            }
            return $permissions[$accessType] ? true : false;
        }

	private function setAccessForKey($access, $userId, $allowed){
            if (!($access == 'read' || $access == 'write')) {
                return;
            }
            if ($userId instanceOf Parse\User) {
                $userId = $userId->objectId;
            } else if ($userId instanceOf Parse\Role) {
                $userId = 'role:' . $userId->name;
            }

            if (is_object($this->permission)) {
                $this->permission = array();
            }
            if ($allowed) {
                $this->permission[$userId][$access] = true;
            } else {
                if (isset($this->permission[$userId])) { 
                    unset($this->permission[$userId][$access]);
                    if (sizeof($this->permission[$userId]) == 0) {
                        unset($this->permission[$userId]);
                    }
                }
                if(sizeof($this->permission) == 0) {
                    // Force JSON to curlies
                    $this->permission = new \stdClass();
                }
            }
	}


	public function __construct(){
            // Force JSON to curlies
            $this->permission = new \stdClass();
	}
        public function toTransient() {
            return $this->permission;
        }
        /**
         * Get whether the public is allowed to read this object.
         */
        public function getPublicReadAccess() {
            return $this->getAccess('read', self::$PUBLIC_KEY);
        }
 	
        /**
         * Get whether the public is allowed to write this object.
         */
        public function getPublicWriteAccess() {
            return $this->getAccess('write', self::$PUBLIC_KEY);
        }
 	
        /**
         * Get whether the given user id is *explicitly* allowed to read this object.
         */
        public function getReadAccess($userId) {
            return $this->getAccess('read', $userId);
        }
 	
        /**
         * Get whether users belonging to the given role are allowed to read this object.
         */
        public function getRoleReadAccess($role) {
            if ($role instanceOf Parse\Role) {
                $role = $role->name;
            }
            if (is_string($role)) {
                return $this->getReadAccess('role:' . $role);
            }
            throw new \Exception('role must be a Parse\Role or a String');
        }
 	
        /**
         * Get whether users belonging to the given role are allowed to write this object.
         */
        public function getRoleWriteAccess($role) {
            if ($role instanceOf Parse\Role) {
                $role = $role->name;
            }
            if (is_string($role)) {
                return $this->getWriteAccess('role:' . $role);
            }
            throw new \Exception('role must be a Parse\Role or a String');
        }
 	
        /**
         * Get whether the given user id is *explicitly* allowed to write this object.
         */
        public function getWriteAccess($userId) {
            return $this->getAccess('write', $userId);
        }

	public function setPublicReadAccess($allowed){
            $this->setAccessForKey('read', self::$PUBLIC_KEY, $allowed);
	}
	public function setPublicWriteAccess($allowed){
		$this->setAccessForKey('write', self::$PUBLIC_KEY, $allowed);
	}
	public function setReadAccess($userId, $allowed){
		$this->setAccessForKey('read', $userId, $allowed);
	}
	public function setWriteAccess($userId, $allowed){
		$this->setAccessForKey('write', $userId, $allowed);
	}
	public function setRoleReadAccess($role, $allowed) {
		$this->setAccessForKey('read', 'role:' . $role, $allowed);
	}
	public function setRoleWriteAccess($role, $allowed){
		$this->setAccessForKey('write', 'role:' . $role, $allowed);
	}

        public static $PUBLIC_KEY = '*';
}
