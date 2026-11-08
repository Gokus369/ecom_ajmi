<?php

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

/** Shield's users, plus the name a customer signs up with. */
class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [...$this->allowedFields, 'name'];
    }
}
