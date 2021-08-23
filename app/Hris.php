<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Hris extends Model
{
    protected $connection = "mysql";
    protected $table = "hris.hrms_emp_masterlist";

    public function manpower($where)
    {
        return Hris::select('emp_pms_id','emp_last_name','emp_first_name','emp_middle_name','emp_photo','position','section','emp_system_status','section_code')
                    ->where($where)
                    ->first();        
    }
}
