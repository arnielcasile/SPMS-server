<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\MasterData;
use App\ForDelivery;
use App\Report;
use DateTime;
use DatePeriod;
use DateInterval;
use Carbon\Carbon;
use App\Http\Controllers\MonitoringController;
use Illuminate\Support\Collection;
use Carbon\CarbonPeriod;
use DB;

use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    protected $delivery, $master_data, $monitoring, $report;
    
    public function __construct(MonitoringController $monitoring)
    {
        $this->delivery = new ForDelivery();
        $this->master_data = new MasterData();
        $this->monitoring = $monitoring;
        $this->report = new Report();
    }
    public function delivery_leadtime(Request $request)
    {
        $delivery_leadtime = $this->report->load_delivery_leadtime ($request->date_from,$request->date_to);
        $area_code=[];
        $dates_covered=[];
        $result=[];
        $data_array=[];
      

        foreach($delivery_leadtime as  $delivery_leadtime_key => $delivery_leadtime_value)
        {
            if(!in_array($delivery_leadtime_value->warehouse_class,$area_code))
            {
                array_push($area_code,$delivery_leadtime_value->warehouse_class);
            }
        }
        foreach($delivery_leadtime as  $delivery_leadtime_key => $delivery_leadtime_value)
        {
            if(!in_array($delivery_leadtime_value->ticket_issue_date,$dates_covered))
            {
                array_push($dates_covered,$delivery_leadtime_value->ticket_issue_date);
            }
        }
        $data_array = [];
     
        foreach ($dates_covered as $dates_covered_key => $dates_covered_value) {
            $total_qty=0;
            $temp_array = [];
            foreach ($area_code as $area_code_key => $area_code_value) {
                $temp_value = 0;
                foreach ($delivery_leadtime as $delivery_leadtime_key => $delivery_leadtime_value) {
                    if(($delivery_leadtime_value->warehouse_class == $area_code_value) && ($delivery_leadtime_value->ticket_issue_date == $dates_covered_value))
                    {
                        $temp_value = $delivery_leadtime_value->count;
                        $total_qty+= $delivery_leadtime_value->count;
                    }
                }
                
                $temp_array[$area_code_value] = $temp_value;
                $temp_array["total_qty"] = $total_qty;
            }
            $temp_array["ticket_issue_date"] = $dates_covered_value;
            $data_array[] = $temp_array;
        }
        return $data_array;  
    }

    public function lead_time_report(Request $request)
    {
        $data = 
        [
            'date_range' => $request->date_range,
            'date_from'  => $request->date_from,
            'date_to'    => $request->date_to,
            'area_code'  => $request->area_code
        ];

        $rule = 
        [
            'date_range'    => 'required',
            'date_from'     => 'required',
            'date_to'       => 'required',
            'area_code'     => 'required'
        ];

        $validator = Validator::make($data, $rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    =>  0,
                    'message'   =>  $validator->errors()->all(),
                    'data'      => ''
                ]);
        }
        else
        {
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

            $where =
            [
                'c.warehouse_class' => $request->area_code,
            ];
            
            try
            {
                DB::beginTransaction();
                $load_master_data = $this->master_data->load_data_for_monitoring($area_code,$from,$to);
                $load_master_count = $this->master_data->load_filtered_item($from,$to,$area_code);
                $load_delivery = $this->delivery->load_delivery_for_report($where);
                $array_date = $this->monitoring->date_range($request->date_from,$request->date_to);
                $load_finish_count = $this->report->finish_count($where);
              
                DB::commit();
                
                $rows_delivery = [];
                $ticket_issuance = [];
                $row_finished_count = [];
                $rows_average_leadtime = [];
                $rows_max_leadtime = [];

                //get the lead time codes by princess
                $count_master = count($load_master_data);
                $temp_lead_time = [];
                for($a = 0; $a < $count_master; $a++)
                {
                    $time = $load_master_data[$a]->ticket_issue_date .' '. $load_master_data[$a]->ticket_issue_time;

                    if($load_master_data[$a]->ticket_issue_time == null && $load_master_data[$a]->delivered == null || $load_master_data[$a]->ticket_issue_time == null)
                        $leadtime = 0;
                    elseif($load_master_data[$a]->delivered == null)
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
                        $timestamp2 = strtotime($load_master_data[$a]->delivered);
                        $leadtime = abs($timestamp2 - $timestamp1)/(60*60);
                    }
                    $temp_lead_time[] = [$leadtime,$load_master_data[$a]->ticket_issue_date];
                }
                
                $temp_max = [];
                
                if($temp_lead_time == null)
                {
                    $temp_max[] = [0,0];
                    $temp_average[] = 0;

                }
                else
                {
                 
                      //sum of lead time
                  
                    foreach($temp_lead_time as $a)
                    {
                     
                        if(!isset($result[$a[1]])){
                            $result[$a[1]] = $a;  // store temporary city-keyed result array
                        }else{
                            $result[$a[1]][0] += $a[0];  // add current value to previous value
                           
                        } 
                    }
                //    return $temp_lead_time;
                    foreach($array_date as $dates)
                    {
                        $max = 0;
                        foreach($temp_lead_time as $a)
                        {
                            if($dates == $a[1])
                            {
                                $lead = $a[0];
                                if(floatval($lead) > $max)
                                {
                                    $max = floatval($lead);
                                }
                            }
                        }
                        $temp_max[] = [$dates, $max]; 
                    }                  

                    //get the average and max lead time 
              
                    foreach($result as $b)
                    {
                        $temp_average[] = [$b[1],$b[0]/count($temp_lead_time)];  
                    }   
                }
              
              

                for($range_date=0;$range_date<count($array_date);$range_date++)
                {
                    $delivery_count = 0;
                    $ticket_issue= '';
                    $count = 0;

                    //get the delivery count per ticket_issue_date
                    for($delivery=0; $delivery<count($load_delivery); $delivery++)
                    {
                        $delivery_date = $load_delivery[$delivery]->created_at;
                        if($array_date[$range_date] == date("Y-m-d",strtotime($delivery_date)))
                        {
                            $delivery_count ++;   
                            $ticket_issue = $load_delivery[$delivery]->ticket_issue_date;   
                        }
                    }
                    $rows_delivery[] =[$ticket_issue,$delivery_count];

                    //get the finish count per ticket_issue_date
                    for($a=0;$a<count($load_finish_count);$a++)
                    {
                        if($array_date[$range_date] == $load_finish_count[$a]->ticket_issue_date)
                            {
                                $count = $load_finish_count[$a]->count;
                            }
                    }
                        
                    $row_finished_count[] = $count;
                    
                    //get the average lead time
                    $average_lead_time =0;
                    for($b=0;$b<count($temp_average);$b++)
                    {
                        if($array_date[$range_date] == $temp_average[$b][0])
                        {
                            $average_lead_time = $temp_average[$b][1];
                        }
                    }
                    $rows_average_leadtime[] = round($average_lead_time,2);

                    //get the max lead time
                    $max_leadtime =0;
                    for($b=0;$b<count($temp_max);$b++)
                    {
                        if($array_date[$range_date] == $temp_max[$b][0])
                        {
                            $max_leadtime = $temp_max[$b][1];
                        }
                    }
                    $rows_max_leadtime[] = round($max_leadtime,2);

                }

                //get the ticket count per issue date
                $row_ticket_count = [];
                for($range_date=0;$range_date<count($array_date);$range_date++)
                {
                    $ticket_count = 0;
                        for($master=0; $master<count($load_master_count); $master++)
                        {
                            $ticket_issue = $load_master_count[$master]->ticket_issue_date;
                            if($array_date[$range_date] == date("Y-m-d",strtotime($ticket_issue)))
                                $ticket_count ++;                               
                        }
                    $row_ticket_count[] =$ticket_count;
                }       
                
                //get the ddelivery data per rows
                $delivery= [];
                for($g=0;$g<count($array_date);$g++)
                {
                    $total_delivery = '';
                    $delivery_data = null;
                        for($b=0;$b<count($rows_delivery);$b++)
                        {
                            for($j=0;$j<count($array_date);$j++)
                            { 
                                $total_ticket_delivery = '';
                                if($array_date[$g] == $rows_delivery[$b][0])
                                {
                                    $total_ticket_delivery = $rows_delivery[$b][1]; 
                                }
                            }     
                            $delivery_data[] =  $total_ticket_delivery;                             
                        }
                    $delivery[]=[$array_date[$g],$delivery_data];
               
                }       

                $per_rows = [];
                $balance = 0;
                $range = [];
                
                for($range_date=0;$range_date<count($array_date);$range_date++)
                {
                    $balance = $row_ticket_count[$range_date] - $row_finished_count[$range_date];
                    $range[] = date('Y-m-d D', strtotime($array_date[$range_date]));
                    $per_rows[] = [date('Y-m-d D', strtotime($array_date[$range_date])), $row_ticket_count[$range_date],$row_finished_count[$range_date], $balance, $rows_average_leadtime[$range_date],$rows_max_leadtime[$range_date],$delivery[$range_date][1]];                      
                }

                $header = array_merge(['Issuance Date', 'Total', 'Finish', 'Balance', 'Lead Time', 'Max'],$range);
                

                $final = [];
                $final[] = $header;
                $final[] = $per_rows;

                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $final
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

    public function load_pallet_report(Request $request)
    {
        $data = 
        [
            'date_range'       => $request->date_range,
            'date_from'        => $request->date_from . " " . "00:00:00",
            'date_to'          => $request->date_to . " " . "23:59:59",
            'area_code'        => $request->area_code,
        ];

        $rule = 
        [
            'date_range'       => 'required',
            'date_from'        => 'required',
            'date_to'          => 'required',
            'area_code'        => 'required',
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

                $array_delivery_type = $this->report->delivery_type();
                $array_destination = $this->report->destination();
                $array_dates = $this->date_range($data['date_from'], $data['date_to']);

                DB::commit();

                $storage = [];

                foreach($array_delivery_type as $delivery_type)
                {
                    $temp = [];
                    $temp[] = $delivery_type->destination;
                    $temp[] = $delivery_type->delivery_type;
                    $total = 0;

                    for($a = 0; $a < count($array_dates); $a++)
                    {
                        $count = 0;
                        foreach($array_destination as $destination)
                        {
                            if($delivery_type->destination == $destination->destination && $destination->created_at == $array_dates[$a] && $delivery_type->delivery_type_id == $destination->delivery_type_id)
                                $count++;
                        }
                        $temp[] = $count;
                        $total += $count;
                    }
                    $temp[] = $total;
                    $storage[] = $temp;
                }

                array_unshift($array_dates, 'DESTINATION', 'TYPE');
                $array_dates[]  = 'TOTAL';
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

    public function overall_report(Request $request)
    {
        $data = 
        [
            'date_range' => $request->date_range,
            'date_from'  => $request->date_from,
            'date_to'    => $request->date_to,
            'area_code'  => $request->area_code
        ];

        $rule = 
        [
            'date_range'    => 'required',
            'date_from'     => 'required',
            'date_to'       => 'required',
            'area_code'     => 'required'
        ];

        $validator = Validator::make($data, $rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    =>  0,
                    'message'   =>  $validator->errors()->all(),
                    'data'      => ''
                ]);
        }
        else
        {
            // try
            // {
               
                $temp_lead_time = [];
                $temp_ticket = [];

                $rows_ticket[] = 'Issue Ticket';
                $rows_finish[] = 'Finished Ticket';
                $rows_balance[] = 'Unfinished Ticket';
                $rows_average[] = 'Lead Time';
                $rows_max[] = 'Max';

                $columns[] = 'Month';

                if($request->date_range == "WEEKLY HORENSO")
                {
                    $new_range = $this->horenso_range($request->date_from,$request->date_to);
                    $from = $new_range['date_from'];
                    $to = $new_range['date_to'];
                    $date_ranges = $this->get_weekly_range($from,$to);
                    $counter = 0;
                    for($a=0;$a<count($date_ranges);$a++)
                    {
                        $counter++;
                        $range_from = $date_ranges[$a]['from'];
                        $range_to = $date_ranges[$a]['to'];
                        $temp_lead_time[] = $this->get_weekly_lead_time($range_from,$range_to,$request->area_code);
                        $temp_ticket[] = $this->get_issued_tickets($range_from,$range_to,$request->area_code);

                        $columns[] = 'W' . $counter . ' ' .  date('M-d', strtotime($range_from)) . ' - ' .  date('M-d', strtotime($range_to));

                    }
                    
                    for($a=0;$a<count($date_ranges);$a++)
                    {
                        $rows_ticket[] = $temp_ticket[$a][0];
                        $rows_finish[] = $temp_ticket[$a][1];
                        $rows_balance[] = $temp_ticket[$a][2];
                        $rows_average[] = $temp_lead_time[$a]['average'];
                        $rows_max[] = $temp_lead_time[$a]['max'];
                        
                    }

                }
                else if($request->date_range == "WEEKLY")
                   {
                   
                        $date_ranges = $this->get_weekly_range($date_from,$date_to);
                        $counter = 0;
                        for($a=0;$a<count($date_ranges);$a++)
                        {
                            $counter++;
                            $range_from = $date_ranges[$a]['from'];
                            $range_to = $date_ranges[$a]['to'];
                            $temp_lead_time[] = $this->get_weekly_lead_time($range_from,$range_to,$request->area_code);
                            $temp_ticket[] = $this->get_issued_tickets($range_from,$range_to,$request->area_code);

                            $columns[] = 'W' . $counter . ' ' .  date('M-d', strtotime($range_from)) . ' - ' .  date('M-d', strtotime($range_to));

                        }
                        
                        for($a=0;$a<count($date_ranges);$a++)
                        {
                            $rows_ticket[] = $temp_ticket[$a][0];
                            $rows_finish[] = $temp_ticket[$a][1];
                            $rows_balance[] = $temp_ticket[$a][2];
                            $rows_average[] = $temp_lead_time[$a]['average'];
                            $rows_max[] = $temp_lead_time[$a]['max'];
                            
                        }


                   }
                   else if($request->date_range == "DAILY")
                   {
                        $from  = $this->date_range($request->date_from,$request->date_to);   
                        for($a=0;$a<count($from);$a++){

                            $temp_lead_time[] = $this->get_weekly_lead_time($from[$a],$from[$a],$request->area_code);
                            $temp_ticket[] = $this->get_issued_tickets($from[$a],$from[$a],$request->area_code);

                            $rows_ticket[] = $temp_ticket[$a][0];
                            $rows_finish[] = $temp_ticket[$a][1];
                            $rows_balance[] = $temp_ticket[$a][2];
                            $rows_average[] = $temp_lead_time[$a]['average'];
                            $rows_max[] = $temp_lead_time[$a]['max'];
                        }
                        $columns[] = $from;
                    } 
                    else if($request->date_range == "MONTHLY")
                    {
                        $months = $this->get_month($request->date_from,$request->date_to);
                        for($a=0;$a<count($months);$a++)
                        {
                            $from = date('Y-m-01', strtotime($months[$a]));
                            $to = date('Y-m-t', strtotime($months[$a]));

                            
                            $temp_lead_time[] = $this->get_weekly_lead_time($from,$to,$request->area_code);
                            $temp_ticket[] = $this->get_issued_tickets($from,$to,$request->area_code);

                            $rows_ticket[] = $temp_ticket[$a][0];
                            $rows_finish[] = $temp_ticket[$a][1];
                            $rows_balance[] = $temp_ticket[$a][2];
                            $rows_average[] = $temp_lead_time[$a]['average'];
                            $rows_max[] = $temp_lead_time[$a]['max'];
                        }

                        $columns[] = $months;
                       

                    }
                    else if($request->date_range == "YEARLY")
                    {
                        $years = $this->get_year($request->date_from,$request->date_to);
                        for($a=0;$a<count($years);$a++)
                        {
                            $from = date('Y-m-01',mktime(0, 0, 0, 1, 1, $years[$a]));
                            $to  = date('Y-m-t',mktime(0, 0, 0, 12, 31, $years[$a]));

                            $temp_lead_time[] = $this->get_weekly_lead_time($from,$to,$request->area_code);
                            $temp_ticket[] = $this->get_issued_tickets($from,$to,$request->area_code);

                            $rows_ticket[] = $temp_ticket[$a][0];
                            $rows_finish[] = $temp_ticket[$a][1];
                            $rows_balance[] = $temp_ticket[$a][2];
                            $rows_average[] = $temp_lead_time[$a]['average'];
                            $rows_max[] = $temp_lead_time[$a]['max'];

                        }
                        $columns[] = $years;
                    } 
                
                   $per_rows = [$rows_ticket, $rows_finish, $rows_balance, $rows_average, $rows_max];
      
                   $final = [];
                   $final[] = $columns;
                   $final[] = $per_rows;

                   return response()
                    ->json([
                        'status'    => 1,
                        'messaage'  => '',
                        'data'      => $final
                    ]);
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
        }

    }

    public function get_year($from, $to)
    {
        $start_date    = $from;
        $end_date      = $to;
        $getRangeYear   = range(gmdate('Y', strtotime($start_date)), gmdate('Y', strtotime($end_date)));
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
    public function horenso_range($from, $to)
    {
        $no_day_from = date('N', strtotime($from));
        $no_day_to = date('N', strtotime($to));
    

        if($no_day_from  == 7)
        {
            $date_from =  Carbon::parse($from);
           
        }
        else if($no_day_to == 6)
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

    public function get_weekly_lead_time($from, $to, $area_code)
    {
        // try
        // {
            DB::beginTransaction();
            $load_master_data = $this->master_data->load_data_for_monitoring($area_code,$from,$to);
            DB::commit();

            $count_master = count($load_master_data);
                $temp_lead_time = [];
                $sum_lead_time = 0;
                for($a = 0; $a < $count_master; $a++)
                {
                    $time = $load_master_data[$a]->ticket_issue_date .' '. $load_master_data[$a]->ticket_issue_time;

                    if($load_master_data[$a]->ticket_issue_time == null && $load_master_data[$a]->irreg_delivery == null || $load_master_data[$a]->ticket_issue_time == null)
                        $leadtime = 0;
                    elseif($load_master_data[$a]->irreg_delivery == null)
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
                        $timestamp2 = strtotime($load_master_data[$a]->irreg_delivery);
                        $leadtime = abs($timestamp2 - $timestamp1)/(60*60);
                    }
                    $temp_lead_time[] = $leadtime;
                    $sum_lead_time = floatval($sum_lead_time) + floatval($leadtime);

                }

                $average_lead_time =  []; 
                $max_lead_time = [];
                if(count($temp_lead_time) < 1)
                {
                    $average_lead_time = 0;
                    $max_lead_time = 0;  
                }
                else
                {
                    $average_lead_time = floatval($sum_lead_time) / count($temp_lead_time);
                    $max_lead_time = max($temp_lead_time);
                }
                $result = 
                    [
                        'max'     => $max_lead_time,
                        'average' => $average_lead_time
                    ];
                return $result;             
        // }
        // catch(\Throwable $th)
        // {
        //     DB::rollback();
        //     return response()
        //         ->json([
        //             'status'    => 0,
        //             'message'   => $th->getMessage(),
        //             'data'      => ''
        //         ]);
        // }
    }

    public function get_issued_tickets($from, $to, $area_code)
    {
        // try
        // {
            $ticket_count = 0;
            $finish_ticket = 0;
            $unfinish_ticket = 0;
            DB::beginTransaction();
            $load_master_data = $this->master_data->load_filtered_item($from,$to,$area_code);
            $load_finish_count= $this->report->all_finish_count($area_code,$from,$to);
            DB::commit();

            $ticket_count = count($load_master_data); 
            $finish_ticket = $load_finish_count[0]->count;
            $unfinish_ticket  = intval($ticket_count) - intval($finish_ticket);
      
            return [$ticket_count, $finish_ticket,$unfinish_ticket];
                

        // }
        // catch(\Throwable $th)
        // {
        //     DB::rollback();
        //     return response()
        //         ->json([
        //             'status'    => 0,
        //             'message'   => $th->getMessage(),
        //             'data'      => ''
        //         ]);
        // }
    }

}
