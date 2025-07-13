<?php

namespace Integration_v2\viavarejo_b2b\Resources\Parsers\DTO;

class AvailabilityCollectionDTO
{
    /**
     * @var AvailabilityDTO[]
     */
    public $Skus = [];

    public function __construct()
    {
        $this->Skus = [];
    }

    public function add(AvailabilityDTO $availabilityDTO)
    {
        array_push($this->Skus, $availabilityDTO);
    }
}