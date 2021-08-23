<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\MasterData;
use App\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Validator;
use DateTime;

class LeadTimeController extends Controller
{
    protected $master_data, $monitoring;

    public function __construct()
    {
        ini_set('memory_limit', '3000M');
        $this->master_data = new MasterData();
        $this->monitoring = new MonitoringController();

    }

    public function lead_time_data(Request $request)
    {
        $data = [
            'ticket_from' => $request->ticket_from,
            'ticket_to' => $request->ticket_to,
            'area_code' => $request->area_code,
        ];

        $rule = [
            'ticket_from' => 'required|date_format:"Y-m-d"',
            'ticket_to' => 'required|date_format:"Y-m-d"',
            'area_code' => 'required|exists:area,area_code',
            
        ];

        $validator = Validator::make($data, $rule);

        if($validator->fails())
        {
            return response()->json([
                'status'  => 0, 
                'message' => $validator->errors()->all(),
                'data'    => ''
            ]);
        }
        else
        {
            try
            {
                $master_data = $this->master_data->get_load_for_lead_time($request->ticket_from, $request->ticket_to, $request->area_code);
                $time_logs = $this->master_data->time_out($request->ticket_from, $request->ticket_to, $request->area_code);
                // return $master_data;
                $log_date   = []; 
                $log_in     = [];
                $log_out    = [];

                if($time_logs == null)
                {
                    return response()->json([
                        'status'  => 0, 
                        'message' => 'No time logs detected!',
                        'data'    => ''
                    ]);
                 
                }
                foreach($time_logs as $logs)
                {
                    $log_date[] = $logs->date;
                    $log_in[]   = $logs->time_in;
                    $log_out[]  = $logs->time_out;
                }
                
               $data = [];
               
                foreach($master_data as $master)
                {
                    $ticket_issue   = $master->issue_date;
                    $delivery_date  = $master->delivery_date;
                    $working = null;

                    $temp = [];

                    $temp['warehouse_class']    = $master->warehouse_class;
                    $temp['item_no']            = $master->item_no;
                    $temp['delivery_qty']       = $master->delivery_qty;
                    $temp['stock_address']      = $master->stock_address;
                    $temp['manufacturing_no']   = $master->manufacturing_no;
                    $temp['destination_code']   = $master->destination_code;
                    $temp['delivery_due_date']  = $master->delivery_due_date;
                    $temp['ticket_no']          = $master->ticket_no;
                    $temp['order_download_no']  = $master->order_download_no;
                    $temp['ticket-issue']       = $master->issue_date;
                    $temp['delivery-date']      = $master->delivery_date;

                    $dates = ($master->delivery_date <> null) ? $this->monitoring->date_range($ticket_issue, $delivery_date) : [];
                
                    if($master->delivery_date <> null)
                    {          
            
                        $e = new DateTime('00:00');
                        $f = clone $e;

                        for($a=0;$a<count($dates);$a++)
                        {
                            $counter = 0;
                            for($b=0;$b<count($log_date);$b++)
                            {
                                $counter++;
                                if($log_date[$b] == $dates[$a])
                                {
                                    $date_out = new DateTime($log_date[$b] . ' ' . $log_out[$b]);
                                    if(count($log_date) != $counter)
                                    {
                                        $date_in =  new DateTime($log_date[$b+1]  . ' ' . $log_in[$b+1]);
                                    }

                                    $interval = $date_out->diff($date_in);
                                    $e->add($interval);
                                    
                                }
                            }
                            
                            $d = ($f->diff($e)->format("%d") > 1) ? 'days' : 'day';
                            $h = ($f->diff($e)->format("%H") > 1) ? 'hours' : 'hour';
                            $m = ($f->diff($e)->format("%I") > 1) ? 'minutes' : 'minute';
                            $temp["non-working-hours"] = $f->diff($e)->format("%d {$d} %H {$h} %I {$m}"); 
                            //total working hours             
                            $working = $this->monitoring->two_dates_interval($master->issue_date, $master->delivery_date);  
                            $temp['total-hours'] = $working;  
                            $temp["leadtime"] = $this->monitoring->convert_leadtime($temp['total-hours'], $temp["non-working-hours"]);
        
                        }    
                    }
                    else
                    {
                        $temp['total-hours'] = null;
                        $temp["non-working-hours"] = null;
                        $temp["leadtime"] = null;
                    }
                    $data[] = $temp;                
                }
               

                return $data;
        
            }
            catch(\Throwable $th)
            {
                return response()->json([
                    'status'  => 0, 
                    'message' => $th->getMessage(),
                    'data'    => ''
                ]);
            }

        }
    }

    public function get_difference($issue_date, $delivery_date)
    {

    }
    
}
