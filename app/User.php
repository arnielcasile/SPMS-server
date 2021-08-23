<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;
    public $timestamps = true;
    use softDeletes;

    protected $fillable = 
        [
            'employee_number',
            'last_name',
            'first_name',
            'middle_name',
            'photo',
            'email',
            'position',
            'status',
            'section',
            'section_code',
            'area_id',
            'user_type_id',
            'process',
            'approver',
            'support'
        ];

    public function insert_user($data)
    {
        $result = User::create($data);
        $where = 
            [
                'a.status' => 1, 'a.id' => $result->id
            ];
        return  $this->load_one($where);

    }

    public function retrieve_one($where)
    {
        // return User::where($where)
        //             ->first();
        return DB::table('users')
            ->select('id','employee_number','last_name','first_name','middle_name','photo','email','position','status','section','section_code','area_id','user_type_id','created_at','updated_at','deleted_at','process','approver','support','receiver')
            ->where($where)
            ->first();
    }

    public function retrive_area_code($where)
    {
    return DB::table('users as a')
                ->join('area as b', 'a.area_id','b.id')
                ->select('a.deleted_at','a.user_type_id','b.area_code')
                ->where($where)
                ->get();
    }

    public function load_one($where)
    {
        return DB::table('users as a')
                    ->leftjoin('area as b', 'a.area_id', '=', 'b.id')
                    ->leftjoin('user_type as c', 'c.id', '=', 'a.user_type_id')
                    ->select('a.*', 'b.area_code', 'c.user_type')
                    ->where($where)
                    ->whereNull('a.deleted_at')
                    ->orderBy('a.id')
                    ->get();
    }

    public function load_user()
    {
        return DB::table('users as a')
                    ->leftjoin('area as b', 'a.area_id', '=', 'b.id')
                    ->leftjoin('user_type as c', 'c.id', '=', 'a.user_type_id')
                    ->select('a.*', 'b.area_code', 'c.user_type')
                    ->where('a.status', 1)
                    ->whereNull('a.deleted_at')
                    ->orderBy('a.id')
                    ->get();
    }

    public function update_user($data, $where)
    {
        $result = DB::table('users')
                        ->where($where)
                        ->update($data);

        $where = 
            [
                'a.status' => 1, 'a.id' => $where['id']
            ];
        return  $this->load_one($where);
    }

    public function soft_delete($where)
    {
        return User::where($where)
                    ->delete();
    }

    public function active_user_status($id)
    {
        return User::withTrashed()
                    ->where('id', $id)
                    ->restore();
    }

    public function update_support($data, $where)
    {
        return DB::table('users')
                    ->where($where)
                    ->update($data);
    }

    public function load_user_overall()
    {
        return DB::table('users as a')
        // ->select('id','employee_number','last_name','first_name','middle_name','photo','email','position','status','section','section_code','area_id','user_type_id','created_at','updated_at','deleted_at','process','approver','support','receiver')
        ->leftjoin('area as b', 'a.area_id', '=', 'b.id')
        ->leftjoin('user_type as c', 'c.id', '=', 'a.user_type_id')
        ->select('a.*', 'b.area_code', 'c.user_type')
        ->where('a.status', 1)
        ->orderBy('a.id')
        ->get();
    }
}
