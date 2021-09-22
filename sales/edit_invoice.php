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
//-----------------------------------------------------------------------------
//
//	Entry/Modify Sales Quotations
//	Entry/Modify Sales Order
//	Entry Direct Delivery
//	Entry Direct Invoice
//

$path_to_root = "..";
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

set_page_security( @$_SESSION['Items']->trans_type,
	array(	ST_SALESORDER=>'SA_SALESORDER',
			ST_SALESQUOTE => 'SA_SALESQUOTE',
			ST_CUSTDELIVERY => 'SA_SALESDELIVERY',
			ST_SALESINVOICE => 'SA_SALESINVOICE'),
	array(	'NewOrder' => 'SA_SALESORDER',
			'ModifyOrderNumber' => 'SA_SALESORDER',
			'AddedID' => 'SA_SALESORDER',
			'UpdatedID' => 'SA_SALESORDER',
			'NewQuotation' => 'SA_SALESQUOTE',
			'ModifyQuotationNumber' => 'SA_SALESQUOTE',
			'NewQuoteToSalesOrder' => 'SA_SALESQUOTE',
			'AddedQU' => 'SA_SALESQUOTE',
			'UpdatedQU' => 'SA_SALESQUOTE',
			'NewDelivery' => 'SA_SALESDELIVERY',
			'AddedDN' => 'SA_SALESDELIVERY',
			'NewInvoice' => 'SA_SALESINVOICE',
			'AddedDI' => 'SA_SALESINVOICE'
			)
);

$js = '';

if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}
$_SESSION['page_title'] = _($help_context = "Modify Invoice");

page($_SESSION['page_title'], false, false, "", $js);


//--------------------------------------------------------------------------------
function can_process() {

	global $Refs, $SysPrefs;

    if (!get_post('c17_entry_number')){
        display_error(_("C17 Entry Number cannot be empty."));
        set_focus('c17_entry_number');
        return false;
    }

    if (!get_post('ecs_number')){
        display_error(_("ECS Number cannot be empty."));
        set_focus('ecs_number');
        return false;
    }

	return true;
}

//-----------------------------------------------------------------------------


if (isset($_POST['ProcessOrder']) && can_process()) {

   update_debtor($_POST['order'],$_POST['c17_entry_number'],$_POST ['ecs_number']);
    meta_forward($path_to_root."/sales/inquiry/view_invoices.php?type=30");
}

//--------------------------------------------------------------------------------
start_form();

echo '<br><br>';
$row = get_debtor($_GET['OrderNumber']);
start_table(TABLESTYLE2);

text_row(_("C17 Entry Number:"), 'c17_entry_number', $row['c17_entry_number'], 21, 20, null, "");
text_row_required(_("ECS Number:"), 'ecs_number', $row['ecs_number'], 21, 20, null, "");
hidden('order',$_GET['OrderNumber']);
end_table(1);
submit_center_first('ProcessOrder', 'Update',
    _('Check entered data and save document'), 'default');


end_form();
end_page();
