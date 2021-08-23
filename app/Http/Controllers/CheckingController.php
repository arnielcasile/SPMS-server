<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Checking;
use DB;

class CheckingController extends Controller
{
    protected $checking;

    public function __construct()
    {
        $this->checking = New Checking();
    }
    /*
    * return @array
    * _method PATCH
    * request data required [ticket_no,users_id]
    */
    public function update_check(Request $request)
    {
   
        // $looping[] = 
        // [
        //     'delivery_qty'      => '86',
        //     'irreg_type'        => 'NO STOCK',
        //     'item_no'           => 'CA82008-0903',
        //     'order_download_no' => 'P14-PL-20-771',
        //     'process'           => 'COMPLETION',
        //     'ticket_no'         => 'MK730200201',
        //     'users_id'           => '152'
        // ];

        $looping = $request->data;

        foreach($looping as $loop)
        {
            $data =  
            [
                'ticket_no' => $loop['ticket_no'],
                'users_id'  => $loop['users_id'],
                'process'  => ($loop['process'] == "Normal") ? "NORMAL" : "COMPLETION",
            ];
            
            $rule = 
            [
                'ticket_no' => 'required',
                'users_id'  => 'required',
                'process'  => 'required',
            ];
        
            $where = ['ticket_no' => $loop['ticket_no']];

            $normal_data = ["process_masterlist_id" => 2];

            $completion_data = ["process_masterlists_id" => 2];

            $validator = Validator::make($data, $rule);
        
            if ($validator->fails())
            {
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => $validator->errors()->all(),
                        'data'      => '',
                    ]);
            }
            else
            {
                try
                {
                    DB::beginTransaction();

                    $this->checking->add_checking($data);

                    if($loop['process'] == "Normal")
                    {
                        $this->checking->update_checking("master_data", $normal_data, $where);
                    }
                    else
                    {
                        if($loop['irreg_type'] == "NO STOCK" || $loop['irreg_type'] == "EXCESS")
                        {
                            $this->checking->update_checking("master_data", $normal_data, $where);
                            $this->checking->update_checking("irregularity", $completion_data, $where);
                        }
                        else
                        {
                            $this->checking->update_checking("irregularity", $completion_data, $where);
                        }
                        
                    }
                        

                    DB::commit();  
                }
                catch (\Throwable $th)
                {
                    DB::rollback();
            
                        return response()
                            ->json([
                                'status'    => 0,
                                'message'   => $th->getMessage(),
                                'data'      => '',
                            ]);
                }
            }
        }
        
        return response()
            ->json([
                'status'    => 1,
                'message'   => '',
                'data'      => $looping
            ]); 
    }   
      /*
    * return @array
    * _method GET
    * request data required [ticket_no]
    */        
    public function load_checking(Request $request)
    {
        
        $data = 
        [
            'ticket_no' => $request->ticket_no
        ];
        $rule = 
        [
            'ticket_no' => 'required'
        ];

        $validator = Validator::make($data,$rule);

        if($validator->fails())
        {
            return response()
            ->json([
                'status' => 0,
                'message' => $validator->errors()->all(),
                'data' => ''
            ]);
        }
        else
        {
            try
            {
                $where = 
                [
                    'a.ticket_no' => $request->ticket_no
                ];
                DB::beginTransaction();
                $checking_data = $this->checking->load($where);
                DB::commit();
                
                $count = count($checking_data);
                $result = [];
                for($a=0; $a<$count; $a++)
                {
                    $result[] = [
                        'normal'            => $checking_data[$a]->normal_ticket,
                        'completion'        => $checking_data[$a]->completion_ticket,
                        'item_no'           => $checking_data[$a]->item_no,
                        'delivery_qty'      => $checking_data[$a]->delivery_qty,
                        'order_download_no' => $checking_data[$a]->order_download_no,
                        'normal_status'     => $checking_data[$a]->normal_status,
                        'irreg_status'      => $checking_data[$a]->irregularity_status,
                        'warehouse_class'   => $checking_data[$a]->warehouse_class,
                        'irregularity_type' => $checking_data[$a]->irregularity_type,
                        'destination_code'  => $checking_data[$a]->destination,
                        'dest_deleted_at'   => $checking_data[$a]->dest_deleted_at,
                        'issue_time'        => $checking_data[$a]->issue_time,
                    ];

                }
                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $result,
                ]);
            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                ->json([
                    'status'    => 0,
                    'message'   => $th->getMessage(),
                    'data'      => '',
                ]);
            }
        }


    }
}
