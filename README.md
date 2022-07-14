# PHP CEIDG data warehouse library

This library allows you to integrate the "CEIDG data warehouse" with your system written in PHP

This tool gives you the ability to quickly download the data you are interested in using built-in methods. You will be able to create entire data sets according to your needs.

Please refer to the official CEDIG data warehouse API documentation at [(https://akademia.biznes.gov.pl/dokumentacja-api-v2/)](https://akademia.biznes.gov.pl/dokumentacja-api-v2/)  to find out all possible data that you can obtain using our tool.


## Installation

As simple as it can be:
```bash
composer require dominservice/ceidg_data_warehouse
```

## Usage

```php
use Dominservice\CeidgDataWarehouse\Ceidg;

$ceidgApi = new Ceidg('[your_token]');
```
token you make get from [https://www.biznes.gov.pl/pl/e-uslugi/00_9999_00](https://www.biznes.gov.pl/pl/e-uslugi/00_9999_00)

```php
/** criteria make by array or strung key */
$ceidgApi->setCriteria($criteria, $value = null)
/** or shortly */
$ceidgApi->setDateFrom('YYY-MM-DD');
$ceidgApi->setDateTo('YYY-MM-DD');
$ceidgApi->setNIP('0000000000');
$ceidgApi->setNIP_SC('0000000000');
$ceidgApi->setREGON('000000000');
$ceidgApi->setREGON_SC('000000000');
$ceidgApi->setFirstname('Marian');
$ceidgApi->setLastname('Paździch');
$ceidgApi->setName('Paźdzochmania S.A.');
$ceidgApi->setStreet('Ćwiartki');
$ceidgApi->setBuilding('4');
$ceidgApi->setFlat('5');
$ceidgApi->setCity('Wrocław');
$ceidgApi->setVoivodship('Dolnośląskie');
$ceidgApi->setDistrict('Wrocławski');
$ceidgApi->setCommune('Wrocław');
$ceidgApi->setPostcode('00-000');
$ceidgApi->setPKD(['[pkd_list_in_array]']);
$ceidgApi->setStatus($status);

$ceidgApi->getCompanies($getAll = false, $getFullCompanyData = false)

$ceidgApi->clearCriteria();

```