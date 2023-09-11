<?php

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
// -------------------------------
// RESOURCE IDENTITFIER = STRING
// -------------------------------
$sLangName = "Deutsch";

$piErrorAge = 'Um eine Zahlung per Ratepay SEPA-Lastschrift durchzuf&uuml;hren, m&uuml;ssen Sie mindestens 18 Jahre alt sein.';
$piErrorBirth = 'Um eine Zahlung per Ratepay SEPA-Lastschrift durchzuf&uuml;hren, geben Sie bitte Ihr Geburtsdatum ein.';
$piErrorPhone = 'Um eine Zahlung per Ratepay SEPA-Lastschrift durchzuf&uuml;hren, geben Sie bitte Ihre Telefonnummer ein.';
$piErrorCompany = 'Geben Sie bitte Ihren Firmennamen und Ihre Umsatzsteuer-ID ein.';
$piErrorBirthdayDigits = 'Geben Sie bitte Ihr Geburtsjahr vierstellig ein. (z.B. 1982)';

$aLang = [
    'charset'                                       => 'UTF-8',
    'PI_RATEPAY_ELV_VIEW_SANDBOX_NOTIFICATION'      => 'Testmodus aktiviert, bitte nutzen Sie diese Zahlart NICHT f&uuml;r die Bestellung und informieren den H&auml;ndler &uuml;ber diese Nachricht.',
    'PI_RATEPAY_ELV_VIEW_CREDITOR_ID_TEXT'          => 'Gl&auml;ubiger-ID',
    'PI_RATEPAY_ELV_VIEW_CREDITOR_ID_VALUE'         => 'DE39RPY00000568463',
    'PI_RATEPAY_ELV_VIEW_MANDATE_TEXT'              => 'Mandatsreferenz',
    'PI_RATEPAY_ELV_VIEW_MANDATE_VALUE'             => '(wird nach Kaufabschluss &uuml;bermittelt)',
    'PI_RATEPAY_ELV_VIEW_PRIVACY_AGREEMENT'         => 'Einwilligungserklärung zum SEPA-Mandat lesen',
    'PI_RATEPAY_ELV_VIEW_PRIVACY_AGREEMENT_TEXT_1'  => 'Ich erm&auml;chtige die Ratepay GmbH, Zahlungen von meinem Konto mittels Lastschrift einzuziehen. Zugleich weise ich mein Kreditinstitut an, die von der Ratepay GmbH auf mein Konto gezogenen Lastschriften einzul&ouml;sen.',
    'PI_RATEPAY_ELV_VIEW_PRIVACY_AGREEMENT_TEXT_2'  => 'Hinweis: Ich kann innerhalb von acht Wochen, beginnend mit dem Belastungsdatum, die Erstattung des belasteten Betrages verlangen. Es gelten dabei die mit meinem Kreditinstitut vereinbarten Bedingungen.',
    'PI_RATEPAY_ELV_VIEW_POLICY_OWNERPOLICY'        => 'H&auml;ndler-Datenschutzerkl&auml;rung',
    'PI_RATEPAY_ELV_VIEW_POLICY_TEXT_6'             => ' und bin insbesondere damit einverstanden, zum Zwecke der Durchf&uuml;hrung des Vertrags &uuml;ber die von mir angegebene E-Mail-Adresse kontaktiert zu werden.',
    'PI_RATEPAY_ELV_VIEW_POLICY_PRIVACYPOLICY'      => 'Ratepay-Datenschutzerkl&auml;rung',
    'PI_RATEPAY_ELV_ERROR'                          => 'Leider ist eine Bezahlung mit Ratepay nicht m&ouml;glich. Diese Entscheidung ist von Ratepay auf der Grundlage einer automatisierten Datenverarbeitung getroffen worden. Einzelheiten erfahren Sie in der ',
    'PI_RATEPAY_ELV_AGBERROR'                       => 'Bitte akzeptieren Sie die Bedingungen.',
    'PI_RATEPAY_ELV_SUCCESS'                        => 'Bestellung erfolgreich abgeschlossen',
    'PI_RATEPAY_ELV_ERROR_ADDRESS'                  => 'Bitte beachten Sie, dass Ratepay SEPA-Lastschrift nur genutzt werden kann, wenn Rechnungs- und Lieferaddresse identisch sind.',
    'PI_RATEPAY_ELV_ERROR_ZIP'                      => 'Bitte geben Sie Ihre korrekte Postleitzahl ein.',
    'PI_RATEPAY_ELV_ERROR_BIRTH'                    => $piErrorBirth,
    'PI_RATEPAY_ELV_ERROR_PHONE'                    => $piErrorPhone,
    'PI_RATEPAY_ELV_ERROR_AGE'                      => $piErrorAge,
    'PI_RATEPAY_ELV_VIEW_PAYMENT_FON'               => 'Telefon:',
    'PI_RATEPAY_ELV_VIEW_PAYMENT_MOBILFON'          => 'Mobiltelefon:',
    'PI_RATEPAY_ELV_VIEW_PAYMENT_BIRTHDATE'         => 'Geburtsdatum:',
    'PI_RATEPAY_ELV_VIEW_PAYMENT_BIRTHDATE_FORMAT'  => '(tt.mm.jjjj)',
    'PI_RATEPAY_ELV_VIEW_PAYMENT_FON_NOTE'          => 'Tragen Sie bitte entweder Ihr Telefon oder Mobiltelefon ein.',
    'PI_RATEPAY_ELV_VIEW_PAYMENT_COMPANY'           => 'Firma:',
    'PI_RATEPAY_ELV_VIEW_PAYMENT_UST'               => 'USt-IdNr.',
    'PI_RATEPAY_ERROR_BIRTHDAY_YEAR_DIGITS'         => $piErrorBirthdayDigits,
    'PI_RATEPAY_ERROR_COMPANY'                      => $piErrorCompany,
    'PI_RATEPAY_ELV_ERROR_OWNER'                    => 'Um eine Zahlung per Ratepay SEPA-Lastschrift durchzuf&uuml;hren, geben Sie bitte den Namen des Kontoinhabers ein.',
    'PI_RATEPAY_ELV_ERROR_ACCOUNT_NUMBER'           => 'Bitte geben Sie eine korrekte IBAN/Kontonummer ein.',
    'PI_RATEPAY_ELV_ERROR_NAME'                     => 'Um eine Zahlung per Ratepay SEPA-Lastschrift durchzuf&uuml;hren, geben Sie bitte den Banknamen ein.',
    'PI_RATEPAY_ELV_VIEW_BANK_OWNER'                => 'Kontoinhaber',
    'PI_RATEPAY_ELV_VIEW_BANK_ACCOUNT_NUMBER'       => 'Kontonummer',
    'PI_RATEPAY_ELV_VIEW_BANK_IBAN'                 => 'IBAN',
    'PI_RATEPAY_ELV_VIEW_BANK_NAME'                 => 'Kreditinstitut',
    'PI_RATEPAY_ELV_ERROR_BANKCODE_TO_SHORT'        => 'Die Bankleitzahl muss acht Zeichen lang sein.',
    'PI_RATEPAY_ELV_VIEW_BANK_CODE'                 => "BLZ:",
    'PI_RATEPAY_ELV_SEPA_ACCOUNT_INFORMATION'       => 'SEPA Kontodaten',
    'PI_RATEPAY_ELV_CLASSIC_ACCOUNT_INFORMATION'    => 'Klassische Kontodaten',
];
