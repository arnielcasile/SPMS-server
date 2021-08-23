<?php

namespace App;
use \DB;
use Illuminate\Database\Eloquent\Model;

class Receive extends Model
{
    protected $table = 'delivery';
    protected $fillable = ['dr_control', 'users_id', 'remarks', 'breakdown', 'recipient'];

    public function receive($data)
    {
        return DB::table('palletizings as a')
                ->leftjoin('master_data as b', 'a.ticket_no', 'b.ticket_no')
                ->leftjoin('irregularity as c', 'a.ticket_no', 'c.ticket_no')
                ->leftjoin('delivery as d', 'a.dr_control', 'd.dr_control')
                ->select('a.dr_control', 'b.warehouse_class', 'b.process_masterlist_id as normal_status', 'c.process_masterlists_id as irreg_status', 'd.updated_at')
                ->where('a.dr_control', $data)
                ->groupBy('a.dr_control', 'b.warehouse_class', 'normal_status', 'irreg_status', 'd.updated_at')
                ->get();
    }

    public function update_receive($data, $where)
    {
        return Receive::where($where)
                ->update($data);
    }

    public function load_for_receive()
    {
        return DB::connection('pgsql')->select("SELECT 
                                                    a.dr_control,b.ticket_issue_date,b.product_no,b.delivery_qty,c.actual_qty,c.discrepancy,b.manufacturing_no,e.breakdown,e.remarks,
                                                    b.ticket_no as normal,c.ticket_no as irreg,e.created_at,a.process as transaction
                                                    
                                                    FROM palletizings a
                                                    LEFT JOIN master_data as b
                                                    ON a.ticket_no = b.ticket_no
                                                    LEFT JOIN irregularity as c
                                                    ON c.ticket_no = a.ticket_no
                                                    LEFT JOIN destination_masterlist as d
                                                    ON b.destination_code = d.payee_cd
                                                    LEFT JOIN delivery as e
                                                    ON a.dr_control = e.dr_control
                                                    WHERE e.updated_at is null and e.created_at is not null and pdl ='1'
                                                    GROUP BY b.ticket_issue_date, b.product_no, b.delivery_qty, b.manufacturing_no, e.breakdown, e.remarks, e.updated_at,a.dr_control,b.ticket_no,c.actual_qty,c.discrepancy,c.ticket_no,e.created_at,a.process
                                                    ORDER BY a.dr_control,b.product_no,b.ticket_no
                                                ");
    }
}
