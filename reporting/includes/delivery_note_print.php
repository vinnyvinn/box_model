<?php
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once("../rep110.php");

$company = get_company_prefs();

$from = $_GET['PARAM_0'];
$to = $_GET['PARAM_1'];
$email = $_GET['PARAM_2'];
$packing_slip = $_GET['PARAM_3'];
$comments = $_GET['PARAM_4'];
$orientation = $_GET['PARAM_5'];


if (!$from || !$to) return;

$orientation = ($orientation ? 'L' : 'P');
$dec = user_price_dec();

$fno = explode("-", $from);
$tno = explode("-", $to);
$from = min($fno[0], $tno[0]);
$to = max($fno[0], $tno[0]);


$params = array('comments' => $comments);

$cur = get_company_Pref('curr_default');

for ($i = $from; $i <= $to; $i++) {
    if (!exists_customer_trans(ST_CUSTDELIVERY, $i))
        continue;
    $myrow = get_customer_trans($i, ST_CUSTDELIVERY);
    $branch = get_branch($myrow["branch_code"]);
    $sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
    $contacts = get_branch_contacts($branch['branch_code'], 'delivery', $branch['debtor_no'], true);

    //form data
    $formData = array();
    $contactData = array();
    $datnames = array(
        'myrow' => array('ord_date', 'date_', 'tran_date',
            'order_no','reference', 'id', 'trans_no', 'name', 'location_name',
            'delivery_address', 'supp_name', 'address',
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
    $formData['doctype'] = ST_CUSTDELIVERY;
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

    $result = get_customer_trans_details(ST_CUSTDELIVERY, $i);
    $SubTotal = 0;

    if ($packing_slip == 0) {
        $tax_items = get_trans_tax_details(ST_CUSTDELIVERY, $i);
        $first = true;
    }
    $logo = company_path() . "/images/" . $formData['coy_logo'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Deliver Note - WizERP</title>
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
            <div class="col-sm-5 text-center text-sm-right">
             <h4 class="mb-0">Delivery Note</h4>
             <p class="mb-0">Delivery Note No. <?php echo $formData['reference'];?></p>
             <p class="mt-5"><b>Date</b> <?php echo date("d/m/Y", strtotime($formData['tran_date']));?></p>
            </div>
        </div>
        <hr>
    </header>
    <!-- Main Content -->
    <main>
        <div class="row">
            <div class="col-sm-6 text-sm-right order-sm-1"> <strong></strong>
<!--                <address>-->
<!--                    --><?php //echo @$formData['supp_name'];?><!--<br />-->
<!--                </address>-->
            </div>
            <div class="col-sm-6 order-sm-0"> <strong>Charge To:</strong>
                <address>
               <?php
               echo $formData['address'];?><br />
                </address>
            </div>

        </div>
        <br>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="thead-dark">
                <tr class="table-info">
                    <td class="text-center"><strong>Customer's Balance</strong></td>
                    <td class="text-center"><strong>Payment Terms</strong></td>
                    <td class="text-center"><strong>Credit Limit</strong></td>
                    <td class="text-center"><strong>Last Unpaid Invoice Days</strong></td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                </tr>
                </tbody>
            </table>
        </div>


        <div class="card">
            <div class="card-body p-0">
                <div class="table-bordered">
                    <table class="table mb-0">
                        <thead>
                        <tr class="table-info">
                            <td class="border-top-0"><strong>Item Description</strong></td>
                            <td class="border-top-0"  style="width: 15%;text-align: center"><strong>Qty Unit</strong></td>
                            <td class="text-right border-top-0"><strong>Price</strong></td>
                            <td class="border-top-0" style="width: 15%;"><strong>Discount %</strong></td>
                            <td class="text-right border-top-0" style="width: 15%;"><strong>Total Incl</strong></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ($myrow2 = db_fetch($result)) {
                            if ($myrow2["quantity"] == 0)
                                continue;

                            $Net = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
                                user_price_dec());
                            $SubTotal += $Net;
                            $DisplayPrice = number_format2($myrow2["unit_price"], $dec);
                            $DisplayQty = number_format2($myrow2["quantity"], get_qty_dec($myrow2['stock_id']));
                            $DisplayNet = number_format2($Net, $dec);
                            if ($myrow2["discount_percent"] == 0)
                                $DisplayDiscount = "";
                            else
                            $DisplayDiscount = number_format2($myrow2["discount_percent"] * 100, user_percent_dec()) . "%";

                        if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !$SysPrefs->no_zero_lines_amount()):
                        if ($packing_slip == 0):
                        ?>
                        <tr>
                            <td><span class="text-3"><?php echo $myrow2['StockDescription'];?></td>
                            <td class="text-center"><?php echo $DisplayQty.' '.$myrow2['units'];?></td>
                            <td class="text-center"><?php echo $DisplayPrice?></td>
                            <td class="text-right"><?php echo $DisplayDiscount?></td>
                            <td class="text-right"><?php echo $DisplayNet?></td>
                        </tr>
                        <?php endif;endif;}?>
                        </tbody>
                        <tfoot class="card-footer">
                        <tr class="tr-spacer"/>
                        <tr class="tr-spacer"/>
                        <tr class="tr-spacer"/>
                        <tr>
                         <td rowspan="3">

                            </td>
                            <?php $DisplaySubTot = number_format2($SubTotal,$dec);?>
                            <td colspan="3" class="text-right" style="width:10px !important;"><strong>Total Excl Amount</strong></td>
                            <td class="text-right"><?php echo $DisplaySubTot;?></td>

                        </tr>
                        <?php
                        while ($tax_item = db_fetch($tax_items)) {
                        if ($tax_item['amount'] == 0)
                            continue;
                        $DisplayTax = number_format2($tax_item['amount'], $dec);

                        if ($SysPrefs->suppress_tax_rates() == 1)
                            $tax_type_name = $tax_item['tax_type_name'];
                        else
                            $tax_type_name = $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) ";

                        ?>
                        <tr>
                           <td colspan="3" class="text-right"><strong>TAX <?php echo $tax_type_name;?></strong></td>
                           <td class="text-right"><?php echo $DisplayTax;?></td>
                        </tr>
                        <?php }

                        $DisplayTotal = ($myrow["ov_freight"] + $myrow["ov_gst"] +
                            $myrow["ov_amount"]+$myrow["ov_freight_tax"]);
                        ?>

                        <tr>
                           <td colspan="3" class="text-right"><strong>Total Incl Amount</strong></td>
                           <td class="text-right"><?php
                               echo number_format2(($DisplayTax+$DisplayTotal),$dec);?></td>
                        </tr>

                        </tfoot>
                    </table>


                </div>
            </div>
        </div>

    </main>
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

<?php }?>