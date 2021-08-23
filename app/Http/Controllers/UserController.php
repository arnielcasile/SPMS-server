<?php

namespace App\Http\Controllers;

use App\Hris;
use App\User;
use App\Areas;
use Session;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    protected $user, $hris, $area;
    public function __construct()
    {
        $this->user = New User();
        $this->hris = New Hris();
        $this->area = New Areas();
    }
    /*
    * return @array
    * _method GET
    * request employee_number 
    */ 
    public function realtime_data(Request $request)
    {
        $employee_info=$this->user->retrive_area_code(['a.employee_number' => $request->employee_no]);
        Session::put('area_code',$employee_info[0]->area_code);
        Session::save();
        return $employee_info[0]->area_code;
    }

    public function login(Request $request)
    {
        $request_data = 
            [
                'employee_number' => $request->employee_number
            ];
        $rule = 
            [
                'employee_number' => 'required'
            ];


        $validator = Validator::make($request_data, $rule);

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
            $employee_number = $request->employee_number;         
            /*get pdls data*/ 
            $get_pdls_data = $this->retrieve_one($employee_number);
            /*hris data*/
            $get_hris_data = $this->get_hris_data($employee_number);

            if(is_null($get_pdls_data))
            { 
                if(is_null($get_hris_data))
                {
                    /*no data in hris*/
                    return response()
                        ->json([
                            'status' => 0,
                            'message' => 'Employee number does not exist in Hris.',
                            'data' => ''
                        ]);                              
                }
                else
                {
                    if($get_hris_data->emp_system_status == 'INACTIVE')
                    {   
                        /*status in HRIS is inactive*/  
                        return response()
                            ->json([
                                'status' => 0,
                                'message' => 'Invalid employee number.',
                                'data' => ''
                            ]);
                    }
                    else
                    {
                        /*insert HRIS data*/
                        return $this->insert_user($get_hris_data);
                    }
                }   
            }
            else if ($get_pdls_data->deleted_at != null)
            {
                /*status in HRIS is inactive*/  
                return response()
                ->json([
                    'status' => 0,
                    'message' => 'Your account has been deactivated',
                    'data' => ''
                ]);

            }
            else
            {
                if($get_hris_data->emp_system_status == 'INACTIVE')
                {
                    $status = '0';
                }
                elseif($get_hris_data->emp_system_status == 'ACTIVE')
                {
                    $status = "1";
                }
                $hris_data = 
                    [
                        'employee_number' => $get_hris_data->emp_pms_id, 
                        'last_name' => $get_hris_data->emp_last_name, 
                        'first_name' => $get_hris_data->emp_first_name, 
                        'middle_name' => $get_hris_data->emp_middle_name, 
                        'photo' => $get_hris_data->emp_photo,
                        'position' => $get_hris_data->position,
                        'section' => $get_hris_data->section,
                        'status' => $status,
                        'section_code' => $get_hris_data->section_code
                    ];
                $pdls_data = 
                    [
                        'employee_number' => $get_pdls_data->employee_number, 
                        'last_name' => $get_pdls_data->last_name, 
                        'first_name' => $get_pdls_data->first_name, 
                        'middle_name' => $get_pdls_data->middle_name, 
                        'photo' => $get_pdls_data->photo,
                        'position' => $get_pdls_data->position,
                        'section' => $get_pdls_data->section,
                        'status' => $get_pdls_data->status,
                        'section_code' => $get_pdls_data->section_code
                    ];

                /*comparison of PDLS and HRIS*/
                $result = (array_values($hris_data) == array_values($pdls_data));
               
                if($result == true)
                {
                    /*no difference*/
                    $where = 
                        [
                            'a.status' => 1, 'a.employee_number' => $employee_number
                        ];
                    return response()
                        ->json([
                            'status' => 1,
                            'message' => '',
                            'data' => $this->user->load_one($where)
                        ]);
                }
                elseif($result == false)
                {
                    /*update pdls using updated hris data*/
                    return $this->update_all_user($hris_data,$employee_number);
                }
            }
        }
    }
    
    public function retrieve_one($employee_number)
    {
        $where =  
            [
                'employee_number' => $employee_number
            ];
        $rule  =  
            [
                'employee_number' => 'required'
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
            $this->user->fresh();
            $data = $this->user->retrieve_one($where);
            return $data;
        }
    }

    public function get_hris_data($employee_number)
    {
        /*getting the hris data*/
        $where = 
            [
                'emp_pms_id' => $employee_number
            ];    
        $hris_data = $this->hris->manpower($where);
        return $hris_data; 
    }

    public function insert_user($hris_data)
    {
        $status = 1;
        $last_name = str_replace(" ", "", strtolower($hris_data->emp_last_name));
        $first_name = str_replace(" ", "", strtolower($hris_data->emp_first_name));
    
        $data = 
            [
                'employee_number' => $hris_data->emp_pms_id, 
                'last_name' => $hris_data->emp_last_name, 
                'first_name' => $hris_data->emp_first_name, 
                'middle_name' => $hris_data->emp_middle_name, 
                'photo' => $hris_data->emp_photo,
                'position' => $hris_data->position,
                'section' => $hris_data->section,
                'status' => $status,
                'email' => "{$first_name}.{$last_name}@ph.fujitsu.com",
                'section_code' => $hris_data->section_code, 
                'user_type_id' => 3,
                'process' => "1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
                'approver' => 0,
                'support' => 0,
            ];
        $rule =
            [
                'employee_number' => 'required|unique:users', 
                'last_name' => 'required', 
                'first_name' => 'required', 
                'middle_name' => 'required', 
                'position' => 'required',
                'status' => 'required',
                'section' => 'required',
                'email' => 'required',
                'section_code' => 'required', 
                'user_type_id' => 'required',
                'process' => 'required'
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
                $user =  $this->user->insert_user($data);
                DB::commit();
                return response()
                    ->json([
                        'status' => 1,
                        'message' => 'asdsad',
                        'data' => $user
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

    public function update_all_user($data, $employee_number)
    {
       $where =
            [
                'employee_number' => $employee_number
            ];
       $rule=
            [
                'employee_number' => 'required', 
                'last_name'  => 'required', 
                'first_name'  => 'required', 
                'middle_name'  => 'required', 
                'position'  => 'required',
                'status'  => 'required',
                'section'  => 'required',
                'section_code'  => 'required'
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
                $result = $this->user->update_user($data, $where); 
                DB::commit();
                return response()
                    ->json([
                        'status' => 1,
                        'message' => '',
                        'data' => $result
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
    * _method PATCH
    * request data [process, id]
    */ 
    public function update_process(Request $request)
    {
       $where = 
            [
                'id' => $request->id
            ];
       $data = 
            [
                'process' => $request->process
            ];
       $rule = 
            [
                'process' => 'required'
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
                $result = $this->user->update_user($data, $where); 
                DB::commit();
                return response()
                    ->json([
                        'status' => 1,
                        'message' => '',
                        'data' => $result
                    ]);
            } 
            catch (\Throwable $th) 
            {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to update process.',
                        'data'      => '',
                    ]);
            }
       }
    }  
    /* return @array
    * _method GET
    * request data []
    */
    public function load_user()
    {
        try 
        {
            DB::beginTransaction();
            $result =  $this->user->load_user(); 
            DB::commit();
            return response()
                ->json([
                    'status' => 1,
                    'message' => '',
                    'data' => $result
                ]);
        } 
        catch (\Throwable $th) 
        {
            DB::rollback();
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => 'Unable to load users.',
                    'data'      => '',
                ]);
        } 
    }
    /* return @array
    * _method PATCH
    * request data required [id, area_id]
    */
    public function update_user_area(Request $request)
    {
        $where = 
            [
                'id'        => $request->id
            ];        
        $data =
            [
                'area_id'   => $request->area_id,
                'id'        => $request->id,
            ];               
        $rule = 
            [
                'area_id'   => 'required',
                'id'        => 'required',
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
                $where_search =
                [
                    'id'    => $request->area_id
                ];

                $area = $this->area->search_area_code($where_search);
                $area_code = $area->area_code;
                if($area_code == 'RECEIVER')
                {
                    $update_data = 
                    [
                        'area_id'   => $request->area_id,
                        'receiver'  => 1
                    ];
                }
                else
                {
                    $update_data = 
                    [
                        'area_id'   => $request->area_id,
                        'receiver'  => 0
                    ];
                }
                $result = $this->user->update_user($update_data, $where);
                DB::commit();
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
                        'message'   => 'Unable to Insert',
                        'data'      => '',
                    ]);
            }                 
        }     
    }
    /* return @array
    * _method DELETE
    * request data required [id]
    */
    public function delete_user(Request $request)
    {
        $where = 
            [
                "id"        => $request->id
            ];
        $data =
            [
                "id"        => $request->id
            ];       
        $rule = 
            [
                "id"   => 'required',
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
                $this->user->soft_delete($data, $where);
                DB::commit();
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => 'Successfully deleted!',
                        'data'      => $data,
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
    * _method PATCH
     * request data [approver, id]
    */ 
    public function update_approver(Request $request)
    {
        $where = 
            [
                'id' => $request->id
            ];
        $data = 
            [
                'approver' => $request->approver
            ];
        $rule = 
            [
                'approver' => 'required'
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
                $this->user->update_user($data, $where); 
                DB::commit();  
                 return response()
                    ->json([
                        'status' => 1,
                        'message' => '',
                        'data' => $data
                    ]);
             } 
             catch (\Throwable $th) 
             {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to update process.',
                        'data'      => '',
                    ]);
             }
        }
    }
    /*
    * return @array
    * _method PATCH
    * request data [user_type, id]
    */ 
    public function update_user_type(Request $request)
    {
        $where = 
            [
                'id' => $request->id
            ];
        $data = 
            [
                'user_type_id' => $request->user_type
            ];
        $rule = 
            [
                'user_type_id' => 'required'
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
                $this->user->update_user($data, $where); 
                DB::commit();  
                return response()
                    ->json([
                        'status' => 1,
                        'message' => '',
                        'data' => $data
                    ]);
             } 
             catch (\Throwable $th) 
             {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to update process.',
                        'data'      => '',
                    ]);
             }
        }
    }

    public function update_support(Request $request)
    {
        $where = 
            [
                'id'        => $request->id
            ];
        $data = 
            [
                'support'   => $request->support
            ];
        $rule = 
            [
                'support'   => 'required'
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
                $this->user->update_support($data, $where); 
                DB::commit();  
                return response()
                    ->json([
                        'status' => 1,
                        'message' => '',
                        'data' => $data
                    ]);
             } 
             catch (\Throwable $th) 
             {
                DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Unable to update support.',
                        'data'      => '',
                    ]);
             }
        }
    }

    public function update_receive(Request $request)
    {
        $data = 
        [
            'user_id'   => $request->user_id,
            'receiver'  => $request->receiver
        ];

        $rule =
        [
            'user_id'   => 'required',
            'receiver'  => 'required'
        ];

        // return $data;
        $validator = Validator::make($data, $rule);

        if($validator->fails())
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => ''
                ]);
        }
        else
        {
            try
            {
                DB::beginTransaction();

                $where =
                [
                    'id'  => $request->user_id 
                ];

                $update_data =
                [
                    'receiver'  => $request->receiver
                ];
                $this->user->update_support($update_data, $where); 
                DB::commit();
             
                return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $data
                ]);
            }
            catch(\Throwable $th)
            {
                DB::rollback();
                return response()
                ->json([
                    'status'    => 0,
                    'message'   => $validator->errors()->all(),
                    'data'      => ''
                ]);

            }
        }
    }

    public function load_user_overall()
    {

        try 
        {
            DB::beginTransaction();

            $this->user->load_user_overall();
            DB::commit();

            return response()
                ->json([
                    'status'    => 1,
                    'message'   => '',
                    'data'      => $this->user->load_user_overall(),
                ]);               
        } 
        catch (\Throwable $th) 
        {
            DB::rollback();

            return response()
                ->json([
                    'status'    => 0,
                    'message'   => 'Unable to load',
                    'data'      => '',
                ]);
        } 
    }

    public function inactive_user_status(Request $request)
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

                $this->user->soft_delete($data, $where);

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

    public function active_user_status(Request $request)
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

               $this->user->active_user_status($request->id);

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
    
    public function load_one(Request $request)
    {
        $where = 
        [
            'a.status' => 1, 'a.employee_number' => $request->employee_number
        ];
        $data=$this->user->load_one($where);
        return response()
        ->json([
            'status'  => 0,
            'message' => '',
             $data
        ]);
    }



}
