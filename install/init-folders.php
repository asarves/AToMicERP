<?

require_once('../inc.php');

// Load csv files in dictionary/init folder and save it in database
if(empty($company) && empty($_REQUEST['id_company'])) {
	echo 'Dictionary not created, company missing';
	return false;
}

@mkdir(DOCROOT,0777);

$id_entity = __get('id_company',  $company->getId());

$moduleToLoad = array_merge($conf->moduleCore, $conf->moduleEnabled);
foreach($moduleToLoad as $moduleName=>$options) {
	if(!empty($conf->modules[$moduleName]['folder'])) {
		$dirEntity = DOCROOT.$id_entity.'/';
		if(!is_dir($dirEntity)) mkdir($dirEntity);
		$dirModule = $dirEntity.$conf->modules[$moduleName]['folder'].'/';
		if(!is_dir($dirModule)) mkdir($dirModule);
	}
}

print "Default folders created in ".DOCROOT." for entity ".$id_entity.'<br>';