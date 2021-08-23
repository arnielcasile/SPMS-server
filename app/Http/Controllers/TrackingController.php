<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Tracking;
use DB;
use Carbon\CarbonPeriod;

class TrackingController extends Controller
{
    protected $tracking;

    public function __construct()
    {
        $this->tracking = new Tracking();
    }

    public function load_track_items(Request $request)
    {
        $data = ['ticket_no' => $request->ticket_no];

        $rule = ['ticket_no' => 'required'];

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

        try
        {
            $result = $this->tracking->load_track_items($request->ticket_no);

            return response()
                ->json([
                'status'    =>  1,
                'data'      =>  $result
            ]);
        }

        catch(\Throwable $th)
        {
            return response()
                ->json([
                'status'    =>  0,
                'message'   =>  $th->getMessage(),
            ]);
        }
    }

    public function load_current_week_status(Request $request)
    {
        $data = 
        [
            'area_code'     => $request->area_code,
            'date_from'     => $request->date_from,
            'date_to'       => $request->date_to
        ];

        $rule =
        [
            'area_code'     => 'required',
            'date_from'     => 'required',
            'date_to'       => 'required'
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

        try
        {
            DB::beginTransaction();

            // $load = $this->tracking->load_current_week_status($data);

            $array_process_id = $this->tracking->array_process_id($data);

            $array_dates = $this->date_range($data['date_from'], $data['date_to']);

            DB::commit();

            $storage = [];
              
            $array_delivered = [];
            $array_other_process = [];

            foreach($array_dates as $date)
            {
                $temp = [];

                $temp[] = $date;

                $count_delivered = 0;
                $count_others = 0;
                
                for($a = 0; $a < count($array_process_id); $a++)
                {
                    if($date == $array_process_id[$a]->ticket_issue_date && $array_process_id[$a]->process_masterlist_id == 6)
                        $count_delivered++;
                    
                    if($date == $array_process_id[$a]->ticket_issue_date)
                        $count_others++;
                }

                $temp[] = $count_delivered;
                $temp[] = $count_others;

                $storage[] = $temp;
            }
            
            // array_unshift($temp, 'DELIVERED');

            // array_unshift($array_dates, 'DELIVERED STATUS');
            // $array_dates[]  = 'TOTAL';

            // $final = [];
            // $final[] = $array_dates;
            // $final[] = $storage;
            
            return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $storage
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

    public function date_range($from, $to)
    {
        $period = CarbonPeriod::create($from, $to);

        $dates = [];
        // Iterate over the period
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

       return $dates;
    }
}
