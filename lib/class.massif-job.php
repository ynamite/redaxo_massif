<?php

class massif_job {
	
	protected static $isDetail = false;
	
	public static $translations = [
		'de' => [
			1 => 'Temporär',
			2 => 'Freelance',
			3 => 'Praktikum',
			4 => 'Nebenerwerb',
			5 => 'Festanstellung',
			6 => 'Lehrstelle'
		],
		'en' => [
			1 => 'Temporary',
			2 => 'Freelance',
			3 => 'Internship',
			4 => 'Sideline',
			5 => 'Permanent',
			6 => 'Apprenticeship'
		]
	];
		
	protected static function _getData() {
		
		$oldId = rex_request('id', 'int');
		
		$titleFieldAdd = rex_clang::getCurrentId() == 1 ? '' : '_'.rex_clang::getCurrentId();

		$query = rex_yform_manager_dataset::query('rex_stellen');
		$query->alias('j');
		$query->joinRelation('id_consultant', 'c');
		$query->selectRaw('c.photo AS c_photo, c.photo_alt AS c_photo_alt, c.name AS c_name, c.title'.$titleFieldAdd.' AS c_title, c.phone AS c_phone, c.email AS c_email');
		$query->where('j.status', 1);
		
		$query->orderBy('j.title', 'asc');
	
		if(rex::getProperty('addon-page-id') || $oldId) {
			if($oldId) {
				$rexUrl = rex_getUrl('', '', ['stelle' => $oldId]);
				if(strpos($rexUrl, '?') !== false) {
					return rex_response::sendRedirect(rex_getUrl(2), '301');
				}
				return rex_response::sendRedirect($rexUrl, '301');
			}
			
			$query->where('j.id', rex::getProperty('addon-page-id'));	
			
			if($query->exists()) {
				self::$isDetail = true;
				return $query->find();
			} else {
				rex_response::sendRedirect(rex_getUrl(2), '301');
			}

			
		}
			
		return $query->find();
		
	}
	
	public static function getAnstellungsart($data) {
		$lang = ($data->lang==1) ? 'de' : 'en';
		$anstellungs_art = array_filter(json_decode($data->anstellungs_art, true));
		if(isset($anstellungs_art[0]))
			$anstellungs_art[0] = massif_job::$translations[$lang][$anstellungs_art[0]];
		if(isset($anstellungs_art[1]))
			$anstellungs_art[1] = massif_job::$translations[$lang][$anstellungs_art[1]];
		return implode('-', $anstellungs_art);
	}

	public static function getAnstellungsgrad($data) {
		return implode('-', array_filter(json_decode($data->anstellungsgrad_von_bis, true)));
	}
	
	public static function get(){
		
		$data = self::_getData();
		
		if(self::$isDetail) {
			return massif_utils::renderModule('job', array('data' => $data[0]));
		} else {
			return massif_utils::renderModule('jobs', array('dataArray' => $data));
		}
	
	}

	public static function xml($template) {
		
		$data = self::_getData();

        header('Content-Type: application/xml');

		return massif_utils::renderModule('xml.'.$template, array('dataArray' => $data));

	}

}



?>