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

## Notes

For more details please visit **[documentation](https://dev.ukrposhta.ua/documentation)**
and **[Swagger](https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0)**
