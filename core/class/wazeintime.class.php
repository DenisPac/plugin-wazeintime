<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class wazeintime extends eqLogic {
    /*     * *************************Attributs****************************** */
    
    public static function cron30($_eqlogic_id = null) {
		if($_eqlogic_id !== null){
			$eqLogics = array(eqLogic::byId($_eqlogic_id));
		}else{
			$eqLogics = eqLogic::byType('wazeintime');
		}
        foreach ($eqLogics as $wazeintime) {
			if ($wazeintime->getIsEnable() == 1) {
				log::add('wazeintime', 'debug', 'Pull Cron pour Waze Duration');
                $latdepart=$wazeintime->getConfiguration('latdepart');
                $londepart=$wazeintime->getConfiguration('londepart');
                $latarrive=$wazeintime->getConfiguration('latarrive');
                $lonarrive=$wazeintime->getConfiguration('lonarrive');
                $wazeRouteurl = "https://www.waze.com/row-RoutingManager/routingRequest?from=x%3A$londepart+y%3A$latdepart&to=x%3A$lonarrive+y%3A$latarrive&at=0&returnJSON=true&returnGeometries=true&returnInstructions=true&timeout=60000&nPaths=3&options=AVOID_TRAILS%3At";
				$wazeRoutereturl = "https://www.waze.com/row-RoutingManager/routingRequest?from=x%3A$lonarrive+y%3A$latarrive&to=x%3A$londepart+y%3A$latdepart&at=0&returnJSON=true&returnGeometries=true&returnInstructions=true&timeout=60000&nPaths=3&options=AVOID_TRAILS%3At";
				$routeResponseText = file_get_contents($wazeRouteurl);
                $routeResponseJson = json_decode($routeResponseText,true);
                $route1Name = $routeResponseJson['alternatives'][0]['response']['routeName'];  
                $route2Name = $routeResponseJson['alternatives'][1]['response']['routeName'];  
                $route3Name = $routeResponseJson['alternatives'][2]['response']['routeName'];
                $route1 = $routeResponseJson['alternatives'][0]['response']['results'];
                $route2 = $routeResponseJson['alternatives'][1]['response']['results'];
                $route3 = $routeResponseJson['alternatives'][2]['response']['results'];
                $route1TotalTimeSec = 0;
                foreach ($route1 as $street){
                    $route1TotalTimeSec += $street['crossTime'];
                }
                $route2TotalTimeSec = 0;
                foreach ($route2 as $street){
                    $route2TotalTimeSec += $street['crossTime'];
                }
                $route3TotalTimeSec = 0;
                foreach ($route3 as $street){
                    $route3TotalTimeSec += $street['crossTime'];
                }
                $route1TotalTimeMin = round($route1TotalTimeSec/60);
                $route2TotalTimeMin = round($route2TotalTimeSec/60);
                $route3TotalTimeMin = round($route3TotalTimeSec/60);
                $routeretResponseText = file_get_contents($wazeRoutereturl);
                $routeretResponseJson = json_decode($routeretResponseText,true);
                $route1retName = $routeretResponseJson['alternatives'][0]['response']['routeName'];  
                $route2retName = $routeretResponseJson['alternatives'][1]['response']['routeName'];  
                $route3retName = $routeretResponseJson['alternatives'][2]['response']['routeName'];
                $routeret1 = $routeretResponseJson['alternatives'][0]['response']['results'];
                $routeret2 = $routeretResponseJson['alternatives'][1]['response']['results'];
                $routeret3 = $routeretResponseJson['alternatives'][2]['response']['results'];
                $route1retTotalTimeSec = 0;
                foreach ($routeret1 as $street){
                    $route1retTotalTimeSec += $street['crossTime'];
                }
                $route2retTotalTimeSec = 0;
                foreach ($routeret2 as $street){
                    $route2retTotalTimeSec += $street['crossTime'];
                }
                $route3retTotalTimeSec = 0;
                foreach ($routeret3 as $street){
                    $route3retTotalTimeSec += $street['crossTime'];
                }
                $route1retTotalTimeMin = round($route1retTotalTimeSec/60);
                $route2retTotalTimeMin = round($route2retTotalTimeSec/60);
                $route3retTotalTimeMin = round($route3retTotalTimeSec/60);
				foreach ($wazeintime->getCmd('info') as $cmd) {
					switch ($cmd->getName()) {
						case 'Durée 1':
							$value=$route1TotalTimeMin;
						break;
						case 'Durée 2':
							$value=$route2TotalTimeMin; break;
						case 'Durée 3':
							$value=$route3TotalTimeMin; break;
						case 'Trajet 1':
							$value=$route1Name; break;
						case 'Trajet 2':
							$value=$route2Name; break;
						case 'Trajet 3':
							$value=$route3Name; break;
                        case 'Durée retour 1':
							$value=$route1retTotalTimeMin;
						break;
						case 'Durée retour 2':
							$value=$route2retTotalTimeMin; break;
						case 'Durée retour 3':
							$value=$route3retTotalTimeMin; break;
						case 'Trajet retour 1':
							$value=$route1retName; break;
						case 'Trajet retour 2':
							$value=$route2retName; break;
						case 'Trajet retour 3':
							$value=$route3retName; break;
					}
					if ($value==0 ||$value != 'old'){
						$cmd->event($value);
						log::add('wazeintime','debug','set:'.$cmd->getName().' to '. $value);
					}
				}
                $wazeintime->refreshWidget();
			}
		}
	}
    
    public function preUpdate() {
       if ($this->getConfiguration('latdepart') == '' || !is_numeric($this->getConfiguration('latdepart'))) {
            throw new Exception(__('La latitude de départ ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if ($this->getConfiguration('latarrive') == '' || !is_numeric($this->getConfiguration('latarrive'))) {
            throw new Exception(__('La latitude d\'arrivée ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if ($this->getConfiguration('londepart') == '' || !is_numeric($this->getConfiguration('londepart'))) {
            throw new Exception(__('La longitude de départ ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if ($this->getConfiguration('lonarrive') == '' || !is_numeric($this->getConfiguration('lonarrive'))) {
            throw new Exception(__('La longitude d\'arrivée ne peut être vide et doit être un nombre valide',__FILE__));
	   }
    }
    
    public function postSave() {
		if (!$this->getId())
          return;
        
        $routename1 = $this->getCmd(null, 'routename1');
		if (!is_object($routename1)) {
			$routename1 = new wazeintimeCmd();
			$routename1->setLogicalId('routename1');
			$routename1->setIsVisible(1);
			$routename1->setName(__('Trajet 1', __FILE__));
		}
        $routename1->setType('info');
		$routename1->setSubType('string');
		$routename1->setConfiguration('onlyChangeEvent',1);
		$routename1->setEventOnly(1);
		$routename1->setEqLogic_id($this->getId());
		$routename1->save();
        
        $time1 = $this->getCmd(null, 'time1');
		if (!is_object($time1)) {
			$time1 = new wazeintimeCmd();
			$time1->setLogicalId('time1');
            $time1->setUnite('min');
			$time1->setIsVisible(1);
			$time1->setName(__('Durée 1', __FILE__));
		}
        $time1->setType('info');
		$time1->setSubType('numeric');
		$time1->setConfiguration('onlyChangeEvent',1);
		$time1->setEventOnly(1);
		$time1->setEqLogic_id($this->getId());
		$time1->save();
        
        $routename2 = $this->getCmd(null, 'routename2');
		if (!is_object($routename2)) {
			$routename2 = new wazeintimeCmd();
			$routename2->setLogicalId('routename2');
			$routename2->setIsVisible(1);
			$routename2->setName(__('Trajet 2', __FILE__));
		}
        $routename2->setType('info');
		$routename2->setSubType('string');
		$routename2->setConfiguration('onlyChangeEvent',1);
		$routename2->setEventOnly(1);
		$routename2->setEqLogic_id($this->getId());
		$routename2->save();
        
        $time2 = $this->getCmd(null, 'time2');
		if (!is_object($time2)) {
			$time2 = new wazeintimeCmd();
			$time2->setLogicalId('time2');
			$time2->setIsVisible(1);
			$time2->setName(__('Durée 2', __FILE__));
		}
        $time2->setType('info');
		$time2->setSubType('numeric');
        $time2->setUnite('min');
		$time2->setConfiguration('onlyChangeEvent',1);
		$time2->setEventOnly(1);
		$time2->setEqLogic_id($this->getId());
		$time2->save();
        
        $routename3 = $this->getCmd(null, 'routename3');
		if (!is_object($routename3)) {
			$routename3 = new wazeintimeCmd();
			$routename3->setLogicalId('routename3');
			$routename3->setIsVisible(1);
			$routename3->setName(__('Trajet 3', __FILE__));
		}
        $routename3->setType('info');
		$routename3->setSubType('string');
		$routename3->setConfiguration('onlyChangeEvent',1);
		$routename3->setEventOnly(1);
		$routename3->setEqLogic_id($this->getId());
		$routename3->save();
        
        $time3 = $this->getCmd(null, 'time3');
		if (!is_object($time3)) {
			$time3 = new wazeintimeCmd();
			$time3->setLogicalId('time3');
			$time3->setIsVisible(1);
			$time3->setName(__('Durée 3', __FILE__));
		}
        $time3->setType('info');
		$time3->setSubType('numeric');
        $time3->setUnite('min');
		$time3->setConfiguration('onlyChangeEvent',1);
		$time3->setEventOnly(1);
		$time3->setEqLogic_id($this->getId());
		$time3->save();
        
        $routeretname1 = $this->getCmd(null, 'routeretname1');
		if (!is_object($routeretname1)) {
			$routeretname1 = new wazeintimeCmd();
			$routeretname1->setLogicalId('routeretname1');
			$routeretname1->setIsVisible(1);
			$routeretname1->setName(__('Trajet retour 1', __FILE__));
		}
        $routeretname1->setType('info');
		$routeretname1->setSubType('string');
		$routeretname1->setConfiguration('onlyChangeEvent',1);
		$routeretname1->setEventOnly(1);
		$routeretname1->setEqLogic_id($this->getId());
		$routeretname1->save();
        
        $timeret1 = $this->getCmd(null, 'timeret1');
		if (!is_object($timeret1)) {
			$timeret1 = new wazeintimeCmd();
			$timeret1->setLogicalId('timeret1');
            $timeret1->setUnite('min');
			$timeret1->setIsVisible(1);
			$timeret1->setName(__('Durée retour 1', __FILE__));
		}
        $timeret1->setType('info');
		$timeret1->setSubType('numeric');
		$timeret1->setConfiguration('onlyChangeEvent',1);
		$timeret1->setEventOnly(1);
		$timeret1->setEqLogic_id($this->getId());
		$timeret1->save();
        
        $routeretname2 = $this->getCmd(null, 'routeretname2');
		if (!is_object($routeretname2)) {
			$routeretname2 = new wazeintimeCmd();
			$routeretname2->setLogicalId('routeretname2');
			$routeretname2->setIsVisible(1);
			$routeretname2->setName(__('Trajet retour 2', __FILE__));
		}
        $routeretname2->setType('info');
		$routeretname2->setSubType('string');
		$routeretname2->setConfiguration('onlyChangeEvent',1);
		$routeretname2->setEventOnly(1);
		$routeretname2->setEqLogic_id($this->getId());
		$routeretname2->save();
        
        $timeret2 = $this->getCmd(null, 'timeret2');
		if (!is_object($timeret2)) {
			$timeret2 = new wazeintimeCmd();
			$timeret2->setLogicalId('timeret2');
			$timeret2->setIsVisible(1);
			$timeret2->setName(__('Durée retour 2', __FILE__));
		}
        $timeret2->setType('info');
		$timeret2->setSubType('numeric');
        $timeret2->setUnite('min');
		$timeret2->setConfiguration('onlyChangeEvent',1);
		$timeret2->setEventOnly(1);
		$timeret2->setEqLogic_id($this->getId());
		$timeret2->save();
        
        $routeretname3 = $this->getCmd(null, 'routeretname3');
		if (!is_object($routeretname3)) {
			$routeretname3 = new wazeintimeCmd();
			$routeretname3->setLogicalId('routeretname3');
			$routeretname3->setIsVisible(1);
			$routeretname3->setName(__('Trajet retour 3', __FILE__));
		}
        $routeretname3->setType('info');
		$routeretname3->setSubType('string');
		$routeretname3->setConfiguration('onlyChangeEvent',1);
		$routeretname3->setEventOnly(1);
		$routeretname3->setEqLogic_id($this->getId());
		$routeretname3->save();
        
        $timeret3 = $this->getCmd(null, 'timeret3');
		if (!is_object($timeret3)) {
			$timeret3 = new wazeintimeCmd();
			$timeret3->setLogicalId(timeret3);
			$timeret3->setIsVisible(1);
			$timeret3->setName(__('Durée retour 3', __FILE__));
		}
        $timeret3->setType('info');
		$timeret3->setSubType('numeric');
        $timeret3->setUnite('min');
		$timeret3->setConfiguration('onlyChangeEvent',1);
		$timeret3->setEventOnly(1);
		$timeret3->setEqLogic_id($this->getId());
		$timeret3->save();
        
        $refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new wazeintimeCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setEqLogic_id($this->getId());
		$refresh->save();
    }
    
    public function postUpdate() {
		$this->cron30($this->getId());
	}
    
    public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
			return '';
		}
		if (!$this->hasRight('r')) {
			return '';
		}
		$_version = jeedom::versionAlias($_version);
		$background=$this->getBackgroundColor($_version);
        $hide1=$this->getConfiguration('hide1');
        $hide2=$this->getConfiguration('hide2');
        $hide3=$this->getConfiguration('hide3');
		$replace = array(
			'#name#' => $this->getName(),
			'#id#' => $this->getId(),
			'#background_color#' => $background,
			'#eqLink#' => $this->getLinkToConfiguration(),
            '#height#' => $this->getDisplay('height', 'auto'),
            '#width#' => $this->getDisplay('width', '330px'),
            '#hide1#' => $hide1,
            '#hide2#' => $hide2,
            '#hide3#' => $hide3,
		);
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_history#'] = '';
                $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
				$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
				$replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
				$replace['#last_date#'] = substr($cmd->getCollectDate(),11,5);
				if ($cmd->getIsHistorized() == 1) {
					$replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
				}
			
		}

		$refresh = $this->getCmd(null, 'refresh');
		$replace['#refresh_id#'] = $refresh->getId();

		$parameters = $this->getDisplay('parameters');
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$replace['#' . $key . '#'] = $value;
			}
		}

		$html = template_replace($replace, getTemplate('core', $_version, 'eqlogic', 'wazeintime'));
		return $html;
	}
}

class wazeintimeCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */
	
    public function execute($_options = null) {
		if ($this->getType() == '') {
			return '';
		}
		$eqLogic = $this->getEqlogic();
		$eqLogic->cron30($eqLogic->getId());
	}

    /*     * **********************Getteur Setteur*************************** */
}
?>