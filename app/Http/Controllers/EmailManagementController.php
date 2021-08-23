<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\EmailManagement;
use DB;

class EmailManagementController extends Controller
{
    public function save_email(EmailManagement $email)
    {
        $id_number = request()->input('id_number');

        $data_mom = $email->get_data_mom($id_number);
        $data_users = $email->get_data_users($id_number);

        if ($id_number === null) 
        {
            return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'Please Input ID Number',
                        'data'      => '',
                    ]);
        }

        if (count($data_users) === 0)
        {
            if (count($data_mom) === 0)
            {
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => 'ID Number not Exist',
                        'data'      => '',
                    ]);
            }

            $result_data = 
            [
                'id_number'     => $data_mom[0]->employeeno,
                'fullname'     =>  $data_mom[0]->fname . ' ' . $data_mom[0]->lname,
                'email'         => $data_mom[0]->email,
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            try 
            {
                // DB::beginTransaction();
                $data_insert = $email->insert_email_user($result_data);
                // DB::commit();
                return response()
                    ->json([
                        'status'    => 1,
                        'message'   => 'Successfully Insert',
                        'data'      => $result_data
                    ]);            
            } 
            catch (\Throwable $th) 
            {
                // DB::rollback();
                return response()
                    ->json([
                        'status'    => 0,
                        'message'   => $th,
                        'data'      => '',
                    ]);
            }
        }

        return response()
                ->json([
                    'status'    => 0,
                    'message'   => 'ID Number Already Exist',
                    'data'      => '',
                ]);
    }

    public function load_email(EmailManagement $email)
    {
        if (count($email->get_all_data()) === 0)
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => 'No Data',
                    'data'      => [],
                ]);
        }
        
        return response()
                ->json([
                    'status'    => 1,
                    'message'   => 'Data Load',
                    'data'      => $email->get_all_data(),
                ]);
    }


    public function inactive_email(EmailManagement $email)
    {
        $id = request()->input('id');
        try 
        {
            $data = $email->soft_delete($id);

            return response()
                ->json([
                    'status'    => 1,
                    'message'   => 'Successfully deactivated',
                    'data'      => $data
                ]);            
        } 
        catch (\Throwable $th) 
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => 'Unable to deactivate',
                    'data'      => '',
                ]);
        }     
    }     

    public function active_email(EmailManagement $email)
    {
        $id = request()->input('id');

        try 
        {
           $data = $email->update_status($id);

            return response()
                ->json([
                    'status'    => 1,
                    'message'   => 'Successfully deactivated',
                    'data'      => $data
                ]);            
        } 
        catch (\Throwable $th) 
        {
            return response()
                ->json([
                    'status'    => 0,
                    'message'   => $th,
                    'data'      => '',
                ]);
        }     
    }   
}
