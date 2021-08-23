<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EmailManagement extends Model
{

    protected $table = 'email_managements';

    protected $dates = ['deleted_at'];
    private $mom;
    use SoftDeletes;

    // protected $table = 'area';

	public function __construct()
	{
		$this->mom = DB::connection('mom');
	}

    public function get_data_mom($id_number)
    {
        return $this->mom->table('tbluser')
        ->select('employeeno', 'lname', 'fname', 'email')
        ->where('employeeno', $id_number)
        ->get();
    }

    public function get_data_users($id_number)
    {
        return DB::connection()
        ->table('email_managements')
        ->where('id_number', $id_number)
        ->get();
    }

    public function insert_email_user($data)
    {
        return DB::connection()
        ->table('email_managements')
        ->insert([$data]);
    }

    public function get_all_data()
    {
        return DB::connection()
        ->table('email_managements')
        ->get();
    }

    public function soft_delete($where)
    {
        return EmailManagement::find($where)->delete();        
    }

    public function update_status($id)
    {           
        return EmailManagement::find($id)->restore();
    }
}
