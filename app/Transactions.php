<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transactions extends Model
{
    use SoftDeletes;

    protected $table = 'delivery_type_masterlists';
    protected $fillable = ['delivery_type'];

    public function load_delivery_type()
    {
        return Transactions::all();
    }
//need request in FE
    public function load_for_dispatch()
    {
        return DB::connection('pgsql')->select("SELECT 
                                                    a.dr_control,b.ticket_issue_date,b.product_no,b.delivery_qty,c.actual_qty,c.discrepancy,b.manufacturing_no,e.breakdown,e.remarks,
                                                    b.ticket_no as normal,c.ticket_no as irreg,b.process_masterlist_id as normal_status,c.process_masterlists_id as irreg_status,
                                                    a.process as transaction
                                                    
                                                    FROM palletizings a
                                                    LEFT JOIN master_data as b
                                                    ON a.ticket_no = b.ticket_no
                                                    LEFT JOIN irregularity as c
                                                    ON c.ticket_no = a.ticket_no
                                                    LEFT JOIN destination_masterlist as d
                                                    ON b.destination_code = d.payee_cd
                                                    LEFT JOIN delivery as e
                                                    ON a.dr_control = e.dr_control
                                                    WHERE (process_masterlist_id ='5' or process_masterlists_id='5') and pdl ='1'
                                                    ORDER BY a.dr_control,b.product_no,b.ticket_no
                                                ");


        // return DB::table('palletizings as a')
        //         ->leftjoin('master_data as b', 'a.ticket_no', 'b.ticket_no')
        //         ->leftjoin('irregularity as c', 'c.dr_control_no', 'a.dr_control')
        //         ->leftjoin('destination_masterlist as d', 'b.destination_code', 'd.payee_cd')
        //         ->leftjoin('delivery as e', 'a.dr_control', 'e.dr_control')
        //         ->select('b.ticket_issue_date', 'b.product_no', 'b.manufacturing_no', 'e.breakdown', 'e.remarks', 'a.dr_control', DB::raw('sum(b.delivery_qty) as delivery_qty'))
        //         ->groupBy('b.ticket_issue_date', 'b.product_no', 'b.delivery_qty', 'b.manufacturing_no', 'e.breakdown', 'e.remarks', 'a.dr_control')
        //         ->where('b.process_masterlist_id', 5)
        //         // ->where('b.warehouse_class', 'P14')
        //         ->orWhere(function($query) {
        //             $query->where('c.process_masterlists_id', 5);
        //             })
        //         ->where('d.pdl', 1)
        //         ->get();
    }
}
