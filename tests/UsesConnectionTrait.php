<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests;

use GuzzleHttp\Client;
use RuntimeException;
use SergiyNezbritskiy\Ukrposhta\Connection;

trait UsesConnectionTrait
{
    private Connection $client;

    public function getConnection(): Connection
    {
        if (empty($this->client)) {
            $bearer = getenv('UKRPOSHTA_BEARER');
            $token = getenv('UKRPOSHTA_TOKEN');
            if (empty($bearer) || empty($token)) {
                throw new RuntimeException(
                    'UKRPOSHTA_BEARER and UKRPOSHTA_TOKEN not provided. '
                    . 'Please pass the sandbox credentials through phpunit.xml or env variables'
                );
            }
            $this->client = new Connection($bearer, $token, new Client(), [Connection::OPTION_SANDBOX => true]);
        }
        return $this->client;
    }

    /**
     * Waits for the sandbox to process the previous request, see GULP env variable
     *
     * @return void
     */
    private function gulp(): void
    {
        $gulp = (int)getenv('GULP');

        if ($gulp) {
            sleep($gulp);
        }
    }
}
