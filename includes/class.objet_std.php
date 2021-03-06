<?php

/**
 * Classe Objet standart
 * 
 * description plus longue
 * 
 * @author Alexis
 * @version 1.4.2.5
 * @subpackage objet standart
 **/
 
 
class TObjetStd {

	public $table = '';
	public $TChamps=array(); /* tableau contenant la d?claration de variables */
	public $TNoSaveVars=array(); /* tableau des variables ? charger mais pas ? sauvegarder */
	public $TList=array(); /* tableau permettant la construction d'une liste */ 
		
	public $TChildForeignKey=array();

	/**
	 * constructeur
	 **/
	function __construct(){
			
		$this->{OBJETSTD_MASTERKEY}=0; /* Primary Key */
		$this->{OBJETSTD_DATECREATE}=time();
		$this->{OBJETSTD_DATEUPDATE}=time();
	}
	/**
	 * change la table
	 */
	function set_table($tableName){
    	$this->table=$tableName;
    }
	
	function add_champs($name, $infos=array()){ //deprecated
		$this->addFields($name, $infos);
	}
	function addFields($name, $infos=array()){
		
		if(is_string($infos))$infos = strtolower($infos); // deprecated
		
        if(!empty($name)){
	        $var = explode(',', $name);
	        $nb=count($var);
	        for ($i=0; $i<$nb ; $i++) {
	        	
	        	$this->TChamps[trim($var[$i])] = $infos; //TODO rename TField
	        } // for
    	}
    
  	}

  function get_table(){
    return $this->table;
  }
	function get_champs(){
    return $this->TChamps;
  }
	function _get_field_list(){
    $r="";
    foreach ($this->TChamps as $nom_champ=>$info) {
    	$r.=$nom_champ.",";    	
    }
    return $r;
  }
	
	
//récupère les données de la base de données
	// d'ou le nom, set vars BY db
	function _set_vars_by_db(&$db){
	
		foreach ($this->TChamps as $nom_champ=>$info) {
			if($this->_is_date($info)){
				$this->{$nom_champ} = strtotime($db->Get_field($nom_champ));
			}
			elseif($this->_is_tableau($info)){
				//echo '<li>TABLEAU '.$nom_champ." ".$db->Get_field($nom_champ)." ".unserialize($db->Get_field($nom_champ));
				$this->{$nom_champ} = @unserialize($db->Get_field($nom_champ));
				//HACK POUR LES DONNES NON UTF8
				if($this->{$nom_champ}===FALSE)@unserialize(utf8_decode($db->Get_field($nom_champ)));
			}
			elseif($this->_is_int($info)){
				$this->{$nom_champ} = (int)$db->Get_field($nom_champ);
			}
			elseif($this->_is_float($info)){
				$this->{$nom_champ} = (double)$db->Get_field($nom_champ);
			}
			elseif($this->_is_null($info)){
				$val = $db->Get_field($nom_champ);
				// le zéro n'est pas null!				
				$this->{$nom_champ} = (is_null($val) || (empty($val) && $val!==0 && $val!=='0')?null:$val);				
			} 
			else{
				$this->{$nom_champ} = $db->Get_field($nom_champ);
			}
				
		}
	}

function _no_save_vars($lst_chp) {
  	  if($lst_chp!=""){
  			$this->TNoSaveVars=array();
			
			$var = explode(',', $lst_chp);
	        $nb=count($var);
	        for ($i=0; $i<$nb ; $i++) {
	        	$this->TNoSaveVars[trim($var[$i])]=true;
	        } // for
      }
  }

  function addFieldsInDb(&$db) {
  	
		$db->Execute('SHOW FIELDS FROM `'.$this->get_table().'`');
		$Tab=array();
		while($db->get_line()) {
			$Tab[] = $db->Get_field('Field');
		}
	
		$TChamps = array_merge(array(OBJETSTD_DATECREATE=>'type=date;',OBJETSTD_DATEUPDATE=>'type=date;'),$this->TChamps);
	
	  	foreach($TChamps as $champs=>$info) {
	  		
			if(!in_array($champs, $Tab)) {
				if($this->_is_int($info)) $db->Execute('ALTER TABLE `'.$this->get_table().'` ADD `'.$champs.'` int(11)');	
				else if($this->_is_date($info)) $db->Execute('ALTER TABLE `'.$this->get_table().'` ADD `'.$champs.'` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\'');	
				else if($this->_is_float($info)) $db->Execute('ALTER TABLE `'.$this->get_table().'` ADD `'.$champs.'` DOUBLE');
				else if($this->_is_tableau($info)) $db->Execute('ALTER TABLE `'.$this->get_table().'` ADD `'.$champs.'` LONGTEXT');
				else $db->Execute('ALTER TABLE `'.$this->get_table().'` ADD `'.$champs.'` VARCHAR(255)');	
				
				if($this->_is_index($info)) {
					 $db->Execute('ALTER TABLE '.$this->get_table().' ADD INDEX `'.$champs.'`(`'.$champs.'`)');
				}
				
			}
			
		}
	
  }

  function init_db_by_vars(&$db) {
  	$db->Execute("SHOW TABLES FROM `".DB_NAME."` LIKE '".$this->get_table()."'");
	if(!$db->Get_line()) {
		/*
		 * La table n'existe pas, on la crée
		 */
		
		$sql = "CREATE TABLE `".$this->get_table()."` (
 				`".OBJETSTD_MASTERKEY."` int(11) NOT NULL DEFAULT '0'
 				,`".OBJETSTD_DATECREATE."` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
 				,`".OBJETSTD_DATEUPDATE."` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
 				
 				,PRIMARY KEY (`".OBJETSTD_MASTERKEY."`)
 				,KEY `".OBJETSTD_DATECREATE."` (`".OBJETSTD_DATECREATE."`)
 				,KEY `".OBJETSTD_DATEUPDATE."` (`".OBJETSTD_DATEUPDATE."`)
 				) ENGINE=MyISAM DEFAULT CHARSET=utf8";
		
		$db->Execute($sql);
	}
		
	$this->addFieldsInDb($db);
	
  }

  function init_vars_by_db(&$db) {
  	$db->Execute("SHOW COLUMNS FROM `".$this->get_table()."`");
	while($db->Get_line()) {
		$nom = strtolower($db->Get_field('Field'));
		$type_my = $db->Get_field('Type');
		
		if(strpos($type_my,'int(')!==false) $type='type=entier;';
		else if(strpos($type_my,'date')!==false) $type='type=date;';		
		else $type='type=chaine;';		
		
		$this->add_champs($nom, $type);		
	}
	
	
	$this->_init_vars();
  }
  
  function _init_vars($lst_chp=''){ 
    if(!empty($lst_chp)){
      $var = explode(',', $lst_chp);
      $nb=count($var);
      for ($i=0; $i<$nb ; $i++) {
      	$this->add_champs($var[$i]);
      } // for
    }

    $first = true;

    foreach ($this->TChamps as $nom_champ=>$info) {
    
        if($first) {
          $this->champs_indexe = $nom_champ;
          $first=false;
        }
    
        if($this->_is_date($info)){
          $this->{$nom_champ} = time();
        }
        elseif($this->_is_tableau($info)){
          $this->{$nom_champ} = array();
        }
        elseif($this->_is_int($info)){
          $this->{$nom_champ} = (int)0;
        }
        elseif($this->_is_float($info)) {
				$this->{$nom_champ} = (double)0;
				}
        else{
          $this->{$nom_champ} = "";
        }
    } 
    $this->to_delete=false; 
  }
  
  function get_date($nom_champ,$format_date='d/m/Y') {
  	if($this->{$nom_champ}<=strtotime('0000-00-00 00:00:00')) return '';
	else {
	    return date($format_date, (int)$this->{$nom_champ});
	}
	
  }
  
  function set_date($nom_champ,$date){
  	
	  	if(empty($date)) {
	  		$this->{$nom_champ} = strtotime('0000-00-00 00:00:00');
	  	}
		else if(strpos($date,'/')===false){
	  		$this->{$nom_champ} = strtotime($date);
		}
	  	else {
			list($d,$m,$y) = explode('/',$date);
			$this->{$nom_champ} = mktime(0,0,0,$m,$d,$y);
		}
		return $this->{$nom_champ};
	}
	
	
  function _is_date($info){
  	
	if(is_array($info)) {
		if(isset($info['type']) && $info['type']=='date') return true;
		else return false;
	}
	else {
	    $pos = strpos($info,'type=date;'); // deprecated
	    if($pos===false)return false;
	    else return true;
		
	}
  }
  
  function _is_tableau($info){
  		return $this->_is_array($info); 
  }
  function _is_array($info) {
  	if(is_array($info)) {
		if(isset($info['type']) && $info['type']=='array') return true;
		else return false;
	}
	else {
		
	    $pos = strpos($info,'type=tableau;'); // deprecated
	    if($pos===false)return false;
	    else return true;
			
	}
	
  }

  
  function _is_null($info){
	if(is_array($info)) {
		if(isset($info['type']) && $info['type']=='null') return true;
		else return false;
	}
	else {
	    $pos = strpos($info,'type=null;'); // deprecated
	    if($pos===false)return false;
	    else return true;
	
	}

  }
  
  function _is_int($info){

	if(is_array($info)) {
		if(isset($info['type']) && ($info['type']=='int' || $info['type']=='integer' )) return true;
		else return false;
	}
	else {

	    $pos = strpos($info,'type=entier;'); // deprecated
	    if($pos===false)return false;
	    else return true;

	}

  }
  function _is_float($info){

	if(is_array($info)) {
		if(isset($info['type']) && $info['type']=='float') return true;
		else return false;
	}
	else {

		$pos = strpos($info,'type=float;'); // deprecated
		if($pos===false) return false;
		else return true;
	}
  }
  function _is_text($info){
  	if(is_array($info)) {
		if(isset($info['type']) && $info['type']=='text') return true;
		else return false;
	}
	else {
		$pos = strpos($info,'type=text;'); // deprecated
		if($pos===false) return false;
		else return true;
	}
  }
  function _is_index($info){
  	if(is_array($info)) {
		if(isset($info['index']) && $info['index']==true) return true;
		else return false;
	}
	else {
	
		$pos = strpos($info,'index;'); // deprecated
		if($pos===false) return false;
		else return true;
	}
  }
  
  
  
  function _set_save_query(&$query){
    foreach ($this->TChamps as $nom_champ=>$info) {
    
     // /* modification des dates au format français vers un format anglais
     // (format d'enregistrement des dates dans la base)
     
      if(isset($this->TNoSaveVars[$nom_champ])) {
				null; // ne pas sauvegarder ce champs 
 	  }
      elseif(!strcmp($nom_champ,'idx')) {
      	$query[$nom_champ] = (isset($this->{$this->champs_indexe}[0]) && ctype_alpha($this->{$this->champs_indexe}[0]))?strtoupper($this->{$this->champs_indexe}[0]):'0'; 
      }
      else if($this->_is_date($info)){
		if(empty($this->{$nom_champ})){
			$query[$nom_champ] = '0000-00-00 00:00:00';
		}
		else{
			$date = date('Y-m-d H:i:s',$this->{$nom_champ});
			$query[$nom_champ] = $date;
		}
      }
      else if($this->_is_tableau($info)){
      	//print_r($this);
      	//die('serialize automatiquement les données '.$nom_champ);
        
        $query[$nom_champ] = serialize($this->{$nom_champ});
      }
      elseif($this->_is_null($info)) {
      	$query[$nom_champ] = (is_null($this->{$nom_champ}) || (empty($this->{$nom_champ}) && $this->{$nom_champ}!==0 && $this->{$nom_champ}!=='0')?null:$this->{$nom_champ}); 
      }
      else{
        $query[$nom_champ] = $this->{$nom_champ};
      }
      
    	
    }
  }
  
  function start(){
  
     $this->{OBJETSTD_MASTERKEY} = 0; // le champ id est toujours def   
     $this->{OBJETSTD_DATECREATE}=time(); // ces champs dates aussi
	 $this->{OBJETSTD_DATEUPDATE}=time();
	 
	 if(!isset($this->TChildObjetStd)) {
	 	$this->TChildObjetStd=array();
	 	$this->withChild = true;
		$this->unsetChildDeleted = false;
	 }
	 
	 if(defined('OBJETSTD_MAKETABLEFORME')) {
			$db=new TPDOdb;
			$this->init_db_by_vars($db);
			$db->close();
	 }
	 
  }
  function run_trigger(&$ATMdb, $state) {
  	/* Execute les trigger */
  	if(class_exists('TTrigger')) {
  		
  		TTrigger::run($ATMdb,$this, get_class($this), $state);
		
  	}	
	
  }
  function loadBy(&$db, $value, $field, $annexe=false) {
  	$db->Execute("SELECT ".OBJETSTD_MASTERKEY." FROM ".$this->get_table()." WHERE ".$field."='".$value."' LIMIT 1");
	if($db->Get_line()) {
		return $this->load($db, $db->Get_field(OBJETSTD_MASTERKEY), $annexe);
	}
	else {
		return false;
	}	
	
  }
  function load(&$db,$id){
		if((int)$id==0) return false;
			
		$db->Execute( 'SELECT '.$this->_get_field_list().OBJETSTD_DATECREATE.','.OBJETSTD_DATEUPDATE.' 
						FROM '.$this->get_table().' 
						WHERE '.OBJETSTD_MASTERKEY.'='.$id );
		if($db->Get_line()) {
				$this->{OBJETSTD_MASTERKEY}=$id;
				
				$this->_set_vars_by_db($db);
				
				$this->{OBJETSTD_DATECREATE}=strtotime($db->Get_field(OBJETSTD_DATECREATE));
				$this->{OBJETSTD_DATEUPDATE}=strtotime($db->Get_field(OBJETSTD_DATEUPDATE));
				
				$this->loadChild($db);
				
				$this->run_trigger($db, 'load');
				
				return true;		
		}
		else {
				return false;
		}
	}
	
	function setChild($class, $foreignKey, $orderBy='', $useSimpleTabName=false) {
		$this->TChildObjetStd[]=array(
			'class'=>$class
			,'foreignKey' => $foreignKey
			,'orderBy'=>$orderBy
			,'useSimpleTabName' => (int)$useSimpleTabName
		);
		
		$tabName = $class;
		if(!$useSimpleTabName && is_array($foreignKey)) {
			$tabName .= '_'.$foreignKey[1];
		}
		
		if(is_array($foreignKey)) {
			$this->TChildForeignKey[$tabName] = $foreignKey;
			$this->TChildForeignKey[$tabName][3] = (int)$useSimpleTabName;
		}
		
		$this->{$tabName} = array();
	
	}
	function removeChild($className, $id, $key=OBJETSTD_MASTERKEY) {
		foreach($this->{$className} as &$object) {
			
				if($object->{$key} == $id) {
					$object->to_delete = true;	
					return true;
				}
				
			
		}
		return false;
	}
	function addChild(&$db, $tabName, $id=0, $key=OBJETSTD_MASTERKEY) {
		if($id>0) {
			foreach($this->{$tabName} as $k=>&$object) {
				if($object->{$key} == $id) return $k;
				
			}
		}

		$k = count($this->{$tabName});
		
		$className = substr($tabName, 0, strrpos($tabName, '_'));
		
		if(empty($className)){
			$className = $tabName;
		}
		
		
		$objectType = empty($this->TChildForeignKey[$tabName]) ? '' : $this->TChildForeignKey[$tabName][1]; 
		$this->{$tabName}[$k] = new $className;	
	
		if(!empty($objectType)) $this->{$tabName}[$k]->objectType = $objectType;
		//var_dump($this->{$tabName});
		//exit;
		if($id>0) { $this->{$tabName}[$k]->load($db, $id); }
		

		return $k;
	}
	function loadChild(&$db) {
		if($this->withChild) {
			//TODO add order by on load
			
			foreach($this->TChildObjetStd as $child) {
					$className = $child['class'];		
					$foreignKey	= $child['foreignKey'];
					//print $className;print_r($foreignKey);print '<br>';
					if(is_array($foreignKey)) {
						$keys = array($foreignKey[0]=>$this->getId(), 'objectType'=>$foreignKey[1]);
						if(empty($child['useSimpleTabName'])) $tabName = $className.'_'.$foreignKey[1];
					}
					else{
						$keys = array($foreignKey=>$this->getId());
						$tabName = $className;
					}
					
					$this->{$tabName}=array();
					$o=new $className;
					
					$TId = TRequeteCore::get_id_from_what_you_want($db, $o->get_table(), $keys);
					foreach($TId as $k=>$id) {
						$this->{$tabName}[$k] = new $className;	
						$this->{$tabName}[$k]->load($db, $id);
					}
					
			}
			
		}
		
	}
	/*
	 * Load a subobject in child's array
	 * I : Array Name, Object Name, Key in line Name
	 * O : null
	 */
	function loadChildSubObject($db, $tabName, $objectName, $innerKeyName) {
		
		if(!empty($this->{$tabName})) {
			
			foreach($this->{$tabName} as &$row) {
				
				$row->{$objectName}->load($db, $row->{$innerKeyName});
				
			}
			
		}
		
	}
	
	function saveChild(&$db) {
	
		if($this->withChild) {
		
			foreach($this->TChildObjetStd as $child) {
				
					$className = $child['class'];		
					$foreignKey	= $child['foreignKey'];
					
					$tabName = $className;
					if(is_array($foreignKey) && empty($this->TChildForeignKey[$tabName][3])) {
						$tabName .= '_'.$foreignKey[1];
					}	
					/*	print "$tabName <br>";
						print_r($this->{$tabName});*/
					foreach($this->{$tabName} as $i => &$object) {
						
						if(is_array($foreignKey)) {
							$object->{$foreignKey[0]} = $this->getId();
							$object->objectType = $foreignKey[1];
						}
						else{
							$object->{$foreignKey} = $this->getId();
						}	
							
						
						$object->save($db);
						if($this->unsetChildDeleted && isset($object->to_delete) && $object->to_delete==true) unset($this->{$tabName}[$i]);
					}
					
			}
		}	
	}
	
	
	function save(&$db){
		//$this->save_log($db);
		if(isset($this->to_delete) && $this->to_delete==true) {
			$this->delete($db);
		}
		else {
			$this->run_trigger($db, ($this->{OBJETSTD_MASTERKEY}==0) ? 'before_create' : 'before_update');
				
			$query = array();
			$query[OBJETSTD_DATECREATE] = date("Y-m-d H:i:s",$this->{OBJETSTD_DATECREATE});
			if(!isset($this->no_dt_maj))$query[OBJETSTD_DATEUPDATE] = date('Y-m-d H:i:s');
			$this->_set_save_query($query);
			
			
			$key[0]=OBJETSTD_MASTERKEY;
			
			if($this->{OBJETSTD_MASTERKEY}==0){
				$this->get_newid($db);
				$query[OBJETSTD_MASTERKEY]=$this->{OBJETSTD_MASTERKEY};

				$db->dbinsert($this->get_table(),$query);

				$this->run_trigger($db, 'create');
			}
			else {
				$query[OBJETSTD_MASTERKEY]=$this->{OBJETSTD_MASTERKEY};
			
				$db->dbupdate($this->get_table(),$query,$key);
				
				$this->run_trigger($db, 'update');
					
			}
			
			$this->saveChild($db);
			
		}
		return $this->{OBJETSTD_MASTERKEY};
	}
	

	function delete(&$db){
		if($this->{OBJETSTD_MASTERKEY}!=0){
			$this->run_trigger($db, 'before_delete');
			$db->dbdelete($this->get_table(),array(OBJETSTD_MASTERKEY=>$this->{OBJETSTD_MASTERKEY}),array(0=>OBJETSTD_MASTERKEY));
			$this->run_trigger($db, 'delete');
	
		}
		
		if($this->withChild) {
			
			foreach($this->TChildObjetStd as $child) {
					$className = $child['class'];		
					$foreignKey	= $child['foreignKey'];
					
					$tabName = $className;
					if(is_array($foreignKey)) {
						$tabName .= '_'.$foreignKey[1];
					}
						
					foreach($this->{$tabName} as &$object) {
							
						$object->delete($db);
						
					}
					
			}
		}	
		
	}
	function get_newid(&$db){
		$sql="SELECT max(".OBJETSTD_MASTERKEY.") as 'maxi' FROM ".$this->get_table();
		$db->Execute($sql);
		$db->Get_line();
		$this->{OBJETSTD_MASTERKEY} = (double)$db->Get_field('maxi')+1;
	}
	function get_dtcre(){
		return $this->get_date(OBJETSTD_DATECREATE);
	}
	function get_dtmaj(){
		return $this->get_date(OBJETSTD_DATEUPDATE);
	}
	function set_dtcre($date){
		return $this->set_date(OBJETSTD_DATECREATE, $date);	
	}
	function set_dtmaj($date){
		return $this->set_date(OBJETSTD_DATEUPDATE, $date);	
	}
	
	function get_values($recursif = false, $object = null) {
		/* Pour la cohérence x/x set_values
		 */
		return $this->get_tab($recursif, $object);
	}
	
	
	/*
	 * Retourne le contenu de l'objet sous forme de tableau
	 */	
	function get_tab($recursif = false, $object = null) {
		if(is_null($object))$object = $this;
		$Tab=array();
		foreach ($object as $key => $value) {
			if($key==='TChamps'){ 
				null;
			}
			else if(is_object($value) || is_array($value)) {
				if($recursif) $Tab[$key] = $this->get_tab($recursif, $value);
				else $Tab[$key] = $value;
			}
			else if(substr($key,0, strlen(OBJETSTD_DATEMASK) )===OBJETSTD_DATEMASK){
				if($value===FALSE)$Tab[$key] = '0000-00-00 00:00:00';
				else $Tab[$key] = date('Y-m-d H:i:s',$value);
			}
			else{
				$Tab[$key]=$value;
			}
		}
		return $Tab;
	}
	
	private function gx_node(&$xml, $Tab) {
 		foreach($Tab as $k=>$v) {
 			if(is_array($v)) {
				$child = $xml->addChild($k);
				$this-> gx_node($child, $v);
 			}
 			else{
 				$xml->addChild($k,$v);	
 			}
 			 
 		}
 	}
 	function get_xml() {
 		$Tab = $this->get_values(true) ;
 		
 		$xml = new SimpleXMLElement("<".get_class($this)."/>");
 		
 		$this->gx_node($xml, $Tab);
 		return $xml->asXML();
 		
 	}
	
	function set_values($Tab) {
		foreach ($Tab as $key=>$value) {
						
			if(substr(strtolower($key),0,strlen(OBJETSTD_DATEMASK))==OBJETSTD_DATEMASK) {
				$this->set_date($key, $value);
			}
			// correction, pour le cas où l'on a une propriété nulle : 
			// $this->validate=null, mais le champ existe bien dans le TChamps
			elseif(isset($this->TChamps[$key])) {
			    if( $this->_is_tableau($this->TChamps[$key])){
					//pas de traitement particulier sur un tableau, le stripslashes est dans le save...
					$this->{$key} = $value;
				}
				else if($this->_is_float($this->TChamps[$key])) {
					$this->{$key} = Tools::string2num($value);						
				}
				else if($this->_is_int($this->TChamps[$key])) {
					$this->{$key} = (int)Tools::string2num($value);						
				}
				else {
					// le @ protège des tableaux passés
					$this->{$key} = @stripslashes($value);
				}
			}
			else{
				// die('cas pas pris en compte...'.$key);
			}
		}
	}
	
	function get_i_tab_by_id($tab, $id) {
	// retourne i tab objet
		$Tab = &$this->{$tab};
		
		$nb=count($Tab);
		
	  for ($i=0; $i<$nb; $i++) {
	  		if($Tab[$i]->{OBJETSTD_MASTERKEY}==$id) return $i;
	  }
	
	
	}
	
	function render($type='liste') {
		
		$r = new TSSRenderControler($this);
		
		switch ($type) {
			case 'liste':
				$r->liste();
					
				break;
			default:
				
				break;
		}
		
	}
	
	/**
	 *  méthode renvoyant une condition WHERE pour un objet en fonction d'un état
	 *  (à surcharger dans la class métier)	 
	 **/	 	
	function getStatus($status=""){
		// par défaut retourne 1
		return "1";
	}
	
	/**
	 *  méthode renvoyant le nb total
	 *  une condition WHERE peut-être passée	 
	 **/	 	
	function getNb(&$db,$cond='1'){
		$table =  $this->get_table();
		$sql = 'SELECT count(*) as nb FROM '.$table.' WHERE '.$cond;
		$db->Execute($sql);
		$nb = 0;
		if($db->get_line())$nb = $db->get_field('nb');
		return $nb;
	}
	function getId(){
		return $this->{OBJETSTD_MASTERKEY};
	}
}
/*
 * Simple Standard Render Controler 
 */
class TSSRenderControler {
	function __construct(&$object) {
		
		$this->TList=array( /* tableau permettant la contruction d'une liste */ 
			'titre'=>'Liste'
			,'nothing'=>'Aucun élément dans la liste'
			,'Fields'=>array()
			,'ColumnType'=>array()
		); 
		
		$this->object = & $object;
		
		foreach($object->TChamps as $k=>$v) {
			if(!in_array($k, array(OBJETSTD_MASTERKEY,OBJETSTD_DATECREATE,OBJETSTD_DATEUPDATE )))  $this->TList['Fields'][$k]=$k;
		}
		
		$this->sql = "SELECT ".OBJETSTD_MASTERKEY." as 'ID' @Champs@, ".OBJETSTD_DATECREATE." as 'Création', ".OBJETSTD_DATEUPDATE." as 'Modification' 
			FROM ".$this->object->get_table();
	}	
	
	function liste(&$db, $sql='', $TParam=array()) {
			
		if(empty($sql)) {
			$sql = $this->sql;
		}	
	
		$fields='';	
		foreach($this->TList['Fields'] as $k=>$v) {
			$fields.=",`$k` as '".addslashes($v)."'";
		}	
				
		$sql=strtr($sql, array('@Champs@'=>$fields, '@table@'=>$this->object->get_table()));	
			
		$listname = 'list_'.$this->object->get_table();
		$lst = new TListviewTBS($listname);
		
		print $lst->render($db, $sql, $TParam);
		
	}
	
}

/**
 * Simple Standard Objet
 * 
 * description plus longue
 * 
 * @author Alexis
 * @version 1.4.2.5
 * @subpackage objet standart
 **/
class TSSObjet extends TObjetStd {
	function TSSObjet(&$db, $table) {
		parent::set_table($table);
		
		parent::init_vars_by_db($db);
		
		parent::start();
	}
	
	function view_file_source() {
		
		$table = $this->get_table();
		
		$pos = strpos($table,'_');
		if($pos!==false) $objname = substr($table, $pos+1);
		else $objname = $table;
		
		$objname = 'T'.ucfirst(strtolower($objname));
		$TNot=array(OBJETSTD_MASTERKEY,OBJETSTD_DATECREATE,OBJETSTD_DATEUPDATE);
		
		$TChp = array();	
		foreach($this->TChamps as $champs=>$type) {
			if(!in_array($champs, $TNot)) {
				if(!isset($TChp[$type]))$TChp[$type]='';
				if($TChp[$type]!='')$TChp[$type].=',';
				$TChp[$type].=$champs;
			}	
		}
		print_r($TChp);
		$cr= "\r\n";
		ob_start();
		
		print '<?php'.$cr;
		print 'class '.$objname.' extends TObjetStd {'.$cr;
		print '	function __construct() { /* declaration */'.$cr;
		print '		parent::set_table(\''.$table.'\');'.$cr;

		foreach($TChp as $type=>$champs) {
			print '		parent::add_champs(\''.$champs.'\',\''.$type.'\');'.$cr;				
		}

		print '		parent::start();'.$cr;
		print '		parent::_init_vars();'.$cr;
		print '	}';

		
		
		
		print '}'.$cr;
	
		
		
		print '<pre>'._htmlentities(ob_get_clean()).'</pre>';
		
	}
	
}
	
