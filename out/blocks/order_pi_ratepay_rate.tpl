[{$smarty.block.parent}]
[{if $pi_payment->getId() == "pi_ratepay_rate"}]
    <link type="text/css" rel="stylesheet" href="out/modules/ratepay/Installment/css/style.css"/>
    <script type="text/javascript" src="[{$oViewConf->getModuleUrl('ratepay')}]Installment/js/path.js"></script>
    <script type="text/javascript" src="[{$oViewConf->getModuleUrl('ratepay')}]Installment/js/layout.js"></script>
    <script type="text/javascript" src="[{$oViewConf->getModuleUrl('ratepay')}]Installment/js/ajax.js"></script>
    <script type="text/javascript" src="[{$oViewConf->getModuleUrl('ratepay')}]Installment/js/mouseaction.js"></script>
    <div id="pi_ratepay_rate_pirpmain-cont">

    </div>
    <script type="text/javascript">
    if(document.getElementById('pi_ratepay_rate_pirpmain-cont')) {
        piLoadrateResult('pi_ratepay_rate');
        if (document.getElementsByClassName('rp-table-striped').length > 0) {
            document.getElementsByClassName('rp-table-striped')[0].style.width = '100%';
        }
    }
    </script>
[{/if}]
