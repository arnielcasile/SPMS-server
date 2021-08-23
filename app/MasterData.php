<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class MasterData extends Model
{
    protected $table = 'master_data';
    protected $fillable = 
            [
                'warehouse_class', 
                'delivery_form', 'item_no', 
                'item_rev', 'delivery_qty', 
                'stock_address', 
                'manufacturing_no', 
                'delivery_inst_date', 
                'destination_code', 
                'item_name', 
                'product_no', 
                'ticket_no', 
                'ticket_issue_date', 
                'ticket_issue_time', 
                'storage_location', 
                'delivery_due_date', 
                'order_download_no', 
                'process_masterlist_id'
            ];

    public function get_master_data()
    {
        return MasterData::select('ticket_no')
                        ->get();
    }

    public function load_process()
    {
        return DB::table('process_masterlists')
            ->select('process')
            ->get();
    }

    public function insert_item($data)
    {
        return MasterData::create($data);
    }

    public function load_filtered_item($from, $to, $area_code)
    {
        return DB::table('master_data as a')
                ->select('a.*')
                ->where('warehouse_class', $area_code)
                ->whereBetween('a.ticket_issue_date', [$from, $to])
                ->orderBy('a.id')
                ->get();
    }

    public function load_all_item()
    {
        return MasterData::all();
    }

    public function load_data_for_monitoring($area_code, $from, $to)
    {
        return DB::table('master_data as a')
                ->leftjoin('process_masterlists as b', 'a.process_masterlist_id', 'b.id')
                ->leftjoin('irregularity as c', 'a.ticket_no', 'c.ticket_no')
                ->leftjoin('process_masterlists as d', 'c.process_masterlists_id', 'd.id')
                ->leftjoin('palletizings as e', 'a.ticket_no', 'e.ticket_no')
                ->leftjoin('delivery as f', 'e.dr_control', 'f.dr_control')
                ->leftjoin('destination_masterlist as g', 'a.destination_code', 'g.payee_cd')
                ->select('a.*','g.payee_name','b.process as normal_status','e.dr_control as dr_control','d.process as irreg_status','c.dr_control_no as irreg_dr','f.created_at as delivered','e.process','c.irregularity_type')
                ->where('warehouse_class', $area_code)
                ->whereBetween('a.ticket_issue_date' , [$from, $to])
                ->orderby('a.ticket_issue_date','ASC')
                ->orderby('e.dr_control','ASC')
                ->get();
    }

    public function array_master_data($data)
    {
        return DB::table('master_data')
                ->select('process_masterlist_id', 'ticket_issue_date')
                ->where('warehouse_class', $data['warehouse_class'])
                ->whereBetween('ticket_issue_date', [$data['issue_from'], $data['issue_to']])
                ->whereBetween('delivery_due_date', [$data['due_from'], $data['due_to']])
                ->orderBy('process_masterlist_id','ASC')
                ->get();
    }

    public function array_master_data_filter($issue_from, $issue_to, $due_from, $due_to, $warehouse_class)
    {
        return DB::table('master_data')
                ->select('process_masterlist_id', 'ticket_issue_date')
                ->where('warehouse_class', $warehouse_class)
                ->whereBetween('ticket_issue_date', [$issue_from, $issue_to])
                ->whereBetween('delivery_due_date', [$due_from, $due_to])
                ->orderBy('process_masterlist_id','ASC')
                ->get();
    }

    public function array_process()
    {
        return DB::table('process_masterlists')
                ->select('id', 'process')
                ->orderBy('id','ASC')
                ->get();
    }

    public function array_process_filter($status)
    {
        return DB::table('process_masterlists')
                ->select('id', 'process')
                ->where('process', $status)
                ->orderBy('id','ASC')
                ->get();
    }

    public function array_list_payee()
    {
        return DB::table('destination_masterlist as a')
                ->Join('master_data as b', 'b.destination_code', 'a.payee_cd')
                ->select('payee_name')
                ->groupBy('payee_name')
                ->orderBy('payee_name','ASC')
                ->get();
    }

    public function array_payee_master($data)
    {
        return DB::table('master_data as a')
                ->join('destination_masterlist as b', 'a.destination_code', 'b.payee_cd')
                ->select('payee_name', 'destination_code', 'ticket_issue_date', 'delivery_qty')
                ->where('warehouse_class', $data['warehouse_class'])
                ->whereBetween('ticket_issue_date', [$data['issue_from'], $data['issue_to']])
                ->whereBetween('delivery_due_date', [$data['due_from'], $data['due_to']])
                ->get();
    }

    public function array_payee_master_filter($data)
    {
        return DB::table('master_data as a')
                ->join('destination_masterlist as b', 'a.destination_code', 'b.payee_cd')
                ->join('process_masterlists as c', 'a.process_masterlist_id', 'c.id')
                ->select('payee_name', 'destination_code', 'ticket_issue_date', 'delivery_qty', 'process')
                ->where('process', $data['status'])
                ->where('warehouse_class', $data['warehouse_class'])
                ->whereBetween('ticket_issue_date', [$data['issue_from'], $data['issue_to']])
                ->whereBetween('delivery_due_date', [$data['due_from'], $data['due_to']])
                ->get();
    }

    public function array_dr_control($data)
    {
        return DB::table('palletizings as a')
                ->leftjoin('dr_makings as b', 'a.dr_control', 'b.dr_control')
                ->leftjoin('delivery as c', 'c.dr_control', 'a.dr_control')
                ->leftjoin('master_data as d', 'd.ticket_no', 'a.ticket_no')
                ->leftjoin('process_masterlists as e', 'd.process_masterlist_id', 'e.id')
                ->leftjoin('irregularity as f', 'a.dr_control', 'f.dr_control_no')
                ->leftjoin('destination_masterlist as g', 'd.destination_code', 'g.payee_cd')
                ->select('a.dr_control', 'b.pallet', 'b.pcase', 'b.box', 'b.bag', 'a.created_at', DB::raw('(count(a.ticket_no)) as ticket_count'), DB::raw('DATE(a.created_at) as date'), 'e.process', 'g.destination')
                ->groupBy('a.dr_control', 'b.pallet', 'b.pcase', 'b.box', 'b.bag', 'date', 'a.created_at', 'e.process', 'g.destination')
                ->where('d.warehouse_class', $data['area_code'])
                ->where('g.pdl', '<>', 1)
                ->whereBetween('c.created_at', [$data['date_from'], $data['date_to']])
                ->orderBy('a.dr_control')
                ->get();
    }

    public function load_delivery($data)
    {
        return DB::table('palletizings as a')
        ->leftjoin('dr_makings as b', 'a.dr_control', 'b.dr_control')
        ->leftjoin('delivery as c', 'c.dr_control', 'a.dr_control')
        ->leftjoin('master_data as d', 'd.ticket_no', 'a.ticket_no')
        ->leftjoin('process_masterlists as e', 'd.process_masterlist_id', 'e.id')
        ->leftjoin('irregularity as f', 'a.dr_control', 'f.dr_control_no')
        ->leftjoin('destination_masterlist as g', 'd.destination_code', 'g.payee_cd')
        ->select('a.dr_control', 'b.pallet', 'b.pcase', 'b.box', 'b.bag', 'c.created_at', 'a.ticket_no','c.created_at as date', 'e.process', 'g.destination')
        // ->groupBy('a.dr_control', 'b.pallet', 'b.pcase', 'b.box', 'b.bag', 'date', 'a.created_at', 'e.process', 'g.destination')
        ->where('d.warehouse_class', $data['area_code'])
        ->where('d.warehouse_class', $data['area_code'])
        ->where('g.pdl', '<>', 1)
        ->whereBetween('c.created_at', [$data['date_from'] . ' ' . '00:00:00', $data['date_to'] . ' '. '23:59:59'])
        ->orderBy('c.created_at', 'desc')
        ->get();
    }

    public function additional_leadtime_data($where)
    {
        return DB::table('checkings as a')
                ->leftjoin('palletizings as b', 'a.ticket_no', 'b.ticket_no')
                ->leftjoin('dr_makings as c', 'b.dr_control','c.dr_control')
                ->leftjoin('delivery as d', 'b.dr_control','d.dr_control')
                ->select('a.ticket_no','a.created_at as checking', 'b.created_at as palletizing', 'c.updated_at as dr_making', 'd.created_at as delivery', 'd.updated_at as receiving')
                ->whereIn('a.ticket_no', $where)
                ->get();
    }

    public function get_load_for_lead_time($ticket_from, $ticket_to, $area_code)
    {
        $master = DB::connection('pgsql')->select(
            "select a.*, CONCAT(a.ticket_issue_date,' ', a.ticket_issue_time) as issue_date, c.created_at as delivery_date, d.payee_name
                from
                    master_data as a
                    left Join palletizings as b
                        on a.ticket_no = b.ticket_no
                    left Join delivery as c
                        on b.dr_control = c.dr_control
            		left join destination_masterlist as d
            			on d.payee_cd = a.destination_code
                    where a.ticket_issue_date between '$ticket_from' AND '$ticket_to'
                        and a.warehouse_class = '$area_code'
                        and (b.process = 'NORMAL' or a.process_masterlist_id < 6)
            ");

            return $master;
    }

    public function time_out($from, $to, $area_code)
    {
       

        $time_out =  DB::table('time_in_out as a')
            ->select('a.*')
            ->whereBetween('a.date', [$from, $to])
            ->where('a.area_code' , $area_code)
            ->get();
         
            return $time_out;
 
             
            
    }

}
