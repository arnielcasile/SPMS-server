<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


class Areas extends Model
{
    use SoftDeletes;

    protected $table = 'area';
    protected $fillable = ['area_code'];
    protected $guarded = [];

    public function load_area_code()
    {
        return Areas::all();
    }
    
    public function add_area_code($data)
    {
        return Areas::create($data);
    }

    public function search_area_code($where)
    {
        return Areas::where($where)
                    ->first();
    }

    public function update_area_code($data, $where)
    {
        return Areas::where($where)
                    ->update($data);
    }

    public function soft_delete($where)
    {
        return Areas::where($where)
                    ->delete();
    }

    public function update_status($id)
    {
        return Areas::withTrashed()
                    ->where('id', $id)
                    ->restore();
                    
    }
    public function load_area_code_for_restore()
    {
        return DB::table('area')
        ->select('id','area_code','created_at','updated_at','deleted_at')
        ->orderBy('id', 'desc')
        ->get();
    }
}
