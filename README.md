# Ukrposhta API

Library for connecting your PHP application with Ukrposhta REST API

[![codecov](https://codecov.io/gh/sergiynezbritskiy/ukrposhta-api/branch/main/graph/badge.svg)](https://codecov.io/gh/sergiynezbritskiy/ukrposhta-api)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sergiynezbritskiy/ukrposhta-api/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/sergiynezbritskiy/ukrposhta-api/?branch=main)
[![Build Status](https://scrutinizer-ci.com/g/sergiynezbritskiy/ukrposhta-api/badges/build.png?b=main)](https://scrutinizer-ci.com/g/sergiynezbritskiy/ukrposhta-api/build-status/main)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sergiynezbritskiy/ukrposhta-api/badges/code-intelligence.svg?b=main)](https://scrutinizer-ci.com/code-intelligence)

[![Latest Stable Version](http://poser.pugx.org/sergiynezbritskiy/ukrposhta-api/v)](https://packagist.org/packages/sergiynezbritskiy/ukrposhta-api)
[![Total Downloads](http://poser.pugx.org/sergiynezbritskiy/ukrposhta-api/downloads)](https://packagist.org/packages/sergiynezbritskiy/ukrposhta-api)
[![Latest Unstable Version](http://poser.pugx.org/sergiynezbritskiy/ukrposhta-api/v/unstable)](https://packagist.org/packages/sergiynezbritskiy/ukrposhta-api)
[![License](http://poser.pugx.org/sergiynezbritskiy/ukrposhta-api/license)](https://packagist.org/packages/sergiynezbritskiy/ukrposhta-api)
[![PHP Version Require](http://poser.pugx.org/sergiynezbritskiy/ukrposhta-api/require/php)](https://packagist.org/packages/sergiynezbritskiy/ukrposhta-api)

## Installation

The easiest way to install module is using Composer

```
composer require sergiynezbritskiy/ukrposhta-api
```

## Configuration

In order to use this library you need two credentials issued by Ukrposhta after signing the eCom contract
(ask your Ukrposhta manager, there is no self-service registration):

- **Bearer eCom** - sent in the `Authorization: Bearer` header;
- **Counterparty token** - sent in the `token` query parameter.

Ukrposhta provides a separate pair of credentials for the sandbox (`dev.ukrposhta.ua`), pass `'sandbox' => true`
in the options to use it.

## Usage

Using library is as easy as possible

```php
//create client for connecting with API
$client = new \SergiyNezbritskiy\Ukrposhta\Client('<your_bearer>', '<your_token>');
//or for the sandbox
$client = new \SergiyNezbritskiy\Ukrposhta\Client('<sandbox_bearer>', '<sandbox_token>', ['sandbox' => true]);

//1. create addresses
$senderAddress = $client->address->save(['postcode' => '07401', 'region' => 'Київська', 'city' => 'Бровари', /* ... */]);
$recipientAddress = $client->address->save(['postcode' => '04071', 'region' => 'Київ', 'city' => 'Київ', /* ... */]);

//2. create sender and recipient
$sender = $client->client->save([
    'type' => \SergiyNezbritskiy\Ukrposhta\Models\Client::TYPE_INDIVIDUAL,
    'firstName' => 'Петро',
    'lastName' => 'Петренко',
    'phoneNumber' => '0671231234',
    'addressId' => $senderAddress['id'],
]);
$recipient = $client->client->save([/* ... */ 'addressId' => $recipientAddress['id']]);

//3. create shipment
$shipment = $client->shipment->save([
    'sender' => ['uuid' => $sender['uuid']],
    'recipient' => ['uuid' => $recipient['uuid']],
    'deliveryType' => \SergiyNezbritskiy\Ukrposhta\Models\Shipment::DELIVERY_TYPE_W2D,
    'paidByRecipient' => true,
    'parcels' => [['weight' => 1200, 'length' => 170]],
]);

//4. print the sticker
file_put_contents('sticker.pdf', $client->forms->getShipmentSticker($shipment['uuid']));
```

Every other option from the request body of the corresponding endpoint can be passed through the arrays,
see the PHPDoc of the model methods and the Swagger below.

### Address classifier

The address classifier returns the regions and the post offices (branches), e.g. to let a customer pick a branch:

```php
//1. find the region, the response is a list of matches by the part of the name
$regions = $client->classifier->getRegions(['region_name' => 'Київ']); // or ['region_name_en' => 'Kyiv']
// [['REGION_ID' => '286', 'REGION_UA' => 'Київ', 'REGION_EN' => 'Kyiv', ...], ['REGION_ID' => '270', 'REGION_UA' => 'Київська', ...]]

//2. get all the post offices of the region (optionally narrowed down with district_id, city_id or postindex)
$postOffices = $client->classifier->getPostOffices(['region_id' => 286]);
// [['ID' => '266', 'PO_SHORT' => 'Київ 26', 'POSTINDEX' => '03026', 'ADDRESS' => 'вул. Заболотного Академіка, 37', 'LOCK_CODE' => '0', ...], ...]

//3. or find the post offices near the given coordinates, within 2 km
$nearest = $client->classifier->getNearestPostOffices(50.450166, 30.523307, 2);
```

The classifier is authorized with the bearer only and has no sandbox host, so `https://www.ukrposhta.ua` is used
regardless of the `sandbox` option. Pass `'classifier_uri' => 'https://...'` in the client options to override it.
Post offices with `LOCK_CODE` other than `Classifier::LOCK_CODE_ACTIVE` are blocked.

## Notes

For more details please visit **[documentation](https://dev.ukrposhta.ua/documentation)**
and **[Swagger](https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0)**
