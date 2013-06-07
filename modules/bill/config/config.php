<?php

/******************************************************************************************
 * Définition du module
 ******************************************************************************************/
$conf->modules['bill']=array(
	'name'=>'Bill'
	,'id'=>'TBill'
	,'class'=>array('TBill')
	,'folder'=>'bill'
);

/******************************************************************************************
 * Définition des menus (top / left)
 ******************************************************************************************/
$conf->menu->top[] = array(
	'name'=>'Bills'
	,'id'=>'TBill'
	,'position'=>20
	,'url'=>HTTP.'modules/bill/bill.php'
);

/******************************************************************************************
 * Définition des onglet à afficher sur une fiche de l'objet
 ******************************************************************************************/
$conf->tabs->TBill=array(
	'fiche'=>array('label'=>'__tr(Card)__','url'=>HTTP.'modules/bill/bill.php?action=view&id=@id@')
);

/******************************************************************************************
 * Définition des templates à utiliser
 ******************************************************************************************/
@$conf->template->TBill->fiche = ROOT.'modules/bill/template/bill.html';

/******************************************************************************************
 * Définition de la conf par défaut du module
 ******************************************************************************************/
$conf->defaultConf['company'] = array(
	'TBill_autoref_ref_mask' => 'IN{yy}{mm}-{000}'
	,'TBill_autoref_ref_dateField' => 'dt_bill'
);

/******************************************************************************************
 * Définition des listes
 ******************************************************************************************/
@$conf->list->TBill->billList=array(
	'sql'=>"SELECT id, ref, dt_bill FROM ".DB_PREFIX."bill WHERE id_entity IN (@getEntity@)"
	,'param'=>array(
		'title'=>array(
			'ref'=>'__tr(Ref)__'
			,'dt_bill'=>'__tr(DateBill)__'
		)
		,'hide'=>array('id')
		,'link'=>array(
			'ref'=>'<a href="'.HTTP.'modules/bill/bill.php?action=view&id=@id@">@name@</a>'
		)
	)
);
