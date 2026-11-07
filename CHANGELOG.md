# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-11-07

### Added

- `Models\Classifier` (`$client->classifier`): address classifier requests `getRegions()`, `getPostOffices()`
  and `getNearestPostOffices()`, returning the list of entries of the response.
- `Connection::getClassifier()` for the address classifier base URL (`https://www.ukrposhta.ua/address-classifier-ws/`),
  authorized with the bearer only, and the `classifier_uri` client option overriding that URL.
- `Connection::buildClassifierOptions()` building the request options without the counterparty `token`.

## [1.0.0] - 2025-10-24

### Added

- Initial release: addresses, clients, shipments, shipment groups and forms of the eCom API.
