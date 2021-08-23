<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;

class Remark extends Model
{
    use SoftDeletes;
    protected $fillable = ['issued_date', 'area_code', 'remarks', 'corrective_action'];

    public function add_remarks($data)
    {
        return Remark::create($data);
    }

    public function update_remarks($data, $where)
    {
        return Remark::where($where)
                    ->update($data);
    }

    public function load_remarks($where)
    {
        return DB::table('remarks')
                    ->select('area_code', 'remarks', 'corrective_action', 'id')
                    ->where($where)
                    ->where('deleted_at' , '=', null)
                    ->get();
    }

    public function soft_delete($where)
    {
        return Remark::where($where)->delete();
    }

}
