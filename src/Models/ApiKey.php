<?php


namespace GoogleMap\Models;


use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $guarded = ['id'];
}
