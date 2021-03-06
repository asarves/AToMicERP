<?php
/*
 * Equivalent Classe DB basé sur librarie PDO
 */ 

class TPDOdb{
/**
* Construteur
**/
function __construct($db_type = '', $connexionString='', $DB_USER='', $DB_PASS='') {
		
	$this -> db = null;
	$this -> rs = null;            //RecordSet
	$this -> currentLine = null;   //ligne courante
	$this -> query = '';			//requete actuelle
	$this -> type = $db_type;
	$this -> debug = false;
	$this -> debugError = false;
	$this -> error = '';
	
	
	if(empty($connexionString)) {
		if (($db_type == '') && (defined('DB_DRIVER')))
			$db_type = DB_DRIVER;
		else {
			if ($db_type == 'mysql')
				$db_type = 'mysql';
			else
				$db_type = 'mysqli';
		}
	
		if (defined('DB_NAME') && constant('DB_NAME')!='') {
			$db = DB_NAME;
			$usr = DB_USER;
			$pass = DB_PASS;
			$host = DB_HOST;
		}
		else {
			print ('PDO DB ErrorConnexion : Paramètres de connexion impossible à utiliser (db:'.DB_NAME.'/user:'.DB_USER.')');
		}
		
		$this->connexionString = 'mysql:dbname='.DB_NAME.';host='.DB_HOST;
		
		try {
		    $this -> db = new PDO($this->connexionString, DB_USER, DB_PASS);
		} catch (PDOException $e) {
		    $this->Error('PDO DB ErrorConnexion : '.$e->getMessage().' ( '. $this->connexionString.' - '.DB_USER .' )' );
		}
		
	}
	else{
		if(empty($DB_USER))$DB_USER = DB_USER;
		if(empty($DB_PASS))$DB_PASS = DB_PASS;
		
		$this->connexionString = $connexionString;
		try {
		    $this -> db = new PDO($this->connexionString, $DB_USER, $DB_PASS);
		} catch (PDOException $e) {
		    $this->Error('PDO DB ErrorConnexion : '.$e->getMessage().' ( '. $this->connexionString.' ) '.$DB_USER );
		} 
	}
	
	
	
	
	$this -> currentLine = array();

	$this->debugError = defined('SHOW_LOG_DB_ERROR') || (ini_get('display_errors')=='On');
	
	if (_debug() || defined('DB_SHOW_ALL_QUERY') ) {
		print "SQL DEBUG : 	<br>";
		$this -> debug = true;
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	} 

	if (defined('USE_UTF8'))
		$this -> Execute("set names 'utf8'");
}

function beginTransaction() {
	return $this->db->beginTransaction();
}
function commit() {
/*
 * Valide une transaction débuté par beginTransaction()
 * Sinon en AutoCommit
 */
	return $this->db->commit();
}
function rollBack() {
/*
 * Annule une transaction débuté par beginTransaction()
 * Sinon en AutoCommit
 */

	return $this->db->rollBack();
}

function Get_DbType() {
	return $this -> type;
}

function Get_Recordcount() {
	return $this -> rs -> rowCount();
}
private function Error($message, $showTrace=true) {
	$this -> error = $message;
	
	if($this->debug ||  $this->debugError) {
		//print $this->connexionString.'<br/>';
		print "<strong>".$message."</strong>";
		
		if($showTrace) {
			print '<pre>';
			$trace=debug_backtrace();       
	      
	        $log=''; 
	        foreach($trace as $row) {
	                if((!empty($row['class']) && $row['class']=='TPDOdb') 
	                        || (!empty($row['function']) && $row['function']==__FUNCTION__)
	                        || (!empty($row['function']) && $row['function']=='call_user_func')) continue;
	                        
	                if(!empty($row['line'])) $log='<strong>L. '.$row['line'].'</strong>';
	                if(!empty($row['class'])) $log.=' '.$row['class'];
	             	if(!empty($row['file']))   $log.=' <span style="color:green">'.$row['function'].'()</span> dans <span style="color:blue">'.$row['file'].'</span>';
					
					print $log.'<br>';
	        }
			
			//debug_print_backtrace();
			print '</pre><hr>';
		}
		
	}	
	else {
		$trace=debug_backtrace();       
	      
        $log=''; 
        foreach($trace as $row) {
                if((!empty($row['class']) && $row['class']=='TPDOdb') 
                        || (!empty($row['function']) && $row['function']==__FUNCTION__)
                        || (!empty($row['function']) && $row['function']=='call_user_func')) continue;
                        
                if(!empty($row['line']))$log.=' < L. '.$row['line'];
                if(!empty($row['class']))$log.=' '.$row['class'];
                if(!empty($row['file']))$log.=$row['function'].'() dans '.$row['file'];
				//print $log;
        }
		
			
		error_log($message.$log);
	}
		
}
function ExecuteAsArray($sql, $TBind=array() ,$mode = PDO::FETCH_OBJ) {
	
	$this->Execute($sql, $TBind);
	return $this->Get_All($mode);
		
	
}


function Execute ($sql, $TBind=array()){
        $mt_start = microtime(true)*1000;
		 
        $this->query = $sql;
		
		if($this->debug) {
				$this->Error('Debug requête : '.$this->query);
						
		}
		
		if(!empty($TBind)) {
			$this->rs = $this->db->prepare( $this->query);
			foreach($TBind as $k=>$v) {
				$this->rs->bindParam($k, $v);
			}
			
			$this->rs->execute();
		}
		else {
			$this->rs = $this->db->query( $this->query);	
		}
		
        $mt_end = microtime(true)*1000;
		
		if (mysql_errno()) {
			if($this->debug) $this->Error("PDO DB ErrorExecute : " . print_r($this ->db-> errorInfo(),true).' '.$this -> query);
			//return(mysql_errno());
		}
		
		if(defined('LOG_DB_SLOW_QUERY')) {
                $diff = $mt_end - $mt_start;
                if($diff >= LOG_DB_SLOW_QUERY) {
                        $this->Error('PDO DB SlowQuery('.round($diff/1000,2).' secondes) : '.$this -> query)    ;
                        
                }
                
        }
		
		return $this->rs;
}
function quote($s) {
	return $this->db->quote($s);
}
function close() {
	$this->db=null;
}
function dbupdate($table,$value,$key){
        $fmtsql = "UPDATE `$table` SET %s WHERE %s";
        foreach ($value as $k => $v) {
                $v=stripslashes($v);
                if (is_array($key)){
                        $i=array_search($k , $key );
                        if ( $i !== FALSE) {
                                $where[] = "`".$key[$i]."`=" . $this->quote( $v ) ;
                            continue;
                        }
                } else {
                        if ( $k == $key) {
                                $where[] = "`$k`=" .$this->quote( $v ) ;
                                continue;
                        }
                }

                if ($v == '') {
                        $val = 'NULL';
                } else {
                        $val = $this->quote( $v );
                }
                $tmp[] = "`$k`=$val";
        }
        $this->query = sprintf( $fmtsql, implode( ",", $tmp ) , implode(" AND ",$where) );
		
		$res = $this->db->exec( $this->query );
		
		if($res===false) $this->Error("PDO DB ErrorUpdate : " . print_r($this ->db-> errorInfo(),true)." ".$this->query);
		
		if($this->debug)$this->Error("Mise à jour (".(int)$res." ligne(s)) ".$this->query);
		
        return $res;
}
function dbinsert($table,$value){
        $fmtsql = 'INSERT INTO `'.$table.'` ( %s ) values( %s ) ';
        foreach ($value as $k => $v) {
                
                $fields[] = $k;
                if(is_null($v)){
                	$values[] = 'NULL';
				}else{
					$v=stripslashes($v);
					$values[] =$this->quote( $v );
				}
        }
        $this->query = sprintf( $fmtsql, implode( ",", $fields ) ,  implode( ",", $values ) );

        if (!$this->db->exec( $this->query )) {
        		$this->Error("PDO DB ErrorInsert : ". print_r($this ->db-> errorInfo(),true).' '.$this->query);
                return false;
        }
		if($this->debug)$this->Error("Insertion ".$this->query);
		
        return true;
}

function dbdelete($table,$value,$key){
    if (is_array($value)){
          foreach ($value as $k => $v) {
           if (is_array($key)){
              $i=array_search($k , $key );
              if ( $i !== FALSE) {
                 $where[] = "$k=" . $this->quote( $v ) ;
                 continue;
                 }
           }
           else {
              $v=stripslashes($v);
              if( $k == $key ) {
                 $where[] = "$key=" . $this->quote( $v ) ;
                 continue;
                 }
              }
           }
    } else {
        $value=stripslashes($value);
                $where[] = "$key=" . $this->quote( $value );
    }

    $tmp=implode(" AND ",$where);
	
	$this->query = sprintf( 'DELETE FROM '.$table.' WHERE '.$tmp);


    return $this->db->exec( $this->query );
}
function Get_All($mode = PDO::FETCH_OBJ, $functionOrClassOrColumn=null) {
			
	if(!is_null($functionOrClassOrColumn))return $this->rs->fetchAll($mode,$functionOrClassOrColumn);
	else return $this->rs->fetchAll($mode);	
	
	
}
function Get_line($mode = PDO::FETCH_OBJ){
	if(!is_object($this->rs)){
			$this->Error("PDO DB ErrorGetLine : " . print_r($this ->db-> errorInfo(),true).' '.$this->query);
		return FALSE;
	}
	
	$this->currentLine=$this->rs->fetch($mode);
	
	return $this->currentLine;
}

function Get_lineHeader(){
   $ret=array();
   
   if (!empty($this->currentLine)){
      foreach ($this->currentLine as $key=>$val){
         	$ret[]=$key;
      }
	}
   return $ret;
}


function Get_field($pField){
		
		if(isset($this->currentLine->{$pField})) return $this->currentLine->{$pField};
		else return false;

}

}
