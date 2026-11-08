<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/** One Instagram post's saves and shares, entered by hand from the app. */
class PostStat extends Entity
{
    protected $casts = [
        'id'     => 'int',
        'saves'  => 'int',
        'shares' => 'int',
    ];

    /** Read as $post->engagement */
    public function getEngagement(): int
    {
        return $this->saves + $this->shares;
    }
}
