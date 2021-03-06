<?php

/******************************************************************************************
 * Définition du module
 ******************************************************************************************/
$conf->modules['address']=array(
	'name'=>'Address'
	,'id'=>'TAddress'
	,'class'=>array('TAddress')
	,'moduleRequire'=>array('core','company')
);

/******************************************************************************************
 * Définition des templates à utiliser
 ******************************************************************************************/
@$conf->template->TAddress->card = ROOT.'modules/address/template/address.html';
@$conf->template->TAddress->TCompanyAddressList = ROOT.'modules/address/template/address-list.html';
@$conf->template->TAddress->TContactAddressList = $conf->template->TAddress->TCompanyAddressList;

/******************************************************************************************
 * Définition des listes
 ******************************************************************************************/
@$conf->list->TAddress->TCompanyAddressList=array(
	'sql'=>"SELECT id, address, zip, city, country FROM ".DB_PREFIX."address WHERE id_company = @id_company@"
	,'param'=>array(
		'title'=>array(
			'address'=>'__tr(Address)__'
			,'zip'=>'__tr(Zip)__'
			,'city'=>'__tr(City)__'
			,'country'=>'__tr(Country)__'
		)
		,'hide'=>array('id')
		,'link'=>array(
			'address'=>'<a href="?action=view&id=@id@">@val@</a>'
		)
	)
);

@$conf->list->TAddress->TContactAddressList=array(
	'sql'=>"SELECT id, address, zip, city, country FROM ".DB_PREFIX."address WHERE id_contact = @id_contact@"
	,'param'=>array(
		'title'=>array(
			'address'=>'__tr(Address)__'
			,'zip'=>'__tr(Zip)__'
			,'city'=>'__tr(City)__'
			,'country'=>'__tr(Country)__'
		)
		,'hide'=>array('id')
		,'link'=>array(
			'address'=>'<a href="?action=view&id=@id@">@val@</a>'
		)
	)
);