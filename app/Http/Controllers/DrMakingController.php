<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\DrMaking;
use App\Checking;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;


class DrMakingController extends Controller
{
    protected $dr_making, $checking;

    public function __construct()
    {
        $this->dr_making = new DrMaking();
        $this->checking = new Checking();
    }
     /*
    * return @array
    * _method PATCH
    * request data required [dr_control_no,user_id]
    */
    public function update_dr_making(Request $request)
    {
            // $data[] = 
            // [
            //     'dr_control_no' => 'P14-PL-20-933-1',
            // ];

            // $data[] =
            // [
            //     'dr_control_no' => 'P14-PL-20-513-A',
            // ];

            $data = $request->data;
            $result = [];

            try
            {
                $data_completion = 
                [
                    'a.process_masterlists_id' => '5'
                ];

                $data_users_id =
                [
                    'c.users_id' =>$request->user_id
                ];

                $data_normal = 
                [
                    'a.process_masterlist_id' => '5'
                ];
                
                for($a=0;$a<count($data);$a++)
                {
                    $where =
                    [
                        'c.dr_control' => $data[$a]['dr_control_no']
                    ];          
                                         
                    DB::beginTransaction();

                    $count = explode('-', $data[$a]['dr_control_no']);
                    $no = $count[4];

                    $this->dr_making->update_dr_makings($where,$data_users_id);

                    if(is_numeric($no))
                    {
                        $this->dr_making->update_process('master_data', $where, $data_normal);
                    }
                    else
                    {
                        $this->dr_making->update_process('irregularity', $where, $data_completion);
                    }   
                    $load = $this->dr_making->load_data($where);
                    $collection = new Collection($load);
                    
                    // Get all unique items.
                    $unique_items = $collection->unique('ticket_no');

                    $load = $this->dr_making->load_data($where,$no);

                    DB::commit();
                    $count = count($load);
                   
                    DB::commit();
                    $count = count($unique_items);
                  
                    $dt = Carbon::now();

                    foreach($unique_items as $item)
                    {
                        if($item->dr_control_no == null) 
                        {
                            $qty = $item->delivery_qty;
                        }
                        else
                        {
                            if(is_numeric($no))
                                {
                                    $qty =  $item->actual_qty;
                                }
                                else
                                {
                                    $qty = $item->discrepancy;
                                }
                        }
                        $result[] = 
                            [
                                'dr_no'                 => $item->dr_control,
                                'delivery_type'         => $item->delivery_type . " " . $item->delivery_no,
                                'order_download_no'     => $item->order_download_no,
                                'item_rev'              => $item->item_rev,
                                'item_no'               => $item->item_no,
                                'item_name'             => $item->item_name,
                                'delivery_qty'          => $qty,
                                'stock_address'         => $item->stock_address,
                                'manufacturing_no'      => $item->manufacturing_no,
                                'payee_cd'              => $item->payee_cd,
                                'ticket_no'             => $item->ticket_no,
                                'product_no'            => $item->product_no,
                                'destination'           => $item->destination,
                                'dr_control'            => $item->dr_control,
                                'datetoday'             => $dt->format('D M d Y'),
                                'checker_name'          => $item->first_name . " " . $item->middle_name . " " . $item->last_name,
                                'pallet'                => $item->pallet,
                                'pcase'                 => $item->pcase,
                                'box'                   => $item->box,
                                'bag'                   => $item->bag,
                            ];         
                    }              
                }               
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   =>  '',
                        'data'      => $result
                    ]);
            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   =>  $th->getMessage(),
                        'data'      => ''
                    ]);
            }
    }
   
    public function load_dr_making(Request $request)
    {
        $where = 
        [
            'area_code'    => $request->user_area_code
        ];

        $rule = 
        [
            'area_code'    => 'required'
        ];

        $validator = Validator::make($where,$rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   =>  $validator->errors()->all(),
                    'data'      =>  ''
                ]);
        }
        else
        {
            try
            {   
                DB::beginTransaction();
                // return $this->dr_making->load_dr_making($where);
                $load = $this->dr_making->load_dr_making($where);
                // return $load;
                DB::commit();
                $count_load = count($load);
                $result = [];
                $ticket_count = 0;
                $dr_control = null;
                $dr_control_temp = null;
                $dr_control = [];

                for ($z = 0; $z<$count_load ; $z++)
                {
                    if (!in_array($load[$z]->dr_control,$dr_control))
                    {
                    $dr_control [] = $load[$z]->dr_control;
                    }
                }

                $count_control = count($dr_control);

                for($y = 0; $y<$count_control ; $y++)
                {
                    $temp_id           = null;
                    $total_qty         = 0;
                    $temp_dr_control   = null;
                    $destination       = null;

                    for ($x = 0; $x<$count_load ; $x++)
                    {
                        if ($dr_control[$y] == $load[$x]->dr_control) 
                        {
                            if($load[$x]->process == "NORMAL" &&
                                    $load[$x]->normal == 4)
                            {
                                $total_qty += 1;
                                $temp_dr_control   = $load[$x]->dr_control;
                                $temp_id           = $load[$x]->id;
                                $destination       = $load[$x]->destination;
                                $attention_to       = $load[$x]->attention_to;
                            }
                            elseif($load[$x]->process == "COMPLETION" &&
                                $load[$x]->irreg == 4)
                            {
                                $total_qty += 1;
                                $temp_dr_control   = $load[$x]->dr_control;
                                $temp_id           = $load[$x]->id;
                                $destination       = $load[$x]->destination;
                                $attention_to       = $load[$x]->attention_to;
                            }
                        }
                        
                    }
                    if($temp_id <> null)
                    {
                        $result[] =
                        [
                            'dr_id'             => $temp_id,
                            'dr_control'        => $temp_dr_control,
                            'ticket_count'      => $total_qty,
                            'destination'       => $destination,
                            'attention_to'      => $attention_to,
                        ];
    
                    }
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
                        'message'   =>  $th->getMessage(),
                        'data'      => ''
                    ]);
            }  
        }
    }   

    public function load_dr_no(Request $request)
    {
        $data = 
        [
            'date_range'    => $request->date_range,
            'date_from'     => $request->date_from,
            'date_to'       => $request->date_to,
            'area_code'     => $request->area_code

        ];

        $rule = 
        [
            'date_range'    => 'required',
            'date_from'     => 'required',
            'date_to'       => 'required',
            'area_code'     => 'required'
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
                $from  = $request->date_from . " " . '00:00:00';
                $to    = $request->date_to . " " . '23:59:59';
                $where = 
                [
                    'c.warehouse_class' => $request->area_code
                ];
                
                $load = $this->dr_making->load_dr_no($from, $to, $where);
                DB::commit();

                $collection = new Collection($load);
                $unique_items = $collection->unique('dr_control');

                $result = [];
                foreach($unique_items as $items)
                {
                    $result[] =
                    [
                        'dr_control' => $items->dr_control
                    ];

                }

                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      =>  $result
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

    public function load_dr_making_details(Request $request)
    {
        $data = 
        [
            'dr_control'    => $request->dr_control,
        ];

        $rule = 
        [
            'dr_control'    => 'required',
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
                $where = 
                [
                    'a.dr_control' => $request->dr_control
                ];
                
                $load = $this->dr_making->load_dr_making_details($where);
                DB::commit();

                $result = [];

                for($a=0;$a<count($load);$a++)
                {
                    $result [] =
                    [
                        'dr_control'   => $load[$a]->dr_control,
                        'pallet_qty'   => $load[$a]->pallet,
                        'pcase_no'     => $load[$a]->pcase,
                        'box'          => $load[$a]->box,
                        'bag'          => $load[$a]->bag,
                        'attention_to' => $load[$a]->attention_to,
                    ];
                }

                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      =>  $result
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

    public function load_reprint_data(Request $request)
    {
        $data = 
        [
            'dr_control'    => $request->dr_control,
            'pallet_qty'    => $request->pallet_qty,
            'pcase_no'      => $request->pcase_no,
            'box'           => $request->box,
            'bag'           => $request->bag,
        ];

        $rule = 
        [
            'dr_control'    => 'required',
            'pallet_qty'    => 'required',
            'pcase_no'      => 'required',
            'box'           => 'required',
            'bag'           => 'required',
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
                $where = 
                [
                    'c.dr_control' => $request->dr_control
                ];

                $where_data = 
                [
                    'dr_control' => $request->dr_control,
                    'pcase'      => $request->pcase_no,
                    'pallet'     => $request->pallet_qty,
                    'box'        => $request->box,
                    'bag'        => $request->bag
                ];
                
                
                $load_dr = $this->dr_making->load_dr_making_details($where_data);

                if(count($load_dr) == 0)
                {
                    $this->dr_making->update_dr_makings($where, $where_data);
                }

                $load = $this->dr_making->load_data($where);

                DB::commit();
                
                $result =[];
               
                $dt = Carbon::now();
                 
                for($b=0;$b<count($load);$b++)
                {
                    $result[] = 
                    [
                        'dr_no'                 => $load[$b]->dr_control,
                        'delivery_type'         => $load[$b]->delivery_type . " " . $load[$b]->delivery_no,
                        'order_download_no'     => $load[$b]->order_download_no,
                        'item_rev'              => $load[$b]->item_rev,
                        'item_no'               => $load[$b]->item_no,
                        'item_name'             => $load[$b]->item_name,
                        'delivery_qty'          =>  $load[$b]->delivery_qty,
                        'stock_address'         => $load[$b]->stock_address,
                        'manufacturing_no'      => $load[$b]->manufacturing_no,
                        'payee_cd'              => $load[$b]->payee_cd,
                        'ticket_no'             => $load[$b]->ticket_no,
                        'product_no'            => $load[$b]->product_no,
                        'destination'           => $load[$b]->destination,
                        'dr_control'            => $load[$b]->dr_control,
                        'datetoday'             => $dt->format('D M d Y'),
                        'checker_name'          => $load[$b]->first_name . " " . $load[$b]->middle_name . " " . $load[$b]->last_name,
                        'pallet'                => $load[$b]->pallet,
                        'pcase'                 =>  $load[$b]->pcase,
                        'box'                   => $load[$b]->box,
                        'bag'                   => $load[$b]->bag,
                    ];
                }                 
                           

              
                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      =>  $result
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
