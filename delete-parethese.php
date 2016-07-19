<?php

	require 'config.php';
	set_time_limit(0);
	$conf->global->SOCIETE_CODECLIENT_ADDON = '';
	
	$resSoc = $db->query("SELECT rowid
		FROM ".MAIN_DB_PREFIX."societe 
		WHERE entity = ".$conf->entity."
		AND nom LIKE '%(%' 
		
	");
	
	while($objsoc = $db->fetch_object($resSoc)) {
		
		$s=new Societe($db);
		$s->fetch($objsoc->rowid);
		
		$pos = strpos($s->name, '(');
		echo $s->getNomUrl(1).' -> ';
		if($pos>0) {
			
			$s->name = substr($s->name,0,$pos);
			$res = $s->update($s->id,$user,1,1,0);
			if($res<0) {
				var_dump($s);exit;
			}
			echo $s->getNomUrl(1).' <br />';
		}
		
	}

		echo 'Terminé';
