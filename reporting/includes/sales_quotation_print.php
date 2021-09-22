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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/sales/includes/cart_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SALES QUOTATION - WizERP</title>
    <meta name="author" content="wizag.co.ke">
    <!-- Web Fonts
    ======================= -->
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900' type='text/css'>
    <!-- Stylesheet
    ======================= -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="/themes/vendor/bootstrap/css/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="/themes/vendor/font-awesome/css/all.min.css"/>
    <link rel="stylesheet" type="text/css" href="/themes/css/stylesheet.css"/>
</head>
<body>
<!-- Container -->
<div class="container-fluid invoice-container A4">
    <!-- Header -->
    <header>
        <div class="row align-items-center">
            <div class="col-sm-7 text-center text-sm-left mb-3 mb-sm-0">
                <img id="logo" src="<?php echo isset($formData['coy_logo']) ? $logo : '/themes/default/images/erp.png' ;?>" title="WizERP" alt="WizERP" width="132px"/>
                <address>
                    <strong><?php
                        echo $company['coy_name'];?></strong><br />
                    <?php echo $company['postal_address'];?><br />
                    PHONE: <?php echo $company['phone'];?><br />
                    EMAIL: <?php echo $company['email'];?><br />
                </address>
            </div>
			

			<?php 
				if ($_GET['trans_type'] == ST_SALESQUOTE)
				{
					$heading = "Sales Quotation";
					$help_context = "View Sales Quotation";
					$context_no = sprintf(_("Sales Quotation #%d"),$_GET['trans_no']);
				}	
				else
				{
					$heading = "Sales Order";
					$help_context = "View Sales Order";
					$context_no = sprintf(_("Sales Order #%d"),$_GET['trans_no']);
				}
			?>

            <div class="col-sm-5 text-center text-sm-right">
				<h4 class="mb-0"><?php  echo $heading ?></h4>
				<p class="mb-0"> <?php echo $context_no;?></p>
				<p class="mt-5"><b>Date</b> <?php echo date("d/m/Y", strtotime($_SESSION['View']->document_date));?></p>
            </div>

        </div>
        <hr>
    </header>
    <!-- Main Content -->
    <main>
        <div class="row">
            <div class="col-sm-6 text-sm-right order-sm-1"> <strong>Charge To:</strong>
                <address>
                    <?php echo @$formData['br_name'] ? $formData['br_name'] : @$formData['DebtorName'];?><br />
                </address>
            </div>
            <div class="col-sm-6 order-sm-0"> <strong>Delivered To:</strong>
                <address>
               <?php
               echo $_SESSION['View']->customer_name;?><br />
			   <?php
               echo nl2br($_SESSION['View']->delivery_address);?><br />
			   
                </address>
            </div>

        </div>
        <br>
        <p>
        <b>PLEASE RECEIVE THE FOLLOWING GOODS IN GOOD ORDER AND CONDITION</b>
        </p>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-bordered">
                    <table class="table mb-0">
                        <thead>
                        <?php
                            $th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"),
                            _("Price"), _("Discount"), _("Total"), _("Quantity Delivered"));
                        table_header($th);
                        ?>
                        </thead>
                        <tbody>
                        <?php
                        

                        

				
				foreach ($_SESSION['View']->line_items as $stock_item) {

                    

                            $line_total = round2($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent),
                            user_price_dec());

                        alt_table_row_color($k);

                        $box = get_box_type($stock_item->box_id);
                        $box_no = ceil($stock_item->quantity/$box['quantity']);
                        $stem_per_box = $box['quantity'];
                        // error_log(print_r(($_SESSION['View']),true));

                        label_cell($stock_item->stock_id);
                        label_cell($stock_item->item_description.', '.$box_no.' Boxes, '.$stem_per_box.' stems per box'); // add item description
                        $dec = get_qty_dec($stock_item->stock_id);
                        qty_cell($stock_item->quantity, false, $dec);
                        label_cell($stock_item->units);
                        amount_cell($stock_item->price);
                        amount_cell($stock_item->discount_percent * 100);
                        amount_cell($line_total);

                        qty_cell($stock_item->qty_done, false, $dec);
                            
						 }?>
                         
                        </tbody>
                        <tfoot class="card-footer">
                        <tr class="tr-spacer"/>
                        <tr class="tr-spacer"/>
                        <tr class="tr-spacer"/>
                        <tr>
                         <td colspan="4" rowspan="4">
                               <span>
                                Any exceptions, errors or change of address should be promptly
                                advised to the company.Under no circumstances will the above goods be
                                returned. Above goods received and accepted in good order and condition.
                                All goods remain the property of the seller until payment is received in full.
                            </span>
                            </td>
                        </tr>
                        <?php

                    if ($_SESSION['View']->freight_cost != 0.0)
                        label_row(_("Shipping"), price_format($_SESSION['View']->freight_cost),
                            "align=right colspan=3", "nowrap align=right", 1);

                    $sub_tot = $_SESSION['View']->get_items_total() + $_SESSION['View']->freight_cost;

                    $display_sub_tot = price_format($sub_tot);

                    label_row(_("Sub Total"), $display_sub_tot, "align=right colspan=3",
                        "nowrap align=right", 0);

                    $taxes = $_SESSION['View']->get_taxes();

                    $tax_total = display_edit_tax_items($taxes, 3, $_SESSION['View']->tax_included,0);

                    $display_total = price_format($sub_tot + $tax_total);

                    label_cells(_("Amount Total"), $display_total, "colspan=3 align='right'","align='right'");
                        ?>

                        </tfoot>
                    </table>


                </div>
            </div>
        </div>

    </main>
    <!-- Footer -->
    <footer class="text-center">
        <br>
        <p class="text-1"><strong>NOTE :</strong> Cheques are payable to <b>OCEAN FOODS LIMITED</b>.
            <br>
            <span class="text-1">Cash payable to: <b>Mpesa Paybill Number 400 47 47</b> -> Account No. Invoice No. -> Amount as per Invoice</span>
        </p>
        <div class="btn-group btn-group-sm d-print-none"> <a href="javascript:window.print()" class="btn btn-light border text-black-50 shadow-none"><i class="fa fa-print"></i> Print</a> <a href="" class="btn btn-light border text-black-50 shadow-none"><i class="fa fa-download"></i> Download</a> </div>
    </footer>
</div>
</body>
</html>

<script>
     window.print();
    setTimeout(() =>{
     window.close();
    },2000)

</script>

<style>
    .tr-spacer
    {
        height: 100px;
    }
    .table td, .table th{
     vertical-align: bottom !important;
    }
    @media print {
        .table-info{
            background: #86cfda !important;
        }
        .invoice-container {
            page-break-after: always;
        }

    }
    body{
     -webkit-print-color-adjust:exact;
    }
</style>


