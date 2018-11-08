<?php
namespace App\Model;

use Air\Database\Model\Model;

class User extends Model
{
    protected $driver = 'mongo';
    protected $database = 'demo';
    protected $table = 'users';
}