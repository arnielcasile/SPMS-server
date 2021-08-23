<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DeliveryTypeMasterlist extends Model
{
    use SoftDeletes;

    protected $table = 'delivery_type_masterlists';
    protected $fillable = 
                [
                    'delivery_type'
                ];
    protected $guarded = [];

    public function load_delivery_type()
    {
        return DeliveryTypeMasterlist::all();
    }

    public function insert($data)
    {
        return DeliveryTypeMasterlist::create($data);
    }

    public function search_delivery_type($where)
    {
        return DeliveryTypeMasterlist::where($where)
                                    ->first();
    }

    public function update_delivery_type($data, $where)
    {
        return DeliveryTypeMasterlist::where($where)
                                    ->update($data);
    }

    public function soft_delete($where)
    {
        return DeliveryTypeMasterlist::where($where)
                                    ->delete();
    }

    public function load_delivery_type_overall()
    {
        return DB::table('delivery_type_masterlists')
        ->select('id','delivery_type','created_at','updated_at','deleted_at')
        ->get();
    }

    public function active_delivery_type($id)
    {
        return DeliveryTypeMasterlist::withTrashed()
                    ->where('id', $id)
                    ->restore();
                    
    }
}
