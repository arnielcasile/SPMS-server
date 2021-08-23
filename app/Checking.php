<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class Checking extends Model
{
    protected $table = 'checkings';
    protected $fillable = ['ticket_no', 'users_id', 'process'];
    protected $guarded = [];

    public function add_checking($data)
    {
        return Checking::create($data);
    }
    
    public function update_checking($table, $data, $where)
    { 
        return DB::table($table)
            ->where($where)
            ->update($data);
    }

    public function load($where)
    {
        return DB::table('master_data as a')
        ->leftjoin('irregularity as b', 'b.ticket_no', 'a.ticket_no')
        ->leftjoin('process_masterlists as c', 'a.process_masterlist_id', 'c.id')
        ->leftjoin('process_masterlists as d', 'b.process_masterlists_id', 'd.id')
        ->leftjoin('destination_masterlist as e', 'e.payee_cd', 'a.destination_code')
        ->select(
            'a.ticket_no as normal_ticket',
            'b.ticket_no as completion_ticket',
            'b.*',
            'a.*',
            'c.process as normal_status', 
            'd.process as irregularity_status',
            'a.warehouse_class',
            'e.pdl', 
            'a.destination_code', 
            'a.manufacturing_no',
            'e.destination as destination',
            'b.irregularity_type',
            'e.deleted_at as dest_deleted_at',
            'a.ticket_issue_time as issue_time',)
        ->where($where)
        ->get(); 

    }
}
