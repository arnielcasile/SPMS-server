<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class DrMaking extends Model
{
    protected $fillable = ['dr_control', 'users_id', 'pcase', 'box', 'bag', 'pallet', 'delivery_status'];
    protected $a;
    public function add_finish_palletizing($data)
    {
        return DB:: table('dr_makings')
                ->insert(
                            [
                                'dr_control'      => $data['dr_control'],
                                'users_id'        => $data['users_id'],
                                'pcase'           => $data['pcase'],
                                'box'             => $data['box'],
                                'bag'             => $data['bag'],
                                'pallet'          => $data['pallet'],
                                'delivery_status' => $data['delivery_status'],
                                'created_at'      => date('Y-m-d H:i:s'),      
                            ]                                    
                        );
    }

    public function update_dr_makings($where,$data)
    {
        return DB::table('dr_makings as c')
                ->where($where)
                ->update($data);    
    }

    public function update_process($table,$where,$data)
    {
        return DB::table($table. ' as a')
                ->join('palletizings as b', 'a.ticket_no', 'b.ticket_no')
                ->join('dr_makings as c', 'b.dr_control', 'c.dr_control')
                ->where($where)
                ->update($data);
    }

    public function load_data($where, $no)
    { 
        $dr_control = $where['c.dr_control'];

        if(is_numeric($no))
        {
            $query = DB::table('palletizings as a')
                    ->leftjoin('irregularity as b', 'a.ticket_no', 'b.ticket_no')
                    ->where('a.dr_control', $dr_control)
                    ->get();
            
            if($query[0]->irregularity_type == null)
            {
                return DB::table('palletizings as a')
                    ->join('master_data as b', 'b.ticket_no', 'a.ticket_no')
                    ->join('dr_makings as c', 'a.dr_control', 'c.dr_control')
                    ->leftjoin('destination_masterlist as d', 'b.destination_code', 'd.payee_cd')
                    // ->join('checkings as e', 'a.ticket_no' , 'e.ticket_no')
                    ->join('checkings as e', function($join){
                        $join->on('a.ticket_no', '=', 'e.ticket_no');
                        $join->on('a.process','=','e.process');    
                    })
                    ->join('delivery_type_masterlists as f', 'f.id', 'a.delivery_type_id')
                    ->join('users as g', 'e.users_id', 'g.id')
                    ->select('a.*', 'b.*', 'c.*','d.destination','d.payee_cd', 'e.*', 'f.*','g.*')
                    ->where($where)
                    ->get();
            }
            else
            {
                return DB::table('palletizings as a')
                    ->join('master_data as b', 'b.ticket_no', 'a.ticket_no')
                    ->join('dr_makings as c', 'a.dr_control', 'c.dr_control')
                    ->leftjoin('destination_masterlist as d', 'b.destination_code', 'd.payee_cd')
                    ->join('checkings as e', function($join){
                        $join->on('a.ticket_no', '=', 'e.ticket_no');
                        $join->on('a.process','=','e.process');    
                    })
                    ->join('delivery_type_masterlists as f', 'f.id', 'a.delivery_type_id')
                    ->join('users as g', 'e.users_id', 'g.id')
                    ->leftjoin('irregularity as h', 'a.ticket_no', 'h.ticket_no')
                    ->select('a.*', 'b.*', 'c.*','d.destination','d.payee_cd', 'e.*', 'f.*','g.*', 'h.actual_qty as delivery_qty')
                    ->where($where)
                    ->get();
            }
        }
        else
        {
            return DB::table('palletizings as a')
                ->join('master_data as b', 'b.ticket_no', 'a.ticket_no')
                ->join('dr_makings as c', 'a.dr_control', 'c.dr_control')
                ->leftjoin('destination_masterlist as d', 'b.destination_code', 'd.payee_cd')
                ->join('checkings as e', function($join){
                    $join->on('a.ticket_no', '=', 'e.ticket_no');
                    $join->on('a.process','=','e.process');    
                })
                ->join('delivery_type_masterlists as f', 'f.id', 'a.delivery_type_id')
                ->join('users as g', 'e.users_id', 'g.id')
                ->join('irregularity as h', 'a.ticket_no' , 'h.ticket_no')
                ->select('a.*', 'b.*', 'c.*','d.destination','d.payee_cd', 'e.*', 'f.*','g.*','h.*')
                ->where($where)
                ->get();
        }
    }

    public function load_dr_making($where)
    {
        return DB::table('dr_makings as a')
                ->join('users as f', 'a.users_id', 'f.id')
                ->join('area as g', 'f.area_id', 'g.id')
                ->join('palletizings as b', 'a.dr_control', 'b.dr_control')
                ->leftjoin('master_data as c', 'b.ticket_no', 'c.ticket_no')
                ->leftjoin('irregularity as d', 'c.ticket_no', 'd.ticket_no')
                ->leftjoin('destination_masterlist as e', 'c.destination_code', 'e.payee_cd')
                ->select('a.id', 'a.dr_control', 'e.destination as destination','c.ticket_no', 'd.process_masterlists_id as irreg', 'c.process_masterlist_id as normal', 'b.process','e.attention_to as attention_to')
                ->where('c.warehouse_class',$where)
                ->where(function ($query){
                    $query->where('d.process_masterlists_id', 4)
                            ->orWhere('c.process_masterlist_id', 4);
                })
                ->get();

    }

    public function load_dr_no($from, $to, $where)
    {
        return DB::table('dr_makings as a')
                    ->join('palletizings as b', 'a.dr_control','b.dr_control')
                    ->join('master_data as c', 'b.ticket_no', 'c.ticket_no')
                    ->whereBetween('a.created_at' , [$from, $to])
                    ->where(function ($query) {
                        $query->where('c.process_masterlist_id', '=', 5)
                              ->orWhere('c.process_masterlist_id', '=', 6);
                    })
                    ->where($where)
                    ->select('a.*')
                    ->orderBy('a.dr_control','DESC')
                    ->get();

    }

    public function load_dr_making_details($where)
    {
        // return DrMaking::where($where)
        //                 ->get();
        return DB::table('dr_makings as a')
        ->join('users as f', 'a.users_id', 'f.id')
        ->join('area as g', 'f.area_id', 'g.id')
        ->join('palletizings as b', 'a.dr_control', 'b.dr_control')
        ->leftjoin('master_data as c', 'b.ticket_no', 'c.ticket_no')
        ->leftjoin('destination_masterlist as e', 'c.destination_code', 'e.payee_cd')
        ->select(
            'a.dr_control as dr_control', 
             'a.pallet as pallet', 
             'a.pcase as pcase', 
             'a.box as box', 
             'a.bag as bag',
             'e.attention_to as attention_to')
        ->where($where)
        ->get();
    }

    public function masterdata_process_count($where)
    {
        return DB::table('master_data as a')
                ->leftjoin('irregularity as b', 'b.ticket_no', 'a.ticket_no')
                ->where($where)
                ->select('a.ticket_no','b.ticket_no as irreg_ticket','a.process_masterlist_id as master_process','b.process_masterlists_id as irreg_process')
                ->get();
    }

}
