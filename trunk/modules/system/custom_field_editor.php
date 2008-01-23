<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

/*
*	Custom Field Editor (NEW)
*
*/

$AppUI->savePlace();

require_once ($AppUI->getSystemClass('CustomFields'));
$yesno = w2PgetSysVal('GlobalYesNo');
$html_types = array('textinput' => $AppUI->_('Text Input'), 'textarea' => $AppUI->_('Text Area'), 'checkbox' => $AppUI->_('Checkbox'), 'select' => $AppUI->_('Select List'), 'label' => $AppUI->_('Label'), 'separator' => $AppUI->_('Separator'), 'href' => $AppUI->_('Weblink'), );

$titleBlock = new CTitleBlock('Custom field editor', 'customfields.png', 'admin', 'admin.custom_field_editor');
$titleBlock->addCrumb('?m=system', 'system admin');

$edit_field_id = w2PGetParam($_POST, 'field_id', null);

$titleBlock->show();

$q = new DBQuery;
$q->addTable('modules');
$q->addOrder('mod_ui_order');
$q->addWhere('mod_name IN ("Companies", "Projects", "Tasks", "Calendar", "Contacts")');
$modules = $q->loadList();
$q->clear();

echo '<table width="100%" class="std" cellpadding="2">';

foreach ($modules as $module) {
	echo '<tr valign="bottom"><td colspan="4">';
	echo '<h3><span title="' . $AppUI->_('Add Custom Field') . '::' . $AppUI->_('Click this icon to Add a new Custom Field to this Module.') . "\"><a href=\"?m=system&a=custom_field_addedit&module=" . $module['mod_name'] . "\"><img src='" . w2PfindImage('icons/edit_add.png') . "' align='center' width='16' height='16' border='0'></a></span>";
	echo $AppUI->_($module['mod_name']) . '</h3>';
	echo '</td></tr>';

	$q = new DBQuery;
	$q->addTable('custom_fields_struct');
	$q->addWhere('field_module = "' . strtolower($module["mod_name"]) . '"');
	$q->addOrder('field_order ASC');
	$custom_fields = $q->loadList();
	$q->clear();

	if (count($custom_fields)) {
		echo '<th width="10"></th>';
		echo '<th width="10"></th>';
		echo '<th>' . $AppUI->_('Name') . '</th>';
		echo '<th>' . $AppUI->_('Description') . '</th>';
		echo '<th>' . $AppUI->_('Type') . '</th>';
		echo '<th>' . $AppUI->_('Pub.') . '</th>';
		echo '<th>' . $AppUI->_('Order') . '</th>';
	}

	foreach ($custom_fields as $f) {
		echo '<tr><td class="hilite" width="10">';
		echo '<span title="' . $AppUI->_('Edit Custom Field') . '::' . $AppUI->_('Click this icon to Edit this Custom Field.') . "\"><a href=\"?m=system&a=custom_field_addedit&module=" . $module['mod_name'] . "&field_id=" . $f['field_id'] . "\"><img src='" . w2PfindImage('icons/stock_edit-16.png') . "' align='center' width='16' height='16' border='0'></a></span>";
		echo '</td><td class="hilite" width="10">';
		echo '<span title="' . $AppUI->_('Delete Custom Field') . '::' . $AppUI->_('Click this icon to Delete this Custom Field.') . "\"><a href=\"?m=system&a=custom_field_addedit&field_id=" . $f['field_id'] . "&delete=1\"><img src='" . w2PfindImage('icons/stock_delete-16.png') . "' align='center' width='16' height='16' border='0'></a> ";
		echo '<td class="hilite">';
		echo stripslashes($f['field_name']);
		echo '</td><td class="hilite">';
		echo stripslashes($f['field_description']);
		echo '</td><td class="hilite">';
		echo $html_types[$f['field_htmltype']];
		echo "</td><td class=\"hilite\">";
		echo $yesno[$f['field_published']];
		echo '</td><td class="hilite" style="text-align:right;">';
		echo stripslashes($f['field_order']);
		echo '</td></tr>';
	}
}
echo '</table>';
?>