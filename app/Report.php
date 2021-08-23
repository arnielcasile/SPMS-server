<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Report extends Model
{
    public function load_delivery_leadtime($date_from, $date_to)
    {
        return DB::table('master_data as a')
        ->select(DB::raw('count(a.warehouse_class)'),'a.warehouse_class','a.ticket_issue_date')
        ->whereBetween('a.ticket_issue_date',[$date_from, $date_to])
        ->groupBy('a.warehouse_class','a.ticket_issue_date')
        ->orderBy('a.ticket_issue_date','asc')
        ->get();
    }

    public function finish_count($where)
    {
        return DB::table('delivery as a')
        ->leftjoin('palletizings as b', 'a.dr_control', 'b.dr_control')
        ->leftjoin('master_data as c', 'b.ticket_no', 'c.ticket_no')
        ->select(DB::raw('count(c.id)'),'c.ticket_issue_date')
        ->groupBy('c.ticket_issue_date')
        ->where($where)
        ->get();
    }

    public function all_finish_count($area_code,$from,$to)
    {
        return DB::table('delivery as a')
        ->leftjoin('palletizings as b', 'a.dr_control', 'b.dr_control')
        ->leftjoin('master_data as c', 'b.ticket_no', 'c.ticket_no')
        ->select(DB::raw('count(c.id)'))
        ->where('c.warehouse_class', $area_code)
        ->whereBetween('c.ticket_issue_date', [$from,$to])
        ->get();
    }
   
    public function destination()
    {
        return DB::table('palletizings as a')
                ->leftjoin('master_data as b', 'a.ticket_no', 'b.ticket_no')
                ->join('destination_masterlist as c', 'b.destination_code', 'c.payee_cd')
                ->select('c.destination', 'a.delivery_type_id', DB::raw('DATE(a.created_at) as created_at'))
                ->orderBy('c.destination')
                ->get();
    }

    public function delivery_type()
    {
        return DB::table('palletizings as a')
                ->leftjoin('master_data as b', 'a.ticket_no', 'b.ticket_no')
                ->join('delivery_type_masterlists as c', 'a.delivery_type_id', 'c.id')
                ->join('destination_masterlist as d', 'b.destination_code', 'd.payee_cd')
                ->select('d.destination', 'c.delivery_type', 'a.delivery_type_id')
                ->groupBy('d.destination', 'c.delivery_type', 'a.delivery_type_id')
                ->orderBy('d.destination')
                ->get();
    }     
    
   
}
