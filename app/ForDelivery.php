<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class ForDelivery extends Model
{
    protected $fillable = [ 'dr_control', 'users_id', 'remarks', 'breakdown', 'recipient'];

    protected $table = 'delivery';

    public function setUpdatedAt($value) {
        ;
     }

    public function save_delivery($data)
    {
        return ForDelivery::create($data);
    }

    public function load_delivery_update($where)
    {
        return DB::table('dr_makings as a')
                ->leftjoin('palletizings as b', 'a.dr_control', 'b.dr_control')
                ->leftjoin('master_data as c', 'b.ticket_no', 'c.ticket_no')
                ->leftjoin('irregularity as d', 'b.ticket_no', 'd.ticket_no')
                ->leftjoin('process_masterlists as e', 'e.id', 'd.process_masterlists_id')
                ->leftjoin('process_masterlists as f', 'f.id', 'c.process_masterlist_id')
                ->select('d.process_masterlists_id', 'b.dr_control', 'c.process_masterlist_id', 'c.warehouse_class','e.process as irreg_process', 'f.process as normal_process','c.warehouse_class', DB::raw('(count(b.dr_control)) as total_ticket'))
                ->groupBy('b.dr_control', 'c.process_masterlist_id', 'd.process_masterlists_id', 'e.process', 'f.process','c.warehouse_class')
                ->where('b.dr_control', $where)
                ->get();
    }

    public function load_delivery_for_report($where)
    {
       
        return DB::table('delivery as a')
                ->leftjoin('palletizings as b', 'a.dr_control', 'b.dr_control')
                ->leftjoin('master_data as c', 'b.ticket_no', 'c.ticket_no')
                ->select('a.*', 'b.ticket_no','c.ticket_issue_date')
                ->where($where)
                ->get();
    }

    public function load_delivery_banner($where)
    {
        return DB::table('palletizings as a')
                ->leftjoin('master_data as b', 'a.ticket_no', 'b.ticket_no')
                ->join('destination_masterlist as c', 'b.destination_code', 'c.payee_cd')
                ->leftjoin('irregularity as d', 'b.ticket_no', 'd.ticket_no')
                ->leftjoin('process_masterlists as e', 'e.id', 'd.process_masterlists_id')
                ->leftjoin('process_masterlists as f', 'f.id', 'b.process_masterlist_id')
                ->select('a.dr_control', 'b.warehouse_class', 'c.destination', 'c.purpose', 'b.process_masterlist_id', 'd.process_masterlists_id','c.attention_to')
                ->groupby('a.dr_control', 'b.warehouse_class', 'c.destination', 'c.purpose', 'b.process_masterlist_id', 'd.process_masterlists_id','c.attention_to')
                ->where($where)
                ->get();
    }

    public function load_inhouse_delivery($data)
    {
        return DB::table('delivery as a')
        ->leftjoin('palletizings as b', 'a.dr_control', 'b.dr_control')
        ->leftjoin('master_data as c', 'b.ticket_no', 'c.ticket_no')
        ->leftjoin('destination_masterlist as d', 'd.payee_cd', 'c.destination_code')
        ->leftjoin('irregularity as e', 'c.ticket_no', 'e.ticket_no')
        ->leftjoin('users as f', 'a.recipient','f.id')
        ->select('c.ticket_issue_date','a.dr_control','c.product_no','c.delivery_qty','e.actual_qty','e.discrepancy','c.manufacturing_no','a.breakdown','a.created_at','f.last_name','f.first_name', 'a.updated_at', 'a.remarks','c.ticket_no as normal','e.ticket_no as irreg','b.process as transaction')
        ->where('d.pdl','=',1)
        ->whereBetween('a.created_at', [$data['date_from'], $data['date_to']])
        ->orderby('a.created_at','DESC')
        ->orderby('a.dr_control','ASC')
      // ->groupBy('c.ticket_issue_date','a.dr_control','c.product_no','c.manufacturing_no','a.breakdown','a.created_at','f.last_name','f.first_name', 'a.updated_at', 'a.remarks')
        ->get();

    }

    public function load_process($where)
    {
        return DB::table('palletizings as a')
        ->leftjoin('irregularity as b', 'a.ticket_no', 'b.ticket_no')
        ->select('a.ticket_no','a.process', 'b.irregularity_type')
        ->where($where)
        ->get();
    }
}