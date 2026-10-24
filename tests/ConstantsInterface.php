<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests;

interface ConstantsInterface
{
    /**
     * Sender address, see tmp/documentation.pdf
     */
    public const SENDER_ADDRESS = [
        'postcode' => '07401',
        'country' => 'UA',
        'region' => 'Київська',
        'district' => 'Київський',
        'city' => 'Бровари',
        'street' => 'Котляревського',
        'houseNumber' => '12',
        'apartmentNumber' => '33',
    ];

    public const RECIPIENT_ADDRESS = [
        'postcode' => '04071',
        'country' => 'UA',
        'region' => 'Київ',
        'district' => 'Подільський',
        'city' => 'Київ',
        'street' => 'Хорива',
        'houseNumber' => '40',
        'apartmentNumber' => '20',
    ];

    public const SENDER = [
        'firstName' => 'Петро',
        'middleName' => 'Петрович',
        'lastName' => 'Петренко',
        'phoneNumber' => '0671231234',
        'email' => 'sender@example.com',
    ];

    public const RECIPIENT = [
        'firstName' => 'Іван',
        'middleName' => 'Іванович',
        'lastName' => 'Іванов',
        'phoneNumber' => '0981231234',
        'email' => 'recipient@example.com',
    ];

    public const COMPANY = [
        'name' => 'ТОВ Лімон Банк',
        'edrpou' => '40145721',
        'phoneNumber' => '0671231234',
        'email' => 'company@example.com',
        'bankAccount' => 'UA073808050000000026000439806',
        'resident' => true,
    ];

    public const SHIPMENT_DESCRIPTION = 'ЦЕ ТЕСТОВЕ ВІДПРАВЛЕННЯ, В ЖОДНОМУ РАЗІ НЕ ОБРОБЛЯТИ !!!';
}
