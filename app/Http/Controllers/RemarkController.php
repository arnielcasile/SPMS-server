<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Remark;
use \DB;

class RemarkController extends Controller
{
    protected $remark;

    public function __construct()
    {
        $this->remark = new Remark();
    }

    public function add_remarks(Request $request)
    {
        $data = 
        [
            'issued_date'       => $request->issued_date,
            'area_code'         => $request->area_code,
            'remarks'           => $request->remarks,
            'corrective_action' => $request->corrective_action
        ];

        $where =
        [
            'issued_date'       => $request->issued_date,
            'area_code'         => $request->area_code
        ];

        $rule = 
        [
            'issued_date'       => 'required',
            'area_code'         => 'required',
            'remarks'           => 'required',
            'corrective_action' => 'required'
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
                $load = $this->remark->load_remarks($where);
                $count = count($load);

                if ($count != 0)
                {
                    return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Issued date already exist',
                        'data'      => '',
                    ]);
                }
                else
                {
                    $this->remark->add_remarks($data);
                }

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

    public function update_remarks(Request $request)
    {
        $where = 
        [
            "id" => $request->id
        ];

        $data = 
        [
            'issued_date'       => $request->issued_date,
            'area_code'         => $request->area_code,
            'remarks'           => $request->remarks,
            'corrective_action' => $request->corrective_action
        ];

        $rule = 
        [
            'issued_date'       => 'required',
            'area_code'         => 'required',
            'remarks'           => 'required',
            'corrective_action' => 'required'
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

                $this->remark->update_remarks($data, $where);

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

    public function load_remarks(Request $request)
    {
        $data = 
        [
            'issued_date'       => $request->issued_date,
            'area_code'         => $request->area_code
        ];

        $rule = 
        [
            'issued_date'       => 'required',
            'area_code'         => 'required'
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

                $load =  $this->remark->load_remarks($data);

                DB::commit();

                $result = [];
                $count = count($load);

                for($a=0;$a<$count;$a++)
                {
                    $result[] = 
                        [
                            'area_code'         => $load[$a]->area_code,
                            'remarks'           => $load[$a]->remarks,
                            'corrective_action' => $load[$a]->corrective_action,
                            'id'                => $load[$a]->id
                        ];
                }
                 
                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $result,
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

    public function remove_remarks(Request $request)
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

                $this->remark->soft_delete($where);

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
                        'message'   => 'Unable to Delete',
                        'data'      => '',
                    ]);
            }          
        }

    }
}
