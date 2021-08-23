<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transactions;
use \DB;

class TransactionController extends Controller
{
    protected $transaction;

    public function __construct()
    {
        $this->transaction = new Transactions();
    }

    public function load_delivery_type()
    {
        return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $this->transaction->load_delivery_type()
                ]);
    }

    public function load_for_dispatch()
    {
        try
        {
            DB::beginTransaction();

            $load = $this->transaction->load_for_dispatch();

            DB::commit();
            $count_load = count($load);
            $result = [];
            $dr_control = [];
            $ticket_no = [];

            for ($z = 0; $z<$count_load ; $z++)
            {
                if (!in_array($load[$z]->dr_control,$dr_control))
                {
                    $dr_control [] = $load[$z]->dr_control;
                }
            }
            $count_control = count($dr_control);

            for  ($y = 0; $y<$count_control ; $y++)
            {
                $total_qty                = 0;
                $temp_dr_control          = "";
                $temp_ticket_issue_date   = "";
                $temp_product_no          = "";
                $temp_delivery_qty        = "";
                $temp_manufacturing_no    = "";
                $temp_breakdown           = "";
                $temp_remarks             = "";

                for ($x = 0; $x<$count_load ; $x++)
                {
                    if ($dr_control[$y] == $load[$x]->dr_control) 
                    {
                        if ($load[$x]->irreg == null) 
                        {
                            $total_qty                  += $load[$x]->delivery_qty;
                            $temp_dr_control            = $load[$x]->dr_control;
                            $temp_ticket_issue_date     = $load[$x]->ticket_issue_date;
                            $temp_product_no            = $load[$x]->product_no;
                            $temp_manufacturing_no      = $load[$x]->manufacturing_no;
                            $temp_breakdown             = $load[$x]->breakdown;
                            $temp_remarks               = $load[$x]->remarks;
                            $ticket_no []               = $load[$x]->normal;
                        }
                        else
                        {
                            $count_dr = explode('-', $load[$x]->dr_control);
                            $no = $count_dr[4];
                            if($load[$x]->transaction == "NORMAL")
                            {
                                // if (!in_array($load[$x]->normal,$ticket_no))
                                // {
                                    if ($load[$x]->normal_status == "5")
                                    {
                                        $total_qty                  += $load[$x]->actual_qty;
                                        $temp_dr_control            = $load[$x]->dr_control;
                                        $temp_ticket_issue_date     = $load[$x]->ticket_issue_date; 
                                        $temp_product_no            = $load[$x]->product_no;
                                        $temp_manufacturing_no      = $load[$x]->manufacturing_no;
                                        $temp_breakdown             = $load[$x]->breakdown;
                                        $temp_remarks               = $load[$x]->remarks;
                                        $ticket_no []               = $load[$x]->normal;
                                    }  
                                // } 
                            }
                            else
                            {
                                if ($load[$x]->irreg_status == 5)
                                {
                                    $total_qty                  += $load[$x]->discrepancy;
                                    $temp_dr_control            = $load[$x]->dr_control;
                                    $temp_ticket_issue_date     = $load[$x]->ticket_issue_date;
                                    $temp_product_no            = $load[$x]->product_no;
                                    $temp_manufacturing_no      = $load[$x]->manufacturing_no;
                                    $temp_breakdown             = $load[$x]->breakdown;
                                    $temp_remarks               = $load[$x]->remarks;
                                    $ticket_no []               = $load[$x]->normal;
                                }
                            }
                        }
                    }
                }

                if ($temp_dr_control != "")
                { 
                    $result [] =
                    [
                        'dr_control'            => $temp_dr_control,
                        'ticket_issue_date'     => $temp_ticket_issue_date,
                        'product_no'            => $temp_product_no,
                        'delivery_qty'          => $total_qty,
                        'manufacturing_no'      => $temp_manufacturing_no,
                        'breakdown'             => $temp_breakdown,
                        'remarks'               => $temp_remarks,
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
