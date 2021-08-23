<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Forecast;
use Illuminate\Support\Facades\Validator;
use \DB;
use Carbon\Carbon;
use App\Http\Controllers\MonitoringController;

class ForecastController extends Controller
{
    protected $forecast;

    public function __construct(MonitoringController $monitoring)
    {
        $this->forecast = New Forecast();  
        $this->monitoring = $monitoring;
    }

    public function save_forecast(Request $request)
    {
        $looping = $request['data'];  

        // $date_data = [];
        // $data['date_forecast'] = 7/01/2020 ;
        // $data['date_forecast'] = 7/02/2020 ;

        // return $looping;

        foreach($looping as $loop)
        {

            $data =
            [
                'date_forecast'     => $loop['date'],
                'qty'               => $loop['qty'],
                'area_code'         => $loop['area_code']
            ];     
            try
            {
                DB::beginTransaction();

                $forecast = $this->forecast->search_forecast($data);
                // $date_data [] = $forecast;
                if(count($forecast) !== 0 )
                    $this->forecast->update_forecast($loop['date'], $loop['area_code'], $loop['qty']);
                else
                {
                    if ($loop['qty'] != 0)
                    {
                        $this->forecast->add_forecast($data);
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

    //    return $date_data;
        return response()
                    ->json([
                    'status'    =>  1,
                    'message'   =>  '',
                    'data'      =>  $looping
                ]);
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
                $load = $this->forecast->load_data($where, $request->date_from,$request->date_to);
                DB::commit();

                $result = [];

                for($a = 0; $a < count($array_date); $a++)
                {
                    $temp = [];

                    $qty = 0;

                    $temp['date'] = $array_date[$a];

                    for($b = 0; $b < count($load); $b++)
                    {
                        if($array_date[$a] == $load[$b]->date_forecast)
                        {
                            $qty = $load[$b]->qty;
                        }
                    }

                    $temp['qty'] = $qty;

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
}
