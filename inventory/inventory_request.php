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
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/fixed_assets/includes/fixed_assets_db.inc");
include_once($path_to_root . "/inventory/includes/item_requests_ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
if (isset($_GET['NewRequest'])) {
		$_SESSION['page_title'] = _($help_context = "Inventory Request");
}

if (isset($_GET['transId'])){
    $_SESSION['REQUEST'] = get_stock_move($_GET['transId']);
    $_SESSION['trans_id'] = $_GET['transId'];

}else{
    unset($_SESSION['REQUEST']);
}
page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID']))
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_INVREQUEST;

  $result = get_stock_request_items($trans_no);
  $row = db_fetch($result);
    display_notification_centered(_("Items request has been processed"));
    display_note(get_trans_view_str($trans_type, $trans_no, _("&View this request")));

  // display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this request")), 1, 0);

	  hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another request"), "NewRequest=1");


	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$trans_no");

	display_footer_exit();
}
//--------------------------------------------------------------------------------------------------

function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_stock_id_edit');
}
//-----------------------------------------------------------------------------------------------

function handle_new_order()
{
	if (isset($_SESSION['req_items']))
	{
		$_SESSION['req_items']->clear_items();
		unset ($_SESSION['req_items']);
	}

    $_SESSION['req_items'] = new items_cart(ST_INVREQUEST);

	$_POST['ReqDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['ReqDate']))
		$_POST['ReqDate'] = end_fiscalyear();
	$_SESSION['req_items']->tran_date = $_POST['ReqDate'];
}

//-----------------------------------------------------------------------------------------------

function can_process()
{
	global $SysPrefs;

	$req = &$_SESSION['req_items'];

	if (count($req->line_items) == 0)	{
		display_error(_("You must enter at least one non empty item line."));
		set_focus('stock_id');
		return false;
	}

	if (!check_reference($_POST['ref'], ST_INVREQUEST))
	{
		set_focus('ref');
		return false;
	}

	if (!is_date($_POST['ReqDate']))
	{
		display_error(_("The entered date for the request is invalid."));
		set_focus('ReqDate');
		return false;
	}
	elseif (!is_date_in_fiscalyear($_POST['ReqDate']))
	{
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('ReqDate');
		return false;
	}
//	elseif (!$SysPrefs->allow_negative_stock())
//	{
//		$low_stock = $req->check_qoh($_POST['StockLocation'], $_POST['ReqDate']);
//
//		if ($low_stock)
//		{
//    		display_error(_("The request cannot be processed because it would cause negative inventory balance for marked items as of document date or later."));
//			unset($_POST['Process']);
//			return false;
//		}
//	}
	return true;
}

function can_process_update()
{

    if (!is_numeric(get_post('qty_item_requested')) || get_post('qty_item_requested') <=0)	{
        display_error(_("You have entered Invalid Quantity Input."));
        set_focus('qty_item_requested');
        return false;
    }

    return true;
}
//-------------------------------------------------------------------------------

if (isset($_POST['Process']) && can_process()){

	$trans_no = add_stock_request($_SESSION['req_items']->line_items,
		$_POST['StockLocation'], $_POST['ReqDate'],	$_POST['ref'], $_POST['memo_']);
	new_doc_date($_POST['ReqDate']);
	$_SESSION['req_items']->clear_items();
	unset($_SESSION['req_items']);

   	meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");

}

if (isset($_POST['ProcessUpdate']) && can_process_update()){
	update_stock_move_req($_SESSION['trans_id'],$_POST['qty_item_requested']);

   	meta_forward('/inventory/inquiry/view_inventory_request.php?');

}

/*end of process credit note */

//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	if (input_num('qty') <= 0)
	{
		display_error(_("The quantity entered is invalid."));
		set_focus('qty');
		return false;
	}

	if (!check_num('std_cost', 0))
	{
		display_error(_("The entered standard cost is negative or invalid."));
		set_focus('std_cost');
		return false;
	}

   	return true;
}

//-----------------------------------------------------------------------------------------------

function handle_update_item()
{
	$id = $_POST['LineNo'];
   	$_SESSION['req_items']->update_cart_item($id, input_num('qty'),
		input_num('std_cost'));
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['req_items']->remove_from_cart($id);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_new_item()
{
	add_to_order($_SESSION['req_items'], $_POST['stock_id'],
	input_num('qty'), input_num('std_cost'));
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);

if (isset($_POST['AddItem']) && check_item_data()) {
	handle_new_item();
	unset($_POST['selected_id']);
}
if (isset($_POST['UpdateItem']) && check_item_data()) {
	handle_update_item();
	unset($_POST['selected_id']);
}
if (isset($_POST['CancelItemChanges'])) {
	unset($_POST['selected_id']);
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------

if (isset($_GET['NewRequest']) || !isset($_SESSION['req_items']))
{
  check_db_has_costable_items(_("There are no inventory items defined in the system which can be requested (Purchased or Manufactured)."));

	handle_new_order();
}

//-----------------------------------------------------------------------------------------------
start_form();
if (isset($_SESSION['REQUEST'])):
    $items_title = _("Update Request Item");
    $button_title = _("Update Request");

    display_order_header_($_SESSION['req_items']);

    start_outer_table(TABLESTYLE, "width='70%'", 10);

    display_requests_items_($items_title, $_SESSION['req_items']);
   adjustment_options_controls();

    end_outer_table(1, false);

    submit_center_first('Update', _("Update"), '', null);
    submit_center_last('ProcessUpdate', $button_title, '', 'default');
    else:

	$items_title = _("Request Items");
	$button_title = _("Process Request");

 display_order_header($_SESSION['req_items']);

start_outer_table(TABLESTYLE, "width='70%'", 10);

display_requests_items($items_title, $_SESSION['req_items']);
adjustment_options_controls();

end_outer_table(1, false);

submit_center_first('Update', _("Update"), '', null);
submit_center_last('Process', $button_title, '', 'default');

end_form();
endif;
end_page();

