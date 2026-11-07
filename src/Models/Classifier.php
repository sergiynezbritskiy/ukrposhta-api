<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Models;

use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\ModelInterface;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Class Classifier
 *
 * Address classifier: regions, post offices (branches) and their lookup by coordinates.
 * Every method returns the list of entries (`Entries.Entry` of the response); all the values are strings or null.
 *
 * @see https://dev.ukrposhta.ua/documentation Address Classifier documentation (PDF)
 */
class Classifier implements ModelInterface
{
    /** Post office interlock codes, see the LOCK_CODE field */
    public const LOCK_CODE_ACTIVE = '0';
    public const LOCK_CODE_BLOCKED = '65535';

    private const REGIONS_URI = 'get_regions_by_region_ua';
    private const POST_OFFICES_URI = 'get_postoffices_by_city_id';
    private const NEAREST_POST_OFFICES_URI = 'get_postoffices_by_geolocation';

    private Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the regions (oblasts) matching the given part of the name, at least one filter is required
     *
     * @param array $filters
     *    $filters = [
     *        'region_name'    => (string) Part of the region name in Ukrainian, e.g. 'Київ'. Optional.
     *        'region_name_en' => (string) Part of the region name in English, e.g. 'Kyiv'. Optional.
     *    ];
     * @return array list of entries with the keys REGION_ID, REGION_UA, REGION_EN, REGION_RU,
     *     REGION_KOATUU, REGION_KATOTTG
     * @throws UkrposhtaApiException
     */
    public function getRegions(array $filters): array
    {
        if (empty($filters['region_name']) && empty($filters['region_name_en'])) {
            throw new UkrposhtaApiException('Either region_name or region_name_en filter is required');
        }
        return $this->unwrap($this->connection->getClassifier(self::REGIONS_URI, $filters));
    }

    /**
     * Get the post offices of a region, optionally narrowed down to a district, a city or a post office zip code
     *
     * @param array $filters
     *    $filters = [
     *        'region_id'   => (int|string) Region ID, see self::getRegions(). Required.
     *        'district_id' => (int|string) District ID. Optional.
     *        'city_id'     => (int|string) Locality ID. Optional.
     *        'postindex'   => (string) Post office zip code. Optional.
     *    ];
     * @return array list of entries with the keys ID, PO_SHORT, PO_LONG, POSTINDEX, TECHINDEX, PO_CODE, ADDRESS,
     *     TYPE_SHORT, TYPE_LONG, TYPE_ACRONYM, REGION_ID, REGION_UA, REGION_EN, DISTRICT_ID, DISTRICT_UA, DISTRICT_EN,
     *     CITY_ID, CITY_UA, CITY_EN, CITYTYPE_UA, SHORTCITYTYPE_UA, STREET_UA, STREET_EN, STREETTYPE_UA, HOUSENUMBER,
     *     PHONE, LATTITUDE (sic), LONGITUDE, LOCK_CODE (see self::LOCK_CODE_*), LOCK_UA, ISVPZ, IS_AUTOMATED,
     *     IS_SECURITY, IS_NOLETTERS, IS_CASH, IS_DHL, IS_SMARTBOX, IS_FLAGMAN, POSTTERMINAL, PELPEREKAZY, PARENT_ID,
     *     MEREZA_NUMBER
     * @throws UkrposhtaApiException
     */
    public function getPostOffices(array $filters): array
    {
        if (empty($filters['region_id'])) {
            throw new UkrposhtaApiException('The region_id filter is required');
        }
        return $this->unwrap($this->connection->getClassifier(self::POST_OFFICES_URI, $filters));
    }

    /**
     * Get the post offices within the given radius from the coordinates
     *
     * @param float $latitude e.g. 50.450166
     * @param float $longitude e.g. 30.523307
     * @param int $maxDistanceKm search radius, km
     * @return array list of entries with the keys ID, POSTFILIALNAME, POSTINDEX, POSTCODE, ADDRESS, CITYNAME,
     *     DISTANCE (km), LATITUDE, LONGITUDE
     * @throws UkrposhtaApiException
     */
    public function getNearestPostOffices(float $latitude, float $longitude, int $maxDistanceKm): array
    {
        $query = ['lat' => $latitude, 'long' => $longitude, 'maxdistance' => $maxDistanceKm];
        return $this->unwrap($this->connection->getClassifier(self::NEAREST_POST_OFFICES_URI, $query));
    }

    /**
     * Extract the list of entries from the response envelope, a single entry may be returned as an object
     *
     * @param array $response
     * @return array
     */
    private function unwrap(array $response): array
    {
        $entries = $response['Entries']['Entry'] ?? [];
        if (!is_array($entries)) {
            return [];
        }
        return $entries === [] || isset($entries[0]) ? $entries : [$entries];
    }
}
