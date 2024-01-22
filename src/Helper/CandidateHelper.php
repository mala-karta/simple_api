<?php

namespace App\Helper;

use App\Entity\Candidate;

class CandidateHelper
{
    private const TOP_JUNIOR = 5000;

    private const TOP_REGULAR = 10000;

    public static function getLevelBySalary(int $salary): string
    {
        if ($salary < self::TOP_JUNIOR) {
            return Candidate::LEVEL_JUNIOR;
        }
        if ($salary < self::TOP_REGULAR) {
            return Candidate::LEVEL_REGULAR;
        }

        return Candidate::LEVEL_SENIOR;
    }
}