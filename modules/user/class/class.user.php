<?php

class TUser extends TContact {
	
	function __construct() {
		
		parent::__construct();
		
		$this->setChild('TGroupUser', 'id_user');
		
		$this->t_connexion = 0;
		$this->id_entity = 0;
		
		$this->lang = DEFAULT_LANG;
		$this->rights = array();
		
	}
	
	function load(&$db, $id) {
		parent::load($db, $id);
		$this->load_right($db);
	}
	
	function load_right(&$db) {
		foreach($this->TGroupUser as $groupUser) {
				$TRight = TRequeteCore::get_id_from_what_you_want($db, 'right', array('id_group'=>$groupUser->id_group));
				foreach($TRight as $id_right) {
					$right = new TRight;
					$right->load($db, $id_right);
					$this->rights[$groupUser->id_entity]->{$right->module}->{$right->submodule}->{$right->action} = true;
				}
		}
	}
	
	function login (&$db, $login, $pwd, $id_entity) {
		
		$sql = "SELECT id FROM ".$this->get_table()." 
			WHERE login=".$db->quote($login)." AND password=".$db->quote($pwd)."
			AND status = 1";
		$db->Execute($sql);
			
		if($db->Get_line()) {
			$this->id_entity = $id_entity;	
			$this->t_connexion = time();
			$db->debug=true;
			return $this->load($db, $db->Get_field('id'));
		}	
		/*else  {
			print "ErrorBadLogin";
		}*/
			
		return false;
	}
	
	function isLogged() {
		
		if(!empty($_SESSION['user']) && !empty($this->rights[$this->id_entity])  && $this->t_connexion > 0 ) {
			return true;
			
		}
		
		return false;
	}
	function right($module='main', $submodule='main', $action='view') {
		
		if($this->isAdmin) return true;
		else if(!empty($this->rights[$this->id_entity]->{$module}->{$submodule}->{$action})) return true;
		
		return false;
		
	}
}
