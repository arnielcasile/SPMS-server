<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DestinationMasterlist extends Model
{
    use SoftDeletes;

    protected $table = 'destination_masterlist';
    protected $fillable = ['payee_cd', 'payee_name', 'destination', 'attention_to', 'destination_class', 'purpose', 'pdl'];

    public function add_destination($data)
    {
        return DestinationMasterlist::create($data);
    }

    public function load_destination()
    {
        return DB::table('destination_masterlist')
                ->select('id','payee_cd','payee_name','destination','attention_to','destination_class','purpose','created_at','updated_at','deleted_at','pdl')
                ->get();
    }

    public function update_destination($data, $where)
    {
        return DestinationMasterlist::where($where)
                                    ->update($data);
    }

    public function search_destination($where)
    {
        return DestinationMasterlist::where($where)
                                    ->first();
    }

    public function soft_delete($where)
    {
        return DestinationMasterlist::where($where)->delete();
    }

    public function destination_exist($where)
    {
        return DestinationMasterlist::where($where)
                                    ->first();
    }

    public function update_status($id)
    {
        // return ('saomple');
        return DestinationMasterlist::withTrashed()
                    ->where('id', $id)
                    ->restore();
                    
    }
}
