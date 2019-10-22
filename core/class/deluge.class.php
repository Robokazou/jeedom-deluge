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
require_once __DIR__ . '/../../../../core/php/core.inc.php';

include("delugeLib.class.php");

class deluge extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    public static function update() {
        foreach (self::byType('deluge') as $delugeEqLogic) {
            $autorefresh = $delugeEqLogic->getConfiguration('autorefresh');
            if ($delugeEqLogic->getIsEnable() == 1 && $autorefresh != '') {
                try {
                    log::add('deluge', 'debug', $delugeEqLogic->getHumanName() . ' : Schredule' . $autorefresh);
                    $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                    if ($c->isDue()) {
                        log::add('deluge', 'debug', $delugeEqLogic->getHumanName() . ' : cron is Due (start Refresh)');
                        try {
                            $delugeEqLogic->refresh();
                        } catch (Exception $exc) {
                            log::add('deluge', 'error', __('Erreur pour ', __FILE__) . $delugeEqLogic->getHumanName() . ' : ' . $exc->getMessage());
                        }
                    }
                } catch (Exception $exc) {
                    log::add('deluge', 'error', __('Expression cron non valide pour ', __FILE__) . $delugeEqLogic->getHumanName() . ' : ' . $autorefresh);
                }
            }
        }
    }

    /*     * *********************Méthodes d'instance************************* */

    public function getDelugeObj($eqLogic) {
        $url = $eqLogic->getConfiguration("url");
        $password = $eqLogic->getConfiguration("password");
        $retry = 3;
        if ($url == "") { //si le paramètre est vide ou n’existe pas
            log::add('deluge', 'error', 'Url non renseigner:');
        }

        $deluge = new delugePhpApi($url, $password);//, $retry);
        return $deluge;
    }

    public function refresh($_options) {
        $delugeEqLogic = deluge::byId($_options['deluge_id']);

        ob_start();
        var_dump($delugeEqLogic);
        $result = ob_get_clean();
        //log::add('deluge', 'debug', 'deluge:$delugeEqLogic:' . $result);

                // if(!is_object($delugeEqLogic)){
                        // $cron = cron::byClassAndFunction('deluge', 'refresh', array('deluge_id' => intval($_options['deluge_id'])));
                        // log::add('deluge', 'debug', 'deluge:Delete Cron');
                        // $cron->remove();
        // }

        if (is_object($delugeEqLogic) && $delugeEqLogic->getIsEnable() == 1 && $delugeEqLogic->getConfiguration('autorefresh') != '') {

            log::add('deluge', 'debug', $delugeEqLogic->getHumanName() .':'.'refresh start');

            $deluge = $delugeEqLogic->getDelugeObj($delugeEqLogic);

            $torrents = $deluge->getTorrents(null, null);

            //$status = $deluge->getTorrentStatus($torrents[0]->hash, array(), array());
            // var_dump($status);

            $config = $deluge->getConfig();
            // ob_start();
            // var_dump($config);
            // $result = ob_get_clean();
            // log::add('deluge','debug','config:'.$result );

            $up = 0;
            $down = 0;
            $nbConnection = 0;


            for ($i = 0; $i < count($torrents); $i++) {
                $status = $deluge->getTorrentStatus($torrents[$i]->hash, array(), array());
                //log::add('deluge', 'debug',  $status);

                $up += $status->upload_payload_rate;
                $down += $status->download_payload_rate;
                $nbConnection += $status->num_seeds;
                $nbConnection += $status->num_peers;
            }

            $down = round($down / 1024 ,1);
            $up = round($up / 1024 ,1);
            $nbConnection = round($nbConnection ,1);

            $maxDown = round($config->max_download_speed ,1);
            $maxUp = round($config->max_upload_speed ,1);
            $maxConnection = round($config->max_connections_global ,1);

            $deluge->close(); //Closes the cURL handle

            log::add('deluge', 'debug', $delugeEqLogic->getHumanName() .':'.'down value:' . $down);
            log::add('deluge', 'debug', $delugeEqLogic->getHumanName() .':'.'up value:' . $up);
            log::add('deluge', 'debug', $delugeEqLogic->getHumanName() .':'.'Connection value:' . $nbConnection);
            log::add('deluge', 'debug', $delugeEqLogic->getHumanName() .':'.'max down value:' . $maxDown);
            log::add('deluge', 'debug', $delugeEqLogic->getHumanName() .':'.'max up value:' . $maxUp);
            log::add('deluge', 'debug', $delugeEqLogic->getHumanName() .':'.'max Connection value:' . $maxConnection);

            $delugeEqLogic->checkAndUpdateCmd('down', $down);
            $delugeEqLogic->checkAndUpdateCmd('up', $up);
            $delugeEqLogic->checkAndUpdateCmd('connection', $nbConnection);
            $delugeEqLogic->checkAndUpdateCmd('down_max', $maxDown);
            $delugeEqLogic->checkAndUpdateCmd('up_max', $maxUp);
            $delugeEqLogic->checkAndUpdateCmd('connection_max', $maxConnection);

//        $mc = cache::byKey('weatherWidgetmobile' . $this->getId());
//        $mc->remove();
//        $mc = cache::byKey('weatherWidgetdashboard' . $this->getId());
//        $mc->remove();
//        $this->toHtml('mobile');
//        $this->toHtml('dashboard');
            $delugeEqLogic->refreshWidget();

            log::add('deluge', 'debug', $delugeEqLogic->getHumanName() .':'.'refresh end');
        }
        return array($down, $up, $nbConnection, $maxDown, $maxUp, $maxConnection);
    }

    public function preInsert() {

    }

    public function postInsert() {

    }

    public function preSave() {
        $url = $this->getConfiguration("url");
        if ($url != '' && substr_count($url, ':') <= 1) {
            log::add('deluge', 'debug', $this->getHumanName() .':'.'url not contains :port add it as default value :8112');
            $this->setConfiguration("url", $url . ':8112');
        }
    }

    public function postSave() {

        $upCmd = $this->getCmd(null, 'up');
        if (!is_object($upCmd)) {
            $upCmd = new delugeCmd();
            $upCmd->setName(__('Up', __FILE__));
        }
        $upCmd->setUnite('ko/s');
        $upCmd->setLogicalId('up');
        $upCmd->setEqLogic_id($this->getId());
        $upCmd->setType('info');
        $upCmd->setSubType('numeric');
        $upCmd->save();

        $downCmd = $this->getCmd(null, 'down');
        if (!is_object($downCmd)) {
            $downCmd = new delugeCmd();
            $downCmd->setName(__('Down', __FILE__));
        }
        $downCmd->setUnite('ko/s');
        $downCmd->setLogicalId('down');
        $downCmd->setEqLogic_id($this->getId());
        $downCmd->setType('info');
        $downCmd->setSubType('numeric');
        $downCmd->save();

        $connectionCmd = $this->getCmd(null, 'connection');
        if (!is_object($connectionCmd)) {
            $connectionCmd = new delugeCmd();
            $connectionCmd->setName(__('Connections', __FILE__));
        }
        $connectionCmd->setLogicalId('connection');
        $connectionCmd->setEqLogic_id($this->getId());
        $connectionCmd->setType('info');
        $connectionCmd->setSubType('numeric');
        $connectionCmd->save();


        $upSettingCmd = $this->getCmd(null, 'up_max');
        if (!is_object($upSettingCmd)) {
            $upSettingCmd = new delugeCmd();
            $upSettingCmd->setName(__('Up max', __FILE__));
        }
        $upSettingCmd->setUnite('ko/s');
        $upSettingCmd->setLogicalId('up_max');
        $upSettingCmd->setEqLogic_id($this->getId());
        $upSettingCmd->setType('info');
        $upSettingCmd->setSubType('numeric');
        $upSettingCmd->save();

        $downSettingCmd = $this->getCmd(null, 'down_max');
        if (!is_object($downSettingCmd)) {
            $downSettingCmd = new delugeCmd();
            $downSettingCmd->setName(__('Down max', __FILE__));
        }
        $downSettingCmd->setUnite('ko/s');
        $downSettingCmd->setLogicalId('down_max');
        $downSettingCmd->setEqLogic_id($this->getId());
        $downSettingCmd->setType('info');
        $downSettingCmd->setSubType('numeric');
        $downSettingCmd->save();

        $connectionSettingCmd = $this->getCmd(null, 'connection_max');
        if (!is_object($connectionSettingCmd)) {
            $connectionSettingCmd = new delugeCmd();
            $connectionSettingCmd->setName(__('Connection Max', __FILE__));
        }
        $connectionSettingCmd->setLogicalId('connection_max');
        $connectionSettingCmd->setEqLogic_id($this->getId());
        $connectionSettingCmd->setType('info');
        $connectionSettingCmd->setSubType('numeric');
        $connectionSettingCmd->save();


        $setConnectionSettingCmd = $this->getCmd(null, 'set_up_max');
        if (!is_object($setConnectionSettingCmd)) {
            $setConnectionSettingCmd = new delugeCmd();
            $setConnectionSettingCmd->setName(__('set Up max', __FILE__));
        }
        $setConnectionSettingCmd->setLogicalId('set_up_max');
        $setConnectionSettingCmd->setEqLogic_id($this->getId());
        $setConnectionSettingCmd->setType('action');
        $setConnectionSettingCmd->setSubType('slider');
        $setConnectionSettingCmd->save();

        $setConnectionSettingCmd = $this->getCmd(null, 'set_down_max');
        if (!is_object($setConnectionSettingCmd)) {
            $setConnectionSettingCmd = new delugeCmd();
            $setConnectionSettingCmd->setName(__('set Down max', __FILE__));
        }
        $setConnectionSettingCmd->setLogicalId('set_down_max');
        $setConnectionSettingCmd->setEqLogic_id($this->getId());
        $setConnectionSettingCmd->setType('action');
        $setConnectionSettingCmd->setSubType('slider');
        $setConnectionSettingCmd->save();

        $setConnectionSettingCmd = $this->getCmd(null, 'set_connection_max');
        if (!is_object($setConnectionSettingCmd)) {
            $setConnectionSettingCmd = new delugeCmd();
            $setConnectionSettingCmd->setName(__('set Connections Max', __FILE__));
        }
        $setConnectionSettingCmd->setLogicalId('set_connection_max');
        $setConnectionSettingCmd->setEqLogic_id($this->getId());
        $setConnectionSettingCmd->setType('action');
        $setConnectionSettingCmd->setSubType('slider');
        $setConnectionSettingCmd->save();


        $refresh = $this->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
            $refresh = new delugeCmd();
            $refresh->setName(__('Rafraichir', __FILE__));
        }
        $refresh->setEqLogic_id($this->getId());
        $refresh->setLogicalId('refresh');
        $refresh->setType('action');
        $refresh->setSubType('other');
        $refresh->save();

        $refreshPeriode = $this->getConfiguration("autorefresh");
        //log::add('deluge', 'debug', $this->getHumanName() .':'.'refresh period read "' . $refreshPeriode . '"');
        $cron = cron::byClassAndFunction('deluge', 'refresh', array('deluge_id' => intval($this->getId())));

        //log::add('deluge', 'debug', $this->getHumanName() .':tata'.$this->getIsEnable());
        if ( $this->getIsEnable() && $refreshPeriode != '') {
            if(!is_object($cron)){
                log::add('deluge', 'debug', $this->getHumanName() .':Create Cron');
                $cron = new cron();
                $cron->setClass('deluge');
                $cron->setFunction('refresh');
                $cron->setOption(array('deluge_id' => intval($this->getId())));
            }
        }else{
            if(is_object($cron)){
                log::add('deluge', 'debug', $this->getHumanName() .':Delete Cron');
                $cron->stop();
                $cron->remove();
            }
        }

        //if it is just actualised of refreshPeriode
        if ($refreshPeriode != '' && is_object($cron)) {
            log::add('deluge', 'debug', $this->getHumanName() .':set Schedule "'.$refreshPeriode.'"');
            $cron->setSchedule($refreshPeriode);
        }
        if(is_object($cron)){
            $cron->save();
        }
    }

    public function preUpdate() {
        if ($this->getConfiguration("url") == '') {
            throw new Exception(__('L\'adresse URD ne peut être vide', __FILE__));
        }
    }

    public function postUpdate() {

    }

    public function preRemove() {

    }

    public function postRemove() {
                log::add('deluge', 'debug', $this->getHumanName() .':Delete Cron');
        $cron = cron::byClassAndFunction('deluge', 'refresh', array('deluge_id' => intval($this->getId())));
                $cron->stop();
                $cron->remove();
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
      public static function postConfig_<Variable>() {
      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
      public static function preConfig_<Variable>() {
      }
     */

    public function setConfig($configName, $value) {
        $delugeObj = $this->getDelugeObj($this);

        $newConfig = array($configName => $value,);
        log::add('deluge', 'debug', $this->getHumanName().':'.$configName . ' value for config:' . $newConfig[$configName]);
        $delugeObj->setConfig($newConfig);

        $delugeObj->close();
    }

    /*     * **********************Getteur Setteur*************************** */
}

class delugeCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */




    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
        $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
        //$deluge = self::byType('deluge')
        $isRefresh = FALSE;
        switch ($this->getLogicalId()) {
            case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe vdm .
                $isRefresh = TRUE;
                break;
            case 'set_down_max':
                $eqlogic->setConfig('max_download_speed', $_options['slider']);
                break;
            case 'set_up_max':
                $eqlogic->setConfig('max_upload_speed', $_options['slider']);
                break;
            case 'set_connection_max':
                $eqlogic->setConfig('max_connections_global', $_options['slider']);
                break;
        }

        if ($isRefresh === TRUE) {
            $data = $eqlogic->refresh(array('deluge_id' => intval($eqlogic->getId())));
        }

        //ob_start();
        //var_dump($_options);
        //$result = ob_get_clean();
        //log::add('deluge', 'debug', $eqlogic->getHumanName() .':'.'_options:' . $result);
    }

    /*     * **********************Getteur Setteur*************************** */
}
