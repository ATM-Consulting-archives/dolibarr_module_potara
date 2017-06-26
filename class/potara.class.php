<?php

class TPotara extends TObjetStd{
	
	function __construct() {
		
		parent::set_table(MAIN_DB_PREFIX.'potara');
		parent::add_champs('id_master,id_slave,entity',array('type'=>'integer','index'=>true));
				
		parent::_init_vars();
		parent::start();
		
		
	}
	
	function getObjectKey($name, $zip) {
		global $conf;
		
		$precision = empty($conf->global->POTARA_METAPHONE_PRECISION) ? 15 : $conf->global->POTARA_METAPHONE_PRECISION;
		
		return metaphone($name,$precision).str_pad(trim($zip),5,'0');
	}
	
	function searchTuple($keySearch = null) {
		global $conf, $TTuple,$db;
		
		if(empty($TTuple))$TTuple=array();
		
		$resSoc = $db->query("SELECT rowid,nom,zip,town,status,client
			FROM ".MAIN_DB_PREFIX."societe
			WHERE entity = ".$conf->entity."
			ORDER BY nom
		");
		
		
		while($objs = $db->fetch_object($resSoc)) {

			if($objs->zip == 'NULL') $objs->zip = '';
			
			$key = $this->getObjectKey($objs->nom, $objs->zip);
			@$TTuple[$key][] = $objs;
			
		}
		
		if(!is_null($keySearch)) {
			
			if(isset($TTuple[$keySearch])) return $TTuple[$keySearch];
			else return array();
			
		}
		
		return $TTuple;
		
	}
	
	function fetchTuple(&$PDOdb, $id_master, $id_slave) {
		
		
		$PDOdb->Execute("SELECT rowid FROM ".$this->get_table()." WHERE id_master=".(int)$id_master." AND id_slave=".(int)$id_slave);
		if($obj = $PDOdb->Get_line()) {
			
			return $this->load($PDOdb,$obj->rowid);
			
		}
		
		return false;
		
	}
	
	function alter_element_link() {
		global $db;
		
		$sql = 'SELECT rowid
			FROM '.MAIN_DB_PREFIX.'societe_commerciaux
			WHERE fk_soc = '.(int)$this->id_slave.' AND fk_user IN (
			  SELECT fk_user
			  FROM '.MAIN_DB_PREFIX.'societe_commerciaux
			  WHERE fk_soc = '.(int)   $this->id_master.'
		);';

		$query = $db->query($sql);

		while ($result = $db->fetch_object($query)) {
			$db->query('DELETE FROM '.MAIN_DB_PREFIX.'societe_commerciaux WHERE rowid = '.$result->rowid);
		}
		
		$TTable = array(
			'societe_address',
			'societe_commerciaux',
			'societe_log',
			'societe_prices',
			'societe_remise',
			'societe_remise_except',
			'societe_rib'
			,'propal'
			,'commande'
			,'facture'
			,'facture_rec'
			,'socpeople'
			,'bookmark'
			,'categorie_societe'
			,'actioncomm'
			,'prelevement_lignes'
			,'contrat'
			,'expedition'
			,'fichinter'
			,'commande_fournisseur'
			,'product_fournisseur_price'
			,'facture_fourn'
			,'livraison'
			/*,'product_customer_price'
			,'product_customer_price_log' existe pas en 3.5*/
			,'projet'
			,'user'
		);
		
		$this->commonReplaceThirdparty( $this->id_slave, $this->id_master, $TTable,1);
		
	}
	
	private function commonReplaceThirdparty($origin_id, $dest_id, array $tables, $ignoreerrors=0)
	{
		
		global $db;
		
		foreach ($tables as $table)
		{
			
			$field = 'fk_soc';
			if($table == 'user' || $table=='categorie_societe')$field = 'fk_societe';
				
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$table.' SET '.$field.' = '.$dest_id.' WHERE '.$field.' = '.$origin_id;

			if (! $db->query($sql))
			{
				echo 'Error SQL : '.$sql.'<br />';
			}
		}

		return true;
	}
	
}
