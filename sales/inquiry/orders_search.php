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
$path_to_root = "../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$page_security = 'SA_SALESTRANSVIEW';


if (get_post('type'))
    $trans_type = $_POST['type'];
elseif (isset($_GET['type']) && $_GET['type'] == ST_SALESQUOTE)
    $trans_type = ST_SALESQUOTE;
else
    $trans_type = ST_SALESORDER;


    $_POST['order_view_mode'] = "Lot Item Sales Listing";
    $_SESSION['page_title'] = _($help_context = "Lot Item Sales Listing");


$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 600);
if (user_use_date_picker())
    $js .= get_js_date_picker();
page($_SESSION['page_title'], false, false, "", $js);
//---------------------------------------------------------------------------------------------
//	Query format functions
//
function check_overdue($row)
{
    global $trans_type;
    if ($trans_type == ST_SALESQUOTE)
        return (date1_greater_date2(Today(), sql2date($row['delivery_date'])));
    else
        return ($row['type'] == 0
            && date1_greater_date2(Today(), sql2date($row['delivery_date']))
            && ($row['TotDelivered'] < $row['TotQuantity']));
}

function view_link($dummy, $order_no)
{
    global $trans_type;
    return  get_customer_trans_view_str($trans_type, $order_no);
}

function prt_link($row)
{
    global $trans_type;
    return print_document_link($row['order_no'], _("Print"), true, $trans_type, ICON_PRINT);
}

function edit_link($row)
{
    global $page_nested;

    if (is_prepaid_order_open($row['order_no']))
        return '';

    return $page_nested ? '' : trans_editor_link($row['trans_type'], $row['order_no']);
}

function dispatch_link($row)
{
    global $trans_type, $page_nested;

    if ($row['ord_payments'] + $row['inv_payments'] < $row['prep_amount'])
        return '';

    if ($trans_type == ST_SALESORDER)
    {
        if ($row['TotDelivered'] < $row['TotQuantity'] && !$page_nested)
            return pager_link( _("Dispatch"),
                "/sales/customer_delivery.php?OrderNumber=" .$row['order_no'], ICON_DOC);
        else
            return '';
    }
    else
        return pager_link( _("Sales Order"),
            "/sales/sales_order_entry.php?OrderNumber=" .$row['order_no'], ICON_DOC);
}

function invoice_link($row)
{
        return pager_link( _("Issue"),
            "/sales/lot_issue.php?NewInvoice=" .$row["order_no"].'&invtem='.$row['inv_item'].'&po_item='.$row['po_detail_item'], ICON_DOC);

}

function delivery_link($row)
{
    return pager_link( _("Delivery"),
        "/sales/sales_order_entry.php?NewDelivery=" .$row['order_no'], ICON_DOC);
}

function order_link($row)
{
    return pager_link( _("Sales Order"),
        "/sales/sales_order_entry.php?NewQuoteToSalesOrder=" .$row['order_no'], ICON_DOC);
}

function tmpl_checkbox($row)
{
    global $trans_type, $page_nested;

    if ($trans_type == ST_SALESQUOTE || !check_sales_order_type($row['order_no']))
        return '';

    if ($page_nested)
        return '';
    $name = "chgtpl" .$row['order_no'];
    $value = $row['type'] ? 1:0;

// save also in hidden field for testing during 'Update'

    return checkbox(null, $name, $value, true,
            _('Set this order as a template for direct deliveries/invoices'))
        . hidden('last['.$row['order_no'].']', $value, false);
}

function invoice_prep_link($row)
{
    // invoicing should be available only for partially allocated orders
    return
        $row['inv_payments'] < $row['total'] ?
            pager_link($row['ord_payments']  ? _("Prepayment Invoice") : _("Final Invoice"),
                "/sales/customer_invoice.php?InvoicePrepayments=" .$row['order_no'], ICON_DOC) : '';
}

$id = find_submit('_chgtpl');
if ($id != -1)
{
    sales_order_set_template($id, check_value('chgtpl'.$id));
    $Ajax->activate('orders_tbl');
}

if (isset($_POST['Update']) && isset($_POST['last'])) {
    foreach($_POST['last'] as $id => $value)
        if ($value != check_value('chgtpl'.$id))
            sales_order_set_template($id, !check_value('chgtpl'.$id));
}

$show_dates = !in_array($_POST['order_view_mode'], array('OutstandingOnly', 'InvoiceTemplates', 'DeliveryTemplates'));
//---------------------------------------------------------------------------------------------
//	Order range form
//
if (get_post('_OrderNumber_changed') || get_post('_OrderReference_changed')) // enable/disable selection controls
{
    $disable = get_post('OrderNumber') !== '' || get_post('OrderReference') !== '';

    if ($show_dates) {
        $Ajax->addDisable(true, 'OrdersAfterDate', $disable);
        $Ajax->addDisable(true, 'OrdersToDate', $disable);
    }

    $Ajax->activate('orders_tbl');
}

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(_("#:"), 'OrderNumber', '',null, '', true);
ref_cells(_("Ref"), 'OrderReference', '',null, '', true);
if ($show_dates)
{
    date_cells(_("from:"), 'OrdersAfterDate', '', null, -user_transaction_days());
    date_cells(_("to:"), 'OrdersToDate', '', null, 1);
}
locations_list_cells(_("Location:"), 'StockLocation', null, true, true);

if($show_dates) {
    end_row();
    end_table();

    start_table(TABLESTYLE_NOBORDER);
    start_row();
}
stock_items_list_cells(_("Item:"), 'SelectStockFromList', null, true, true);

if (!$page_nested)
    customer_list_cells(_("Select a customer: "), 'customer_id', null, true, true);
if ($trans_type == ST_SALESQUOTE)
    check_cells(_("Show All:"), 'show_all');

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
hidden('order_view_mode', $_POST['order_view_mode']);
hidden('type', $trans_type);

end_row();

end_table(1);
//---------------------------------------------------------------------------------------------
//	Orders inquiry table
//

$sql = get_sql_for_sales_lot_view($trans_type, get_post('OrderNumber'), get_post('order_view_mode'),
    get_post('SelectStockFromList'), get_post('OrdersAfterDate'), get_post('OrdersToDate'), get_post('OrderReference'), get_post('StockLocation'),
    get_post('customer_id'));

    $cols = array(
        _("Invoice #") => array('fun'=>'view_link', 'align'=>'center', 'ord' =>''),
        _("Item Code"),
        _("Item Description"),
        _("Quantity") => 'amount',
        array('insert'=>true, 'fun'=>'invoice_link')
    );

$table =& new_db_pager('orders_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);
submit_center('Update', _("Update"), true, '', null);

end_form();
end_page();
