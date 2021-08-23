<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\DestinationMasterlist;
use \DB;

class DestinationMasterlistController extends Controller
{
    protected $destination;

    public function __construct()
    {
        $this->destination = new DestinationMasterlist();
    }

    /*
    * return @array
    * _method POST
    * request data required [payee_cd, payee_name, destination, attention_to, destination_class, purpose]
    */
    public function add_destination(Request $request)
    {
        $data = 
        [
            "payee_cd"          => $request->payee_cd,
            "payee_name"        => $request->payee_name,
            "destination"       => $request->destination,
            "attention_to"      => $request->attention_to,
            "destination_class" => $request->destination_class,
            "purpose"           => $request->purpose,
            "pdl"               => $request->pdl
        ];

        $rule =
        [
            "payee_cd"          => 'required',
            "payee_name"        => 'required',
            "destination"       => 'required',
            "attention_to"      => 'required',
            "destination_class" => 'required',
            "purpose"           => 'required',
            "pdl"               => 'required'
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

                $this->destination->add_destination($data);

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
    public function load_destination()
    {
        $this->destination->load_destination();

        return response()
            ->json([
                'status'    => 1,
                'message'   => '',
                'data'      => $this->destination->load_destination(),
            ]);    
    }

    /*
    * return @array
    * _method PATCH
    * request data required [id, payee_cd, payee_name, destination, attention_to, destination_class, purpose]
    */
    public function update_destination(Request $request)
    {
        $where = 
        [
            "id" => $request->id
        ];

        $data =
        [
            "payee_cd"          => $request->payee_cd,
            "payee_name"        => $request->payee_name,
            "destination"       => $request->destination,
            "attention_to"      => $request->attention_to,
            "destination_class" => $request->destination_class,
            "purpose"           => $request->purpose,
            "pdl"               => $request->pdl
        ];

        $rule = 
        [
            "payee_cd"          => 'required',
            "payee_name"        => 'required',
            "destination"       => 'required',
            "attention_to"      => 'required',
            "destination_class" => 'required',
            "purpose"           => 'required',
            "pdl"               => 'required'
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

                $this->destination->update_destination($data, $where);

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
                        'message'   => 'Unable to Update',
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
    public function search_destination(Request $request)
    {
        $where = 
        [   
            'id'        => $request->id
        ];

        $rule = 
        [
            'id'        => 'required'
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

                $load =  $this->destination->search_destination($where);

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
    * _method DELETE
    * request data required [id]
    */
    public function delete_destination(Request $request)
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

                $this->destination->soft_delete($data, $where);

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


    public function active_destination(Request $request)
    {
        // return ('sample');
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

               $this->destination->update_status($request->id);

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

    public function destination_exist(Request $request)
    {
        $where = 
        [   
            'payee_cd'        => $request->payee_cd
        ];

        $rule = 
        [
            'payee_cd'        => 'required'
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

                $load =  $this->destination->destination_exist($where);

                DB::commit();

                return response()
                        ->json([
                            'status'    => 1,
                            'message'   => '',
                            'data'      => $load,
                        ]);  
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
}
