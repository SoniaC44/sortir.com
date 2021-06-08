<?php


namespace App\Data;


use App\Entity\Campus;

/**
 * Class RechercheData
 * @package App\Data
 */
class RechercheData
{

    public $mot;

    public ?Campus $campus = null;

    public $dateMin;

    public $dateMax;

    public $passee;

    public $inscrit;

    public $nonInscrit;

    public $organisee;

    public $user;


}