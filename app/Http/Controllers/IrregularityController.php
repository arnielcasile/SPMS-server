<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Irregularity;
use Illuminate\Support\Facades\Validator;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IrregularityController extends Controller
{
    protected $irregularity,$process;

    public function __construct()
    {
        $this->irregularity = New Irregularity();
    }

    /*
    * return @array
    * _method GET
    * request data required [ticket_no]
    */
    public function select_barcode(Request $request)
    {
        $where = 
        [
            'ticket_no'       => $request->ticket_no
        ];

        $rule =
        [
            'ticket_no'       => 'required'
        ];

        $validator = Validator::make($where,$rule);

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
                DB::beginTransaction();

                $data =  $this->irregularity->select_barcode($where);

                DB::commit();

                if(is_null($data))
                {
                    return response()
                        ->json([
                            'status'    => 0,
                            'message'   => 'Id does not exist.',
                            'data'      => $data,
                        ]);
                }
                else
                {
                    return response()
                        ->json([
                            'status'    => 0,
                            'message'   => '',
                            'data'      => $data,
                        ]);         
                }       
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

    /*
    * return @array
    * _method POST
    * request data required [ticket_no, control_no, users_id, irregularity_type, actual_qty, discrepancy, remarks]
    */
    public function select_whclass(Request $request)
    {

    }



    public function add_irregularity_item(Request $request)
    {
        $where =
        [
            'warehouse_class'   => $request->warehouse_class,
        ];

        $control_no = $this->irregularity->generate_control_no($where);
        $looping = $request->datas;
        $count=0;
        foreach($looping as $loop)
        {
            
            // Pushing control number for FE use.
            $loop['control_no'] = $control_no;
            $looping[$count]['control_no']=$control_no;
            $count++;
            $data = 
            [
                'ticket_no'                 => $loop['ticket_no'],
                'control_no'                => $control_no,
                'users_id'                  => $loop['users_id'],
                'irregularity_type'         => $loop['irregularity_type'],
                'actual_qty'                => $loop['actual_qty'],
                'discrepancy'               => $loop['discrepancy'],
                'remarks'                   => $loop['remarks'],
            ];

            $rule =
            [
                'ticket_no'                 => 'required',
                'control_no'                => 'required',
                'users_id'                  => 'required',
                'irregularity_type'         => 'required',
                'actual_qty'                => 'required',
                'discrepancy'               => 'required',
                'remarks'                   => 'required',
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

                    $this->irregularity->add_irregularity_item($data);

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
    * _method PATCH
    * request data required [irregularity_type, actual_qty, discrepancy, remarks]
    */
    public function update_irregularity(Request $request)
    {
        $where = 
        [
            'ticket_no'             => $request->ticket_no
        ];

        $data = 
        [
            'irregularity_type'     => $request->irregularity_type,
            'actual_qty'            => $request->actual_qty,
            'discrepancy'           => $request->discrepancy,
            'remarks'               => $request->remarks
        ];

        $rule =
        [
            'irregularity_type'     => 'required',
            'actual_qty'            => 'required',
            'discrepancy'           => 'required',
            'remarks'               => 'required'
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

                $this->irregularity->update_irregularity($data, $where);

                DB::commit();

                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $data
                    ]);  
            }
            catch (\Throwable $th) 
            {
                DB::rollback();
    
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   =>  $th->getMessage(),
                        'data'      => '',
                    ]);
            }  
        }
    }

    /*
    * return @array
    * _method GET
    * request data required [range, date_from, date_to, area_code]
    */ 
    public function load_irregularity(Request $request)
    {
        $data = 
        [
            'range'     => $request->range,
            'date_from' => $request->date_from,
            'date_to'   => $request->date_to,
            'area_code' => $request->area_code,
        ];

        $rule = 
        [
            'range'     => 'required',
            'date_from' => 'required',
            'date_to'   => 'required',
            'area_code' => 'required',
        ];

        $validator = Validator::make($data,$rule);

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
            if($request->range == "DAILY")
            {
                $where_date = 
                    [
                        'date_from' => $request->date_from,
                        'date_to'   => $request->date_to
                    ];
                $where =
                    [
                        'b.warehouse_class' => $request->area_code, 
                    ];
            }
            else if($request->range == "MONTHLY")
            {
                $from  = $request->date_from;
                $to = $request->date_to;
                $where_date = 
                    [
                        'date_from' => $from,
                        'date_to'   => $to,
                    ];
                $where = 
                    [
                        'b.warehouse_class' => $request->area_code
                        
                    ];  
            }
             
            try 
            {      
                DB::beginTransaction();
                $load = $this->irregularity->load_irregularity($where_date,$where); 
                DB::commit();
                $count = count($load);
                $result = [];
               
                for($a=0; $a<$count; $a++)
                {
                    $status = (int)$load[$a]->process_masterlist_id;

               
                    if($load[$a]->irreg_id <> "" && $status < 3)
                    {
                        $result[] = 
                        [
                            'ticket_no'                 => $load[$a]->ticket_no,
                            'order_download_no'         => $load[$a]->order_download_no,
                            'irregularity_type'         => $load[$a]->irregularity_type,
                            'process_masterlists_id'    => $load[$a]->process_masterlists_id,
                            'stock_address'             => $load[$a]->stock_address,
                            'item_no'                   => $load[$a]->item_no,
                            'item_name'                 => $load[$a]->item_name,
                            'delivery_qty'              => $load[$a]->delivery_qty,
                            'actual_qty'                => $load[$a]->actual_qty,
                            'discrepancy'               => $load[$a]->discrepancy,
                            'remarks'                   => $load[$a]->remarks,
                            'control_no'                => $load[$a]->control_no,
                            'normal_status'             => $load[$a]->normal_status,
                            'irreg_status'              => $load[$a]->irregularity_status,
                            'id'                        => $load[$a]->irreg_id
                        ];
                    }
                }
                        
                return response()
                    ->json([
                        'status' => 1,
                        'message' => '',
                        'data' =>  $result
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
    
    public function load_irregularity_item()
    {
        $where_date = 
        [
            'date_from' => Carbon::now()->toDateString(),
            'date_to'   => Carbon::now()->toDateString(),
        ];
        $where = [];
        try 
        {
            DB::beginTransaction();
            $load = $this->irregularity->load_irregularity($where_date,$where);
            DB::commit();
         
            $count = count($load);
            $result = [];
            for($a=0; $a<$count; $a++)
            {
                $result[] = 
                    [
                        'ticket_no'                 => $load[$a]->ticket_no,
                        'order_download_no'         => $load[$a]->order_download_no,
                        'irregularity_type'         => $load[$a]->irregularity_type,
                        'process_masterlists_id'    => $load[$a]->process_masterlist_id,
                        'stock_address'             => $load[$a]->stock_address,
                        'item_no'                   => $load[$a]->item_no,
                        'item_name'                 => $load[$a]->item_name,
                        'delivery_qty'              => $load[$a]->delivery_qty,
                        'actual_qty'                => $load[$a]->actual_qty,
                        'discrepancy'               => $load[$a]->discrepancy,
                        'remarks'                   => $load[$a]->remarks,
                        'id'                        => $load[$a]->irreg_id
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
                    'message'   => $th->getMessage(),
                    'data'      => '',
                ]);
        }           
    }

    /*
    * return @array
    * _method GET
    * request data required [range, date_from, date_to, area_code]
    */ 
    public function load_list_irregularity(Request $request)
    {
        $data = 
        [
            'range'     => $request->range,
            'date_from' => $request->date_from,
            'date_to'   => $request->date_to,
            'area_code' => $request->area_code,
        ];

        $rule = 
        [
            'range'     => 'required',
            'date_from' => 'required',
            'date_to'   => 'required',
            'area_code' => 'required',
        ];

        $validator = Validator::make($data,$rule);

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
            if($request->range == "DAILY")
            {
                $where_date = 
                    [
                        'date_from' => $request->date_from,
                        'date_to'   => $request->date_to
                    ];
                $where =
                    [
                    'b.warehouse_class' => $request->area_code
                    ];
            }
            else if($request->range == "MONTHLY")
            {
                $from  = date('Y-m-01', strtotime($request->date_from));
                $to = date('Y-m-t', strtotime($request->date_to));
                $where_date = 
                [
                    'date_from' => $from,
                    'date_to'   => $to
                ];
            $where =
                [
                    'b.warehouse_class' => $request->area_code
                ];
            }
                         
            try 
            {      
                DB::beginTransaction();
                $load = $this->irregularity->irregularity_list($where_date,$where); 
                DB::commit();
                $result_status = [];
                $result_no_status = [];
                $final_result = [];
                $count = count($load);
                for($a=0; $a<$count; $a++)
                {
                    if($load[$a]->irregularity_status != null)
                    {
                        $result_status[] = 
                            [
                                'ticket_no'                     => $load[$a]->ticket_no,
                                'order_download_no'             => $load[$a]->order_download_no,
                                'irregularity_type'             => $load[$a]->irregularity_type,
                                'process_masterlists_id'        => $load[$a]->process_masterlist_id,
                                'stock_address'                 => $load[$a]->stock_address,
                                'item_no'                       => $load[$a]->item_no,
                                'item_name'                     => $load[$a]->item_name,
                                'delivery_qty'                  => $load[$a]->delivery_qty,
                                'actual_qty'                    => $load[$a]->actual_qty,
                                'discrepancy'                   => $load[$a]->discrepancy,
                                'remarks'                       => $load[$a]->remarks,
                                'control_no'                    => $load[$a]->control_no,
                                'dr_control_no'                 => $load[$a]->dr_control_no,
                                'last_name'                     => $load[$a]->last_name,
                                'first_name'                    => $load[$a]->first_name,
                                'created_at'                    => $load[$a]->delivery_date,
                                'normal_status'                 => $load[$a]->normal_status,
                                'irreg_status'                  => $load[$a]->irregularity_status,
                                'id'                            => $load[$a]->irreg_id
                            ]; 
                    }
                    else
                    {
                        $result_no_status[] = 
                            [
                                'ticket_no'                     => $load[$a]->ticket_no,
                                'order_download_no'             => $load[$a]->order_download_no,
                                'irregularity_type'             => $load[$a]->irregularity_type,
                                'process_masterlists_id'        => $load[$a]->process_masterlist_id,
                                'stock_address'                 => $load[$a]->stock_address,
                                'item_no'                       => $load[$a]->item_no,
                                'item_name'                     => $load[$a]->item_name,
                                'delivery_qty'                  => $load[$a]->delivery_qty,
                                'actual_qty'                    => $load[$a]->actual_qty,
                                'discrepancy'                   => $load[$a]->discrepancy,
                                'remarks'                       => $load[$a]->remarks,
                                'control_no'                    => $load[$a]->control_no,
                                'dr_control_no'                 => $load[$a]->dr_control_no,
                                'last_name'                     => $load[$a]->last_name,
                                'first_name'                    => $load[$a]->first_name,
                                'created_at'                    => $load[$a]->delivery_date,
                                'normal_status'                 => $load[$a]->normal_status,
                                'irreg_status'                  => $load[$a]->irregularity_status,
                                'id'                            => $load[$a]->irreg_id
                            ]; 
                    }
                        
                }

                $final_result = array_merge($result_no_status, $result_status);
                      
                return response()
                    ->json([
                        'status' => 1,
                        'message' => '',
                        'data' =>  $final_result
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

    /*
    * return @array
    * _method DELETE
    * request data required [id]
    */ 
    public function delete_irregularity(Request $request)
    {
        $where = 
        [
            "id"        => $request->id
        ];

        $data =
        [
            "id"        => $request->id,
        ];

        $rule =
        [
            "id"        => 'required',
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

                $this->irregularity->delete_irregularity($where);
                DB::commit();
    
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $data
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

    public function update_irregularity_status(Request $request)
    {
        $where = 
        [
            "id"        => $request->id
        ];

        $data = 
        [
            'process_masterlists_id'    => 1
        ];

        $rule =
        [
            "id"        => 'required',
        ];

        $validator = Validator::make($where, $rule);

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

                $this->irregularity->update_irregularity_status($where, $data);
                DB::commit();
    
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $where
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

    public function load_control_no(Request $request)
    {
        $data = 
        [
            'date_range'     => $request->date_range,
            'date_from'      => $request->date_from,
            'date_to'        => $request->date_to,
            'area_code'      => $request->area_code,
        ];

        $rule = 
        [
            'date_range'     => 'required',
            'date_from'      => 'required',
            'date_to'        => 'required',
            'area_code'      => 'required',
        ];

        $validator = Validator::make($data,$rule);

        if($validator->fails())
        {
            return response()->json([
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
                $from = $request->date_from . " " . '00:00:00';
                $to   = $request->date_to . " " . '23:59:59';
                $where = 
                [
                    'b.warehouse_class' => $request->area_code
                ];

                $load = $this->irregularity->load_control_no($where,$from,$to);
                DB::commit();
                $collection = new Collection($load);
                $unique_load = $collection->unique('control_no');
                $result = [];
            
                foreach($unique_load as $item)
                {
                    $result[] =
                    [
                        'control_no' => $item->control_no,
                        'date'       => date('Y-m-d',strtotime($item->created_at))
                    ];
                }
                
                
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      =>  $result
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

    public function load_reprint(Request $request)
    {
        $data = 
        [
            'control_no'     => $request->control_no,
        ];

        $rule = 
        [
            'control_no'     => 'required',
        ];

        $validator = Validator::make($data,$rule);

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
            try 
            {      
                DB::beginTransaction();
                $where = 
                [
                    'b.control_no' => $request->control_no
                ];

                $load = $this->irregularity->load_reprint($where);
                DB::commit();
                
                $result = [];

                for($a=0;$a<count($load);$a++)
                {
                     $result[] = 
                        [
                            'ticket_no'                 => $load[$a]->ticket_no,
                            'order_download_no'         => $load[$a]->order_download_no,
                            'irregularity_type'         => $load[$a]->irregularity_type,
                            'process_masterlists_id'    => $load[$a]->process_masterlists_id,
                            'stock_address'             => $load[$a]->stock_address,
                            'item_no'                   => $load[$a]->item_no,
                            'item_name'                 => $load[$a]->item_name,
                            'delivery_qty'              => $load[$a]->delivery_qty,
                            'actual_qty'                => $load[$a]->actual_qty,
                            'discrepancy'               => $load[$a]->discrepancy,
                            'remarks'                   => $load[$a]->remarks,
                            'control_no'                => $load[$a]->control_no,
                            'normal_status'             => $load[$a]->normal_status,
                            'irreg_status'              => $load[$a]->irregularity_status,
                            'id'                        => $load[$a]->irreg_id,
                            'irreg_create'              => $load[$a]->irreg_create,

                    ];
                        
                }
                return response()
                    ->json([
                        'status' => 1,
                        'message' => '',
                        'data' =>  $result
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
}
