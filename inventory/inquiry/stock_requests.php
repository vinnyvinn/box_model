<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_INVENTORYREQUEST';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

include_once($path_to_root . "/includes/ui.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Inventory Request");

page($_SESSION['page_title'], isset($_GET['stock_id']), false, "", $js);
//------------------------------------------------------------------------------------------------



if(get_post('ShowMoves'))
{
	$Ajax->activate('doc_tbl');
}

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}

start_form();

hidden('fixed_asset');

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();

start_table(TABLESTYLE_NOBORDER);
start_row();
if (!$page_nested)
{
 stock_costable_items_list_cells(_("Item:"), 'stock_id', $_POST['stock_id']);
}
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

locations_list_cells(_("From Location:"), 'StockLocation', null, true, false, (get_post('fixed_asset') == 1));

date_cells(_("From:"), 'AfterDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'BeforeDate');

submit_cells('ShowMoves',_("Show Requests"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();
end_form();

set_global_stock_item($_POST['stock_id']);

$before_date = date2sql($_POST['BeforeDate']);
$after_date = date2sql($_POST['AfterDate']);
$display_location = !$_POST['StockLocation'];

$result = get_stock_requests_($_POST['stock_id'], $_POST['StockLocation'],
	$_POST['BeforeDate'], $_POST['AfterDate']);

div_start('doc_tbl');
start_table(TABLESTYLE);
$th = array(_("Type"), _("#"), _("Reference"));

if ($display_location)
	array_push($th, _("Location"));

array_push($th, _("Date"), _("Detail"), _("Quantity In"), _("Quantity Out"), _("Quantity On Hand"));

table_header($th);

$j = 1;
$k = 0; //row colour counter

$total_in = 0;
$total_out = 0;


while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);

	$trandate = sql2date($myrow["tran_date"]);

	if (get_post('fixed_asset') == 1 && isset($fa_systypes_array[$myrow["type"]]))
		$type_name = $fa_systypes_array[$myrow["type"]];
	else
		$type_name = $systypes_array[$myrow["type"]];

	if ($myrow["qty"] > 0)
	{
		$quantity_formatted = number_format2($myrow["qty"], $dec);
		$total_in += $myrow["qty"];
	}
	else
	{
		$quantity_formatted = number_format2(-$myrow["qty"], $dec);
		$total_out += -$myrow["qty"];
	}
	$after_qty += $myrow["qty"];

	label_cell($type_name);

	label_cell(get_trans_view_str($myrow["type"], $myrow["trans_no"]), "nowrap align='right'");

	label_cell(get_trans_view_str($myrow["type"], $myrow["trans_no"], $myrow["reference"]));

	if($display_location) {
		label_cell($myrow['loc_code']);
	}
	label_cell($trandate);

	$gl_posting = "";

	label_cell($myrow['name']);
	label_cell((($myrow["qty"] >= 0) ? $quantity_formatted : ""), "nowrap align=right");
	label_cell((($myrow["qty"] < 0) ? $quantity_formatted : ""), "nowrap align=right");
	qty_cell( get_qoh_on_date($myrow['stock_id'],$myrow['loc_code']), false, $dec);
	end_row();

	$j++;
	if ($j == 12)
	{
		$j = 1;
		table_header($th);
	}
}

end_table(1);
div_end();
end_page();

