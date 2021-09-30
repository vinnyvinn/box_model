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
$page_security = 'SA_INVENTORYISSUE';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
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

	$_SESSION['page_title'] = _($help_context = "Inventory Item Requests");


page($_SESSION['page_title'], isset($_GET['stock_id']), false, "", $js);
//------------------------------------------------------------------------------------------------
	check_db_has_stock_items(_("There are no items defined in the system."));

if(get_post('ShowMoves'))
{
	$Ajax->activate('doc_tbl');
}

if(isset($_GET['Issued']) && isset($_GET['qty'])){
    update_stock_move_req_issue($_GET['Issued'],$_GET['qty']);

    $stock_id_query = get_stock_id_from_stock_moves($_GET['Issued']);
    $stock_id=$stock_id_query['stock_id'];
    $stock_gl_codes = get_stock_gl_code($stock_id);

    add_gl_trans_std_cost(ST_INVREQUEST, $req_id, $date_,
        $stock_gl_codes['adjustment_account'], $stock_gl_codes['dimension_id'], $stock_gl_codes['dimension2_id'], $memo_, $req_value);

    add_gl_trans_std_cost(ST_INVREQUEST, $req_id, $date_, $stock_gl_codes['inventory_account'], 0, 0, $memo_, $inv_value);
    

   // meta_forward('/inventory/inquiry/view_inventory_request.php');
}

if (isset($_GET['stock_id']))
{
    $_POST['stock_id'] = $_GET['stock_id'];

    
}

start_form();

hidden('fixed_asset');

//if (!isset($_POST['stock_id']))
//	$_POST['stock_id'] = get_global_stock_item();

start_table(TABLESTYLE_NOBORDER);
start_row();

stock_costable_items_list_cells(_("Item:"), 'stock_id', null, true, true);
check_cells(_("Show All:"), 'show_all');

end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

locations_list_cells(_("From Location:"), 'StockLocation', null, true, false, (get_post('fixed_asset') == 1));

date_cells(_("From:"), 'AfterDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'BeforeDate');

submit_cells('ShowMoves',_("Show Item Requests"),'',_('Refresh Requests'), 'default');
end_row();
end_table();
end_form();

set_global_stock_item($_POST['stock_id']);

$before_date = date2sql($_POST['BeforeDate']);
$after_date = date2sql($_POST['AfterDate']);
$display_location = !$_POST['StockLocation'];

//---------------------------------------------------------------------------------------------
function trans_view($row)
{
  //  $myrow["type"], $myrow["trans_no"])
    return get_trans_view_str(ST_INVREQUEST, $row["trans_no"]);
}

function edit_link($row)
{
    return pager_link( _("Edit"),'/inventory/inventory_request.php?transId='.$row['trans_id'],ICON_EDIT);
}

function prt_link($row)
{
 return print_document_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT);
}

function receive_link($row)
{
  return pager_link( _("Issue"),
        "/inventory/inquiry/view_inventory_request.php?Issued=" . $row["trans_id"].'&qty='.$row["qty"], ICON_ALLOC);
}
//---------------------------------------------------------------------------------------------

//figure out the sql required from the inputs available
$sql = get_stock_requests($_POST['stock_id'], $_POST['StockLocation'],
    $_POST['BeforeDate'], $_POST['AfterDate']);


$result = db_query($sql,"No item requests were returned");


/*show a table of the inventory requests by the sql */
$return = false;
$cols = array(
    _("#") => array('fun'=>'trans_view', 'ord'=>''),
    _("Reference") => array('align'=>'center'),
    _("Location") => array('align'=>'center'),
    _("Date") => 'date',
    _("Quantity") => 'amount',
    array('insert'=>true, 'fun'=>'edit_link'),
    array('insert'=>true, 'fun'=>'receive_link'),
);


$table =& new_db_pager('stock_moves_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);

end_form();

end_page();

