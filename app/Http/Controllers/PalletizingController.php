<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Checking;
use App\Palletizing;
use App\DrMaking;
use App\Irregularity;
use \DB;

class PalletizingController extends Controller
{
    protected $checking, $palletizing, $drmaking;
    
    public function __construct()
    {
        $this->checking = new Checking();
        $this->palletizing = new Palletizing();
        $this->drmaking = new DrMaking();
        $this->irregularity = new Irregularity();
    }

    public function load_barcode(Request $request)
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
                    'status'    =>  0,
                    'message'   =>  $validator->errors()->all(),
                    'data'      =>  ''
                ]);
        }
        else
        {
            try
            {
                DB::beginTransaction();
                $where = 
                [
                    'a.ticket_no' => $request->ticket_no
                ];
                $load = $this->checking->load($where);
                DB::commit();
                $result = [];
                $count = count($load);

                for($a=0;$a<$count;$a++)
                {
                    $result[] = 
                        [
                            'normal'            => $load[$a]->normal_ticket,
                            'completion'        => $load[$a]->completion_ticket,
                            'item_no'           => $load[$a]->item_no,
                            'order_download_no' => $load[$a]->order_download_no,
                            'delivery_qty'      => $load[$a]->delivery_qty,
                            'normal_status'     => $load[$a]->normal_status,
                            'irreg_status'      => $load[$a]->irregularity_status,
                            'area_code'         => $load[$a]->warehouse_class,
                            'pdl'               => $load[$a]->pdl,
                            'destination_code'  => $load[$a]->destination_code,
                            'manufacturing_no'  => $load[$a]->manufacturing_no,
                            'destination'       => $load[$a]->destination,
                            'irreg_type'        => $load[$a]->irregularity_type,
                            'dest_deleted_at'   => $load[$a]->dest_deleted_at,
                            

                        ];
                }
                return response()
                    ->json([
                    'status'    =>  1,
                    'message'   =>  '',
                    'data'      =>  $result
                ]);
                

            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                    ->json([
                    'status'    =>  0,
                    'message'   =>  $th->getMessage(),
                    'data'      =>  ''
                ]);
            }
        }
    }

    public function load_ongoing_palletizing(Request $request)
    {
        $data =
            [
                'warehouse_class' => $request->area_code
            ];
        
        $rule = 
            [
                'warehouse_class' => 'required'
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
                DB::beginTransaction();
                $load = $this->palletizing->load_ongoing_palletizing($data['warehouse_class']);
                DB::commit();

                $result = [];
                $count = count($load);

                for($a=0;$a<$count;$a++)
                {
                    $result[] = 
                        [
                            'dr_control'         => $load[$a]->dr_control,
                            'order_download_no'  => $load[$a]->order_download_no,
                            'delivery_type'      => $load[$a]->delivery_type,
                            'delivery_no'        => $load[$a]->delivery_no,
                            'total_items'        => $load[$a]->total_items,
                            'process'            => $load[$a]->process,
                            'destination_code'   => $load[$a]->destination_code,
                            'manufacturing_no'   => $load[$a]->manufacturing_no,
                            'destination'        => $load[$a]->destination,
                            'payee_name'         => $load[$a]->payee_name
                            
                        ];
                }
                return response()
                    ->json([
                    'status'    =>  1,
                    'message'   =>  '',
                    'data'      =>  $result
                ]);
            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                    ->json([
                    'status'    =>  0,
                    'message'   =>  $th->getMessage(),
                    'data'      =>  ''
                ]);
            }

        }
    }

    public function remove_palletizing_item(Request $request)
    {
        $data =
            [
                'id'        => $request->id,
                'ticket_no' => $request->ticket_no,
                'process'   => $request->process,
                'irreg_type'=> $request->irreg_type,
            ];
        
        $rule = 
            [
                'id'            => 'required|exists:palletizings',
                'ticket_no'     => 'required',
                'process'       => 'required',
                'irreg_type'    => 'required',
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
                $normal_data =
                    [
                        'process_masterlist_id' => 2,
                    ];

                $completion_data =
                    [
                        'process_masterlists_id' => 2,
                        'dr_control_no'          => ''
                    ];
                $where =
                    [
                        'ticket_no' => $request->ticket_no,
                    ];
                
                DB::beginTransaction();

                $this->palletizing->remove_ongoing_palletizing($request->id);

                if($request->process == 'NORMAL')
                {
                    $this->checking->update_checking("master_data", $normal_data, $where);
                }
                else
                {
                    if($request->irreg_type == "NO STOCK" || $request->irreg_type == "EXCESS")
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

                return response()
                    ->json([
                        'status'    =>  1,
                        'message'   =>  '',
                        'data'      =>  $data
                ]);
            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    =>  0,
                        'message'   =>  $th->getMessage(),
                        'data'      =>  ''
                ]);

            }
        }

    }

    //old function
    public function save_palletizing(Request $request)
    {
        // $looping = [];
        // $array = [];
        // $array['process'] = 'COMPLETION';
        // $array['ticket_no'] = 'MK5E0294201';
        // $array['delivery_type'] = 24; 
        // $array['delivery_no'] = 3;
        // $array['users_id'] = 157;
        // $array['order_download_no'] = '-';
        // $array['irreg_type'] = 'NO STOCK';
        
        // $looping[] = $array;

        // $array = [];
        // $array['process'] = 'NORMAL';
        // $array['ticket_no'] = 'MK5E0294201';
        // $array['delivery_type'] = 24; 
        // $array['delivery_no'] = 3;
        // $array['users_id'] = 157;
        // $array['order_download_no'] = '-';
        // $looping[] = $array;

        // $array['process'] = 'COMPLETION';
        // $array['ticket_no'] = 'MK5K0151701';
        // $array['delivery_type'] = 24; 
        // $array['delivery_no'] = 3;
        // $array['users_id'] = 157;
        // $array['order_download_no'] = '-';
        // $array['irreg_type'] = 'NO STOCK';
        
        // $looping[] = $array;

        // $array = [];
        // $array['process'] = 'NORMAL';
        // $array['ticket_no'] = 'MK5K0151701';
        // $array['delivery_type'] = 24; 
        // $array['delivery_no'] = 3;
        // $array['users_id'] = 157;
        // $array['order_download_no'] = '-';
        // $looping[] = $array;
        
        $looping = $request['data']; 

        
        $order_download_no = $looping[0]['order_download_no'];
        $users_id = $looping[0]['users_id'];

        
        $temp_ticket =[];
        
        foreach($looping as $arr)
        {
            $temp_ticket[] = $arr['ticket_no'];
        }

        $result = count($temp_ticket) != count(array_unique($temp_ticket));

        if($order_download_no == "-")
        {     
            if($result == true)
            {
                    $normal =  $this->palletizing->get_user($users_id)->generate_same_ticket_control('NORMAL', '1', $result,$temp_ticket);
                    $completion = $this->palletizing->get_user($users_id)->generate_same_ticket_control('COMPLETION', 'A', $result,$temp_ticket);
        
            }
            else
            {
                $normal = $this->palletizing->get_user($users_id)->generate_dash_control('NORMAL', '1');
                $completion = $this->palletizing->get_user($users_id)->generate_dash_control('COMPLETION', 'A');
            }
        }
        else
        {
            $normal = $this->palletizing->generate_control($order_download_no);
            $completion = $this->palletizing->generate_control($order_download_no, 'COMPLETION', 'A', 0);
        }
     
        foreach($looping as $loop)
        {
            if($loop['process'] == "NORMAL")
                $control_no = $normal;
            else
                $control_no = $completion;
    
           
            $data = [];
            $data[] = 
            [
                'process'           => $loop['process'],
                'ticket_no'         => $loop['ticket_no'],
                'delivery_type_id'  => $loop['delivery_type'],
                'delivery_no'       => $loop['delivery_no'],
                'dr_control'        => $control_no,
                'users_id'          => $loop['users_id'],
                'created_at'        => date("Y-m-d H:i:s")
            ];
            
            $where =
            [
                'ticket_no' => $loop['ticket_no'],
            ];
        
            try
            {
                DB::beginTransaction();

                $normal_data =
                [
                    'process_masterlist_id' => 3,
                ];

                $completion_data =
                [
                    "process_masterlists_id" => 3,
                    "dr_control_no" => $control_no,
                ];

                $this->palletizing->add_palletizing($data);

                if($loop['process'] == "NORMAL")
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
        return response()
            ->json([
                'status'    => 1,
                'message'   => '',
                'data'      => $data
            ]); 
    }

    public function new_save_palletizing(Request $request)
    {
    //    $looping = [];
    //     $array = [];
    //     $array['process'] = 'NORMAL';
    //     $array['ticket_no'] = 'MK830119201';
    //     $array['delivery_type'] = 24; 
    //     $array['delivery_no'] = 3;
    //     $array['users_id'] = 157;
    //     $array['order_download_no'] = 'P14-PL-20-1374';
    //     // $array['irreg_type'] = 'NO STOCK';
        
    //     $looping[] = $array;

    //     $array = [];
    //     $array['process'] = 'NORMAL';
    //     $array['ticket_no'] = 'MK830118301';
    //     $array['delivery_type'] = 24; 
    //     $array['delivery_no'] = 3;
    //     $array['users_id'] = 157;
    //     $array['order_download_no'] = 'P14-PL-20-1374';
    //     $looping[] = $array;

    //     $array['process'] = 'NORMAL';
    //     $array['ticket_no'] = 'MK830117301';
    //     $array['delivery_type'] = 24; 
    //     $array['delivery_no'] = 3;
    //     $array['users_id'] = 157;
    //     $array['order_download_no'] = 'P14-PL-20-1374';
        
    //     $looping[] = $array;

        // $array = [];
        // $array['process'] = 'NORMAL';
        // $array['ticket_no'] = 'MK9E0235701';
        // $array['delivery_type'] = 24; 
        // $array['delivery_no'] = 3;
        // $array['users_id'] = 178;
        // $array['order_download_no'] = 'P14-PL-20-2285';
        // // $array['irreg_type'] = 'LACKING';
        // $looping[] = $array;

        // $array = [];
        // $array['process'] = 'NORMAL';
        // $array['ticket_no'] = 'MK9E0235801';
        // $array['delivery_type'] = 24; 
        // $array['delivery_no'] = 3;
        // $array['users_id'] = 178;
        // $array['order_download_no'] = 'P14-PL-20-2285';
        // $array['irreg_type'] = 'LACKING';
        // $looping[] = $array;
        
        $looping = $request['data']; 
        
        $order_download_no = $looping[0]['order_download_no'];
        $users_id = $looping[0]['users_id'];
        // return $users_id;
        $temp_ticket =[];
        $temp_process =[];
        
        foreach($looping as $arr)
        {
            $temp_ticket[] = $arr['ticket_no'];
            $temp_process[] = $arr['process'];
        }
        $input_count = count(array_unique($temp_ticket));
        $process_count = $this->palletizing->get_completion_process(array_unique($temp_ticket));

        $dr_control = $this->generate_dr_control($order_download_no, $process_count, $input_count);
    //    return $dr_control;
        foreach($looping as $loop)  
        {
            $control_no = $dr_control;
            
            $data = [];
            $data[] = 
            [
                'process'           => $loop['process'],
                'ticket_no'         => $loop['ticket_no'],
                'delivery_type_id'  => $loop['delivery_type'],
                'delivery_no'       => $loop['delivery_no'],
                'dr_control'        => $control_no,
                'users_id'          => $loop['users_id'],
                'created_at'        => date("Y-m-d H:i:s")
            ];
            
            $where =
            [
                'ticket_no' => $loop['ticket_no'],
            ];
        
            try
            {
                DB::beginTransaction();

                $normal_data =
                [
                    'process_masterlist_id' => 3,
                ];

                $completion_data =
                [
                    "process_masterlists_id" => 3,
                    "dr_control_no" => $control_no,
                ];

                $this->palletizing->add_palletizing($data);

                if($loop['process'] == "NORMAL")
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
        return response()
            ->json([
                'status'    => 1,
                'message'   => '',
                'data'      => $data
            ]); 
    
    }

    public function generate_dr_control($order_download_no, $process, $input_count)
    {
        $dr_control = '';
        $previous_dr = $this->palletizing->previous_dr_control($process, $order_download_no);
        $ticket_count = $this->palletizing->ticket_count($order_download_no);
        // return $input_count;
        // $total_ticket = $ticket_count
        
            if($ticket_count == $input_count)
            {
                if($process == 0)
                {
                    $dr_control = $order_download_no;
                }
                else
                {
                    $dr_control = $order_download_no . "-" . "A";
                }
                    
            }
            else
            {
                if(count($previous_dr) == 0)
                {
                    if($process == 0)
                    {
                        $dr_control = $order_download_no . "-" . "1";
                    }
                    else
                    {
                        $dr_control = $order_download_no . "-" . "A";
                    }
                }
                else
                {
                    $explode = explode('-', $previous_dr[0]->dr_control);
                    $identifer = 1;
                    if(is_numeric($explode[4]))
                    {
                        ($identifer) ? $identifer = ($explode[4] + 1) : $identifer = ++$explode[4];
                        $dr_control = $order_download_no . "-" . $identifer;
                    }
                    else
                    {
                        $letter = $explode[4];
                        $increment = chr(ord($letter)+$identifer);
                        $dr_control = $order_download_no . "-" . $increment;
                    }
                }     
            }
            return $dr_control;       

    }

    public function load_palletizing_items(Request $request)
    {
        $where = 
        [
            'dr_control' => $request->dr_control 
        ];

        $rule = 
        [
            'dr_control' => 'required'   
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
                $load = $this->palletizing->load_palletizing_items($where);
                DB::commit();
                $count = count($load);
                $result = [];
                for($a=0;$a<$count;$a++)
                {
                    $result[] =
                    [
                        'ticket_no'         => $load[$a]->ticket_no,
                        'delivery_type'     => $load[$a]->delivery_type,
                        'delivery_no'       => $load[$a]->delivery_no,
                        'item_no'           => $load[$a]->item_no,
                        'delivery_qty'      => $load[$a]->delivery_qty,
                        'order_download_no' => $load[$a]->order_download_no,
                        'users_id'          => $load[$a]->users_id,
                        'process'           => $load[$a]->palletizing_process,
                        'id'                => $load[$a]->palletizing_id,
                        'pdl'               => $load[$a]->pdl,
                        'destination_code'  => $load[$a]->destination_code,
                        'manufacturing_no'  => $load[$a]->manufacturing_no,
                        'dr_control'        => $load[$a]->dr_control,
                        'irreg_type'        => $load[$a]->irregularity_type
                    ];
                }
            
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

    public function add_palletizing(Request $request)
    {
        $data = 
        [
            'ticket_no'         => $request->ticket_no,
            'users_id'          => $request->users_id,
            'dr_control'        => $request->dr_control,
            'delivery_type_id'  => $request->delivery_type_id,
            'delivery_no'       => $request->delivery_no,
            'process'           => $request->process,
            'irreg_type'        => $request->irreg_type,
            'created_at'        => date("Y-m-d H:i:s")
        ];

        $rule =
        [
            'ticket_no'         => 'required',
            'users_id'          => 'required',
            'dr_control'        => 'required',
            'delivery_type_id'  => 'required',
            'delivery_no'       => 'required',
            'process'           => 'required',
            'created_at'        => 'required',
        ];

        $validator = Validator::make($data,$rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'messgae'   => $validator->errors()->all(),
                    'data'      => ''
                ]);
        }
        else
        {
            try
            {

                DB::beginTransaction();

                $normal_data = ['process_masterlist_id' => 3];

                $completion_data = ['process_masterlists_id' => 3, 'dr_control_no' => $request->dr_control];

                $where = ['ticket_no' => $request->ticket_no];

                $where_load = ['a.ticket_no' => $request->ticket_no];

                $insert_data = 
                [
                    'ticket_no'         => $request->ticket_no,
                    'users_id'          => $request->users_id,
                    'dr_control'        => $request->dr_control,
                    'delivery_type_id'  => $request->delivery_type_id,
                    'delivery_no'       => $request->delivery_no,
                    'process'           => $request->process,
                    'created_at'        => date("Y-m-d H:i:s")
                ];

                $this->palletizing->add_palletizing($insert_data);

                if($request->process == 'NORMAL')
                { 
                    $this->checking->update_checking("master_data", $normal_data, $where); 
                }
                else
                {
                    if($request->irreg_type == 'NO STOCK' || $request->irreg_type == 'EXCESS')
                    {
                        $this->checking->update_checking("master_data", $normal_data, $where); 
                        $this->checking->update_checking("irregularity", $completion_data, $where);
                    }
                    else
                    {    
                        $this->checking->update_checking("irregularity", $completion_data, $where);
                    }
                }
                
                $load = $this->checking->load($where_load);

                DB::commit();

                $result[] = 
                [
                    'ticket_no'         => $request->ticket_no,
                    'users_id'          => $request->users_id,
                    'dr_control'        => $request->dr_control,
                    'delivery_type_id'  => $request->delivery_type_id,
                    'delivery_no'       => $request->delivery_no,
                    'process'           => $request->process,
                    'created_at'        => date("Y-m-d H:i:s"),
                    'area_code'         => $load[0]->warehouse_class
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

    public function finish_palletizing(Request $request)
    {
        $data_rule =
        [
            'dr_control'        => $request->dr_control,
            'pcase'             => $request->pcase,
            'box'               => $request->box,
            'bag'               => $request->bag,
            'pallet'            => $request->pallet,
            'users_id'          => $request->users_id,
            // 'process'           => $request->process
        ];

        $rule =
        [
            'dr_control'        => 'required',
            'pcase'             => 'required',
            'box'               => 'required',
            'bag'               => 'required',
            'pallet'            => 'required',
            'users_id'          => 'required',
            // 'process'           => 'required'
        ];

        $validator = Validator::make($data_rule,$rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'messgae'   => $validator->errors()->all(),
                    'data'      => ''
                ]);
        }
        else
        {
            try
            {
                DB::beginTransaction();

                $normal_data =
                [
                    "a.process_masterlist_id" => 4,
                ];

                $completion_data =
                [
                    "process_masterlists_id" => 4,
                ];

                $where_completion =
                [
                'dr_control_no' => $request->dr_control,
                ];

                $where_normal =
                [
                'b.dr_control' => $request->dr_control,
                ];
                
                $load = $this->palletizing->load_palletizing_items($request->dr_control);

                foreach($load as $load_data)
                {
                    $ticket_no = $load_data->ticket_no;
                    $process = $load_data->palletizing_process;

                    if($process == 'NORMAL')
                    {       
                
                        $this->palletizing->update_checking($normal_data, $where_normal);
                    }
                    else
                    {
                  
                            $where_irreg = 
                        [
                            'a.dr_control_no' => $request->dr_control,
                        ];
                        $load_irregularity = $this->irregularity->select_irreg_type($where_irreg);

                        foreach($load_irregularity as $data)
                        {
                            $where_palletizing =
                            [
                                'a.ticket_no' => $data->ticket_no
                            ];
                            $where_checking =
                            [
                                'ticket_no' => $data->ticket_no
                            ];
                            if($data->irregularity_type == "NO STOCK" || $data->irregularity_type == "EXCESS")
                            {
                                $this->palletizing->update_checking($normal_data, $where_palletizing);
                                $this->checking->update_checking("irregularity", $completion_data, $where_checking);
                            }
                            else
                            {
                                $this->checking->update_checking("irregularity", $completion_data, $where_checking);
                            }
                        }
                    }
                    
                }
                $arr=explode('-',$request->dr_control); // get the dr_control and seperate it in dashes
                unset($arr[count($arr)-1]);  // remove the last value in array
                $arr_=implode('-',$arr); // join the array

                $masterdata_process_count_where  = ['a.order_download_no'=>$arr_];
                // $masterdata_drmaking_count_where = ['process_masterlist_id'=>'4'];
               
                $masterdata_count = $this->drmaking->masterdata_process_count($masterdata_process_count_where);
                $a=0;
                foreach($masterdata_count as $values)
                {
                    if(($values->irreg_ticket == null))
                    {
                        if(($values->master_process < 4 || $values->irreg_process < 4) && ($values->irreg_process != null))
                        {
                            $a+=1;
                        }
                        else if($values->master_process < 4 )
                        {
                            $a+=1;
                        }
                    }
                    else
                    {
                        if($values->master_process < 4 || $values->irreg_process < 4 || $values->irreg_process == null)
                        {
                            $a+=1;
                        }
                        else if($values->master_process < 4 )
                        {
                            $a+=1;
                        }
                    }
                   
                }
                if($a>0)
                {
                    $delivery_status=['delivery_status' => 'Partial Delivery'];
                    $data_rule=array_merge($data_rule, $delivery_status);
                }
                else
                {
                    $delivery_status=['delivery_status' => 'Full Delivery'];
                    $data_rule=array_merge($data_rule, $delivery_status);
                }
               
// return $data_rule;
                $this->drmaking->add_finish_palletizing($data_rule);

                DB::commit();

                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $data_rule
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
