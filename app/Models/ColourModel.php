<?php

namespace App\Models;

class ColourModel extends OptionListModel
{
    protected $table         = 'colours';
    protected $allowedFields = ['name', 'hex', 'sort_order'];

    protected string $variantColumn = 'colour_id';
}
