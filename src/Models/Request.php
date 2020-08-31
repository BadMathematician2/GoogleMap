<?php


namespace GoogleMap\Models;


use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'requests';

    protected $guarded = ['id'];
}
