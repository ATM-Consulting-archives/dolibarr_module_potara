<?php

	require 'config.php';
	dol_include_once('/potara/class/potara.class.php');
	
	$action = GETPOST('action');
	
	llxHeader();

	if($action == 'fusion') _fusion();
	else{
		_select_tiers();
	}

	llxFooter();
function _fusion() {
	global $db,$langs,$conf,$user;

	$PDOdb=new TPDOdb;

	$TFusion = $_POST['TFusion'];
	$TFieldNoFusion=array('id','datec','date_update','ref','nom');
	$TFieldMetaphoneCompare=array('address','url','email');
	
	foreach($TFusion as $id_master=>$ids_slave) {
		
		$s1 = new Societe($db);
		$s1->fetch($id_master);
		
		$TId_slave = explode(',',$ids_slave);
		
		foreach($TId_slave as $id_slave) {
			
			$p=new TPotara;
			$p->fetchTuple($PDOdb,$id_master,$id_slave);
			$p->id_master = $id_master;
			$p->id_slave = $id_slave;
			$p->entity= $conf->entity;
			$p->save($PDOdb);
			
			$s2 = new Societe($db);
			if($s2->fetch($id_slave)>0) {
				
				echo '('.$s1->id.') '. $s1->getNomUrl(1).' < ('.$s2->id.') '.$s2->getNomUrl().'<br />';
				
				foreach($s1 as $k=>$v) {
					
					if(in_array($k, $TFieldNoFusion)) continue;
					
					if($s2->{$k} != $v) {
						
						if(empty($s2->{$k})) {
							null;
						}
						elseif(empty($v)){
							echo $k. ' < '.$s2->{$k}.'<br />';
							
							$s1->{$k} = $s2->{$k};
							
						}
						else if(in_array($k, $TFieldMetaphoneCompare) && metaphone($s2->{$k},50) != metaphone($v,50)){
							echo 'note +'.$k.'='.$s2->{$k}.' (init : '.$v.', meta '.metaphone($s2->{$k}).' - '.metaphone($v).')<br />';
							
							_add_note_private($s1, '('.$s2->id.') '.$k.'='.$s2->{$k});
							
						}
						else if(!in_array($k, $TFieldMetaphoneCompare)){
							echo 'note +'.$k.'='.$s2->{$k}.' (init : '.$v.')<br />';
							_add_note_private($s1, $k.'='.$s2->{$k});
						}
						
					}
					
				}
				
				$s1->update($s1->id);
				$s1->set_commnucation_level($user);
				$s1->set_OutstandingBill($user);
				$s1->set_parent($s1->parent);
				$s1->set_price_level($s1->price_level, $user);
				$s1->set_prospect_level($user);
				$s1->update_note(dol_html_entity_decode($s1->note_private, ENT_QUOTES),'_private');
				
				$p->alter_element_link();
				
				if($s2->id>0 && $s2->delete($s2->id)<=0) {
					var_dump($s2);
				}
				
				//var_dump($s1);
				echo '<hr />';
			}			
		}		
		
	}

}

function _add_note_private(&$s, $value) {
	
	if(strpos($s->note_private, $value) === false){
		
		$s->note_private.=$value."\n";
	}
	
}

function _select_tiers() {
	global $db,$langs,$conf,$user;

	
	$TTuple=array();
	var_dump($conf->entity);
	$resSoc = $db->query("SELECT rowid,nom,zip,town,status,client FROM ".MAIN_DB_PREFIX."societe WHERE entity = ".$conf->entity." 
	");
	
	while($objs = $db->fetch_object($resSoc)) {
		
		//var_dump($objs->nom,metaphone($objs->nom,15),str_pad($objs->zip,5,'0'), $objs->town, metaphone($objs->town,10),$objs->status);
		
		$key = metaphone($objs->nom,15).str_pad($objs->zip,5,'0'). metaphone($objs->town,10);
		
		@$TTuple[$key][] = $objs;
				
	}
	
	$formCore = new TFormCore('auto','formFusion','post');
	echo $formCore->hidden('action', 'fusion');
	
	?>
	1000 tuple max. recommencez pour la suite
	<table class="border liste" width="100%">
		<tr class="liste_titre">
			<th>Tiers de référence</th>
			<th>Tiers à fusionner qui disparaitrons</th>
			<th>.</th>
		</tr>
	<?php
	
	$nb_tuple = 0;
	foreach($TTuple as $key=>$TSoc) {
		
		if(count($TSoc) <= 1) continue;
	
		usort($TSoc, '_sort_actif_client');
		
		?>
		<tr><td>
		<?php
		
		foreach($TSoc as $k=>&$objsoc) {
			$s=new Societe($db);
			$s->fetch($objsoc->rowid);
				
			if($k == 0) {
				
				echo $s->getNomUrl(1).'</td><td>';
				$s1id = $s->id;
				$TS2id=array();
			}
			else{
				
				echo $s->getNomUrl(1).' - ';
				$TS2id[]= $s->id;
			}
					
			
		} 
		
		?>
		</td><td><?php echo $formCore->checkbox1('', 'TFusion['.$s1id.']', implode(',',$TS2id),1) ?></td></tr>
		<?php
		
		$nb_tuple++;	
		
		if($nb_tuple>=1000) break;
		
	}
	
	echo '</table>';
	
	echo '<div class="tabsAction">'.$formCore->btsubmit('Fusioner', 'bt_fusion').'</div>';
	
	$formCore->end();
	
	echo $nb_tuple .' doublon(s) détecté(s)';
	
	}	
	
	
	
function _sort_actif_client(&$a, &$b) {
	
	if($a->status!=$b->status) {
		return ($a->status == 0) ? 1 : -1;
	}
	if($a->client!=$b->client) {
		return ($a->client == 0) ? 1 : -1;
	}
	
	
	return 0;
}
