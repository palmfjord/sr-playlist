<?php

namespace AppBundle\WebService\SR;

use AppBundle\WebService\SR\Responses\AllEpisodesResponse;
use AppBundle\WebService\SR\Responses\AllProgramsResponse;
use AppBundle\WebService\SR\Responses\Entities\Episode;
use AppBundle\WebService\SR\Responses\Entities\Program;
use Ci\RestClientBundle\Services\RestClient;
use JMS\Serializer\Serializer;


/**
 * Class SrWebServiceClient
 */
class SrWebServiceClient
{
    /** @var RestClient */
    protected $client;
    /** @var Serializer */
    protected $serializer;

    const API_BASE_URI = 'http://api.sr.se/api/v2/';

    /**
     * SrWebServiceClient constructor.
     *
     * @param RestClient $client
     * @param $serializer $serializer
     */
    public function __construct(RestClient $client, Serializer $serializer)
    {
        $this->client = $client;
        $this->serializer = $serializer;
    }

    /**
     * Get all programs/shows by SR
     *
     * @return Program[]
     */
    public function getAllPrograms() : array
    {
        $url = static::API_BASE_URI.'programs/index?format=json&size=100';

        $programs = $this->getObjectsFromPaginatedRequest($url, AllProgramsResponse::class);

        return $programs;
    }

    /**
     * Fetch all episodes from a program
     *
     * @param int $programId
     *
     * @return Episode[]
     */
    public function getAllEpisodesByProgramId(int $programId) : array
    {
        $url = static::API_BASE_URI.'episodes/index?programid='.urlencode($programId).'&format=json&size=100';

        $episodes = $this->getObjectsFromPaginatedRequest($url, AllEpisodesResponse::class);

        return $episodes;
    }

    /**
     * Fetches objects/entities (i.e. programs, episodes) from the paginated index
     *
     * @param string $url The url to fetch the objects from
     * @param string $targetClass The target response class to create
     * @param array $foundObjects Objects found on previous pages
     *
     * @return array
     */
    protected function getObjectsFromPaginatedRequest(
        string $url,
        string $targetClass,
        array $foundObjects = []
    ) : array
    {
        $rawResponse = $this->client->get($url);
        $deserializedResponse = $this->serializer->deserialize(
            $rawResponse->getContent(),
            $targetClass,
            'json'
        );

        $foundObjects = array_merge($foundObjects, $deserializedResponse->getEntities());

        if (
            $deserializedResponse->getPagination()->getPage() < $deserializedResponse->getPagination()->getTotalPages()
        ) {
            return $this->getObjectsFromPaginatedRequest(
                $deserializedResponse->getPagination()->getNextPage(),
                $targetClass,
                $foundObjects
            );
        }

        return $foundObjects;
    }
}