<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2012 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    David Durieux
   @co-author
   @copyright Copyright (c) 2010-2012 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010

   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginFusioninventoryInventoryComputerImport_Networkport extends CommonDBTM {


   /**
   * Add or update network port
   *
   * @param $type value "add" or "update"
   * @param $items_id integer
   *     - if add    : id of the computer
   *     - if update : id of the network port
   * @param $dataSection array all values of the section
   * @param $itemtype value name of the type of item
   *
   * @return id of the network port or false
   *
   **/
   function AddUpdateItem($type, $items_id, $dataSection, $itemtype='Computer') {

      $computer = new Computer();
      $computer->getFromDB($items_id);
      
      if ((!isset($dataSection['DESCRIPTION'])) AND
              (!isset($dataSection['IPADDRESS']))
             AND (!isset($dataSection['MACADDR']))
             AND (!isset($dataSection['TYPE']))) {

         return "";
      }
      $pfConfig = new PluginFusioninventoryConfig();
      if ($pfConfig->getValue($_SESSION["plugin_fusioninventory_moduleid"],
              "component_networkcardvirtual", 'inventory') == '0') {
         if (isset($dataSection['VIRTUALDEV'])
                 AND $dataSection['VIRTUALDEV']=='1') {

            return "";
         }
      }

      $NetworkPort = new NetworkPort();
      $networkName = new NetworkName();

      $a_NetworkPort = array();
      $a_NetworkName = array();

      if ($type == 'update') {
         $networkName->getFromDB($items_id);
         $a_NetworkName = array();
         $a_NetworkName['id'] = $networkName->fields['id'];
         $a_NetworkName['items_id'] = $networkName->fields['items_id'];
//         $a_NetworkName['_ipaddresses'] = explode('\n', $networkName->fields['ip_addresses']);
      } else {
         $a_NetworkPort['items_id']=$items_id;
         
         if (!isset($a_NetworkName['items_id'])) {
            // Find if this networkport yet exist
            $a_networkports_find = current($NetworkPort->find("`items_id`='".$items_id."'
                                                       AND `itemtype`='Computer'
                                                       AND `name`='".$dataSection["DESCRIPTION"]."'", "", 1));
            if (isset($a_networkports_find['id'])) {
               $a_networknames_find = current($networkName->find("`items_id`='".$a_networkports_find['id']."'
                                                                AND `itemtype`='NetworkPort'
                                                                AND `name`='".$computer->fields['name']."'", "", 1));
               if (isset($a_networknames_find['id'])) {
                  $a_NetworkName = array();
                  $a_NetworkName['id'] = $a_networknames_find['id'];
                  $a_NetworkName['items_id'] = $a_networknames_find['items_id'];
//                  $a_NetworkName['_ipaddresses'] = explode('\n', $a_networknames_find['ip_addresses']);
               }
            } else {
               // Create networkport

               $a_NetworkPort['itemtype'] = $itemtype;
               if (isset($dataSection["DESCRIPTION"])) {
                  $a_NetworkPort['name'] = $dataSection["DESCRIPTION"];
               }
   //            if (isset($dataSection["IPADDRESS"])) {
   //               $a_NetworkPort['ip'] = $dataSection["IPADDRESS"];
   //            }
               if (isset($dataSection["MACADDR"])) {
                  $a_NetworkPort['mac'] = $dataSection["MACADDR"];
               }
   //            if (isset($dataSection["TYPE"])) {
   //               $a_NetworkPort["networkinterfaces_id"]
   //                           = Dropdown::importExternal('NetworkInterface',
   //                                                      $dataSection["TYPE"],
   //                                                      $_SESSION["plugin_fusinvinventory_entity"]);
   //            }
   //            if (isset($dataSection["IPMASK"]))
   //               $a_NetworkPort['netmask'] = $dataSection["IPMASK"];
   //            if (isset($dataSection["IPGATEWAY"]))
   //               $a_NetworkPort['gateway'] = $dataSection["IPGATEWAY"];
   //            if (isset($dataSection["IPSUBNET"]))
   //               $a_NetworkPort['subnet'] = $dataSection["IPSUBNET"];

               $a_NetworkPort['entities_id'] = $_SESSION["plugin_fusinvinventory_entity"];

               if (isset($dataSection["TYPE"])
                       AND $dataSection["TYPE"] == 'Ethernet') {
                  $a_NetworkPort['instantiation_type'] = 'NetworkPortEthernet';
               } else {
                  $a_NetworkPort['instantiation_type'] = 'NetworkPortLocal';
               }
               if ($_SESSION["plugin_fusinvinventory_no_history_add"]) {
                  $a_NetworkPort['_no_history'] = $_SESSION["plugin_fusinvinventory_no_history_add"];
               }
               $a_NetworkName['items_id'] = $NetworkPort->add($a_NetworkPort, array(), $_SESSION["plugin_fusinvinventory_history_add"]);
            }
         }         
      }


      
      $a_NetworkName['name'] = $computer->fields['name'];
      $a_NetworkName['entities_id'] = $computer->fields['entities_id'];
      $a_NetworkName['is_recursive'] = 0;
      $a_NetworkName['itemtype'] = 'NetworkPort';

      if (isset($dataSection['IPADDRESS'])) {
         $a_NetworkName['_ipaddresses'][-100] = $dataSection['IPADDRESS'];
      } else if (isset($dataSection['IPADDRESS6'])) {
         $a_NetworkName['_ipaddresses'][-100] = $dataSection['IPADDRESS6'];
      }

      $devID = 0;
      if (isset($a_NetworkName['id'])) { // Update
         $networkName->update($a_NetworkName, $_SESSION["plugin_fusinvinventory_history_add"]);
      } else {
         if ($_SESSION["plugin_fusinvinventory_no_history_add"]) {
            $a_NetworkName['_no_history'] = $_SESSION["plugin_fusinvinventory_no_history_add"];
         }
         $devID = $networkName->add($a_NetworkName, array(), $_SESSION["plugin_fusinvinventory_history_add"]);
      }
      return $devID;
   }



   /**
   * Delete network port
   *
   * @param $items_id integer id of the network port
   * @param $idmachine integer id of the computer
   *
   * @return nothing
   *
   **/
   function deleteItem($items_id, $idmachine) {
      $NetworkPort = new NetworkPort();
      $NetworkPort->getFromDB($items_id);
      if (($NetworkPort->fields['items_id'] == $idmachine) AND ($NetworkPort->fields['itemtype'] == 'Computer')) {
         $input = array();
         $input['id'] = $items_id;
         if ($_SESSION["plugin_fusinvinventory_no_history_add"]) {
            $input['_no_history'] = $_SESSION["plugin_fusinvinventory_no_history_add"];
         }
         $NetworkPort->delete($input, 0, $_SESSION["plugin_fusinvinventory_history_add"]);
      }
   }
}

?>