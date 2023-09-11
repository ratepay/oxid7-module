/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function piRatepayRateCalculatorAction(mode, paymentMethod, month) {
    var calcValue;
    var calcMethod;
    var paymentFirstday = 28;
    var html;

    document.getElementById(paymentMethod + '_month').value = month;
    document.getElementById(paymentMethod + '_mode').value = mode;
    document.querySelector('.sticky-md-top .btn').disabled = false;


    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    stoken = document.getElementsByName("stoken")[0].value;
    if(document.getElementsByName("shp")[0] === undefined){
        shop = 1;
    } else{
        shop = document.getElementsByName("shp")[0].value;
    }

    if (mode == 'rate') {
        calcValue = document.getElementById(paymentMethod + '_rp-rate-value').value;
        calcMethod = 'calculation-by-rate';
    } else if (mode == 'runtime') {
        calcValue = month;
        calcMethod = 'calculation-by-time';
    }

    if (document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban') !== null) {
        if (document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').style.display !== 'none') {
            if (calcValue > 0) {
                document.getElementById(paymentMethod + '_rp-rate-elv').style.display = 'block';
            }
            document.querySelector('.sticky-md-top .btn').disabled = true;

            if (document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').style.display === 'block'
                && document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').value !== ''
                && document.getElementById(paymentMethod + '_rp-sepa-aggreement').checked === true
            ) {
                document.querySelector('.sticky-md-top .btn').disabled = false;
            }

            var bankAccount;
            if (document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').value !== '') {
                if (document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').style.display === 'block') {
                    bankAccount = document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').value;
                }
            }
            paymentFirstday = document.getElementById(paymentMethod + '_paymentFirstday').value;
        }
        if (calcValue <= 0) {
            document.getElementById(paymentMethod + '_rp-rate-elv').style.display = 'none';
        }
    } else {
        document.querySelector('.sticky-md-top .btn').disabled = false;
    }
    xmlhttp.open("POST", pi_ratepay_rate_calc_path + "Php/PiRatepayRateCalcRequest.php", false);

    xmlhttp.setRequestHeader("Content-Type",
        "application/x-www-form-urlencoded");

    xmlhttp.send("calcValue=" + calcValue + "&calcMethod=" + calcMethod + "&bankAccount=" + bankAccount + "&paymentFirstday=" + paymentFirstday + "&stoken=" + stoken + "&shp=" + shop + "&smethod=" + paymentMethod);

    if (xmlhttp.responseText != null) {
        html = xmlhttp.responseText;
        document.getElementById(paymentMethod + '_piRpResultContainer').innerHTML = html;
        document.getElementById(paymentMethod + '_piRpResultContainer').style.display = 'block';

    }
}

function updateCalculator(paymentMethod) {
    var month = document.getElementById(paymentMethod + '_month').value;
    var mode = document.getElementById(paymentMethod + '_mode').value;

    if (month !== '') {
        piRatepayRateCalculatorAction(mode, paymentMethod, month);
    }

}

function rp_change_payment(payment, paymentMethod) {
    if (payment == 28) {
        document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').value = '';
        document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').style.display = 'none';
        document.getElementById(paymentMethod + '_rp-rate-elv').style.display = 'none';
        document.getElementById(paymentMethod + '_rp-switch-payment-type-direct-debit').style.display = 'block';
        document.getElementById(paymentMethod + '_paymentFirstday').value = 2;
    } else {
        document.getElementById(paymentMethod + '_pi_ratepay_rate_bank_iban').style.display = 'block';
        document.getElementById(paymentMethod + '_rp-rate-elv').style.display = 'block';
        document.getElementById(paymentMethod + '_rp-switch-payment-type-direct-debit').style.display = 'none';
        document.getElementById(paymentMethod + '_paymentFirstday').value = 28;
    }
    updateCalculator(paymentMethod);
}

function piLoadrateCalculator(paymentMethod) {
    var html;

    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    stoken = document.getElementsByName("stoken")[0].value;
    if(document.getElementsByName("shp")[0] === undefined){
        shop = 1;
    } else{
        shop = document.getElementsByName("shp")[0].value;
    }

    xmlhttp.open("POST", pi_ratepay_rate_calc_path + "Php/PiRatepayRateCalcDesign.php", false);

    xmlhttp.setRequestHeader("Content-Type",
        "application/x-www-form-urlencoded");

    xmlhttp.send("stoken=" + stoken + "&shp=" + shop + "&smethod=" + paymentMethod);

    if (xmlhttp.responseText != null) {
        html = xmlhttp.responseText;
        document.getElementById(paymentMethod + '_pirpmain-cont').innerHTML = html;
    }
}

function piLoadrateResult(paymentMethod) {
    var html;

    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    stoken = document.getElementsByName("stoken")[0].value;
    if(document.getElementsByName("shp")[0] === undefined){
        shop = 1;
    } else{
        shop = document.getElementsByName("shp")[0].value;
    }

    xmlhttp.open("POST", pi_ratepay_rate_calc_path + "Php/PiRatepayRateCalcRequest.php", false);

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const controller = urlParams.get('cl')

    xmlhttp.setRequestHeader("Content-Type",
        "application/x-www-form-urlencoded");

    xmlhttp.send("stoken=" + stoken + "&shp=" + shop + "&smethod=" + paymentMethod + "&cl=" + controller);

    if (xmlhttp.responseText != null) {
        html = xmlhttp.responseText;
        document.getElementById(paymentMethod + '_pirpmain-cont').innerHTML = html;
    }
}
