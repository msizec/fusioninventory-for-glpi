<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2013 by the FusionInventory Development Team.

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
   along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    David Durieux
   @co-author
   @copyright Copyright (c) 2010-2013 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2013

   ------------------------------------------------------------------------
 */

class SoftwareEntityCreation extends PHPUnit_Framework_TestCase {
   
  
      /*
      Tests: Add computer in entity 1
       
       * (step1: new install) entities_id_software = -2 (current entity)
       
            * add pc1 => Software in entity 1
      
       * (step2: new install) entities_id_software = 0 (root entity)
       
            * add pc1 => Software in entity 0

       * (step3: current install) entities_id_software = -2 (current entity)
       
            * delete pc1   
            * add pc1 with same softwares => Software duplucate (name) in entity 1
      
      */
      
   
   public function testAddComputerStep1() {
      global $DB;

      $DB->connect();
      
      $Install = new Install();
      $Install->testInstall(0);
      
      $GLPIlog = new GLPIlogs();
      
      $DB->query("INSERT INTO `glpi_entities` 
         (`id`, `name`, `entities_id`, `completename`, `level`) 
         VALUES (1, 'entity1', 0, 'Entité racine > entity1', 2)");
      
    
      $_SESSION['glpiactive_entity'] = 0;
      $_SESSION['glpiactiveentities_string'] = 0;
      $_SESSION['glpishowallentities'] = 1;
      $_SESSION['glpiname'] = 'glpi';
      $pfiComputerInv  = new PluginFusioninventoryInventoryComputerInventory();
      $computer = new Computer();
      $software = new Software();
      
      $a_inventory = array();
      $a_inventory['CONTENT']['HARDWARE'] = array(
          'NAME' => 'pc1'
      );
      $a_inventory['CONTENT']['SOFTWARES'][] = array(
          'COMMENTS' => "Non-interactive tool to get files from FTP, GOPHER, HTTP(S)",
          'NAME'     => "curl",
          'VERSION'  => "7.24.0_1"
          );
      
      // * Add rule ignore
         $rule = new Rule();
         $ruleCriteria = new RuleCriteria();
         $ruleAction = new RuleAction();

         $input = array();
         $input['sub_type']   = 'PluginFusioninventoryInventoryRuleEntity';
         $input['name']       = 'pc1';
         $input['match']      = 'AND';
         $input['is_active']  = 1;
         $rules_id = $rule->add($input); 

         $input = array();
         $input['rules_id']   = $rules_id;
         $input['criteria']   = 'name';
         $input['condition']  = 0;
         $input['pattern']    = 'pc1';
         $ruleCriteria->add($input);

         $input = array();
         $input['rules_id']      = $rules_id;
         $input['action_type']   = 'assign';
         $input['field']         = 'entities_id';
         $input['value']         = 1;
         $ruleAction->add($input);  

      // ** Add agent
         $pfAgent = new PluginFusioninventoryAgent();
         $a_agents_id = $pfAgent->add(array('name'      => 'pc-2013-02-13',
                                            'device_id' => 'pc-2013-02-13'));
         $_SESSION['plugin_fusioninventory_agents_id'] = $a_agents_id;

      // ** Add
         $pfiComputerInv->import("pc-2013-02-13", "", $a_inventory); // creation

         $GLPIlog->testSQLlogs();
         $GLPIlog->testPHPlogs();

         $computer->getFromDB(1);
         $this->assertEquals(1, $computer->fields['entities_id'], 'Add computer');
         
         $software->getFromDB(1);
         $this->assertEquals(1, $software->fields['entities_id'], 'Add computer');
         
   }   

   
   
   public function testAddComputerStep2() {
      global $DB;

      $DB->connect();
      
      $Install = new Install();
      $Install->testInstall(0);
      
      $GLPIlog = new GLPIlogs();
      
      $DB->query("INSERT INTO `glpi_entities` 
         (`id`, `name`, `entities_id`, `completename`, `level`, `entities_id_software`) 
         VALUES (1, 'entity1', 0, 'Entité racine > entity1', 2, 0)");
      
    
      $_SESSION['glpiactive_entity'] = 0;
      $_SESSION['glpiactiveentities_string'] = 0;
      $_SESSION['glpishowallentities'] = 1;
      $_SESSION['glpiname'] = 'glpi';
      $pfiComputerInv  = new PluginFusioninventoryInventoryComputerInventory();
      $computer = new Computer();
      
      $a_inventory = array();
      $a_inventory['CONTENT']['HARDWARE'] = array(
          'NAME' => 'pc1'
      );
      $a_inventory['CONTENT']['SOFTWARES'][] = array(
          'COMMENTS' => "Non-interactive tool to get files from FTP, GOPHER, HTTP(S)",
          'NAME'     => "curl",
          'VERSION'  => "7.24.0_1"
          );
      
      // * Add rule ignore
         $rule = new Rule();
         $ruleCriteria = new RuleCriteria();
         $ruleAction = new RuleAction();

         $input = array();
         $input['sub_type']   = 'PluginFusioninventoryInventoryRuleEntity';
         $input['name']       = 'pc1';
         $input['match']      = 'AND';
         $input['is_active']  = 1;
         $rules_id = $rule->add($input); 

         $input = array();
         $input['rules_id']   = $rules_id;
         $input['criteria']   = 'name';
         $input['condition']  = 0;
         $input['pattern']    = 'pc1';
         $ruleCriteria->add($input);

         $input = array();
         $input['rules_id']      = $rules_id;
         $input['action_type']   = 'assign';
         $input['field']         = 'entities_id';
         $input['value']         = 1;
         $ruleAction->add($input);  

      // ** Add agent
         $pfAgent = new PluginFusioninventoryAgent();
         $a_agents_id = $pfAgent->add(array('name'      => 'pc-2013-02-13',
                                            'device_id' => 'pc-2013-02-13'));
         $_SESSION['plugin_fusioninventory_agents_id'] = $a_agents_id;

      // ** Add
         $pfiComputerInv->import("pc-2013-02-13", "", $a_inventory); // creation

         $GLPIlog->testSQLlogs();
         $GLPIlog->testPHPlogs();

         $computer->getFromDB(1);
         $this->assertEquals(1, $computer->fields['entities_id'], 'Add computer');
         
   }   

   
   
   public function testAddComputerStep3() {
      global $DB;

      $DB->connect();
      
      $GLPIlog = new GLPIlogs();
      
      $DB->query("UPDATE `glpi_entities` 
         SET `entities_id_software` = '-2' 
         WHERE `id`='1'");
      
      $_SESSION['glpiactive_entity'] = 0;
      $_SESSION['glpiactiveentities_string'] = 0;
      $_SESSION['glpishowallentities'] = 1;
      $_SESSION['glpiname'] = 'glpi';
      $pfiComputerInv  = new PluginFusioninventoryInventoryComputerInventory();
      $computer = new Computer();
      $software = new Software();

      $computer->add(array('name' => 'pc2', 'entities_id' => 1));
      
      $a_inventory = array();
      $a_inventory['CONTENT']['HARDWARE'] = array(
          'NAME' => 'pc2'
      );
      $a_inventory['CONTENT']['SOFTWARES'][] = array(
          'COMMENTS' => "Non-interactive tool to get files from FTP, GOPHER, HTTP(S)",
          'NAME'     => "curl",
          'VERSION'  => "7.24.0_1"
          );

      // ** Add agent
         $pfAgent = new PluginFusioninventoryAgent();
         $a_agents_id = $pfAgent->add(array('name'      => 'pc-2013-02-13',
                                            'device_id' => 'pc-2013-02-13'));
         $_SESSION['plugin_fusioninventory_agents_id'] = $a_agents_id;

      // ** Add
         $pfiComputerInv->import("pc2-2013-02-13", "", $a_inventory); // creation

         $GLPIlog->testSQLlogs();
         $GLPIlog->testPHPlogs();

         $computer->getFromDB(2);
         $this->assertEquals(1, $computer->fields['entities_id'], 'Add computer');
         
         $nbSoftwares = countElementsInTable("glpi_softwares");
         $this->assertEquals(2, $nbSoftwares, 'Nb softwares');
         
         $software->getFromDB(2);
         $this->assertEquals(1, $software->fields['entities_id'], 'Entity software of pc2');
            
        
   }   
 }



class SoftwareEntityCreation_AllTests  {

   public static function suite() {

 
     
      $suite = new PHPUnit_Framework_TestSuite('SoftwareEntityCreation');
      return $suite;
   }
}

?>