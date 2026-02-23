<?php

namespace App\Search;

use FOS\ElasticaBundle\Finder\TransformedFinder;
use App\Entity\User;

class UserFinder extends TransformedFinder
{
    public function __construct(TransformedFinder $innerFinder)
    {
        parent::__construct($innerFinder);
    }
}
