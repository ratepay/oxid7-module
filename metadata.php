<?php

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Metadata version
 */
$sMetadataVersion = '2.1';

/**
 * Module information
 */
$aModule = [
    'id' => 'ratepay',
    'title' => 'Ratepay',
    'description' => [
        'de' => 'Bezahlung mit Ratepay',
        'en' => 'Payment with Ratepay'
    ],
    'thumbnail' => 'ratepay_logo.png',
    'lang' => 'en',
    'version' => '7.0.0',
    'author' => 'Ratepay GmbH',
    'email' => 'integration@ratepay.com',
    'url' => 'https://www.ratepay.com/',
    'extend' => [
        // Extend controllers
        \OxidEsales\Eshop\Application\Controller\Admin\ModuleConfiguration::class => \pi\ratepay\Extend\Application\Controller\Admin\RatepayModuleConfig::class,
        \OxidEsales\Eshop\Application\Controller\OrderController::class => \pi\ratepay\Extend\Application\Controller\RatepayOrder::class,
        \OxidEsales\Eshop\Application\Controller\PaymentController::class => \pi\ratepay\Extend\Application\Controller\RatepayPayment::class,

        // Extend model
        \OxidEsales\Eshop\Application\Model\Order::class => \pi\ratepay\Extend\Application\Model\RatepayOxorder::class,
        \OxidEsales\Eshop\Application\Model\PaymentGateway::class => \pi\ratepay\Extend\Application\Model\RatepayPaymentGateway::class,
    ],
    'controllers' => [
        'RatepayAdminListBase' => \pi\ratepay\Application\Controller\Admin\AdminListBase::class,
        'RatepayAdminViewBase' => \pi\ratepay\Application\Controller\Admin\AdminViewBase::class,
        'RatepayDetails' => \pi\ratepay\Application\Controller\Admin\Details::class,
        'RatepayLog' => \pi\ratepay\Application\Controller\Admin\Log::class,
        'ratepayloglist' => \pi\ratepay\Application\Controller\Admin\LogList::class,
        'RatepayLogMain' => \pi\ratepay\Application\Controller\Admin\LogMain::class,
        'RatepayProfile' => \pi\ratepay\Application\Controller\Admin\Profile::class,
        'ratepayprofilelist' => \pi\ratepay\Application\Controller\Admin\ProfileList::class,
        'RatepayProfileMain' => \pi\ratepay\Application\Controller\Admin\ProfileMain::class,
        'RatepayRate0Calc' => \pi\ratepay\Application\Controller\Rate0Calc::class,
        'RatepayRateCalc' => \pi\ratepay\Application\Controller\RateCalc::class,
        'RatepayModuleConfig' => \pi\ratepay\Extend\Application\Controller\Admin\RatepayModuleConfig::class,
        'RatepayOrder' => \pi\ratepay\Extend\Application\Controller\RatepayOrder::class,
        'RatepayPayment' => \pi\ratepay\Extend\Application\Controller\RatepayPayment::class,
    ],
    'events' => [
        'onActivate' => \pi\ratepay\Core\Events::class . '::onActivate',
        'onDeactivate' => \pi\ratepay\Core\Events::class . '::onDeactivate',
    ],
    'settings' => [
        // ratepay general
        ['group' => 'PI_RATEPAY_GENERAL', 'name' => 'blRPLogging', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GENERAL', 'name' => 'blRPAutoPaymentConfirm', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GENERAL', 'name' => 'sRPDeviceFingerprintSnippetId', 'type' => 'str', 'value' => ''],
        // ratepay germany invoice
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'blRPInvoiceActive', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'blRPInvoiceSandbox', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'sRPInvoiceProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'sRPInvoiceSecret', 'type' => 'str', 'value' => ''],
        // ratepay germany installment
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'blRPInstallmentActive', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'blRPInstallmentSandbox', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'sRPInstallmentProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'sRPInstallmentSecret', 'type' => 'str', 'value' => ''],
        // ratepay germany installment 0%
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'blRPInstallment0Active', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'blRPInstallment0Sandbox', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'sRPInstallment0ProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'sRPInstallment0Secret', 'type' => 'str', 'value' => ''],
        // ratepay germany elv
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'blRPElvActive', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'blRPElvSandbox', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'sRPElvProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_GERMANY', 'name' => 'sRPElvSecret', 'type' => 'str', 'value' => ''],
        // ratepay austria invoice
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'blRPAustriaInvoice', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'blRPAustriaInvoiceSandbox', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'sRPAustriaInvoiceProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'sRPAustriaInvoiceSecret', 'type' => 'str', 'value' => ''],
        // ratepay austria installment
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'blRPAustriaInstallment', 'type' => 'bool', 'value' => false],
        [
            'group' => 'PI_RATEPAY_AUSTRIA',
            'name' => 'blRPAustriaInstallmentSandbox',
            'type' => 'bool',
            'value' => false
        ],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'sRPAustriaInstallmentProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'sRPAustriaInstallmentSecret', 'type' => 'str', 'value' => ''],
        // ratepay austria installment 0%
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'blRPAustriaInstallment0', 'type' => 'bool', 'value' => false],
        [
            'group' => 'PI_RATEPAY_AUSTRIA',
            'name' => 'blRPAustriaInstallment0Sandbox',
            'type' => 'bool',
            'value' => false
        ],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'sRPAustriaInstallment0ProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'sRPAustriaInstallment0Secret', 'type' => 'str', 'value' => ''],
        // ratepay austria elv
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'blRPAustriaElv', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'blRPAustriaElvSandbox', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'sRPAustriaElvProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_AUSTRIA', 'name' => 'sRPAustriaElvSecret', 'type' => 'str', 'value' => ''],
        // ratepay switzerland invoice
        ['group' => 'PI_RATEPAY_SWITZERLAND', 'name' => 'blRPSwitzerlandInvoice', 'type' => 'bool', 'value' => false],
        [
            'group' => 'PI_RATEPAY_SWITZERLAND',
            'name' => 'blRPSwitzerlandInvoiceSandbox',
            'type' => 'bool',
            'value' => false
        ],
        [
            'group' => 'PI_RATEPAY_SWITZERLAND',
            'name' => 'sRPSwitzerlandInvoiceProfileId',
            'type' => 'str',
            'value' => ''
        ],
        ['group' => 'PI_RATEPAY_SWITZERLAND', 'name' => 'sRPSwitzerlandInvoiceSecret', 'type' => 'str', 'value' => ''],
        // ratepay netherland invoice
        ['group' => 'PI_RATEPAY_NETHERLAND', 'name' => 'blRPNetherlandInvoice', 'type' => 'bool', 'value' => false],
        [
            'group' => 'PI_RATEPAY_NETHERLAND',
            'name' => 'blRPNetherlandInvoiceSandbox',
            'type' => 'bool',
            'value' => false
        ],
        ['group' => 'PI_RATEPAY_NETHERLAND', 'name' => 'sRPNetherlandInvoiceProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_NETHERLAND', 'name' => 'sRPNetherlandInvoiceSecret', 'type' => 'str', 'value' => ''],
        // ratepay netherland elv
        ['group' => 'PI_RATEPAY_NETHERLAND', 'name' => 'blRPNetherlandElv', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_NETHERLAND', 'name' => 'blRPNetherlandElvSandbox', 'type' => 'bool', 'value' => false],
        ['group' => 'PI_RATEPAY_NETHERLAND', 'name' => 'sRPNetherlandElvProfileId', 'type' => 'str', 'value' => ''],
        ['group' => 'PI_RATEPAY_NETHERLAND', 'name' => 'sRPNetherlandElvSecret', 'type' => 'str', 'value' => ''],
    ],
];
