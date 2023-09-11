<?php
/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require_once 'PiRatepayRateCalc.php';
require_once 'path.php';

$pi_calculator = new \pi\ratepay\Installment\Php\PiRatepayRateCalc();

$pi_calculator->unsetData();
$pi_monthAllowed = $pi_calculator->getRatepayRateMonthAllowed();
$pi_amount = $pi_calculator->getRequestAmount();
$pi_language = $pi_calculator->getLanguage();
$pi_firstday = $pi_calculator->getRequestFirstday();
$pi_owner = $pi_calculator->getRequestBankOwner();
$pi_valid_firstday = $pi_calculator->getValidRequestPaymentFirstday();
$pi_companyName = $pi_calculator->getRequestCompanyName();

$sPaymentMethod = $pi_calculator->getPaymentMethod();

$oSettings = $pi_calculator->getSettings();
$sSettlementType = $oSettings->getSettlementType(); // debit, banktransfer, both

if ($pi_language == "DE" || $pi_language == "AT") {
    require_once 'languages/german.php';
    $pi_currency = 'EUR';
    $pi_decimalSeperator = ',';
    $pi_thousandSeperator = '.';
} else {
    require_once 'languages/english.php';
    $pi_currency = 'EUR';
    $pi_decimalSeperator = '.';
    $pi_thousandSeperator = ',';
}

$pi_amount = number_format($pi_amount, 2, $pi_decimalSeperator, $pi_thousandSeperator);

if ($pi_calculator->getErrorMsg() != '') {
    if ($pi_calculator->getErrorMsg() == 'serveroff') {
        echo "<div>" . $pi_lang_server_off . "</div>";
    } else {
        echo "<div>" . $pi_lang_config_error_else . "</div>";
    }
} else {
    ?>
    <div class="rpContainer">
        <?php if ($sPaymentMethod != 'pi_ratepay_rate0') { ?>
            <div class="row"<?php if ($sPaymentMethod == 'pi_ratepay_rate0') { echo 'style="display: none"';  }?>>
                <div class="col-md-12">
                    <?php
                    echo $rp_calculation_intro_part1;
                    echo $rp_calculation_intro_part2;
                    echo $rp_calculation_intro_part3;
                    ?>
                </div>
            </div>
        <?php } ?>
        <div class="card-group"<?php if ($sPaymentMethod == 'pi_ratepay_rate0') { echo 'style="display: none"';  }?>>
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title" id="<?php echo $sPaymentMethod; ?>_firstInput"><?php echo $rp_runtime_title; ?></h3>
                    <p class="card-text"><?php echo $rp_runtime_description; ?></p>
                </div>
                <input type="hidden" id="<?php echo $sPaymentMethod; ?>_rate_elv" name="rate_elv" value="<?php echo $pi_rate_elv ?>">
                <input type="hidden" id="<?php echo $sPaymentMethod; ?>_rate" name="rate" value="<?php echo $pi_rate ?>">
                <input type="hidden" id="<?php echo $sPaymentMethod; ?>_paymentFirstday" name="paymentFirstday" value="<?php echo $pi_firstday ?>">
                <input type="hidden" id="<?php echo $sPaymentMethod; ?>_month" name="month" value="">
                <input type="hidden" id="<?php echo $sPaymentMethod; ?>_mode" name="mode" value="">
                <div class="card-footer">
                    <div class="btn-group btn-group-justified" role="group" aria-label="...">
                        <?php foreach ($pi_monthAllowed AS $month) { ?>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-primary rp-btn-runtime" type="button" onclick="piRatepayRateCalculatorAction('runtime', '<?php echo $sPaymentMethod; ?>', <?php echo $month; ?>);" id="<?php echo $sPaymentMethod; ?>_piRpInput-buttonMonth-<?php echo $month; ?>" role="group"><?php echo $month; ?></button>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title" id="<?php echo $sPaymentMethod; ?>_secondInput"><?php echo $rp_rate_title; ?></h3>
                    <p class="card-text"><?php echo $rp_rate_description; ?></p>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">€</span>
                        <input type="text" id="<?php echo $sPaymentMethod; ?>_rp-rate-value" class="form-control" aria-label="Amount">
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-sm btn-outline-primary rp-btn-rate" onclick="piRatepayRateCalculatorAction('rate', '<?php echo $sPaymentMethod; ?>');" type="button" id="<?php echo $sPaymentMethod; ?>_piRpInput-buttonRuntime"><?php echo $rp_calculate_rate; ?></button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12" id="<?php echo $sPaymentMethod; ?>_piRpResultContainer"></div>
        </div>
        <?php if (in_array($sSettlementType, ['both', 'debit'])): ?>
            <div class="row">
                <div id="<?php echo $sPaymentMethod; ?>_rp-rate-elv" class="col-md-12">
                    <?php if ($sSettlementType == 'both'): ?>
                        <strong class="rp-installment-header"><?php echo $rp_header_debit; ?></strong>
                        <div class="rp-payment-type-switch" id="<?php echo $sPaymentMethod; ?>_rp-switch-payment-type-bank-transfer" onclick="rp_change_payment(28, '<?php echo $sPaymentMethod; ?>')">
                            <a class="rp-link"><?php echo $rp_switch_payment_type_bank_transfer; ?></a>
                        </div><br>
                    <?php endif; ?>

                    <div class="rp-row-space rp-sepa-form">
                        <table class="rp-sepa-table">
                            <tr>
                                <td colspan="2">
                                    <?php echo $rp_creditor_info ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <br/>
                    <div class="rp-sepa-form rp-special-item rp-row-space">
                        <?php echo $wcd_sepa_notice_block; ?>
                    </div>
                    <br/>
                    <div class="rp-sepa-fields">
                        <div class="rp-sepa-form">
                            <form>
                                <input type="hidden" name="rp-payment-type" id="<?php echo $sPaymentMethod; ?>_rp-payment-type" />
                                <div class="form-group" style="margin-left: 0px; margin-right: 0px;">
                                    <label class=""><?php echo $rp_account_holder; ?></label>
                                    <?php if (!empty($pi_companyName)) { ?>
                                        <select name="rp_sepa_use_company_name">
                                            <option selected="selected" value="1"><?php echo $pi_companyName; ?></option>
                                            <option value="0"><?php echo $pi_owner; ?></option>
                                        </select>
                                    <?php } else { ?>
                                        <input type="text" class="form-control disabled" value="<?php echo $pi_owner; ?>" disabled />
                                        <input type="hidden" name="rp_sepa_use_company_name" value="0" />
                                    <?php } ?>
                                </div>
                                <!-- Account number is only allowed for customers with german billing address. IBAN must be used for all others -->
                                <div class="form-group" style="margin-left: 0px; margin-right: 0px;">
                                    <label class=""><?php echo $rp_iban; ?></label>
                                    <input type="text" class="form-control required"  maxlength='50' size='37' id="<?php echo $sPaymentMethod; ?>_pi_ratepay_rate_bank_iban" onchange="updateCalculator('<?php echo $sPaymentMethod; ?>')" name="rp-iban-account-number" style="display: block"/>
                                </div>
                                <!-- Bank code is only necesarry if account number (no iban) is set -->
                                <!--<div class="form-group" id="rp-form-bank-code"  style="margin-left: 0px; margin-right: 0px;">
                                    <label class="small">$rp_bank_code; ?></label>
                                    <input type="text" class="form-control" id="rp-bank-code" name="rp-bank-code" />
                                </div>-->
                            </form>
                        </div>
                    </div>
                    <!--<div class="rp-row-space small rp-sepa-form" id="rp-show-sepa-agreement">
                    <a class="rp-link"><?php echo $rp_sepa_link; ?></a>
                </div>-->
                    <div class="rp-row-space rp-sepa-form" id="<?php echo $sPaymentMethod; ?>_rp-sepa-agreement">
                        <input type="checkbox" name="rp-sepa-aggreement" id="<?php echo $sPaymentMethod; ?>_rp-sepa-aggreement" onchange="updateCalculator('<?php echo $sPaymentMethod; ?>')" class="form-check-input required" />
                        <?php echo $rp_sepa_terms_block_21; ?>
                        <br><br>
                        <?php echo $rp_sepa_terms_block_22; ?>
                        <br/><br/>
                        <?php echo $rp_sepa_terms_block_23; ?>
                    </div><br/>
                </div>
            </div>

            <?php
            if ($sSettlementType == 'both'): ?>
                <!-- Switching between payment type direct debit and bank transfer (which requires no sepa form) is only allowed if  -->
                <div id="<?php echo $sPaymentMethod; ?>_rp-switch-payment-type-direct-debit">
                    <strong class="rp-installment-header"><?php echo $rp_header_bank_transfer; ?></strong>
                    <div class="rp-payment-type-switch" onclick="rp_change_payment(2, '<?php echo $sPaymentMethod; ?>')">
                        <a class="rp-link"><?php echo $rp_switch_payment_type_direct_debit; ?></a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php } ?>
