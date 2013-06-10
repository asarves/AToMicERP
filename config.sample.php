<?

	define('ROOT', '/var/www/ATM/AToMicERP/');
	define('HTTP', 'http://127.0.0.1/ATM/AToMicERP/');
	define('DOCROOT', ROOT.'documents/');
	define('COREROOT', '/var/www/ATM/atm-core/');
	define('COREHTTP', 'http://127.0.0.1/ATM/atm-core/');

	define('DB_HOST','localhost');
	define('DB_NAME','atomicERP');
	define('DB_USER','root');
	define('DB_PASS','**********'); /* Your user password Here */
	define('DB_DRIVER','mysqli');
	define('DB_PREFIX','atom_');

	define('DEFAULT_THEME','atom');
	define('DEFAULT_LANG','fr_FR');
	define('DEFAULT_COUNTRY', 'FR');
	define('ATOMIC_LOGO','AToMic ERP');

	define('ADMIN','admin');
	
	/*
	 * Suite au prob de sens parsing répertoire, et temporairement jusqu'à prise de décision
	 */
	@$conf->moduleEnabled=array(
		'address'=>true
		,'bank'=>true
		,'company'=>true
		,'contact'=>true
		,'document'=>true
		,'bill'=>true
		,'product'=>true
		,'user'=>true
		,'dictionary'=>true
		,'project'=>true
	);