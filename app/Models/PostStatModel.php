<?php

namespace App\Models;

use App\Entities\PostStat;
use CodeIgniter\Model;

class PostStatModel extends Model
{
    protected $table         = 'post_stats';
    protected $primaryKey    = 'id';
    protected $returnType    = PostStat::class;
    protected $useTimestamps = true;
    protected $allowedFields = ['title', 'posted_on', 'saves', 'shares'];

    /**
     * @return list<PostStat> posts between two dates, the most saved and shared first
     */
    public function top(string $from, string $to, int $limit = 5): array
    {
        $posts = $this->where('posted_on >=', $from)->where('posted_on <=', $to)->findAll();

        usort($posts, static fn (PostStat $a, PostStat $b): int => $b->engagement <=> $a->engagement);

        return array_slice($posts, 0, $limit);
    }
}
