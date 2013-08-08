<?php

namespace Parse;

class User extends DataObject {

    public $authData;
    public $objectId;
    public $createdAt;
    protected $_url = '';
    protected $_sessionToken;

    public function __construct() {
        parent::__construct('users');
    }
    /**
     * 
     * @param type $username
     * @param type $password
     * @return string
     * 
     * Returns the objectId of the new user.
     */
    public function signup($username = '', $password = '') {
        if ($username != '' && $password != '') {
            $this->username = $username;
            $this->password = $password;
        }

        if ($this->data['username'] != '' && $this->data['password'] != '') {
            $request = $this->request(array(
                'method' => 'POST',
                'requestUrl' => 'users',
                'data' => $this->data
            ));
            
            $this->createdAt = $request->createdAt;
            $this->objectId = $request->objectId;
            $this->_sessionToken = $request->sessionToken;

            return $this->objectId;
        } else {
            $this->throwError('username and password fields are required for the signup method');
        }
    }

    public function login() {
        if (!empty($this->data['username']) || !empty($this->data['password'])) {
            $request = $this->request(array(
                'method' => 'GET',
                'requestUrl' => 'login',
                'data' => array(
                    'password' => $this->data['password'],
                    'username' => $this->data['username']
                )
            ));

            $this->createdAt = $request->createdAt;
            $this->objectId = $request->objectId;
            $this->_sessionToken = $request->sessionToken;

            return $this;
        } else {
            $this->throwError('username and password field are required for the login method');
        }
    }

    public function socialLogin() {
        if (!empty($this->authData)) {
            $request = $this->request(array(
                'method' => 'POST',
                'requestUrl' => 'users',
                'data' => array(
                    'authData' => $this->authData
                )
            ));
            return $request;
        } else {
            $this->throwError('authArray must be set use addAuthData method');
        }
    }
    
    /**
     * 
     * @return string
     */
    public function getSessionToken() {
        return $this->_sessionToken;
    }
    
    /**
     * $sessionToken must be a valid session representing a user with privlages 
     * to delete 'this' user.
     * 
     * @param string $sessionToken
     * @return object
     */
    public function delete($sessionToken) {
        if (!empty($this->objectId) || !empty($sessionToken)) {
            $request = $this->request(array(
                'method' => 'DELETE',
                'requestUrl' => 'users/' . $this->objectId,
                'sessionToken' => $sessionToken
            ));

            return $request;
        } else {
            $this->throwError('objectId and sessionToken are required for the delete method');
        }
    }

    public function addAuthData($authArray) {
        if (is_array($authArray)) {
            $this->authData[$authArray['type']] = $authArray['authData'];
        } else {
            $this->throwError('authArray must be an array containing a type key and a authData key in the addAuthData method');
        }
    }

    public function linkAccounts($objectId, $sessionToken) {
        if (!empty($objectId) || !empty($sessionToken)) {
            $request = $this->request(array(
                'method' => 'PUT',
                'requestUrl' => 'users/' . $objectId,
                'sessionToken' => $sessionToken,
                'data' => array(
                    'authData' => $this->authData
                )
            ));

            return $request;
        } else {
            $this->throwError('objectId and sessionToken are required for the linkAccounts method');
        }
    }

    public function unlinkAccount($objectId, $sessionToken, $type) {
        $linkedAccount[$type] = null;

        if (!empty($objectId) || !empty($sessionToken)) {
            $request = $this->request(array(
                'method' => 'PUT',
                'requestUrl' => 'users/' . $objectId,
                'sessionToken' => $sessionToken,
                'data' => array(
                    'authData' => $linkedAccount
                )
            ));

            return $request;
        } else {
            $this->throwError('objectId and sessionToken are required for the linkAccounts method');
        }
    }
    

    public function requestPasswordReset($email) {
        if (!empty($email)) {
            $this->email - $email;
            $request = $this->request(array(
                'method' => 'POST',
                'requestUrl' => 'requestPasswordReset',
                'email' => $email,
                'data' => $this->data
            ));

            return $request;
        } else {
            $this->throwError('email is required for the requestPasswordReset method');
        }
    }
    
    public function hasRole($name) {
        
        if (!empty($this->objectId)) {
            $role = new \Parse\Role;
            $role->where('name', $name);
            $role->where('users', array(
                "__type" => "Pointer",
                "className" => "Users",
                "objectId" => $this->objectId
            ));
            $role->get();
            if ($role->name == 'admin') {
                return true;
            } else {
                return false;
            }
        } else {
            $this->throwError('objectId is required for the hasRole method');
        }
    }
}