<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $table = 'process_masterlists';

    public function load_all_process()
    {
        return Model::All();
    }

    public function load_process($where)
    {   
        return Model::where('id', $where)
                    ->first();
    }
}
