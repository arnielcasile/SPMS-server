<?php

namespace App;
use \DB;
use Illuminate\Database\Eloquent\Model;

class Forecast extends Model
{
    protected $table = 'forecast';
    protected $fillable = ['date_forecast', 'qty', 'area_code'];

    public function add_forecast($data)
    {
        return Forecast::create($data);
    }

    public function search_forecast($where)
    {
        return DB::table('forecast')
                ->where('date_forecast', $where['date_forecast'])
                ->where('area_code', $where['area_code'])
                ->get();
    }

    public function update_forecast($date, $area, $qty)
    {
        return Forecast::where('date_forecast', $date)
                ->where('area_code', $area)
                ->update(['qty' => $qty]);
    }
    
    public function load_data($where, $from, $to)
    {
        return Model::whereBetween('date_forecast', [$from, $to ])
                    ->where($where)
                    ->get();
    }
}
