<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Timeout;
use Illuminate\Support\Facades\Validator;
use \DB;
use Carbon\Carbon;

class TimeoutController extends Controller
{
    protected $timeout;

    public function __construct(MonitoringController $monitoring)
    {
        $this->timeout = New Timeout();  
        $this->monitoring = $monitoring;
    }

    public function load_data(request $request)
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
                $load = $this->timeout->load_data($where, $request->date_from,$request->date_to);
                DB::commit();

                $result = [];
                $load_time_out = [];
                $load_time_in = [];
                $load_date = [];

                foreach($load as $loads)
                {
                    $load_date[] = $loads->date;
                    $load_time_out[] = $loads->time_out;
                    $load_time_in[] = $loads->time_in;
                }


                for($a = 0; $a < count($array_date); $a++)
                {
                    $temp = [];

                    $time_out = '';
                    $time_in = '';

                    $temp['date'] = $array_date[$a];

                    for($b=0;$b<count($load_date);$b++)
                    {
                        if($array_date[$a]  == $load_date[$b])
                        {
                            if(is_null($load_time_in[$b]))
                            {
                                $time_in = '';
                            }
                            else
                            {
                                $time_in = $load_time_in[$b];
                                // $time_in = date("h:i a", strtotime($load_time_in[$b]));
                            }
                            if(is_null($load_time_out[$b]))
                            {
                                $time_out = '';
                            }
                            else                            
                            {
                                $time_out = $load_time_out[$b];
                                // $time_out = date("h:i a", strtotime($load_time_out[$b]));                               
                            }
                        }
                        $temp['time_in'] = $time_in;
                        $temp['time_out'] = $time_out;
                    }

                    // for($b = 0; $b < count($load); $b++)
                    // {
                    //     if($array_date[$a] == $load[$b]->date)
                    //     {
                    //         if(is_Null($load[$b]->time_out) && is_Null($load[$b]->time_in)) 
                    //         {
                    //             $time_out = ""; 
                    //             $time_in = "";
                    //         }
                    //         // elseif(is_Null($load[$b]->time_in))
                    //         // {
                    //         //     $time_in = "";
                    //         // }
                    //         else
                    //         {
                    //             $time_out = date("g:i a", strtotime($load[$b]->time_out));
                    //             $time_in = date("g:i a", strtotime($load[$b]->time_in));
                    //         }
                            
                    //     }
                    //     $temp['time_out'] = $time_out;
                    //     $temp['time_in'] = $time_in;
                    // }

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

    public function save_timeout(Request $request)
    {
        $looping = $request->data;  
      
        foreach($looping as $loop)
        {
            $data =
            [
                'date'      => $loop['date'],
                'time_in'   => $loop['time_in'],
                'time_out'  => $loop['time_out'],
                'area_code' => $loop['area_code']
            ]; 
           
            
            $rule = 
            [
                'date'          => 'required',
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

                    $timeout = $this->timeout->search_timeout($data);
                    if($timeout > 0 )
                    {
                        $value=
                        [
                            'time_in'  => $loop['time_in'],
                            'time_out' => $loop['time_out']
                        ];
                        $this->timeout->update_timeout($value,$loop['date'], $loop['area_code']);
                    }
                    else
                    {
                        if(!(is_null ($loop['time_in']) || (is_null ($loop['time_out']))))
                        {
                            $this->timeout->add_timeout($data);          
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

    //     return response()
    //                 ->json([
    //                 'status'    =>  1,
    //                 'message'   =>  '',
    //                 'data'      =>  $looping
    //             ]);
    } 

}
