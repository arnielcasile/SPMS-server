<?php

namespace App\Http\Controllers;

use App\AreaPayoutMasterlist;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class AreaPayoutController extends Controller
{
    protected $area_payout;

    public function __construct()
    {
        $this->area_payout = new AreaPayoutMasterlist;
    }

    public function get_all()
    {
       return $this->area_payout->retrieve_all();
    }
    /*
    * return @array
    * _method GET
    * request data [id]
    */ 
    public function get_one(Request $request)
    {
        $where = 
            [
                'id' => $request->id
            ];
        $rule = 
            [
                'id' => 'required'
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
                $load =  $this->area_payout->retrieve_one($where);
                DB::commit();
                if(is_null($load))
                {
                    return response()->json([
                        'status'    => 0,
                        'message'   => 'Id does not exist.',
                        'data'      => '',
                    ]);
                }
                else
                {
                    return response()->json([
                       'status'    => 1,
                        'message'   => '',
                        'data'      => $load,
                    ]);         
                }                  
            }
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()->json([
                    'status'    => 0,
                    'message'   => 'Unable to load data',
                    'data'      => '',
                ]);
            }
        }   
    }
    /*
    * return @array
    * _method POST
    * request data required [area_payout]
    */ 
    public function add(Request $request)
    {
        $data = 
            [
                'area_payout' => $request->area_payout
            ];
        $rule = 
            [
                'area_payout' => 'required|unique:area_payout_masterlists'
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
                $this->area_payout->insert($data);
                DB::commit();
                return response()->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $data,
                ]);               
            }
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()->json([
                    'status'    => 0,
                    'message'   => 'Unable to Insert',
                    'data'      => '',
                ]);
            }
        }
    }
    /*
    * return @array
    * _method POST
    * request data required [id, area_payout]
    */ 
    public function update(Request $request)
    {
        $where = 
            [
                'id' => $request->id
            ];
        $data = 
            [
                'area_payout' => $request->area_payout
            ];
        $rule = 
            [
                'area_payout' => 'required|unique:area_payout_masterlists'
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
                DB::beginTransaction();
                $this->area_payout->update_area_payout($data, $where);
                DB::commit();
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $data,
                    ]);            
    
            } 
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to update.',
                        'data'      => '',
                    ]);
            }
        }       
    }
    /*
    * return @array
    * _method POST
    * request data required [id, area_payout]
    */ 
    public function delete(Request $request)
    {
        $where = 
             [
                'id' => $request->id
             ];
        $rule = 
            [
                'id' => 'required'
            ];

        $validator = Validator::make($where,$rule);

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
                $this->area_payout->soft_delete($where);
                DB::commit();
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $where,
                    ]);            
    
            }
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to delete.',
                        'data'      => '',
                    ]);
            }
        }
    }
}
