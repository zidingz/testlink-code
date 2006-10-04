<?php
/**
* 	TestLink Open Source Project - http://testlink.sourceforge.net/ 
*
*  @version 	$Id: printData.php,v 1.22 2006/10/04 17:07:03 schlundus Exp $
*  @author 	Martin Havlat
* 
* Shows the data that will be printed.
*/
require('../../config.inc.php');
require_once("common.php");
require_once("print.inc.php");
testlinkInitPage($db);

$type = isset($_GET['edit']) ?  $_GET['edit'] : null;
$level = isset($_GET['level']) ?  $_GET['level'] : null;
$format = isset($_GET['format']) ? $_GET['format'] : null;
$dataID = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tproject_id = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
$tproject_name = isset($_SESSION['testprojectName']) ? $_SESSION['testprojectName'] : 'xxx';
$tplan_id = isset($_SESSION['testPlanId']) ? $_SESSION['testPlanId'] : 0;

$printingOptions = array 
						( 'toc' => 0,
						  'body' => 0,
						  'summary' => 0,
						  'header' => 0,
						 );
foreach($printingOptions as $opt => $val)
{
	$printingOptions[$opt] = (isset($_GET[$opt]) && ($_GET[$opt] == 'y'));
}						 

$tproject_mgr = new testproject($db);
$tree_manager = &$tproject_mgr->tree_manager;
$test_spec = $tree_manager->get_subtree($dataID,array('testplan'=>'exclude me'),
                                           array('testcase'=>'exclude my children'),null,null,true);
$tree = null;
$code = null;										   
if ($type == 'testproject')
{
	$tree = &$test_spec;
	$tree['name'] = $tproject_name;
	$tree['id'] = $tproject_id;
	$tree['node_type_id'] = 1;
	$printingOptions['title'] = '';
}
else if ($type == 'testsuite')
{
	$tsuite = new testsuite($db);
	$tInfo = $tsuite->get_by_id($dataID);
	$tInfo['childNodes'] = isset($test_spec['childNodes']) ? $test_spec['childNodes'] : null;
	
	//build the testproject node
	$tree['name'] = $tproject_name;
	$tree['id'] = $tproject_id;
	$tree['node_type_id'] = 1;
	$tree['childNodes'] = array($tInfo);
	$printingOptions['title'] = $tInfo['name'];
}
if ($level == 'testproject')
{
	$tplan_mgr = new testplan($db);
	$tp_tcs = $tplan_mgr->get_linked_tcversions($tplan_id);
	
	$hash_descr_id = $tree_manager->get_available_node_types();
	$hash_id_descr = array_flip($hash_descr_id);
	
	$tree = &$test_spec;
	$tree['name'] = $tproject_name;
	$tree['id'] = $tproject_id;
	$tree['node_type_id'] = 1;
	$testcase_count = prepareNode($tree,$hash_id_descr,null,$tp_tcs,0);
	$printingOptions['title'] = '';
	
}
else if ($level == 'testsuite')
{
	$tsuite = new testsuite($db);
	$tInfo = $tsuite->get_by_id($dataID);
	$tplan_mgr = new testplan($db);
	$tp_tcs = $tplan_mgr->get_linked_tcversions($tplan_id);
	
	$hash_descr_id = $tree_manager->get_available_node_types();
	$hash_id_descr = array_flip($hash_descr_id);
	
	$tInfo['node_type_id'] = $hash_descr_id['testsuite'];
	$tInfo['childNodes'] = isset($test_spec['childNodes']) ? $test_spec['childNodes'] : null;
	$testcase_count = prepareNode($tInfo,$hash_id_descr,null,$tp_tcs,0);
	$printingOptions['title'] = $tInfo['name'];
	
	$tree['name'] = $tproject_name;
	$tree['id'] = $tproject_id;
	$tree['node_type_id'] = 1;
	$tree['childNodes'] = array($tInfo);
}

if($tree)
	$code = renderTestSpecTreeForPrinting($db,$printingOptions,$tree,null,0,1);

// add MS Word header 
if ($format == 'msword')
{
	header("Content-Disposition: inline; filename=testplan.doc");
	header("Content-Description: PHP Generated Data");
	header("Content-type: application/vnd.ms-word; name='My_Word'");
	flush();
}
echo $code;
?>