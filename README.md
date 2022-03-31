# Ratepay GmbH - OXID6 Payment Module
============================================

|Module | Ratepay Payment Module for OXID6
|------|----------
|Author | `Fatchip GmbH`
|Prefix | `pi`
|Shop Version | `CE/PE` `CE/PE/EE` `6.0.x-6.4.x`
|Version | `6.1.0`
|Link | http://www.ratepay.com
|Mail | integration@ratepay.com
|Installation | https://ratepay.gitbook.io/oxid/
|Terms of service / Nutzungsbedingungen | https://www.ratepay.com/legal-payment-terms/
|Legal Disclaimer|https://ratepay.gitbook.io/docs/#legal-disclaimer

## Changelog

### Version 6.1.0 - Released 2022-01-11
* Fix: Bug while displaying logs entries, due to CamelCase/Lower case classnames incompatibility
* Update: Design enhancement in logs details display (syntax highlight, bold title)
* Update: Module tag version was made dynamic in xml request header

### Version 6.0.1 - Released 2021-04-08
* Added pre-calculation to estimate max valid runtime for 0% interest rate cases
* Extend width of installment calculator detail on order review page
* Fixed issue with plugin order
* Fixed SQL to target correct Ratepay details entries for older orders

### Version 6.0.0 - Released 2021-02-05
* Initial release of standalone OXID 6 plugin
