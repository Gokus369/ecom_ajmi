<?php

namespace App\Models;

class SizeModel extends OptionListModel
{
    protected $table         = 'sizes';
    protected $allowedFields = ['name', 'sort_order'];

    protected string $variantColumn = 'size_id';
}
