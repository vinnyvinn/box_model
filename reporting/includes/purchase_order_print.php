<?php
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
//include_once("../rep209_.php");


$company = get_company_prefs();

$from = $_GET['PARAM_0'];
$to = $_GET['PARAM_1'];
$currency = $_GET['PARAM_2'];
$email = $_GET['PARAM_3'];
$comments = $_GET['PARAM_4'];
$orientation = $_GET['PARAM_5'];

if (!$from || !$to) return;

$orientation = ($orientation ? 'L' : 'P');
$dec = user_price_dec();

$fno = explode("-", $from);
$tno = explode("-", $to);
$from = min($fno[0], $tno[0]);
$to = max($fno[0], $tno[0]);

function get_po($order_no)
{
    $sql = "SELECT po.*, supplier.supp_name,supp_address, supplier.supp_account_no,supplier.tax_included,
   		supplier.gst_no AS tax_id,
   		supplier.curr_code, supplier.payment_terms, loc.location_name,
   		supplier.address, supplier.contact, supplier.tax_group_id
		FROM ".TB_PREF."purch_orders po,"
        .TB_PREF."suppliers supplier,"
        .TB_PREF."locations loc
		WHERE po.supplier_id = supplier.supplier_id
		AND loc.loc_code = into_stock_location
		AND po.order_no = ".db_escape($order_no);
    $result = db_query($sql, "The order cannot be retrieved");
    return db_fetch($result);
}

function get_po_details($order_no)
{
    $sql = "SELECT poline.*, units
		FROM ".TB_PREF."purch_order_details poline
			LEFT JOIN ".TB_PREF."stock_master item ON poline.item_code=item.stock_id
		WHERE order_no =".db_escape($order_no)." ";
    $sql .= " ORDER BY po_detail_item";
    return db_query($sql, "Retreive order Line Items");
}

$params = array('comments' => $comments);

$cur = get_company_Pref('curr_default');


for ($i = $from; $i <= $to; $i++)
{
    $myrow = get_po($i);
    if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
        continue;
    }
    $baccount = get_default_bank_account($myrow['curr_code']);
    $params['bankaccount'] = $baccount['id'];

    $contacts = get_supplier_contacts($myrow['supplier_id'], 'order');

    //start form data
    $pageNumber = 0;
    $formData = array();
    $contactData = array();
    $datnames = array(
        'myrow' => array('ord_date', 'date_', 'tran_date',
            'order_no','reference', 'id', 'trans_no', 'name', 'location_name',
            'delivery_address', 'supp_name','supp_address', 'address',
            'DebtorName', 'supp_account_no', 'wo_ref', 'debtor_ref','type', 'trans_no',
            'StockItemName', 'tax_id', 'order_', 'delivery_date', 'units_issued',
            'due_date', 'required_by', 'payment_terms', 'curr_code',
            'ov_freight', 'ov_gst', 'ov_amount', 'prepaid', 'requisition_no', 'contact'),
        'branch' => array('br_address', 'br_name', 'salesman', 'disable_branch'),
        'sales_order' => array('deliver_to', 'delivery_address', 'customer_ref'),
        'bankaccount' => array('bank_name', 'bank_account_number', 'payment_service')
    );

    foreach($datnames as $var => $fields) {
        if (isset($$var)) {
            foreach($fields as $locname) {
                if (isset(${$var}[$locname]) && (${$var}[$locname]!==null)) {
                    $formData[$locname] = ${$var}[$locname];
                }
            }
        }
    }
    $formData['doctype'] = ST_PURCHORDER;
    $formData['document_amount'] = @$formData['ov_amount']+@$formData['ov_freight']+@$formData['ov_gst'];
    if (count($contacts)) {
        if (!is_array($contacts[0]))
            $contacts = array($contacts); // change to array when single contact passed
        $contactData = $contacts;
        // as report is currently generated once despite number of email recipients
        // we select language for the first recipient as report language
        $formData['rep_lang'] = $contacts[0]['lang'];
    }
    //end form data

    $result = get_po_details($i);

    $res = db_fetch(get_po_details($i));
    $SubTotal = 0;
    $items = $prices = array();


    $logo = company_path() . "/images/" . $formData['coy_logo'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Purchase Order - WizERP</title>
        <meta name="author" content="wizag.co.ke">
        <!-- Web Fonts
        ======================= -->
        <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900' type='text/css'>
        <!-- Stylesheet
        ======================= -->
        <link rel="stylesheet" type="text/css" href="/themes/vendor/bootstrap/css/bootstrap.min.css"/>
        <link rel="stylesheet" type="text/css" href="/themes/vendor/font-awesome/css/all.min.css"/>
        <link rel="stylesheet" type="text/css" href="/themes/css/stylesheet.css"/>
    </head>
    <body>
    <!-- Container -->
    <div class="container-fluid invoice-container A4">
        <!-- Header -->
        <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4 text-sm-right">
                <img id="logo" src="<?php echo isset($formData['coy_logo']) ? $logo : '/themes/default/images/erp.png' ;?>" title="WizERP" alt="WizERP" width="200"/>
            </div>
        </div>
        <header>
            <div class="row align-items-center">
                <div class="col-sm-6 text-center text-sm-left mb-3 mb-sm-0">
                    <h3 class="mb-1" style="font-weight: 900">PURCHASE ORDER</h3>

                    <address>
                        <span style="text-transform: uppercase;font-size: 16px;"><?php
                           // echo '<pre>';
                        //  var_dump($formData);
                          echo $formData['supp_name'];
//                            echo $company['coy_name'];
                            ?>
                        </span><br/>
                        <span style="text-transform:uppercase; font-size: 16px;">
                            <?php
                            echo $formData['supp_address'];?><br>
                        </span>

                        <?php echo $company['postal_address'];?><br />
                    </address>
                </div>
                <div class="col-sm-3">
                    <b class="mb-0" style="font-size: 14px">Purchase Order Date</b>
                    <span style="text-align: left"><?php echo date("d/m/Y", strtotime($formData['ord_date']));?></span><br>

                    <div class="mt-2">
                     <b class="mb-2" style="font-size: 14px">Delivery Date</b>
                    </div>
                    <p class="mb-0"><?php echo date('d/m/Y',strtotime($res['delivery_date']));?></p>
                    <div class="mt-2">
                    <b class="mb-2" style="font-size: 14px">
                        Purchase Order Number
                    </b>
                    </div>
                    <p class="mb-0">
                        <?php echo $formData['reference'];?>
                    </p>

                    <div class="mt-2">
                    <b class="mb-2" style="font-size: 14px"><?php echo $company['coy_name'];?></b>
                    </div>
                    <p class="mb-0">PO614729541</p>
                </div>
                <div class="col-sm-3 text-center text-sm-right mt-0">
                    <b class="mb-2" style="font-size: 12px"><?php echo $company['coy_name'];?></b>
                    <p class="mb-0"><?php echo $company['postal_address'];?></p>
                </div>
            </div>
            <hr>
        </header>
        <!-- Main Content -->
        <main>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-bordered">
                        <table class="table mb-0" style="width: 100%;">
                            <thead>
                            <tr class="table-info">
                                <td class="border-top-0"><strong>Description</strong></td>
                                <td class="border-top-0"><strong>Quantity</strong></td>
                                <td class="border-top-0"><strong>Exchange Rate</strong></td>
                                <td class="border-top-0"><strong>Tax</strong></td>
                                <td class="text-right border-top-0"><strong>Unit Price</strong></td>
                                <td class="text-right border-top-0"><strong>Amount KES</strong></td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $subtotal = 0;
                            while ($myrow2=db_fetch($result))
                            {
                                $data = get_purchase_data($myrow['supplier_id'], $myrow2['item_code']);
                                if ($data !== false)
                                {
                                    if ($data['supplier_description'] != "")
                                        $myrow2['description'] = $data['supplier_description'];
                                    if ($data['suppliers_uom'] != "")
                                        $myrow2['units'] = $data['suppliers_uom'];
                                    if ($data['conversion_factor'] != 1)
                                    {
                                        $myrow2['unit_price'] = round2($myrow2['unit_price'] * $data['conversion_factor'], user_price_dec());
                                        $myrow2['quantity_ordered'] = round2($myrow2['quantity_ordered'] / $data['conversion_factor'], user_qty_dec());
                                    }
                                }
                                $Net = round2(($myrow2["unit_price"] * $myrow2["quantity_ordered"]), user_price_dec());
                                $prices[] = $Net;
                                $items[] = $myrow2['item_code'];
                                $SubTotal += $Net;
                                $dec2 = 0;
                                $DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec2);
                                $DisplayQty = number_format2($myrow2["quantity_ordered"],get_qty_dec($myrow2['item_code']));
                                $DisplayNet = number_format2($Net,$dec);

                                $DisplayTax = 0;
                                $tax_items =  $tax_items = get_tax_for_items($items, $prices, 0,
                                    $myrow['tax_group_id'], $myrow['tax_included'],  null, TCA_LINES);
                               //  $tax_items[1]['tax_type_name']; --> 16 %
                                $subtotal +=($DisplayNet * $myrow['usd_rate']);
                                ?>
                                <tr>
                                    <td><span class="text-3"><?php echo $myrow2['description'];?></td>
                                    <td class="text-center"><?php echo $DisplayQty.' '.$myrow2['units'];?></td>
                                    <td class="text-center"><?php echo $myrow['usd_rate'];?></td>
                                    <td class="text-center"><?php echo '16%';?></td>
                                    <td class="text-center"><?php echo $DisplayPrice?></td>
                                    <td class="text-right"><?php echo number_format2($DisplayNet * $myrow['usd_rate'],$dec);?></td>
                                </tr>
                            <?php  }?>
                            </tbody>
                            <tfoot class="card-footer">
                            <tr>
                                <td rowspan="3">

                                </td>
                                <?php

                                $DisplaySubTot = number_format2($subtotal,$dec);?>
                                <td colspan="4" class="text-right" style="width:10px !important;"><strong>Subtotal</strong></td>
                                <td class="text-right"><?php echo $DisplaySubTot;?></td>

                            </tr>
                            <?php
                            $DisplayTax = 0;
                            $tax_items =  $tax_items = get_tax_for_items($items, $prices, 0,
                                $myrow['tax_group_id'], $myrow['tax_included'],  null, TCA_LINES);
                          //   var_dump($tax_items[1]['tax_type_name']);
                            foreach($tax_items as $tax_item)
                            {
                                if ($tax_item['Value'] == 0)
                                    continue;
                                $DisplayTax = number_format2($tax_item['Value'], $dec);
                                $tax_type_name = $tax_item['tax_type_name'];
                                ?>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>TAX <?php echo $tax_type_name;?></strong></td>
                                    <td class="text-right"><?php echo $DisplayTax;?></td>
                                </tr>
                                <?php
                            }

                            $DisplayTaxAmount = $subtotal * 0.16;
                            ?>

                            <tr>
                                <td colspan="4" class="text-right"><strong>TOTAL PURCHASES TAX 16%</strong></td>
                                <td class="text-right" style="border-bottom: none"><?php echo number_format2($DisplayTaxAmount,$dec);?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right"><strong>TOTAL KES</strong></td>
                                <td class="text-right"><?php
                                    echo number_format2(($subtotal + $DisplayTaxAmount),$dec);?></td>
                            </tr>

                            </tfoot>
                        </table>

                    </div>

                    <h3 style="margin-top: 5rem;font-weight: 600">DELIVERY DETAILS</h3>
                    <div class="row">
                        <div class="col-md-3">
                            <b style="font-size: 16px">Delivery Address</b>
                            <table>
                                <tr>
                                 <td><?php echo $formData['delivery_address'];?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-3">
                            <b style="font-size: 16px">Attention</b>
                        </div>
                        <div class="col-md-3">
                         <b style="font-size: 16px">Delivery Instructions</b>
                        </div>
                        <div class="col-md-3"></div>
                    </div>
                    <table>

                    </table>
                    <div style="margin-top: 3%">
                        <p>Company Registration No: CPR2014/144733. Registered Office P.O BOX 23557-00400, Nairobi East Tom Mboya, Nairobi Kenya</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    </body>
   </html>

    <script>
        // window.print();
        // setTimeout(() =>{
        //     window.close();
        // },2000)
    </script>

    <style>
        .tr-spacer
        {
            height: 100px;
        }
        /*.table td, .table th{*/
        /*    vertical-align: bottom !important;*/
        /*}*/
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

    <?php
}
?>
