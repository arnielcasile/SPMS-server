<?php

namespace App;
use \DB;
use Illuminate\Database\Eloquent\Model;

class Timeout extends Model
{
    //
    protected $table = 'time_in_out';
    protected $fillable = ['date', 'time_in', 'time_out', 'area_code'];

    public function load_data($where, $from, $to)
    {
        return Model::whereBetween('date', [$from, $to ])
                    ->where($where)
                    ->get();
    }

    public function add_timeout($data)
    {
        return Timeout::create($data);
    }

    public function search_timeout($where)
    {
        return DB::table('time_in_out')
                ->where('date', $where['date'])
                ->where('area_code', $where['area_code'])
                ->count();
    }

    public function update_timeout($data,$date,$area)
    {
        return Timeout::where('date', $date)
                ->where('area_code', $area)
                ->update($data);
    }
}
