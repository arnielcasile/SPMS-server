<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Areas;
use DB;


class AreasController extends Controller
{
    protected $area_code;
    public function __construct()
    {
        $this->area_code = new Areas();
    }

    /* return @array
    * _method GET
    * request data required []
    */
    public function load_area_code()
    {
        try 
        {
            DB::beginTransaction();

            $this->area_code->load_area_code();
            DB::commit();

            return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $this->area_code->load_area_code(),
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
    * request data required [area_code]
    */
    public function add_area_code(Request $request)
    {
        $data =
        [
            "area_code"     => $request->area_code,
        ];

        $rule =
        [
            "area_code"     => 'required|unique:area',
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

                $this->area_code->add_area_code($data);
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
    public function search_area_code(Request $request)
    {
        $where = 
        [   
            'id' => $request->id
        ];

        $rule = 
        [
            'id' => 'required'
        ];

        $validator = Validator::make($where, $rule);

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

                $load =  $this->area_code->search_area_code($where);
                
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
    * request data required [id]
    */
    public function update_area_code(Request $request)
    {
        $where = 
        [
            "id" => $request->id
        ];

        $data =
        [
            "area_code"     => $request->area_code,
        ];

        $rule = 
        [
            "area_code"     => 'required|unique:area',
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

                $this->area_code->update_area_code($data, $where);

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

    public function active_area_code(Request $request)
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

               $this->area_code->update_status($request->id);

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
    
    /*
    * return @array
    * _method DELETE
    * request data required [id]
    */
    public function inactive_area_code(Request $request)
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

                $this->area_code->soft_delete($data, $where);

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
                        'message'   => 'Unable to deactivate',
                        'data'      => '',
                    ]);
            }          
        }     
    }

    /* return @array
    * _method GET
    * request data required []
    */
    public function load_area_code_for_restore()
    {
        try 
        {
            DB::beginTransaction();

            $this->area_code->load_area_code_for_restore();
            DB::commit();

            return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $this->area_code->load_area_code_for_restore(),
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
