<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\DeliveryTypeMasterlist;
use \DB;

class DeliveryTypeMasterlistController extends Controller
{
    protected $delivery_type;

    public function __construct()
    {
        $this->delivery_type = new DeliveryTypeMasterlist;
    }
    /*
    * return @array
    * _method GET
    * request data required []
    */
    public function load_delivery_type()
    {        
        try 
        {
            DB::beginTransaction();
            $this->delivery_type->load_delivery_type();
            DB::commit();
            return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $this->delivery_type->load_delivery_type()
                ]);            
        } 
        catch (\Throwable $th) 
        {
            DB::rollback();
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => 'Unable to Insert',
                    'data'      => '',
                ]);
        }
    }
    /*
    * return @array
    * _method POST
    * request data required [delivery_type]
    */
    public function add_delivery_type(Request $request)
    { 
        $data =
            [
                "delivery_type"     => $request->delivery_type,
            ];
        $rule =
            [
                "delivery_type"     => 'required|unique:delivery_type_masterlists',
            ];

        $validator = Validator::make($data, $rule);
        if ($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => '',
                ]);
        }
        else
        {
            try 
            {
                DB::beginTransaction();
                $this->delivery_type->insert($data);
                DB::commit();
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $data
                    ]);            
            } 
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to Insert',
                        'data'      => '',
                    ]);
            }           
        }     
    }
    /*
    * return @array
    * _method GET
    * request data required [id]
    */
    public function search_delivery_type(Request $request)
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
                $load =  $this->delivery_type->search_delivery_type($where);
                DB::commit();

                if(is_null($load))
                {
                    return response()
                         ->json([
                            'status'    => 0,
                            'message'   => 'Id does not exist.',
                            'data'      => '',
                    ]);
                }
                else
                {
                    return response()
                        ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $load,
                    ]);         
                }       
            } 
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to load data',
                        'data'      => '',
                    ]);
            }
        }
    }
    /*
    * return @array
    * _method PATCH
    * request data required [id, delivery_type]
    */
    public function update_delivery_type(Request $request)
    {
        $where = 
            [
                "id" => $request->id
            ];
        $data =
            [
                "delivery_type"     => $request->delivery_type,
            ];
        $rule = 
            [
                "delivery_type"     => 'required|unique:delivery_type_masterlists',
            ];

        $validator = Validator::make($data, $rule);
        if ($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => '',
                ]);
        }
        else
        {
            DB::beginTransaction();
        
            try 
            {
                $this->delivery_type->update_delivery_type($data, $where);
                DB::commit();
    
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $data
                    ]);            
    
            } 
            catch (\Throwable $th) 
            {
                DB::rollback();
    
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to Insert',
                        'data'      => '',
                    ]);
            }           
        }     
    }
    /*
    * return @array
    * _method DELETE
    * request data required [id]
    */
    public function delete_delivery_type(Request $request)
    {
        $where = 
            [
                "id" => $request->id
            ];
        $data =
            [
                "id"     => $request->id,
            ];
        $rule =
            [
                "id"     => 'required',
            ];

        $validator = Validator::make($data, $rule);
        if ($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => '',
                ]);
        }
        else
        {   
            try 
            {
                DB::beginTransaction();
                $this->delivery_type->soft_delete($data, $where);
                DB::commit();
    
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => $data
                    ]);            
            } 
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to Insert',
                        'data'      => '',
                    ]);
            }          
        }     
    }


    /*
    * return @array
    * _method GET
    * request data required []
    */
    public function load_delivery_type_overall()
    {        
        try 
        {
            DB::beginTransaction();
            $this->delivery_type->load_delivery_type_overall();
            DB::commit();
            return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $this->delivery_type->load_delivery_type_overall()
                ]);            
        } 
        catch (\Throwable $th) 
        {
            DB::rollback();
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => 'Unable to Insert',
                    'data'      => '',
                ]);
        }
    }

    public function active_delivery_type(Request $request)
    {
        $data =
        [
            "id"     => $request->id,
        ];

        $rule = 
        [
            "id"     => 'required',
        ];
        $validator = Validator::make($data, $rule);

        if ($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => '',
                ]);
        }
        else
        { 
            try 
            {
                DB::beginTransaction();

               $this->delivery_type->active_delivery_type($request->id);

                DB::commit();
    
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => '',
                        'data'      => 'Successfully Updated'
                    ]);            
            } 
            catch (\Throwable $th) 
            {
                DB::rollback();
    
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to Update',
                        'data'      => '',
                    ]);
            }             
        }     
    }

}
