<?php
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

$trans_no = $_GET['trans_no'];
$company = get_company_prefs();
if (!isset($trans_no)) return;

$dec = user_price_dec();
$cur = get_company_Pref('curr_default');
$trans = get_stock_transfer_($trans_no);
$customer = get_customer($trans['customer_id']);
$salesman = get_salesman_name(get_salesman_id($trans['customer_id']));

$transfer_items = get_stock_moves(ST_LOCTRANSFER, $trans_no);
    $SubTotal = 0;
    $logo = company_path() . "/images/" . $company['coy_logo'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice - WizERP</title>
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
            <img id="logo" src="<?php echo (!empty($company['coy_logo'])) ? $logo : '/themes/default/images/erp.png' ;?>" title="WizERP" alt="WizERP" width="132px"/>
                <address>
                    <strong><?php
                        echo $company['coy_name'];?></strong><br />
                    <?php echo $company['postal_address'];?><br />
                    PHONE: <?php echo $company['phone'];?><br />
                    EMAIL: <?php echo $company['email'];?><br />
                </address>
            </div>
            <div class="col-sm-5 text-center text-sm-right">
             <h4 class="mb-0">Consignment Delivery</h4>
             <p class="mb-0">Consignment Number - <?php echo $trans['reference'];?></p>
             <p class="mt-5"><b>Consignment Date</b> <?php echo date("d/m/Y", strtotime($trans['tran_date']));?></p>
            </div>
        </div>
        <hr>
    </header>
    <!-- Main Content -->
    <main>
        <div class="row">
            <div class="col-sm-6 text-sm-right order-sm-1"> <strong>From Location:</strong>
                <address>
                    <?php echo $trans['from_name'];?><br />
                </address>
            </div>
            <div class="col-sm-6 order-sm-0"> <strong>To Location:</strong>
                <address>
               <?php
               echo $trans['to_name'];?><br />
                </address>
            </div>
        </div>
        <br>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr class="table-info">
                    <td class="text-center"><strong>Customer's Reference</strong></td>
                    <td class="text-center"><strong>Sales Person</strong></td>
                    <td class="text-center"><strong>Your VAT no.</strong></td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="text-center"><?php echo $customer['name'];?></td>
                    <td class="text-center"><?php echo $salesman;?></td>
                    <td class="text-center"><?php echo $company['tax_id'];?></td>
                </tr>
                </tbody>
            </table>
        </div>

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
        <p>
        <b>PLEASE RECEIVE THE FOLLOWING GOODS IN GOOD ORDER AND CONDITION</b>
        </p>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-bordered">
                    <table class="table mb-0">
                        <thead>
                        <tr class="table-info">
                            <th class="border-top-0"><strong>Item Description</strong></th>
                            <th class="border-top-0" style="width: 15%;text-align: center"><strong>Qty Unit</strong></th>
                            <th class="text-right border-top-0"><strong>Price</strong></th>
                            <th class="text-right border-top-0"  style="width: 15%;"><strong>Total Incl</strong></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ($myrow2 = db_fetch($transfer_items))
                        {
                            if ($myrow2['loc_code'] == $trans['to_loc']):
                            if (abs($myrow2["qty"]) == 0)
                                continue;

                            $Net = round2( $myrow2["price"] * abs($myrow2["qty"]),
                                user_price_dec());
                            $SubTotal += $Net;
                            $DisplayPrice = number_format2($myrow2["price"],$dec);
                            $DisplayQty = number_format2(abs($myrow2["qty"]),get_qty_dec($myrow2['stock_id']));
                            $DisplayNet = number_format2($Net,$dec);
                        ?>
                        <tr>
                            <td><span class="text-3"><?php echo $myrow2['description'];?></td>
                            <td class="text-center" style="width: 15%"><?php echo $DisplayQty.' '.$myrow2['units'];?></td>
                            <td class="text-center"><?php echo $DisplayPrice?></td>
                            <td class="text-right"><?php echo $DisplayNet?></td>
                        </tr>
                        <?php endif;}?>
                        </tbody>
                        <tfoot class="card-footer">
                        <tr class="tr-spacer"/>
                        <tr class="tr-spacer"/>
                        <tr class="tr-spacer"/>
                        <tr>
                         <td rowspan="2">
                               <span>
                                Any exceptions, errors or change of address should be promptly
                                advised to the company.Under no circumstances will the above goods be
                                returned. Above goods received and accepted in good order and condition.
                                All goods remain the property of the seller until payment is received in full.
                            </span>
                            </td>
                            <?php $DisplaySubTot = number_format2($SubTotal,$dec);?>
                            <td colspan="2" class="text-right" style="width:10px !important;"><strong>Total Excl Amount</strong></td>
                            <td class="text-right"><?php echo $DisplaySubTot;?></td>

                        </tr>

                        <tr>
                           <td colspan="2" class="text-right"><strong>Total Incl Amount</strong></td>
                           <td class="text-right"><?php echo $DisplaySubTot;?></td>
                        </tr>

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
        <p class="text-1" style="margin-top: 5%;"><strong>SIGN: _____________________________________________________</strong></p>
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
