<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [''];
    public function insert($query){
        app('db')->insert($query);

    }

}