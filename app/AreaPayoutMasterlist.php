<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AreaPayoutMasterlist extends Model
{
    use SoftDeletes;
    protected $fillable = 
        [
            'area_payout'
        ];
    protected $table = 'area_payout_masterlists';

    public function insert($data)
    {
        return AreaPayoutMasterlist::create($data);
    }

    public function retrieve_all()
    {
        return AreaPayoutMasterlist::all();
    }

    public function retrieve_one($where)
    {
        return AreaPayoutMasterlist::where($where)
                                    ->first();
    }

    public function update_area_payout($data,$where)
    {
        return AreaPayoutMasterlist::where($where)
                                ->update($data);
    }

    public function soft_delete($where)
    {
        return AreaPayoutMasterlist::where($where)->delete();
    }
}
