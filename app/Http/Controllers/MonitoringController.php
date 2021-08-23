<?php

namespace App\Http\Controllers;
use DB;
use DateTime;
use DateInterval;
use App\MasterData;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
// use App\Http\Controllers\LeadTimeController;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class MonitoringController extends Controller
{
    protected $master_data, $process, $lead_time_controller;

    public function __construct()
    {
        $this->master_data = new MasterData();
        // $this->lead_time_controller = new LeadTimeController();
    }

    public function load_process()
    {
        $load = $this->master_data->load_process();

        return response()
            ->json([
                'status'    => 1,
                'message'   => '',
                'data'      => $load,
            ]);
    }
    public function manipulate_data(Request $request)
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
            // try
            // {
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
                    $temp['payee_name']         = $master->payee_name;

                    $dates = ($master->delivery_date <> null) ? $this->date_range($ticket_issue, $delivery_date) : [];
                
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
                                // if($log_date[$b] == $dates[$a])
                                // {
                                    $date_out = new DateTime($log_date[$b] . ' ' . $log_out[$b]);
                                    if($counter == 1)
                                    {
                                        $date_in =  new DateTime($log_date[$b]  . ' ' . $log_in[$b]);
                                    }
                                    elseif(count($log_date) != $counter)
                                    {
                                        
                                        $date_in =  new DateTime($log_date[$b+1]  . ' ' . $log_in[$b+1]);
                                    }
                                 
                                // }
                                $interval = $date_out->diff($date_in);
                                $e->add($interval);
                                
                                // else
                                // {
                                //     $temp['total-hours'] = null;
                                //     $temp["non-working-hours"] = null;
                                //     $temp["leadtime"] = null;
                                // }
                            }
                            
                            $d = ($f->diff($e)->format("%d") > 1) ? 'days' : 'day';
                            $h = ($f->diff($e)->format("%H") > 1) ? 'hours' : 'hour';
                            $m = ($f->diff($e)->format("%I") > 1) ? 'minutes' : 'minute';
                            $temp["non-working-hours"] = $f->diff($e)->format("%d {$d} %H {$h} %I {$m}"); 
                            //total working hours             
                            $working = $this->two_dates_interval($master->issue_date, $master->delivery_date);  
                            $temp['total-hours'] = $working;  
                            $temp["leadtime"] = $this->convert_leadtime($temp['total-hours'], $temp["non-working-hours"]);
        
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
        
            // }
            // catch(\Throwable $th)
            // {
            //     return response()->json([
            //         'status'  => 0, 
            //         'message' => $th->getMessage(),
            //         'data'    => ''
            //     ]);
            // }

        }
    }


    public function manipulate_report(Request $request)
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
                'status' => 0, 
                'message' => $validator->errors()->all(),
            ]);
        }
        else
        {
            $datas = $this->manipulate_data($request);
            $dates = $this->date_range($request->ticket_from, $request->ticket_to);
        //    return $datas;
    
            // ARRANGE THE DATA
                $issue_date = [];
                $delivery_date = [];
                $leadtime_data = [];
                // return $datas['data'];
                foreach($datas as $data)
                {
                    $issue_date[] = substr($data["ticket-issue"], 0, 10);
                    $delivery_date[] = substr($data["delivery-date"], 0, 10);
                    $leadtime_data[] = $data["leadtime"];
                }
                // return $delivery_date;
    
            // FINAL DATA 
                $storage = [];
                foreach($dates as $row)
                {
                    $temp = [];
    
                    $temp["issue-date"] = $row;
    
    
                    $a = array_keys($issue_date, substr($row, 0, 10));
    
                    $finish = 0;
                    $balance = 0;
                    $leadtime = 0;
                    $max = 0;
    
                    $e = new DateTime('00:00');
                    $f = clone $e;
    
                    for($b = 0; $b < count($a); $b++)
                    {
                        if($delivery_date[$a[$b]] != null)
                            $finish++;
    
                        if($delivery_date[$a[$b]] == null)
                            $balance++;
    
                        if($leadtime_data[$a[$b]] != null)
                        {
                            // TOTAL
                            $date3 = new DateTime(date('Y-m-d 0:00'));
                            $date3->modify("0 day 0 hour 00 minute");
                            
                            // NON WORKING
                            $date4 = new DateTime(date('Y-m-d 0:00'));
                            $date4->modify($leadtime_data[$a[$b]]);
    
                            // COMPUTE LEADTIME
                            $interval = $date3->diff($date4);
                            $e->add($interval);
    
                            if($leadtime_data[$a[$b]] >= $max)
                            $max = $leadtime_data[$a[$b]];
                        }
                        
    
                        
                    }
    
                    $temp["total"] = count($a); // TOTAL
                    $temp["finish"] = $finish; // FINISH
                    $temp["balance"] = $balance; // BALANCE
    
                    $mm = ($f->diff($e)->format("%m") > 1) ? 'months' : 'month';
                    $d = ($f->diff($e)->format("%d") > 1) ? 'days' : 'day';
                    $h = ($f->diff($e)->format("%H") > 1) ? 'hours' : 'hour';
                    $m = ($f->diff($e)->format("%I") > 1) ? 'minutes' : 'minute';
    
                    // UNCOMMENT THIS IF YOU WANT TO SEE THE TOTAL SUMMATION OF LEADTIME
                    // $temp["leadtime_sum"] = $finish <> 0 ? $f->diff($e)->format("%m {$mm} %d {$d} %H {$h} %I {$m}") : 0;
    
                    // GET THE MINUTES OF LEADTIME
                    $aaaaa = $finish <> 0 ? $this->leadtime_average($f->diff($e)->format("%m {$mm} %d {$d} %H {$h} %I {$m}")) / $finish : 0;
                    
                    // FLOOR THE MINUTES OF LEADTIME
                    $oldDate = strtotime(floor($aaaaa) . " minutes");
    
                    // NEW LEADTIME
                    $newDate = date('Y-m-d H:i', $oldDate);
    
                    // CONVERT LEADTIME INTERVAL TO FORMAT
                    $temp["leadtime"] = $finish <> 0 ? $this->two_dates_interval(date('Y-m-d H:i'), $newDate) : 0;
    
                    // MAX LEADTIME
                    $temp['max'] = $max <> '' ? $max : 0;
    
                    // LOOP FOR DELIVERED COUNT PER DAY 
                    foreach($dates as $head)
                    {
                        $per_day = 0;
    
                        for($c = 0; $c < count($a); $c++)
                        {
                            if($delivery_date[$a[$c]] == $head)
                                $per_day++;
                        }
                         // DAILY DELIVERY 
                        $temp[$head] = $per_day;
                    }
    
                    $storage[] = $temp;
                }
    
                return $storage;

                // return response()->json([
                //     'status'  => 0, 
                //     'message' => $validator->errors()->all(),
                //     'data'    => $storage
                // ]);

        }

       
    }

    public function lead_time_report(Request $request)
    {
        $data = 
        [
            'ticket_from' => $request->ticket_from,
            'ticket_to' => $request->ticket_to,
            'area_code' => $request->area_code,
        ];
    
        $rule = 
        [
             'ticket_from' => 'required|date_format:"Y-m-d"',
            'ticket_to' => 'required|date_format:"Y-m-d"',
            'area_code' => 'required|exists:area,area_code',
        ];
    
        $validator = Validator::make($data, $rule);
    
            if($validator->fails())
            {
                return response()->json([
                    'status' => 0, 
                    'message' => $validator->errors()->all(),
                ]);
            }
            else
            {
                try
                {
                    $datas = $this->manipulate_data($request);
                    $dates = $this->date_range($request->ticket_from, $request->ticket_to);
                //    return $datas;
            
                    // ARRANGE THE DATA
                        $issue_date = [];
                        $delivery_date = [];
                        $leadtime_data = [];
                        // return $datas['data'];
                        foreach($datas as $data)
                        {
                            $issue_date[] = substr($data["ticket-issue"], 0, 10);
                            $delivery_date[] = substr($data["delivery-date"], 0, 10);
                            $leadtime_data[] = $data["leadtime"];
                        }
                        
                        $date_range = [];
                    // FINAL DATA 
                        $storage = [];
                        foreach($dates as $row)
                        {
                            // $temp_row = [];
                            $date_issue = [];
                            $total_data = [];
                            $finish_data = [];
                            $balance_data = [];
                            $lead_time_data = [];
                            $max_data = [];
    
                            $date_range[] =  date('Y-m-d D', strtotime($row));       
                            $date_issue = date('Y-m-d D', strtotime($row));
            
            
                            $a = array_keys($issue_date, substr($row, 0, 10));
            
                            $finish = 0;
                            $balance = 0;
                            $leadtime = 0;
                            $max = 0;
            
                            $e = new DateTime('00:00');
                            $f = clone $e;
            
                            for($b = 0; $b < count($a); $b++)
                            {
                                if($delivery_date[$a[$b]] != null)
                                    $finish++;
            
                                if($delivery_date[$a[$b]] == null)
                                    $balance++;
            
                                if($leadtime_data[$a[$b]] != null)
                                {
                                    // TOTAL
                                    $date3 = new DateTime(date('Y-m-d 0:00'));
                                    $date3->modify("0 day 0 hour 00 minute");
                                    
                                    // NON WORKING
                                    $date4 = new DateTime(date('Y-m-d 0:00'));
                                    $date4->modify($leadtime_data[$a[$b]]);
            
                                    // COMPUTE LEADTIME
                                    $interval = $date3->diff($date4);
                                    $e->add($interval);
            
                                    if($leadtime_data[$a[$b]] >= $max)
                                    $max = $leadtime_data[$a[$b]];
                                }
                                
            
                                
                            }
            
                            $total_data = count($a);
                            $finish_data = $finish;
                            $balance_data = $balance;
                            // $temp["total"] = count($a); // TOTAL
                            // $temp["finish"] = $finish; // FINISH
                            // $temp["balance"] = $balance; // BALANCE
            
                            $mm = ($f->diff($e)->format("%m") > 1) ? 'months' : 'month';
                            $d = ($f->diff($e)->format("%d") > 1) ? 'days' : 'day';
                            $h = ($f->diff($e)->format("%H") > 1) ? 'hours' : 'hour';
                            $m = ($f->diff($e)->format("%I") > 1) ? 'minutes' : 'minute';
            
                            // UNCOMMENT THIS IF YOU WANT TO SEE THE TOTAL SUMMATION OF LEADTIME
                            // $temp["leadtime_sum"] = $finish <> 0 ? $f->diff($e)->format("%m {$mm} %d {$d} %H {$h} %I {$m}") : 0;
            
                            // GET THE MINUTES OF LEADTIME
                            $aaaaa = $finish <> 0 ? $this->leadtime_average($f->diff($e)->format("%m {$mm} %d {$d} %H {$h} %I {$m}")) / $finish : 0;
                           
                            // FLOOR THE MINUTES OF LEADTIME
                            $oldDate = strtotime(floor($aaaaa) . " minutes");
            
                            // NEW LEADTIME
                            $newDate = date('Y-m-d H:i', $oldDate);
                      
                            // CONVERT LEADTIME INTERVAL TO FORMAT
                            // $temp["leadtime"] = $finish <> 0 ? $this->two_dates_interval(date('Y-m-d H:i'), $newDate) : 0;
                            $lead_time_data = $finish <> 0 ? $this->two_dates_interval(date('Y-m-d H:i'), $newDate) : 0;
                            // if($finish <> 0 && $aaaaa <> 0)
                            // {
                            //     $lead_time_data = $this->two_dates_interval(date('Y-m-d H:i'), $newDate);
                            // }
                            // else
                            // {
                            //     $lead_time_data = '';
                            // }
                            // MAX LEADTIME
                            // $temp['max'] = $max <> '' ? $max : 0;
                            $max_data = $max <> '' ? $max : 0;
                            $temp_row = [];
                            $per_rows = [];
                            // LOOP FOR DELIVERED COUNT PER DAY 
                            foreach($dates as $head)
                            {
                                $per_day = 0;
            
                                for($c = 0; $c < count($a); $c++)
                                {
                                    if($delivery_date[$a[$c]] == $head)
                                        $per_day++;
                                }
                                 // DAILY DELIVERY 
                                 $temp_row[] = $per_day;
                            }
                            
                            // $per_rows[] = $temp_row;
                            $storage[] = [$date_issue, $total_data, $finish_data, $balance_data, $lead_time_data, $max_data, $temp_row];
                            // $storage[] = $temp_row;
    
    
                        }
                        $temp_column = array_merge(
                        [
                            'Issuance Date', 
                            'Total', 
                            'Finish', 
                            'Balance', 
                            'Lead Time', 
                            'Max'
                        ],
                            $date_range
                        );
                        $final = [];
                        $final[] = $temp_column;
                        $final[] = $storage;

                        return response()
                        ->json([
                            'status'    => 1,
                            'message'   => '',
                            'data'      => $final,
                        ]);               

                    }
                    catch(\Throwable $th)
                    {
                        return response()
                        ->json([
                            'status'    => 0,
                            'message'   => $th->getMessage(),
                            'data'      => '',
                        ]);
                    }
              
                }
    }


        // $data = [
        //     'ticket_from' => $request->ticket_from,
        //     'ticket_to' => $request->ticket_to,
        //     'area_code' => $request->area_code,
        //     ];
    
        //     $rule = [
        //         'ticket_from' => 'required|date_format:"Y-m-d"',
        //         'ticket_to' => 'required|date_format:"Y-m-d"',
        //         'area_code' => 'required|exists:area,area_code',
        //     ];
    
        //     $validator = Validator::make($data, $rule);
    
        //     if($validator->fails())
        //     {
        //         return response()->json([
        //             'status' => 0, 
        //             'message' => $validator->errors()->all(),
        //         ]);
        //     }
        //     else
        //     {
        //         // try
        //         // {

        //             $data = $this->manipulate_report($request);
        //             $range = $this->date_range($request->ticket_from, $request->ticket_to);
        //             // return $data;
        //             $data_range = [];
        //             $rows_header = [];
                   
        //             $per_rows = [];
                    
        //             for($a=0;$a<count($data);$a++)
        //             {
                       
        //                 $rows_header[] = 
        //                 [
        //                     date('Y-m-d D', strtotime($data[$a]['issue-date'])),
        //                     $data[$a]['total'], 
        //                     $data[$a]['finish'],
        //                     $data[$a]['balance'], 
        //                     $data[$a]['leadtime'], 
        //                     $data[$a]['max']
        //                 ];
        //                 $count = count($data[$a]) - 6;
        //                 $c = 0;
        //                 for($b=0; $b<count($range); $b++)
        //                 {
        //                     $c = $c+1;
        //                     $value = [];
        //                     if($c > $a)
        //                     {
        //                         $value = $data[$a][$range[$b]];
        //                     }
        //                 }
        //                $rows_value[] = $value;
        //                $per_rows = [$rows_header, $rows_value];
        //             }
                    
                    
                    
        //             // foreach($range as $dates)
        //             // {
        //             //     foreach($report as $key => $value)
        //             //     {
                           
        //             //         if($key == $dates)
        //             //         {
        //             //             // $rows[] = $key;
        //             //             $rows_header[] = [$report['issue-date'], $report['total'],$report['finish'],$report['balance'],$report['leadtime'], $report['max']];
        //             //             $rows_value[] = $report[$key];
        //             //         }
        //             //     }
        //             // }
        //            return $per_rows;
        //             $header = array_merge(['Issuance Date', 'Total', 'Finish', 'Balance', 'Lead Time', 'Max'], $data_range);
        //             $final = [];
        //             $final[] = $header;
        //             // $final[] = [ ,$row;
        //             return response()
        //             ->json([
        //                 'status'    =>  1,
        //                 'message'   =>  '',
        //                 'data'      => $final
        //             ]);

                // }
                // catch(\Throwable $th)
                // {
                //     return response()
                //     ->json([
                //         'status'    =>  0,
                //         'message'   =>  $th->getMessage(),
                //         'data'      => ''
                //     ]);
                // }
            
            // }

    // }

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
                'status' => 0, 
                'message' => $validator->errors()->all(),
            ]);
        }
        else
        { 
            $parts_status = $this->get_parts_status($request->area_code, $request->ticket_from, $request->ticket_to);
            $manipulate_data = $this->manipulate_data($request);
            // return $manipulate_data;

            $ticket_no = [];
            foreach($manipulate_data as $data)
            {
                $ticket_no[] = $data['ticket_no'];
            }
            // return $manipulate_data;
            $status = $this->master_data->additional_leadtime_data($ticket_no);
            
    //   return $status;
            $result = [];
            foreach($manipulate_data as $data)
            {
                $checking       = '';
                $palletizing    = '';
                $dr_making      = '';
                $delivery       = '';
                $receiving      = '';

                $normal_status      = '';
                $normal_dr          = '';
                $normal_delivered   = '';
                $irreg_status       = '';
                $irreg_dr           = '';
                $irreg_delivered    = '';

                $temp['warehouse_class']    = $data['warehouse_class'];
                $temp['item_no']            = $data['item_no'];
                $temp['delivery_qty']       = $data['delivery_qty'];
                $temp['stock_address']      = $data['stock_address'];
                $temp['manufacturing_no']   = $data['manufacturing_no'];
                $temp['destination_code']   = $data['destination_code'];
                $temp['delivery_due_date']  = $data['delivery_due_date'];
                $temp['ticket_no']          = $data['ticket_no'];
                $temp['order_download_no']  = $data['order_download_no'];
                $temp['ticket_issue']       = $data['ticket-issue'];
                $temp['delivery_date']      = $data['delivery-date'];
                $temp['total_hours']        = $data['total-hours'];
                $temp['non_working_hours']  = $data['non-working-hours'];
                $temp['leadtime']           = $data['leadtime'];
                $temp['payee_name']         = $data['payee_name'];
                
                
                foreach($status as $stat)
                {             

                    if($data['ticket_no'] == $stat->ticket_no)
                    {
                 
                        $checking       = $stat->checking;
                        $palletizing    = $stat->palletizing;
                        $dr_making      = $stat->dr_making;
                        $delivery       = $stat->delivery;
                        $receiving      = $stat->receiving;
                       
                    }

                        // $temp['checking']       =  $stat->checking;;
                        // $temp['palletizing']    =  $stat->palletizing;
                        // $temp['dr_making']      =  $stat->dr_making;
                        // $temp['delivery']       =  $stat->delivery;
                        // $temp['receiving']      =  $stat->receiving;
                    
                    
                }
                    $temp['checking']       =  $checking;
                    $temp['palletizing']    =  $palletizing;
                    $temp['dr_making']      =  $dr_making;
                    $temp['delivery']       =  $delivery;
                    $temp['receiving']      =  $receiving;
                    
                    foreach($parts_status as $parts)
                    {
                        if($data['ticket_no'] == $parts['ticket_no'])
                        {
                            $normal_status         = $parts['normal_status'];
                            $normal_dr             = $parts['normal_dr'];
                            $normal_delivered      = $parts['normal_delivered'];
                            $irreg_status          = $parts['irreg_status'];
                            $irreg_dr              = $parts['irreg_dr'];
                            $irreg_delivered       = $parts['irreg_delivered'];
                        }

                        $temp['normal_status']      =  $normal_status;
                        $temp['normal_dr']          = $normal_dr;
                        $temp['normal_delivered']   = $normal_delivered;
                        $temp['irreg_status']       = $irreg_status;
                        $temp['irreg_dr']           = $irreg_dr;
                        $temp['irreg_delivered']    = $irreg_delivered;
                    }

                    
               

                $result[] = $temp;
            }
      

            return $result;

        
        }

    }

    public function over_all_report(Request $request)
    {
        $data = [
            'ticket_from'   => $request->ticket_from,
            'ticket_to'     => $request->ticket_to,
            'area_code'     => $request->area_code,
            'date_range'    => $request->date_range,

            ];
    
            $rule = [
                'ticket_from'   => 'required|date_format:"Y-m-d"',
                'ticket_to'     => 'required|date_format:"Y-m-d"',
                'area_code'     => 'required|exists:area,area_code',
                'date_range'    => 'required'
            ];
    
            $validator = Validator::make($data, $rule);
    
            if($validator->fails())
            {
                return response()->json([
                    'status' => 0, 
                    'message' => $validator->errors()->all(),
                ]);
            }
            else
            {
                try
                {
                    $data = [];
                    if($request->date_range == "WEEKLY")
                    {
                        $data = $this->weekly_normal_report($request);
                    }
                    elseif($request->date_range == "WEEKLY HORENSO")
                    {
                        $data = $this->weekly_horenso_report($request);
                    }
                    elseif($request->date_range == "MONTHLY")
                    {
                        $data = $this->monthly_report($request);
                    }
                    elseif($request->date_range == "DAILY")
                    {
                        $data = $this->daily_report($request);
                    }
                    elseif($request->date_range == "YEARLY")
                    {
                        $data = $this->yearly_report($request);
                    }
                    return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $data,
                    ]);

                }
                catch(\Throwable $th)
                {
                    return response()
                    ->json([
                        'status'    =>  0,
                        'message'   =>  $th->getMessage(),
                        'data'      => ''
                    ]);
                }
            }

    }

    //weekly normal report
    public function weekly_normal_report(Request $request)
    {
            $manipulate_report = $this->manipulate_report($request);
            $months = $this->get_weekly_range($request->ticket_from,$request->ticket_to);
            
            $count_report = count($manipulate_report);
   
            $month= [];
            $week =0;
            
            foreach($months as $month)
            {
                $total =0;
                $finish =0;
                $unfinished=0;
                $minutes=0;
                $max=0;
                $total_lead_time=0;   
                $max_convert = 0;

                $e = new DateTime('00:00');
                $h = new DateTime('00:00');
                $f = clone $e;
                $g = clone $h;
                $week += 1;
                foreach($manipulate_report as $report)
                {
                    if((date('Y-m-d' ,strtotime($report['issue-date'])) >= date('Y-m-d' ,strtotime($month['from']))) && 
                    (date('Y-m-d' ,strtotime($report['issue-date'])) <= date('Y-m-d' ,strtotime($month['to']))))
                    {
                        $total += $report['total'];
                        $finish += $report['finish'];
                        $unfinished += $report['balance'];

                        if($report['leadtime'] != null)
                        {
                        // TOTAL
                            $date3 = new DateTime(date('Y-m-d 0:00'));
                            $date3->modify("0 day 0 hour 00 minute");
                        
                        // NON WORKING
                            $date4 = new DateTime(date('Y-m-d 0:00'));
                            $date4->modify($report['leadtime']);
    
                        // COMPUTE LEADTIME
                            $interval = $date3->diff($date4);
                            $e->add($interval);
    
                            $max_date = new DateTime(date('Y-m-d 0:00'));
                            $max_date->modify($report['max']);
    
                            // COMPUTE max
                            $interval = $date3->diff($max_date);
                            $h->add($interval);
    
                        }
    
        
                        // UNCOMMENT THIS IF YOU WANT TO SEE THE TOTAL SUMMATION OF LEADTIME
                        $lead_time= $finish <> 0 ? $f->diff($e)->format("%d %H %I") : 0;
    
                        $total_lead_time =$this->convert_to_decimal($lead_time);
                
                            $max_val= $finish <> 0 ? $g->diff($h)->format("%d %H %I") : 0;
                            $max_convert = $this->convert_to_decimal($max_val);
                      
                    
                        if($max_convert >= $max)
                        $max = $max_convert;
                    }
                }
                $columns[]= 'W' . $week .' ' . $month['from'] . ' ~ ' . $month['to'];
                $issued_ticket[] = $total;          
                $finisheds[] = $finish;
                $unfinisheds[] = $unfinished;
                $lead_times[] = $total_lead_time;
                $max_lead[] = $max;
            }
            $rows = [$issued_ticket, $finisheds, $unfinisheds, $lead_times, $max_lead];
            $final[] = $columns;
            $final[] = $rows;
            
            return $final;    
           

    }

    public function monthly_report(Request $request)
    {
        $months = $this->get_month($request->ticket_from,$request->ticket_to);
        $manipulate_report = $this->manipulate_report($request);

        $count_report = count($manipulate_report);
   
        $month= [];
        $week =0;
        foreach($months as $month)
        {
            $from = date('Y-m-01', strtotime($month));
            $to = date('Y-m-t', strtotime($month));
            $months_range = date('Y-m', strtotime($month));
            $ranges[] = 
            [
                'months' => $months_range,
                'from'   => $from,
                'to'     => $to
            ]; 
        }
        for($i=0; $i<count($ranges); $i++)
        {
            $e = new DateTime('00:00');
            $h = new DateTime('00:00');
            $f = clone $e;
            $g = clone $h;

            $total =0;
            $finish =0;
            $unfinished=0;
            $minutes=0;
            $max=0;
            $total_lead_time=0;   
            $max_convert = 0;
           
            $from_range = $ranges[$i]['from'];
            $to_range = $ranges[$i]['to'];
          
            foreach($manipulate_report as $report)
            {
                if((date('Y-m-d' ,strtotime($report['issue-date'])) >= date('Y-m-d' ,strtotime($from_range))) && 
                  (date('Y-m-d' ,strtotime($report['issue-date'])) <= date('Y-m-d' ,strtotime($to_range))))
                {
                    
                    $total += $report['total'];
                    $finish += $report['finish'];
                    $unfinished += $report['balance'];

                    if($report['leadtime'] != null)
                    {
                    // TOTAL
                        $date3 = new DateTime(date('Y-m-d 0:00'));
                        $date3->modify("0 day 0 hour 00 minute");
                    
                    // NON WORKING
                        $date4 = new DateTime(date('Y-m-d 0:00'));
                        $date4->modify($report['leadtime']);

                    // COMPUTE LEADTIME
                        $interval = $date3->diff($date4);
                        $e->add($interval);

                        $max_date = new DateTime(date('Y-m-d 0:00'));
                        $max_date->modify($report['max']);

                        // COMPUTE max
                        $interval = $date3->diff($max_date);
                        $h->add($interval);

                    }

    
                    // UNCOMMENT THIS IF YOU WANT TO SEE THE TOTAL SUMMATION OF LEADTIME
                    $lead_time= $finish <> 0 ? $f->diff($e)->format("%d %H %I") : 0;

                    $total_lead_time =$this->convert_to_decimal($lead_time);
            
                        $max_val= $finish <> 0 ? $g->diff($h)->format("%d %H %I") : 0;
                        $max_convert = $this->convert_to_decimal($max_val);
                  
                
                    if($max_convert >= $max)
                    $max = $max_convert;
                    
                }
                
            }
            $columns[] = $ranges[$i]['months'];
            $issued_ticket[] = $total;          
            $finisheds[] = $finish;
            $unfinisheds[] = $unfinished;
            $lead_times[] = $total_lead_time;
            $max_lead[] = $max;
        }
        $rows = [$issued_ticket, $finisheds, $unfinisheds,$lead_times,$max_lead];
        $final[] = $columns;
        $final[] = $rows;
        return $final;    
       
    }


    public function weekly_horenso_report(Request $request)
    {
        $manipulate_report = $this->manipulate_report($request);
        $weekly_horenso = $this->horenso_range($request->ticket_from,$request->ticket_to);
        // return $weekly_horenso;
        
        $from = $weekly_horenso['date_from'];
        $to = $weekly_horenso['date_to'];
        $date_ranges = $this->get_weekly_range($from,$to);        

        $count_report = count($manipulate_report);

        $week =0;
        
        foreach($date_ranges as $dates)
        {
            $total =0;
            $finish =0;
            $unfinished=0;
            $minutes=0;
            $max=0;
            $total_lead_time=0;   
            $max_convert = 0;

            $e = new DateTime('00:00');
            $h = new DateTime('00:00');
            $f = clone $e;
            $g = clone $h;
            $week += 1;
            foreach($manipulate_report as $report)
            {
                if((date('Y-m-d' ,strtotime($report['issue-date'])) >= date('Y-m-d' ,strtotime($dates['from']))) && 
                    (date('Y-m-d' ,strtotime($report['issue-date'])) <= date('Y-m-d' ,strtotime($dates['to']))))
                {
                    $total += $report['total'];
                    $finish += $report['finish'];
                    $unfinished += $report['balance'];

                    if($report['leadtime'] != null)
                    {
                    // TOTAL
                        $date3 = new DateTime(date('Y-m-d 0:00'));
                        $date3->modify("0 day 0 hour 00 minute");
                    
                    // NON WORKING
                        $date4 = new DateTime(date('Y-m-d 0:00'));
                        $date4->modify($report['leadtime']);

                    // COMPUTE LEADTIME
                        $interval = $date3->diff($date4);
                        $e->add($interval);

                        $max_date = new DateTime(date('Y-m-d 0:00'));
                        $max_date->modify($report['max']);

                        // COMPUTE max
                        $interval = $date3->diff($max_date);
                        $h->add($interval);

                    }

    
                    // UNCOMMENT THIS IF YOU WANT TO SEE THE TOTAL SUMMATION OF LEADTIME
                    $lead_time= $finish <> 0 ? $f->diff($e)->format("%d %H %I") : 0;

                    $total_lead_time =$this->convert_to_decimal($lead_time);
            
                        $max_val= $finish <> 0 ? $g->diff($h)->format("%d %H %I") : 0;
                        $max_convert = $this->convert_to_decimal($max_val);
                  
                
                    if($max_convert >= $max)
                    $max = $max_convert;
    
                }
            }
            $columns[] = 'W' . $week .' ' . $dates['from'] . ' ~ ' . $dates['to'];
            $issued_ticket[] = $total;          
            $finisheds[] = $finish;
            $unfinisheds[] = $unfinished;
            $lead_times[] = $total_lead_time;
            $max_lead[] = $max;
         
        }
        $rows = [$issued_ticket, $finisheds, $unfinisheds, $lead_times,$max_lead];
        $final[] = $columns;
        $final[] = $rows;  
        return $final;

    }

    public function daily_report(Request $request)
    {
        $manipulate_report = $this->manipulate_report($request);
        $e = new DateTime('00:00');
            $h = new DateTime('00:00');
            $f = clone $e;
            $g = clone $h;
        foreach($manipulate_report as $report)
        {
            $columns[] = $report['issue-date'];
            $issued_ticket[] = $report['total'];          
            $finisheds[] = $report['finish'];
            $unfinisheds[] = $report['balance'];

            $finish = $report['finish'];
            if($report['leadtime'] != null)
            {
               
                // TOTAL
                $date3 = new DateTime(date('Y-m-d 0:00'));
                $date3->modify("0 day 0 hour 00 minute");
                    
                // NON WORKING
                $date4 = new DateTime(date('Y-m-d 0:00'));
                $date4->modify($report['leadtime']);

                // COMPUTE LEADTIME
                $interval = $date3->diff($date4);
                $e->add($interval);

                $max_date = new DateTime(date('Y-m-d 0:00'));
                $max_date->modify($report['max']);

                // COMPUTE max
                $interval = $date3->diff($max_date);
                $h->add($interval);

            }
        
            $lead_time= $finish <> 0 ? $f->diff($e)->format("%d %H %I") : 0;

            $total_lead_time[] =$this->convert_to_decimal($lead_time);
    
            $max_val= $finish <> 0 ? $g->diff($h)->format("%d %H %I") : 0;
            $max_convert[] = $this->convert_to_decimal($max_val);

        }
        $rows = [$issued_ticket, $finisheds, $unfinisheds, $total_lead_time, $max_convert];
        $final[] = $columns;
        $final[] = $rows;

        return $final;
        
    }

    public function yearly_report(Request $request)
    {
        $monthly = $this->monthly_report($request);
        $years = $this->get_year($request->ticket_from,$request->ticket_to);
        
        foreach($years as $year)
        {
            $e = new DateTime('00:00');
            $h = new DateTime('00:00');
            $f = clone $e;
            $g = clone $h;

            $total =0;
            $finish =0;
            $unfinished=0;
            $minutes=0;
            $max=0;
            $total_lead_time = 0;
 

            for($i=0;$i<count($monthly[0]); $i++)
            {
                $report_year = date('Y' ,strtotime($monthly[0][$i]));
                if($year == $report_year)
                {
                 
                    $total +=  $monthly[1][0][$i];
                    $finish += $monthly[1][1][$i];
                    $unfinished += $monthly[1][2][$i];
                    $total_lead_time += $monthly[1][3][$i];                  
                
                    if($monthly[1][4][$i] >= $max)
                    $max = $monthly[1][4][$i];
                }
            }
            $issued_ticket[] = $total;          
            $finisheds[] = $finish;
            $unfinisheds[] = $unfinished;
            $lead_times[] = $total_lead_time;
            $max_lead[] = $max;
            $columns[] = $year;
        }   
       
        $rows = [$issued_ticket, $finisheds, $unfinisheds, $lead_times, $max_lead];
        $final = [];
        $final[] = $columns;
        $final[] = $rows;

        return $final;
    }

    public function convert_to_decimal($lead_time)
    {
        $count = substr_count($lead_time, ' ');
        $total_lead_time =0;
        if($count == 2){
            $lead_convert = explode(' ', $lead_time);
            $day = $lead_convert[0];
            $hours = $lead_convert[1];
            $minutes = $lead_convert[2];
    
            $total_min = floatval($minutes) / 60;
            $total_hrs = (floatval($total_min) + floatval($hours)) / 24;
            $total_lead_time = round(floatval($total_hrs) + floatval($day), 2);
        }

       
        return $total_lead_time;
    }
    public function get_year($from, $to)
    {
        $start_date    = $from;
        $end_date      = $to;
        $getRangeYear   = range(date('Y', strtotime($start_date)), date('Y', strtotime($end_date)));
        return $getRangeYear; 
    }

    public function get_month($from,$to)
    {
        $start_date = $from;
        $end_date = $to;

        $months = array();
        while (strtotime($start_date) <= strtotime($end_date)) {
            $months[] = date('Y', strtotime($start_date)) . "-" . date('m', strtotime($start_date)) ;
            $start_date = date('01 M Y', strtotime($start_date.
            '+ 1 month')); // Set date to 1 so that new month is returned as the month changes.
        }

        return $months;
    }

    public function get_weekly_range($from, $to)
    {
        $startDate = new DateTime($from);
        $endDate = new DateTime($to);

        $sundays = [];
        $saturdays = [];
        $range = [];

        while ($startDate <= $endDate) {

            if ($startDate->format('w') == 0 
            || $sundays == null) 
            {
                $sundays[] = $startDate->format('Y-m-d');
            }


            if ($startDate->format('w') == 6) 
            {
                $saturdays[] = $startDate->format('Y-m-d');
            }
           
            $startDate->modify('+1 day');
        }

        if((string)in_array($startDate, $saturdays) == '')
        {
           $saturdays[] = $to;
        }

        $range_dates = [];
        for($a=0;$a<count($sundays);$a++)
        {
            $range_dates[] =
            [
                'from' =>  $sundays[$a],
                'to'   =>  $saturdays[$a]
            ];
        }
        return $range_dates;
    }


    public function horenso_range($from, $to)
    {
        
        $no_day_from = date('N', strtotime($from));
        $no_day_to = date('N', strtotime($to));
    

        if($no_day_from  == 7)
        {
            $date_from =  Carbon::parse($from);
        }
        else if($no_day_from == 6)
        {
            $date_from =  Carbon::parse($from)->subDays(6);
        }
        else
        {
            $date_from =  Carbon::parse($from)->subDays(6 - $no_day_from);
        }


        if($no_day_to < 7)
        {
            $date_to =  Carbon::parse($to)->addDays(6 - $no_day_to);
        }
       
        else
        {
            $date_to =  Carbon::parse($to);
        }

       return [
     
           'date_from' => date('Y-m-d', strtotime($date_from)),
           'date_to'   =>date('Y-m-d', strtotime($date_to))
        ];
       
    }   

    public function leadtime_average($leadtime)
    {
        $a = explode(" ", $leadtime);

        $days = $this->months_to_days($a[0]) + $a[2];
        $hours = $this->days_to_hours($days) + $a[4];
        $minutes = $this->hours_to_minutes($hours) + $a[6];

        return $minutes;
    }

    public function months_to_days($months) {

        return $months * 30;
    }

    public function days_to_hours($days) {
        return $days * 24;
    }

    public function hours_to_minutes($hours) {
        return $hours * 60;
    }

    public function convert_leadtime($date1, $date2)
    {
        // TOTAL
        $date3 = new DateTime(date('Y-m-d 0:00'));
        $date3->modify($date1);

        // NON WORKING
        $date4 = new DateTime(date('Y-m-d 0:00'));
        $date4->modify($date2);

        // COMPUTE LEADTIME
        $e = new DateTime('00:00');
        $f = clone $e;
        $interval = $date3->diff($date4);
        $e->add($interval);

        // RETURN THE LEADTIME
        $d = ($f->diff($e)->format("%d") > 1) ? 'days' : 'day';
        $h = ($f->diff($e)->format("%H") > 1) ? 'hours' : 'hour';
        $m = ($f->diff($e)->format("%I") > 1) ? 'minutes' : 'minute';
        return $f->diff($e)->format("%d {$d} %H {$h} %I {$m}");
    }

    public function two_dates_interval($date1, $date2)
    {
        // INITIALIZE DATES
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);

        // GET THE DATE INTERVAL
        $interval = $datetime1->diff($datetime2);

        // FORMAT THE DATE BASED ON INTERVAL
        $d = ($interval->format("%d") > 1) ? 'days' : 'day';
        $h = ($interval->format("%H") > 1) ? 'hours' : 'hour';
        $m = ($interval->format("%I") > 1) ? 'minutes' : 'minute';
        return $interval->format("%d {$d} %H {$h} %I {$m}"); return $interval->format("%d {$d} %H {$h} %I {$m}");
    }

    public function add_day($date, $adjustment)
    {
        // INITIALIZE THE DATE & SET 7:00AM AS DEFAULT TIME
        $new_date = new DateTime($date);

        // ADJUSTMENT DAY
        $new_date->modify($adjustment);

        // RETURN THE NEW DATE 
        return $new_date->format('Y-m-d H:i:s');
    }

    public function load_leadtime(Request $request)
    {
        $data = 
            [
                'area_code'     => $request->area_code,
                'date_from'     => $request->date_from,
                'date_to'       => $request->date_to,
            ];

        $area_code = 
            [
                'area_code'     => $request->area_code,
            ];

        $from = 
            [
                'date_from'    => $request->date_from,
            ];

        $to = 
            [
                'date_to'      => $request->date_to,
            ];

        $rule = 
            [
                'date_from'    => 'required',
                'date_to'      => 'required',
                'area_code'    => 'required',
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
               return $load = $this->master_data->load_data_for_monitoring($area_code,$from,$to);
                DB::commit();
                     
                $result = [];
                $count = count($load);
    
                for($a = 0; $a < $count; $a++)
                {
                    $time = $load[$a]->ticket_issue_date .' '. $load[$a]->ticket_issue_time;

                    if($load[$a]->ticket_issue_time == null && $load[$a]->irreg_delivery == null || $load[$a]->ticket_issue_time == null)
                        $leadtime = '';
                    elseif($load[$a]->irreg_delivery == null)
                    {
                        $seconds = strtotime(date('Y-m-d H:i:s')) - strtotime($time);
                        $hour = $seconds/3600;
                        $min = explode('.', $hour);
                        $minute = $min[1]*60;
                        $time = $hour.'.'.$minute;
                        $final = explode('.', $time);
                        $initial = $final[0].'.'.$final[1];
                        $leadtime = round($initial, 2);
                    }
                    else
                    {
                        $timestamp1 = strtotime($time);
                        $timestamp2 = strtotime($load[$a]->irreg_delivery);
                        $leadtime = abs($timestamp2 - $timestamp1)/(60*60);
                    }

                    $result[] = 
                        [
                            'warehouse_class'      => $load[$a]->warehouse_class,
                            'item_no'              => $load[$a]->item_no,
                            'delivery_qty'         => $load[$a]->delivery_qty,
                            'stock_address'        => $load[$a]->stock_address,
                            'manufacturing_no'     => $load[$a]->manufacturing_no,
                            'destination_code'     => $load[$a]->destination_code,
                            'delivery_due_date'    => $load[$a]->delivery_due_date,
                            'ticket_no'            => $load[$a]->data_ticket_no,
                            'order_download_no'    => $load[$a]->order_download_no,
                            'ticket_issue_date'    => $load[$a]->ticket_issue_date .' '. $load[$a]->ticket_issue_time,
                            'created_at'           => $load[$a]->irreg_delivery,
                            'leadtime'             => $leadtime,
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

    /*
    * return @array
    * _method GET
    * request data required [txt_parts_status_date_from,txt_parts_status_date_to,area_code]
    */
    public function load_part_status(Request $request)
    {
        $data = 
            [
                'from' => $request->txt_parts_status_date_from,
                'to' => $request->txt_parts_status_date_to,
                'area_code' => $request->area_code
            ];

        $rule =
            [
                'from' => 'required',
                'to' => 'required',
                'area_code' => 'required'
            ];
        
        $validator = Validator::make($data,$rule);

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
                $result = $this->get_parts_status($request->area_code, $request->txt_parts_status_date_from, $request->txt_parts_status_date_to);
                
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $result,
                    ]);
            
            }
            catch(\Throwable $th)
            {
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => $th->getMessage(),
                        'data'      => '',
                    ]);
            }
        }
    }

    public function get_parts_status($area_code, $from, $to)
    {
        
            DB::beginTransaction();
            $load = $this->master_data->load_data_for_monitoring($area_code,$from,$to);
            
            DB::commit();
            $result = [];  
            $count = count($load);
            $barcode = [];

            for ($a = 0; $a<$count; $a++)
            {
               if (!in_array($load[$a]->ticket_no,$barcode))
               {
                    $barcode [] = $load[$a]->ticket_no;
               }

            }
            $barcode_count = count($barcode);

            for ($x = 0;$x<$barcode_count; $x++)
            {
                $warehouse_class    = "";
                $item_no            = "";
                $delivery_qty       = "";
                $stock_address      = "";
                $manufacturing_no   = "";
                $destination_code   = "";
                $payee_name         = "";
                $delivery_due_date  = "";
                $ticket_no          = "";
                $order_download_no  = "";
                $ticket_issue_date  = "";
                $normal_status      = "";
                $normal_dr          = "";
                $normal_delivered   = "";
                $irreg_status       = "";
                $irreg_dr           = "";
                $irreg_delivered    = "";
                $process            = "";

                for ($y = 0;$y<$count; $y++)
                {
                    if ($barcode[$x] == $load[$y]->ticket_no)
                    {
                        $warehouse_class    =  $load[$y]->warehouse_class;
                        $item_no            =  $load[$y]->item_no;
                        $delivery_qty       =  $load[$y]->delivery_qty;
                        $stock_address      =  $load[$y]->stock_address;
                        $manufacturing_no   =  $load[$y]->manufacturing_no;
                        $destination_code   =  $load[$y]->destination_code;
                        $payee_name         =  $load[$y]->payee_name;
                        $delivery_due_date  =  $load[$y]->delivery_due_date;
                        $ticket_no          =  $load[$y]->ticket_no;
                        $order_download_no  =  $load[$y]->order_download_no;
                        $ticket_issue_date  =  $load[$y]->ticket_issue_date . " " . $load[$y]->ticket_issue_time;
                        $normal_status      =  $load[$y]->normal_status;

                        if ($load[$y]->process == 'NORMAL' || $load[$y]->irregularity_type == "NO STOCK" || $load[$y]->irregularity_type == "EXCESS")
                        {
                            $normal_status      =  $load[$y]->normal_status;
                            $normal_dr          =  $load[$y]->dr_control;
                            $normal_delivered   =  $load[$y]->delivered;
                        }
                        else
                        {
                            $irreg_status       =  $load[$y]->irreg_status;
                            $irreg_dr           =  $load[$y]->dr_control;
                            $irreg_delivered    =  $load[$y]->delivered;
                        }                        
                    }
                }
                
                $result[] = 
                [
                    'warehouse_class'   =>  $warehouse_class,
                    'item_no'           =>  $item_no,
                    'delivery_qty'      =>  $delivery_qty,
                    'stock_address'     =>  $stock_address,
                    'manufacturing_no'  =>  $manufacturing_no,
                    'destination_code'  =>  $destination_code,
                    'payee_name'        =>  $payee_name,
                    'delivery_due_date' =>  $delivery_due_date,
                    'ticket_no'         =>  $ticket_no,
                    'order_download_no' =>  $order_download_no,
                    'ticket_issue_date' =>  $ticket_issue_date,
                    'normal_status'     =>  $normal_status,
                    'normal_dr'         =>  $normal_dr,
                    'normal_delivered'  =>  $normal_delivered,
                    'irreg_status'      =>  $irreg_status,
                    'irreg_dr'          =>  $irreg_dr,
                    'irreg_delivered'   =>  $irreg_delivered,
                ];   
               
            }

            return $result;
    }

    public function load_master_data(Request $request)
    {
        $data =
            [
                'from'      => $request->from,
                'to'        => $request->to,
                'area_code' => $request->area_code
            ];

        $rule = 
            [
                'from'      => 'required',
                'to'        => 'required',
                'area_code' => 'required' 
            ];
        
        $validator = Validator::make($data, $rule);
        
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
                $load = $this->master_data->load_filtered_item($data['from'],$data['to'], $data['area_code']);
                DB::commit();

                $count = count($load);

                $result= [];
                for($a=0;$a<$count;$a++)
                {
                    $result[] =
                        [
                            'warehouse_class'   => $load[$a]->warehouse_class,
                            'delivery_form'     => $load[$a]->delivery_form,
                            'item_no'           => $load[$a]->item_no,
                            'item_rev'          => $load[$a]->item_rev,
                            'delivery_qty'      => $load[$a]->delivery_qty,
                            'stock_address'     => $load[$a]->stock_address,
                            'manufacturing_no'  => $load[$a]->manufacturing_no,
                            'delivery_inst_date'=> $load[$a]->delivery_inst_date,
                            'destination_code'  => $load[$a]->destination_code,
                            'item_name'         => $load[$a]->item_name,
                            'product_no'        => $load[$a]->product_no,
                            'ticket_no'         => $load[$a]->ticket_no,
                            'ticket_issue_date' => $load[$a]->ticket_issue_date,
                            'ticket_issue_time' => $load[$a]->ticket_issue_time,
                            'storage_location'  => $load[$a]->storage_location,
                            'delivery_due_date' => $load[$a]->delivery_due_date,
                            'order_download_no' => $load[$a]->order_download_no,
                        ];

                }

                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $result,
                ]);

            }
            catch(\Throwable $th)
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

    public function load_report_delivery_status(Request $request)
    {
        $data = 
        [
            'warehouse_class'       => $request->warehouse_class,
            'status'                => $request->status,
            'issue_from'            => $request->issue_from,
            'issue_to'              => $request->issue_to,
            'due_from'              => $request->due_from,
            'due_to'                => $request->due_to
        ];

        $rule =
        [
            'warehouse_class'       => 'required',
            'status'                => 'required',
            'issue_from'            => 'required',
            'issue_to'              => 'required',
            'due_from'              => 'required',
            'due_to'                => 'required'
        ];   
        
        $validator = Validator::make($data, $rule);
        
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
                if($request->status != "ALL")
                    $array_process = $this->master_data->array_process_filter($data['status']); 
                else
                    $array_process = $this->master_data->array_process();
                    
                $array_master_data = $this->master_data->array_master_data($data);                      
                $array_dates = $this->date_range($data['issue_from'], $data['issue_to']);

                DB::commit();
               
                $storage = [];
                
                foreach($array_process as $process)
                {
                    // return $process->process;
                    $temp = [];
                    $temp[] = $process->process;
                    $total = 0;
                   
                    for($a = 0; $a < count($array_dates); $a++)
                    {
                        $count = 0;
                        foreach($array_master_data as $data)
                        {
                            if($process->id == $data->process_masterlist_id 
                            && $data->ticket_issue_date == $array_dates[$a])
                                $count++;
                        }
                        $temp[] = $count;
                        $total += $count;
                    }

                    $temp[] = $total;
                    
                     $storage[] = $temp;
               
                }

                array_unshift($array_dates, 'PROCESS');
                $array_dates[]  = 'TOTAL';

                // CREATE A MANIPULATION THAT COMPUTE THE COLUMN
                $row_total = [];
                $row_total[] = 'TOTAL';
                for($column = 1; $column < count($storage[0]); $column++)
                {
                    $counter = 0;
                    for($row = 0; $row < count($storage); $row++)
                    {
                        $counter += $storage[$row][$column];
                    }
                    $row_total[] = $counter;
                }
                $storage[] = $row_total;
                
                $final = [];
                $final[] = $array_dates;
                $final[] = $storage;

                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $final,
                ]);
            }
            catch(\Throwable $th)
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

    public function load_report_delivery_quantity(Request $request)
    {
        $data = 
        [
            'status'                => $request->status,
            'warehouse_class'       => $request->warehouse_class,
            'issue_from'            => $request->issue_from,
            'issue_to'              => $request->issue_to,
            'due_from'              => $request->due_from,
            'due_to'                => $request->due_to
        ];

        $rule =
        [
            'status'                => 'required',
            'warehouse_class'       => 'required',
            'issue_from'            => 'required',
            'issue_to'              => 'required',
            'due_from'              => 'required',
            'due_to'                => 'required'
        ]; 
        
        $validator = Validator::make($data, $rule);
        
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
                
                if($request->status != "ALL")
                    $array_payee_master = $this->master_data->array_payee_master_filter($data);
                else  
                    $array_payee_master = $this->master_data->array_payee_master($data);
                    
                $array_list_payee = $this->master_data->array_list_payee();
                $array_dates = $this->date_range($data['issue_from'], $data['issue_to']);

                DB::commit();
               
                $storage = [];
              
                foreach($array_list_payee as $destination)
                {
                    $temp = [];
                    $temp[] = $destination->payee_name;
                  
                    $total = 0;
                   
                    for($a = 0; $a < count($array_dates); $a++)
                    {
                        $count = 0;
                        foreach($array_payee_master as $data)
                        {
                            if($destination->payee_name == $data->payee_name 
                            && $data->ticket_issue_date == $array_dates[$a])
                               $count += $data->delivery_qty;
                        }
                        $temp[] = $count;
                        $total += $count;
                    }

                    $temp[] = $total;
                    
                    $storage[] = $temp;
                }
                
                array_unshift($array_dates, 'PAYEE NAME');
                $array_dates[]  = 'TOTAL';

                // CREATE A MANIPULATION THAT COMPUTE THE COLUMN
                $row_total = [];
                $row_total[] = 'TOTAL';
                for($column = 1; $column < count($storage[0]); $column++)
                {
                    $counter = 0;
                    for($row = 0; $row < count($storage); $row++)
                    {
                        $counter += $storage[$row][$column];
                    }
                    $row_total[] = $counter;
                }
                $storage[] = $row_total;
                
                $final = [];
                $final[] = $array_dates;
                $final[] = $storage;
                    
                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $final,
                ]);

            }
            catch(\Throwable $th)
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

    public function load_issuance_payee(Request $request)
    {
        $data = 
        [
            'status'                => $request->status,
            'warehouse_class'       => $request->warehouse_class,
            'issue_from'            => $request->issue_from,
            'issue_to'              => $request->issue_to,
            'due_from'              => $request->due_from,
            'due_to'                => $request->due_to
        ];

        $rule = 
        [
            'status'                => 'required',
            'warehouse_class'       => 'required',
            'issue_from'            => 'required',
            'issue_to'              => 'required',
            'due_from'              => 'required',
            'due_to'                => 'required'
        ];

        $validator = Validator::make($data, $rule);
        
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
                
                if($request->status != "ALL")
                    $array_payee_master = $this->master_data->array_payee_master_filter($data);
                else  
                    $array_payee_master = $this->master_data->array_payee_master($data);
                    
                $array_list_payee = $this->master_data->array_list_payee();
                $array_dates = $this->date_range($data['issue_from'], $data['issue_to']);

                DB::commit();
               
                $storage = [];
              
                foreach($array_list_payee as $destination)
                {
                    $temp = [];
                    $temp[] = $destination->payee_name;
                  
                    $total = 0;
                   
                    for($a = 0; $a < count($array_dates); $a++)
                    {
                        $count = 0;
                        foreach($array_payee_master as $data)
                        {
                            if($destination->payee_name == $data->payee_name 
                            && $data->ticket_issue_date == $array_dates[$a])
                               $count++;
                        }
                        $temp[] = $count;
                        $total += $count;
                    }

                    $temp[] = $total;
                    
                    $storage[] = $temp;
                }
                
                array_unshift($array_dates, 'PAYEE NAME');
                $array_dates[]  = 'TOTAL';

                // CREATE A MANIPULATION THAT COMPUTE THE COLUMN
                $row_total = [];
                $row_total[] = 'TOTAL';
                for($column = 1; $column < count($storage[0]); $column++)
                {
                    $counter = 0;
                    for($row = 0; $row < count($storage); $row++)
                    {
                        $counter += $storage[$row][$column];
                    }
                    $row_total[] = $counter;
                }
                $storage[] = $row_total;
                
                $final = [];
                $final[] = $array_dates;
                $final[] = $storage;
                  
                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $final,
                ]);
            }
            catch(\Throwable $th)
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

    public function load_delivery_data(Request $request)
    {
        $data = 
        [
            'date_from'             => $request->date_from,
            'date_to'               => $request->date_to,
            'area_code'             => $request->area_code,
        ];

        $rule = 
        [
            'date_from'             => 'required',
            'date_to'               => 'required',
            'area_code'             => 'required',
        ];

        $validator = Validator::make($data, $rule);
        
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
              
                $load = $this->master_data->load_delivery($data);
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
                    $temp_dr_control    = null;
                    $ticket_qty         = 0;
                    $temp_pallet        = null;
                    $temp_pcase         = null;
                    $temp_box           = null;
                    $temp_bag           = null;
                    $temp_created_at    = null;
                    $temp_status        = null;
                    $temp_destination   = null;

                    for ($x = 0; $x<$count_load ; $x++)
                    {
                        if ($dr_control[$y] == $load[$x]->dr_control) 
                        {
                           
                                $ticket_qty += 1;
                                $temp_dr_control    = $load[$x]->dr_control;
                                $temp_pallet        = $load[$x]->pallet;
                                $temp_pcase         = $load[$x]->pcase;
                                $temp_box           = $load[$x]->box;
                                $temp_bag           = $load[$x]->bag;
                                $temp_created_at    = $load[$x]->date;
                                $temp_status        = $load[$x]->process;
                                $temp_destination   = $load[$x]->destination;
                           
                            
                        }
                        
                    }
                    $storage[] =
                    [
                        'dr_control'     => $temp_dr_control, 
                        'ticket_count'     => $ticket_qty,
                        'pallet'         => $temp_pallet,
                        'pcase'          => $temp_pcase,
                        'box'            => $temp_box,
                        'bag'            => $temp_bag,
                        'created_at'     => $temp_created_at,
                        'status'         => $temp_status,
                        'destination'    => $temp_destination,
                    ];
                }

            
                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $storage,
                ]);
            }
            catch(\Throwable $th)
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

    // public function manipulate_data(Request $request)
    // {
    //     $data = [
    //         'ticket_from' => $request->ticket_from,
    //         'ticket_to' => $request->ticket_to,
    //         'area_code' => $request->area_code,
    //     ];

    //     $rule = [
    //         'ticket_from' => 'required|date_format:"Y-m-d"',
    //         'ticket_to' => 'required|date_format:"Y-m-d"',
    //         'area_code' => 'required|exists:area,area_code',
            
    //     ];

    //     $validator = Validator::make($data, $rule);

    //     if($validator->fails())
    //     {
    //         return response()->json([
    //             'status' => 0, 
    //             'message' => $validator->errors()->all(),
    //         ]);
    //     }
    //     else
    //     {
    //             $hris = DB::connection('mysql')->select("
    //             SELECT LOG_DATE as logdate, LOG_TIME as logtime, employeeid
    //             FROM(
    //                 SELECT
    //                     b.emp_pms_id as employeeid,
    //                     substring(a.timeentry, 12, 5) AS LOG_TIME,
    //                     substring(a.timeentry, 1, 10) AS LOG_DATE,
    //                     a.eventid AS event,
    //                     'actatek_logs' AS proximity,
    //                     d.section
    //                 FROM hris.actatek_logs a 
    //                 LEFT JOIN hris.pr_sys_employee b
    //                     ON a.userid = b.emp_card_id
    //                 LEFT JOIN hris.pms_employee_position c
    //                     ON c.id = b.emp_position
    //                 LEFT JOIN hris.pms_employee_section d
    //                     ON b.emp_section  = d.id
    //                 WHERE 
    //                     a.eventid = 'OUT'
    //                     AND substring(a.timeentry, 1, 10) BETWEEN '$request->ticket_from' AND '$request->ticket_from'

    //                 UNION
    //                 SELECT
    //                     b.emp_pms_id as employeeid,
    //                     substring(a.Timestamp_ACTAtek, 12, 5) AS LOG_TIME,
    //                     substring(a.Timestamp_ACTAtek, 1, 10) AS LOG_DATE,
    //                     a.EventTrigger AS event,
    //                     'agent_log' AS proximity,
    //                     d.section
    //                 FROM hris.agent_log a 
    //                 LEFT JOIN hris.pr_sys_employee b
    //                     ON a.userid = b.emp_card_id
    //                 LEFT JOIN hris.pms_employee_position c
    //                     ON c.id = b.emp_position
    //                 LEFT JOIN hris.pms_employee_section d
    //                     ON b.emp_section  = d.id
    //                 WHERE 
    //                     a.EventTrigger = 'OUT'
    //                     AND substring(a.Timestamp_ACTAtek,1,10) BETWEEN '$request->ticket_from' AND '$request->ticket_from'

    //                 ) AS a
    //         ");

    //         $employeeid = [];
    //         $logdate = [];
    //         $logtime = [];

    //         foreach($hris as $data)
    //         {
    //             $employeeid[] = $data->employeeid;
    //             $logdate[] = $data->logdate;
    //             $logtime[] = $data->logtime;
    //         }

    //         $master = DB::connection('pgsql')->select(
    //             "select a.*, CONCAT(a.ticket_issue_date,' ', a.ticket_issue_time) as issue_date, c.created_at as delivery_date, d.payee_name
    //             from
    //             master_data as a
    //             left Join palletizings as b
    //             on a.ticket_no = b.ticket_no
    //             left Join delivery as c
    //             on b.dr_control = c.dr_control
	// 			left join destination_masterlist as d
	// 			on d.payee_cd = a.destination_code
    //             where a.ticket_issue_date between '$request->ticket_from' AND '$request->ticket_to'
    //             and a.warehouse_class = '$request->area_code'
    //             and (b.process = 'NORMAL' or a.process_masterlist_id < 6)
    //             ");

    //         // $master = DB::table('master_data as a')
    //         //         ->leftJoin('palletizings as b', 'a.ticket_no', 'b.ticket_no')
    //         //         ->leftJoin('delivery as c', 'b.dr_control', 'c.dr_control')
    //         //         ->select('a.*', DB::raw("CONCAT(a.ticket_issue_date,' ', a.ticket_issue_time) as issue_date"), 'c.created_at as delivery_date')
    //         //         ->whereBetween('a.ticket_issue_date', [$request->ticket_from, $request->ticket_to])
    //         //         ->where('a.warehouse_class', $request->area_code)
    //         //         ->where('b.process', 'NORMAL')
    //         //         ->Orwhere('a.process_masterlist_id', '<', '6')
    //         //         ->get();

    //         $users = DB::table('users as a')
    //                 ->join('area as b', 'a.area_id', 'b.id')
    //                 ->select('a.employee_number', 'b.area_code')
    //                 ->where('b.area_code', $request->area_code)
    //                 ->where('a.user_type_id', '<>', 1)
    //                 ->get();   
            
    //         $datas = []; 
                
    //         foreach($master as $data)
    //         {
    //             $temp = [];

    //             $temp['warehouse_class'] = $data->warehouse_class;
    //             // $temp['delivery_form'] = $data->delivery_form;
    //             $temp['item_no'] = $data->item_no;
    //             // $temp['item_rev'] = $data->item_rev;
    //             $temp['delivery_qty'] = $data->delivery_qty;
    //             $temp['stock_address'] = $data->stock_address;
    //             $temp['manufacturing_no'] = $data->manufacturing_no;
    //             // $temp['delivery_inst_date'] = $data->delivery_inst_date;
    //             $temp['destination_code'] = $data->destination_code;
    //             $temp['delivery_due_date'] = $data->delivery_due_date;
    //             // $temp['item_name'] = $data->item_name;
    //             // $temp['product_no'] = $data->product_no;
    //             $temp['ticket_no'] = $data->ticket_no;
    //             $temp['order_download_no'] = $data->order_download_no;
    //             // $temp['ticket_issue_date'] = $data->ticket_issue_date;
    //             // $temp['ticket_issue_time'] = $data->ticket_issue_time;
    //             // $temp['storage_location'] = $data->storage_location;
                

    //             $temp['ticket-issue'] = $data->issue_date;

    //             $temp['delivery-date'] = $data->delivery_date;
    //             $temp['payee_name'] = $data->payee_name;
                
                
    //             // GET ALL ID HAS SAME AREA CODE
    //                 $emplo = [];
    //                 foreach($users as $e)
    //                 {
    //                     if($e->area_code == $data->warehouse_class)
    //                         $emplo[] = $e->employee_number;
    //                 }
    //                 // return $emplo;
                
    //             // GENERATE DATE FROM ISSUED DATE TO DELIVERY DATE
    //                 $datesssssss = ($data->delivery_date <> null) ? $this->date_range($data->issue_date, $data->delivery_date) : [];
    //                 // return $datesssssss;
                
    //             // GET THE MAXIMUM OUT TIME IN HRIS BASED ON DATE
    //                 $hris_datas = [];
    //                 foreach($datesssssss as $datesss)
    //                 {
    //                     $tempss = [];
                        
    //                     $tempss["date"] = $datesss;

    //                     $time = date('00:00');

    //                     $aa = array_keys($logdate, $datesss);
                        
    //                     for($asd = 0; $asd < count($aa); $asd++)
    //                     {
    //                         if(in_array($employeeid[$aa[$asd]], $emplo))
    //                         {
    //                             if( date($logtime[$aa[$asd]]) > $time ) { $time = date($logtime[$aa[$asd]]); }
    //                         }
    //                     }

    //                     $tempss["time"] = $time;

    //                     $hris_datas[] = $tempss;
    //                 }
    //                 // return $hris_datas;

    //                 if($data->delivery_date <> null)
    //                 {
    //                     // GET THE TOTAL NON-WORKING HOURS
    //                     $e = new DateTime('00:00');
    //                     $f = clone $e;
                        
    //                     foreach($hris_datas as $index => $hris_data)
    //                     {
                            
    //                         if($index <> array_key_last($hris_datas) || $index == 0)
    //                         {
    //                             $a = new DateTime($hris_data['date'] . ' ' . $hris_data['time']);
    //                             $b = new DateTime($this->add_day($hris_data['date'] . ' 07:30:00', '+1 day'));
    //                             $interval = $a->diff($b);
    //                             $e->add($interval);
    //                         }
    //                     }

    //                     $temp['total-hours'] = $this->two_dates_interval($data->issue_date, $data->delivery_date); // TOTAL HOURS

    //                     $d = ($f->diff($e)->format("%d") > 1) ? 'days' : 'day';
    //                     $h = ($f->diff($e)->format("%H") > 1) ? 'hours' : 'hour';
    //                     $m = ($f->diff($e)->format("%I") > 1) ? 'minutes' : 'minute';
    //                     $temp["non-working-hours"] = $f->diff($e)->format("%d {$d} %H {$h} %I {$m}"); // NON WORKING HOURS

    //                     $temp["leadtime"] = $this->convert_leadtime($temp['total-hours'], $temp["non-working-hours"]); // LEADTIME
    //                 }
    //                 else
    //                 {
    //                     // SET THE TOTAL NON-WORKING HOURS TO NULL
    //                     $temp['total-hours'] = null;
    //                     $temp["non-working-hours"] = null;
    //                     $temp["leadtime"] = null;
    //                 }

    //             $datas[] = $temp;
    //         }
            
            
    //         // return response()->json([
    //         //     'status' => 0, 
    //         //     'message' => $validator->errors()->all(),
    //         //     'data'    => $datas
    //         // ]);
    //         return $datas;
    //     }

       
    // }