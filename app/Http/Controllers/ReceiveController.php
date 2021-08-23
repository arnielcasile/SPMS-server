<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Receive;
use \DB;

class ReceiveController extends Controller
{
    protected $receive;

    public function __construct()
    {
        $this->receive = New Receive();
    }

    public function receive(Request $request)
    {
        $data =
        [
            'dr_control'    => $request->dr_control
        ];

        $rule = 
        [
            'dr_control'    => 'required'
        ];

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
                $load = $this->receive->receive($request->dr_control);
                DB::commit();

                $result = [];
                $count = count($load);

                for($a=0;$a<$count;$a++)
                {
                    $result[] = 
                        [
                            'dr_control'         => $load[$a]->dr_control,
                            'warehouse_class'    => $load[$a]->warehouse_class,
                            'normal_status'      => $load[$a]->normal_status,
                            'irreg_status'       => $load[$a]->irreg_status,
                            'updated_at'         => $load[$a]->updated_at
                        ];
                }
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $result,
                    ]);            
            } 
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to load data',
                        'data'      => '',
                    ]);
            }        
        }
    }

    public function update_receive(Request $request)
    {

        $looping = $request['data']; 
        
        foreach($looping as $loop)
        {
            $where = [];

            $where =
            [
                'dr_control'    => $loop['dr_control']
            ];  
            
            $input = 
            [
                'recipient'     => $loop['recipient']
            ];
        
            try
            {
                DB::beginTransaction();

                $this->receive->update_receive($input, $where);

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
        return response()
            ->json([
                'status'    => 1,
                'message'   => '',
                'data'      => $looping
            ]); 
    }

    public function update_receive_special(Request $request)
    {
       $looping = $request['data']; 

        foreach($looping as $loop)
        {
            $where = [];

            $where =
            [
                'dr_control'    => $loop['dr_control']
            ];   
            
            $input = 
            [
                'recipient'     => $loop['recipient']
            ];
        
            try
            {
                DB::beginTransaction();

                $this->receive->update_receive($input, $where);

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
        return response()
            ->json([
                'status'    => 1,
                'message'   => '',
                'data'      => $looping
            ]); 
    }

    public function load_for_receive(Request $request)
    {
        try
        {
            DB::beginTransaction();

            $load = $this->receive->load_for_receive();
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
                $temp_remarks             = "";
                $temp_created_at          = "";

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
                            $temp_remarks               = $load[$x]->remarks;
                            $temp_created_at            = $load[$x]->created_at;
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
                                $temp_remarks               = $load[$x]->remarks;
                                $temp_created_at            = $load[$x]->created_at;
                            }
                            else
                            {
                                $total_qty                  += $load[$x]->discrepancy;
                                $temp_ticket_issue_date     = $load[$x]->ticket_issue_date;
                                $temp_product_no            = $load[$x]->product_no;
                                $temp_manufacturing_no      = $load[$x]->manufacturing_no;
                                $temp_breakdown             = $load[$x]->breakdown;
                                $temp_remarks               = $load[$x]->remarks;
                                $temp_created_at            = $load[$x]->created_at;
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
                    'remarks'               => $temp_remarks,
                    'created_at'            => $temp_created_at,
                ];

            }

            return response()
            ->json([
                'status'    => 1,
                'message'   => '',
                'data'      => $result

            ]); 
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
