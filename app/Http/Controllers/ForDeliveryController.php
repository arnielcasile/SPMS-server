<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\DrMaking;
use App\ForDelivery;
use App\Irregularity;
use App\Checking;
use DB;

class ForDeliveryController extends Controller
{
    protected $dr_making, $for_delivery, $irregularity, $checking;
    
    public function __construct()
    {
        $this->dr_making = new DrMaking();
        $this->for_delivery = new ForDelivery();
        $this->irregularity = new Irregularity();
        $this->checking = new Checking();
    }

    public function update_for_delivery(Request $request)
    {
        // $request_data[] =
        // [
        //     'dr_control'    => 'P14-PL-20-1398-A',
        //     'user_id'      => '152',
        //     'breakdown'     => '125',
        //     'remarks'       => '-',
        //     'created_at'    => '2020-01-26',
        //     // 'irreg_type'    => 'NO STOCK',

        // ];

        $request_data = $request->data;

        $result = [];
            try
            {
                $data_normal = 
                [
                    'a.process_masterlist_id' => '6'
                ];
        
                $data_completion = 
                [
                    'process_masterlists_id' => '6'
                ];

                for($a = 0; $a < count($request_data); $a++)
                {
                    $where =
                    [
                        'c.dr_control' => $request_data[$a]['dr_control']
                    ];

                    $save_data = 
                    [
                        'dr_control'    => $request_data[$a]['dr_control'],
                        'users_id'      => $request_data[$a]['user_id'],
                        'breakdown'     => $request_data[$a]['breakdown'],
                        'remarks'       => $request_data[$a]['remarks'],
                        'created_at'    => date('Y-m-d H:i:s'),
                    ];

                    DB::beginTransaction();
                    $count = explode('-', $request_data[$a]['dr_control']);
                    $ctr = count($count);

                    if($ctr == 4)
                    {
                        $this->dr_making->update_process('master_data', $where, $data_normal);
                    }
                    elseif($ctr == 5)
                    {
                        $no = $count[4];
                        if(is_numeric($no))
                        {
                            $this->dr_making->update_process('master_data', $where, $data_normal);
                        }
                        else
                        {
                                $where_irreg = 
                            [
                                'a.dr_control' => $request_data[$a]['dr_control'],
                            ];
                            $load_irregularity = $this->for_delivery->load_process($where_irreg);

                            foreach($load_irregularity as $data)
                            {   
                                $where_dr =
                                [
                                    'b.ticket_no' => $data->ticket_no
                                ];
                                $where_checking =
                                [
                                    'ticket_no' => $data->ticket_no
                                ];

                        
                                if(($data->irregularity_type == "NO STOCK" && $data->process == "COMPLETION") || ($data->irregularity_type == "EXCESS" && $data->process == "COMPLETION")) 
                                {
                                    $this->dr_making->update_process('master_data', $where_dr, $data_normal);
                                    $this->checking->update_checking("irregularity", $data_completion, $where_checking);
                                }
                                else if($data->process == 'NORMAL')
                                {
                                    $this->dr_making->update_process('master_data', $where_dr, $data_normal);
                                }
                                else
                                {
                                $this->checking->update_checking("irregularity", $data_completion, $where_checking);
                                }
                            
                            }
                        }
                    }

                    // else
                    // {
                        
                  
                    //     // if($data[$a]['irreg_type'] == "NO STOCK")
                    //     // {
                    //     //     $this->dr_making->update_process('irregularity', $where, $data_completion);
                    //     //     $this->dr_making->update_process('master_data', $where, $data_normal);
                    //     // }
                    //     // else
                    //     // {
                    //     //     $this->dr_making->update_process('irregularity', $where, $data_completion);
                    //     // }     
                    // }   

                    $this->for_delivery->save_delivery($save_data);

                    DB::commit();           
                
                }
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $request_data
                    ]);
            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => $th->getMessage(),
                        'data'      => ''
                    ]);
            }
    }
            
    public function load_delivery_update(Request $request)
    {
        $where = 
        [
            'dr_control'    => $request->dr_control
        ];

        $rule =
        [
            'dr_control'    => 'required'
        ];

        $validator = Validator::make($where,$rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => ''
                ]);
        }
        else
        {
            try
            {   
                DB::beginTransaction();
                $load = $this->for_delivery->load_delivery_update($where);
                DB::commit();
                $count = count($load);
                $result = [];
                $ticket_count = 0;
                $irreg_status = null;

                foreach($load as $load_items)
                {
                    if($request->dr_control == $load_items->dr_control)
                    {
                        $ticket_count += $load_items->total_ticket;
                    }
                    if($load_items->irreg_process <> null)
                    {
                        $irreg_status = $load_items->irreg_process;
                    }
                }
                
                    $result[] =
                    [
                        'dr_control'            => $load_items->dr_control,
                        'normal_status'         => $load_items->process_masterlist_id,
                        'normal_process'        => $load_items->normal_process,
                        'irreg_status'          => $load_items->process_masterlists_id,
                        'irreg_process'         => $irreg_status,
                        'item_count'            => $ticket_count,
                        'area_code'             => $load_items->warehouse_class,
                    ];

            
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $result
                    ]);
            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => $th->getMessage(),
                        'data'      => ''
                    ]);
            }
        }
    }

    public function load_for_banner(Request $request)
    {
        $data = 
        [
            'dr_control'  => $request->dr_control
        ];

        $rule = 
        [
            'dr_control'  => 'required'
        ];

        $validator = Validator::make($data,$rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => ''
                ]);
        }
        else
        {
            try
            {
                $where = 
                [
                    'a.dr_control'  => $request->dr_control
                ];

                DB::beginTransaction();
                $load = $this->for_delivery->load_delivery_banner($where);
                DB::commit();

                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $load
                        ]);

            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => $th->getMessage(),
                        'data'      => ''
                        ]);
            }
        }

       
    }

    public function load_inhouse_delivery(Request $request)
    {
        $data = 
        [
            'date_from'     => $request->date_from . " " . "00:00:00",
            'date_to'       => $request->date_to . " " . "23:59:59"
        ];

        $rule =
        [
            'date_from'     => 'required',
            'date_to'       => 'required'
        ];

        $validator = Validator::make($data, $rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => ''
                ]);
        }
        else
        {
            try
            {
                DB::beginTransaction();
               
                $load = $this->for_delivery->load_inhouse_delivery($data);
                DB::commit();
                $count_load = count($load);
                $result = [];
                $dr_control = [];

                for ($z = 0; $z<$count_load ; $z++)
                {
                    if (!in_array($load[$z]->dr_control,$dr_control))
                    {
                        $dr_control [] = $load[$z]->dr_control;
                    }
                }
                // return $dr_control;
                $count_control = count($dr_control);

                for  ($y = 0; $y<$count_control ; $y++)
                {
                    $total_qty                = 0;
                    $temp_dr_control          = "";
                    $temp_ticket_issue_date   = "";
                    $temp_product_no          = "";
                    $temp_delivery_qty        = "";
                    $temp_manufacturing_no    = "";
                    $temp_breakdown           = "";
                    $temp_created_at          = "";
                    $temp_recipient           = "";
                    $temp_updated_at           = "";
                    $temp_remarks             = "";

                    for ($x = 0; $x<$count_load ; $x++)
                    {
                        if ($dr_control[$y] == $load[$x]->dr_control) 
                        {
                            if ($load[$x]->irreg == null) 
                            {
                                $total_qty                  += $load[$x]->delivery_qty;
                                $temp_ticket_issue_date     = $load[$x]->ticket_issue_date;
                                $temp_product_no            = $load[$x]->product_no;
                                $temp_manufacturing_no      = $load[$x]->manufacturing_no;
                                $temp_breakdown             = $load[$x]->breakdown;
                                $temp_created_at            = $load[$x]->created_at;
                                $temp_recipient             = $load[$x]->first_name . ' ' . $load[$x]->last_name;
                                $temp_updated_at            = $load[$x]->updated_at;
                                $temp_remarks               = $load[$x]->remarks;
                            }
                            else
                            {
                                // $total = 0;
                                // $count_dr = explode('-', $load[$x]->dr_control);
                                // $no = $count_dr[4];
                                // if(is_numeric($no))
                                if($load[$x]->transaction == "NORMAL")
                                {
                                    $total_qty                  += $load[$x]->actual_qty;
                                    $temp_ticket_issue_date     = $load[$x]->ticket_issue_date; 
                                    $temp_product_no            = $load[$x]->product_no;
                                    $temp_manufacturing_no      = $load[$x]->manufacturing_no;
                                    $temp_breakdown             = $load[$x]->breakdown;
                                    $temp_created_at            = $load[$x]->created_at;
                                    $temp_recipient             = $load[$x]->first_name . ' ' . $load[$x]->last_name;
                                    $temp_updated_at            = $load[$x]->updated_at;
                                    $temp_remarks               = $load[$x]->remarks;
                                }
                                else
                                {
                                    $total_qty                  += $load[$x]->discrepancy;
                                    $temp_ticket_issue_date     = $load[$x]->ticket_issue_date;
                                    $temp_product_no            = $load[$x]->product_no;
                                    $temp_manufacturing_no      = $load[$x]->manufacturing_no;
                                    $temp_breakdown             = $load[$x]->breakdown;
                                    $temp_created_at            = $load[$x]->created_at;
                                    $temp_recipient             = $load[$x]->first_name . ' ' . $load[$x]->last_name;
                                    $temp_updated_at            = $load[$x]->updated_at;
                                    $temp_remarks               = $load[$x]->remarks;
                                }

                            }
                        }
                    }

                    $result [] =
                    [
                        'dr_control'            => $dr_control[$y],
                        'ticket_issue_date'     => $temp_ticket_issue_date,
                        'product_no'            => $temp_product_no,
                        'delivery_qty'          => $total_qty,
                        'manufacturing_no'      => $temp_manufacturing_no,
                        'breakdown'             => $temp_breakdown,
                        'created_at'            => $temp_created_at,
                        'recipient'             => $temp_recipient,
                        'updated_at'            => $temp_updated_at,
                        'remarks'               => $temp_remarks,
                    ];

                }
                    
                DB::commit();
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $result
                        ]);
            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => $th->getMessage(),
                        'data'      => ''
                    ]);
            }
        }
    }

}
