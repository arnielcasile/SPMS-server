<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Picking;
use Illuminate\Support\Facades\Validator;
use \DB;
use Carbon\Carbon;
use App\Http\Controllers\MonitoringController;

class PickingController extends Controller
{
    protected $picker;

    public function __construct(MonitoringController $monitoring)
    {
        $this->picker = New Picking();  
        $this->monitoring = $monitoring;
    }

    public function load_data(Request $request)
    {
        $data = 
        [
            'date_from'  => $request->date_from,
            'date_to'    => $request->date_to,
            'area_code'  => $request->area_code
        ];

        $rule = 
        [
            'date_from'  => 'required',
            'date_to'    => 'required',
            'area_code'  => 'required'
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
                    'area_code' => $request->area_code
                ];

                $array_date = $this->monitoring->date_range($request->date_from,$request->date_to);

                $load = $this->picker->load_data($where, $request->date_from,$request->date_to);

                DB::commit();

                $result = [];

                for($a = 0; $a < count($array_date); $a++)
                {
                    $temp = [];

                    $qty = 0;

                    $temp['picking_date'] = $array_date[$a];

                    for($b = 0; $b < count($load); $b++)
                    {
                        if($array_date[$a] == $load[$b]->picking_date)
                        {
                            $qty = $load[$b]->picker_count;
                        }
                    }

                    $temp['picker_count'] = $qty;

                    $result[] = $temp;

                }

                return response()
                ->json([
                    'status'    => 1,
                    'message'   =>'',
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

    public function save_picker(Request $request)
    {
        // $looping = [];
        // $array = [];
        // $array['date'] = '2020-02-23';
        // $array['picker_count'] = 1;
        // $array['area_code'] = 'C1';
        // $looping[] = $array;

        // $array = [];
        // $array['date'] = '2020-02-24';
        // $array['picker_count'] = 0;
        // $array['area_code'] = 'C1';
        // $looping[] = $array;

        $looping = $request['data'];

        foreach($looping as $loop)
        {
            $data =
            [
                'picking_date'     => $loop['date'],
                'picker_count'      => $loop['picker_count'],
                'area_code'         => $loop['area_code']
            ];

            try
            {
                DB::beginTransaction();

                $picking = $this->picker->search_picking($data);
                
                if(count($picking) !== 0 )
                {
                    $this->picker->update_picking($loop['date'],$data);
                }
                else
                {
                    if ($loop['picker_count'] != 0)
                    {
                        $this->picker->add_picking($data);
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
                    'status'    =>  1,
                    'message'   =>  '',
                    'data'      =>  $looping
                ]);
    }
}
