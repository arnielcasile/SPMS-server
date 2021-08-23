<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Palletizing extends Model
{
    public $timestamps = true;
    protected $fillable = ['ticket_no', 'users_id', 'dr_control', 'delivery_type_id', 'delivery_no', 'process'];

    protected $user;

    public function load_ongoing_palletizing($where)
    { 
        return DB::table('palletizings as a')
            ->leftjoin('irregularity as b', 'a.ticket_no', 'b.ticket_no')
            ->leftjoin('delivery_type_masterlists as c', 'a.delivery_type_id', 'c.id')
            ->leftjoin('master_data as d', 'd.ticket_no', 'a.ticket_no')
            ->leftjoin('destination_masterlist as e', 'e.payee_cd', 'd.destination_code')
            ->select('d.order_download_no','d.warehouse_class', 'a.dr_control', 'c.delivery_type', 'a.delivery_no', 'a.process', DB::raw('(count(a.dr_control)) as total_items'), 'd.destination_code', 'd.manufacturing_no','e.destination','e.payee_name' )
            ->where([
                ['b.process_masterlists_id', '=' , '3'],
                ['a.process', '=' , 'COMPLETION'],
                ['d.warehouse_class', '=' , $where]
            ])
            ->OrWhere([
                ['d.process_masterlist_id', '=' , '3'],
                ['a.process', '=' , 'NORMAL'],
                ['d.warehouse_class', '=' , $where]
            ])
            ->groupBy('d.order_download_no','d.warehouse_class', 'a.dr_control', 'c.delivery_type', 'a.delivery_no', 'a.process', 'd.destination_code', 'd.manufacturing_no','e.destination','e.payee_name')
            ->orderBy('a.dr_control')
            ->get();
    }

    public function remove_ongoing_palletizing($where)
    {
        return Palletizing::destroy($where); 
    }

    public function get_user($area_id)
    {
        $this->user = DB::table('users as a')
            ->join('area as b', 'a.area_id', 'b.id')
            ->select('b.area_code')
            ->where('a.id', $area_id)
            ->get();

        return $this;
    }

    public function generate_dash_control($process, $value)
    {
        $year = date('y');

        $swt = DB::table('palletizings')
            ->select('dr_control')
            ->where('dr_control', 'like', '%SWT%')
            ->where('process', $process)
            ->orderBy('id', 'DESC') 
            ->get();

        
        if(!count($swt))
        {
            return $this->user[0]->area_code . "-SWT-{$year}-0001-{$value}"; 
        }
        $explode = explode('-', $swt[0]->dr_control);

        if($year != $explode[2])
        {
            return $this->user[0]->area_code . "-SWT-{$year}-0001-{$value}"; 
        }

        $counter = str_pad(($explode[3] + 1), 4, '0', STR_PAD_LEFT);
        return $this->user[0]->area_code . "-SWT-{$year}-{$counter}-{$value}"; 
    }
    public function ticket_count($order_download_no)
    {
        
        $ticket_master = DB::table('master_data as a')
            ->select('a.ticket_no', 'a.process_masterlist_id')
            ->where('a.order_download_no',$order_download_no)
            // ->where('a.process_masterlist_id','2')
            // ->groupby('a.order_download_no','a.process_masterlist_id') 
            ->count();  

        $ticket_irreg = DB::table('irregularity as a')
                ->leftjoin('master_data as b', 'a.ticket_no', 'b.ticket_no')
                ->select('a.ticket_no')
                ->where('b.order_download_no',$order_download_no)
                ->count();
        
        $total_ticket = $ticket_master +  $ticket_irreg;

        return $total_ticket;
    }

    public function previous_dr_control($process, $order_download_no)
    {
        if($process == 1)
        {
            return DB::connection('pgsql')->select("SELECT b.dr_control from
                                                    master_data as a
                                                    join palletizings as b
                                                        on a.ticket_no = b.ticket_no
                                                    where a.order_download_no = '$order_download_no' and
                                                    SUBSTRING (dr_control, 16) !~ '^[0-9\.]+$'
                                                    ORDER BY b.id desc");

        }
        else
        {
            return DB::connection('pgsql')->select("SELECT b.dr_control from
                                                    master_data as a
                                                    join palletizings as b
                                                        on a.ticket_no = b.ticket_no
                                                    where a.order_download_no = '$order_download_no' and
                                                    SUBSTRING (dr_control, 16) ~ '^[0-9\.]+$'
                                                    ORDER BY b.id desc");

        } 
    }

    public function get_completion_process($ticket_no)
    {
        $count =  DB::table('irregularity as a')
            ->select(DB::raw('(count(a.ticket_no)) as ticket_cnt'))
            ->whereIn('a.ticket_no', $ticket_no)
            ->get();

        if($count[0]->ticket_cnt > 0)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
    
    public function generate_same_ticket_control($process, $value, $result, $where)
    {
        $year = date('y');

        $swt = DB::table('palletizings')
            ->select('dr_control')
            ->where('dr_control', 'like', '%SWT%')
            ->orderBy('id', 'DESC') 
            ->get();
        
        $ticket_count =  DB::table('palletizings')
        ->select('dr_control')
        ->whereIn('ticket_no',$where)
        ->orderBy('id', 'DESC') 
        ->get();
        
        if(!count($swt))
            return $this->user[0]->area_code . "-SWT-{$year}-0001-{$value}"; 

            $explode = explode('-', $swt[0]->dr_control);
        
        if($result == true && count($ticket_count) > 0)
        {
            $counter = str_pad($explode[3], 4, '0', STR_PAD_LEFT);
           
            return $this->user[0]->area_code . "-SWT-{$year}-{$counter}-{$value}";
        }
        else
        {
            $counter = str_pad(($explode[3] + 1), 4, '0', STR_PAD_LEFT);
        
            return $this->user[0]->area_code . "-SWT-{$year}-{$counter}-{$value}"; 
        }

        if($year != $explode[2])
            return $this->user[0]->area_code . "-SWT-{$year}-0001-{$value}"; 
    }       

    public function generate_control($order_download_no, $process = 'NORMAL', $value = 1, $identifer = 1)
    {
        $year = date('y');

        $pltz = DB::table('palletizings')
            ->select('dr_control')
            ->where('dr_control', 'like', $order_download_no . '%')
            ->where('process', $process)
            ->orderBy('id', 'DESC') 
            ->get();

        if(!count($pltz))
            return $order_download_no . "-{$value}";

        $explode = explode('-', $pltz[0]->dr_control);
        
        ($identifer) ? $identifer = ($explode[4] + 1) : $identifer = ++$explode[4];
        
        if($year != $explode[2])
            return $explode[0] .'-'. $explode[1] .'-'. $year .'-'. $explode[3] ."-{$value}";

        return $explode[0] .'-'. $explode[1] .'-'. $explode[2] .'-'. $explode[3] .'-'. $identifer;

    }

    public function add_palletizing($data)
    {
        return Palletizing::insert($data);
    }

    public function load_palletizing_items($where)
    {
            return DB::table('master_data as a')
                ->leftjoin('irregularity as b', 'b.ticket_no', 'a.ticket_no')
                ->leftjoin('process_masterlists as c', 'a.process_masterlist_id', 'c.id')
                ->leftjoin('process_masterlists as d', 'b.process_masterlists_id', 'd.id')
                ->leftjoin('destination_masterlist as e', 'a.destination_code', 'e.payee_cd')
                ->leftjoin('palletizings as f', 'a.ticket_no', 'f.ticket_no')
                ->leftjoin('delivery_type_masterlists as g', 'f.delivery_type_id', 'g.id')
                ->leftjoin('users as h', 'f.users_id','h.id')
                ->select('a.*', 'f.*', 'g.delivery_type','f.id as palletizing_id','h.*','f.process as palletizing_process', 'e.pdl','b.irregularity_type')
                ->where('f.dr_control', $where)
            ->get();
    }

    public function update_checking($data, $where2)
    {
        return DB::table('master_data as a')
                ->join('palletizings as b', 'a.ticket_no', 'b.ticket_no')
                ->where($where2)
                ->update($data);
    }
}
