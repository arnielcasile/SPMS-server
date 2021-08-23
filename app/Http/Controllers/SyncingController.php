<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\MasterData;
use App\Oracle;

class SyncingController extends Controller
{
    protected $oracle;
    protected $master;

    public function __construct()
    {
        $this->oracle = New Oracle();
        $this->master = New MasterData();
        ini_set('max_execution_time', '0'); 
    }

    /*
    * return @array
    * _method GET
    * request data required [from, to, area_code]
    */
    public function click_sync(Request $request)
    {
        $request_data = 
        [
            'from'          => $request->from,
            'to'            => $request->to,
            'area_code'     => $request->area_code,
        ];

        $rule = 
        [
            'from'          => 'required',
            'to'            => 'required',
            'area_code'     => 'required',
        ];
        
        $validator = Validator::make($request_data, $rule);

        if($validator->fails())
        {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->all(),
                'data' => ''
            ]);
        }
        else
        {
            return $this->get_sync($request->from, $request->to, $request->area_code);
        }
    }

    public function get_sync($from, $to, $area_code)
    {
        $tickets = $this->master->get_master_data();
        $oracles = $this->oracle->get_oracle_data($this->convert_date($from), $this->convert_date($to), $area_code);

        foreach($oracles as $oracle)
        {
            if($oracle->orderdownloadno != '-')
            {
                $no = true;

                foreach($tickets as $ticket)
                {
                    if($oracle->ticketno == $ticket->ticket_no)
                    {
                        $no = false;
                        break;
                    }
                }
                
                if($no)
                $this->insert_item($oracle);
            }
        }

        return $this->load_filtered_item($from, $to, $area_code); 
    }

    public function insert_item($oracle)
    {
        $data = 
        [
            'warehouse_class'       => $oracle->wh_class,
            'delivery_form'         => $oracle->deliv_form,
            'item_no'               => $oracle->itemno,
            'item_rev'              => $oracle->itemrev,
            'delivery_qty'          => $oracle->deliv_qty,
            'stock_address'         => $oracle->stockaddress,
            'manufacturing_no'      => $oracle->manuf_no,
            'delivery_inst_date'    => $oracle->deliv_inst_date,
            'destination_code'      => $oracle->dest_code,
            'item_name'             => $oracle->itemname,
            'product_no'            => $oracle->productno,
            'ticket_no'             => $oracle->ticketno,
            'ticket_issue_date'     => $oracle->tiketissuedate,
            'ticket_issue_time'     => $oracle->tiketissuetime,
            'storage_location'      => $oracle->storagelocation,
            'delivery_due_date'     => $oracle->deliveryduedate,
            'order_download_no'     => $oracle->orderdownloadno,
            'process_masterlist_id' => 1,
        ];

        $this->master->insert_item($data);

        return response()->json([
            'status' => 1,
            'message' => '',
            'data' => $data
        ]);
    }

     /*
    * return @array
    * _method GET
    * request data required [from, to, area_code]
    */
    public function load_filtered_item($from, $to, $area_code)
    {
        return $this->master->load_filtered_item($from, $to, $area_code);
    }

    public function load_all_item()
    {
        return $this->master->load_all_item();
    }

    public function convert_date($date)
    {
        return str_replace('-', '/', $date);        
    }


    
}
