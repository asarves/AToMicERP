<?
	/*
	 * front-end 
	 */

	require('inc.php');
	
	
	TTemplate::show(TPL_TIER,array(
		'tier'=>array(
			'nom'=>array('type'=>'text')
			,'adresse'=>array('type'=>'textarea')
			,'cp'=>array('type'=>'text')
			,'ville'=>array('type'=>'text')
			,'dt_cre'=>array()
		 )
	)); 
	
?>