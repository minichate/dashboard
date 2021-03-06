<?php
/**
 *  This file is part of Dashboard.
 *
 *  Dashboard is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Dashboard is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Dashboard.  If not, see <http://www.gnu.org/licenses/>.
 *  
 *  @license http://www.gnu.org/licenses/gpl.txt
 *  @copyright Copyright 2007-2009 Norex Core Web Development
 *  @author See CREDITS file
 *
 */

class User extends DBRow {
	function createTable() {
		self::$__CLASS__ = __CLASS__;
		$cols = array(
			'id?',
			DBColumn::make('!text', 'username', 'Username'),
			DBColumn::make('password', 'password', 'Password'),
			DBColumn::make('//text', 'salt', 'Salt'),
			DBColumn::make('select', 'group', 'Group', Group::toArray()),
			DBColumn::make('timestamp', 'timestamp', 'Timestamp'),
			DBColumn::make('text', 'name', 'First Name'),
			DBColumn::make('text', 'last_name', 'Last Name'),
			DBColumn::make('!email', 'email', 'Email'),
			DBColumn::make('//checkbox', 'programmer', 'Programmer'),
			DBColumn::make('status', 'status', 'Status'),
			);
		return parent::createTable("auth", __CLASS__, $cols);
	}
	static function make($id = null) {return parent::make($id, __CLASS__);}
	static function getAll() {
		$args = func_get_args();
		array_unshift($args, __CLASS__);
		return call_user_func_array(array('DBRow', 'getAllRows'), $args);
	}
	static function countAll() {
		$args = func_get_args();
		array_unshift($args, __CLASS__);
		return call_user_func_array(array('DBRow', 'getCountRows'), $args);
	}
	function quickformPrefix() {return 'user_';}
	
	public function hasPerm($class, $key) {
		$p = Permission::hasPerm($this->getGroup(), $class, $key);
		return $p;
	}

	private $oldPassword;

	public function getAddEditFormBeforeSaveHook($form) {
		if ($this->getPassword() && $this->getPassword() != $this->oldPassword) {
			$salt = uniqid('norexcms', true);
			$this->set('salt', $salt);
			$this->setPassword(md5($this->get('password') . md5($salt)));
		} else $this->setPassword($this->oldPassword);
	}

	public function getAddEditForm($target = null) {
		$this->oldPassword = $this->get('password');
		return parent::getAddEditForm($target);
	}

	public function toArray() {
		$array = array();
		foreach (self::getAll() as $s) {
			$array[$s->getId()] = $s->getName() . ' ' . $s->getLastName() . ' (' . $s->getUsername() . ')';
		}
		return $array;
	}

	public static function logout($redirect = true) {
		require_once(SITE_ROOT . '/core/PEAR/Auth.php');
		unset($_SESSION['authenticated_user']);
		$auth_container = DBRow::make(null,"User");
		$auth = new Auth($auth_container, null, 'authInlineHTML');
		$auth->logout();
		if ($redirect) {
			header('Location: /');
			exit;
		}
	}		
}
DBRow::init('User');
?>