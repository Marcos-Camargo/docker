<?php

namespace Integration_v2\viavarejo_b2b\Resources\Parsers\DTO;

class AvailabilityDTO
{
    public $Codigo;
    public $PrecoDe;
    public $PrecoPor;
    public $Disponibilidade;
    public $IdCampanha;
    public $IdLojista;
    public $TiposEntrega = [];


    public function __construct(
        $codigo,
        $precoDe,
        $precoPor,
        $disponibilidade,
        $idCampanha,
        $idLojista,
        $tiposEntrega = []
    )
    {
        $this->Codigo = $codigo;
        $this->PrecoDe = $precoDe;
        $this->PrecoPor = $precoPor;
        $this->Disponibilidade = $disponibilidade;
        $this->IdCampanha = $idCampanha;
        $this->IdLojista = $idLojista;
        $this->TiposEntrega = $tiposEntrega;
    }
}