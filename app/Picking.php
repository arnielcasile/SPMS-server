<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Picking extends Model
{
    protected $table = 'picking';
    protected $fillable = ['picking_date', 'picker_count', 'area_code'];

    public function load_data($where, $from, $to)
    {
        return Model::whereBetween('picking_date', [$from, $to])
                    ->where($where)
                    ->get();
    }
 
    public function add_picking($data)
    {
        return Picking::create($data);
    }

    public function update_picking($where, $data)
    {
        return Picking::where('picking_date',$where)
                ->update($data);
    }

    public function search_picking($where)
    {
        return DB::table('picking')
                ->where('picking_date', $where['picking_date'])
                ->where('area_code', $where['area_code'])
                ->get();
    }
}
