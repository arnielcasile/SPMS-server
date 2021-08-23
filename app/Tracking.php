<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Tracking extends Model
{
    public function load_track_items($ticket_no)
    {
        return DB::connection('pgsql')->select("
            SELECT 
            
            a.warehouse_class, a.delivery_form, a.item_no, a.item_rev, a.delivery_qty, a.stock_address, a.manufacturing_no, a.delivery_inst_date, a.destination_code, a.item_name, a.product_no, a.normal_ticket_no,
            a.ticket_issue_date, a.ticket_issue_time, a.storage_location, a.delivery_due_date, a.order_download_no, a.normal_process_masterlist_id, a.master_data_normal_created_at, a.payee_name, a.destination, a.checking_normal_user_id, a.checking_normal_created_at,
            a.palletizing_normal_users_id, a.palletizing_normal_created_at, a.palletizing_normal_dr_control, a.palletizing_normal_delivery_type_id, a.palletizing_normal_delivery_no, a.palletizing_normal_process,
            a.dr_makings_normal_users_id, a.dr_makings_normal_created_at, a.dr_makings_normal_pcase, a.dr_makings_normal_box, a.dr_makings_normal_bag, a.dr_makings_normal_pallet,
            a.delivery_normal_users_id, a.delivery_normal_created_at, a.delivery_normal_recipient, a.delivery_normal_updated_at,
            
            b.irregularity_ticket_no, b.irregularity_process_masterlists_id, b.irregularity_user_id, b.irregularity_created_at,
            b.checking_irregularity_user_id, b.checking_irregularity_created_at, b.checking_irregularity_process, 
            b.palletizing_irregularity_users_id, b.palletizing_irregularity_created_at, b.palletizing_irregularity_process,
            b.dr_makings_irregularity_users_id, b.dr_makings_irregularity_created_at, b.dr_makings_irregularity_dr_control,
            b.delivery_irregularity_users_id, b.delivery_irregularity_created_at, b.delivery_irregularity_recipient, b.delivery_irregularity_updated_at, b.delivery_irregularity_dr_control

            FROM
            (
            SELECT a.warehouse_class, a.delivery_form, a.item_no, a.item_rev, a.delivery_qty, a.stock_address, a.manufacturing_no, a.delivery_inst_date, a.destination_code, a.item_name, a.product_no, a.ticket_no as normal_ticket_no,
            a.ticket_issue_date, a.ticket_issue_time, a.storage_location, a.delivery_due_date, a.order_download_no, k.process as normal_process_masterlist_id, a.created_at as master_data_normal_created_at,

            b.payee_name, b.destination,
            
            CONCAT(g.first_name, ' ', g.last_name) as checking_normal_user_id, c.created_at as checking_normal_created_at,
            
            CONCAT(h.first_name, ' ', h.last_name) as palletizing_normal_users_id, d.created_at as palletizing_normal_created_at, d.dr_control as palletizing_normal_dr_control,
            d.delivery_type_id as palletizing_normal_delivery_type_id, d.delivery_no as palletizing_normal_delivery_no, d.process as palletizing_normal_process,
            
            CONCAT(i.first_name, ' ', i.last_name) as dr_makings_normal_users_id, e.created_at as dr_makings_normal_created_at,
            e.pcase as dr_makings_normal_pcase, e.box as dr_makings_normal_box, e.bag as dr_makings_normal_bag, e.pallet as dr_makings_normal_pallet,
            
            CONCAT(j.first_name, ' ', j.last_name) as delivery_normal_users_id, f.created_at as delivery_normal_created_at, f.recipient as delivery_normal_recipient, f.updated_at as delivery_normal_updated_at
            
            FROM master_data as a
            LEFT JOIN destination_masterlist as b ON a.destination_code = b. payee_cd
            LEFT JOIN checkings as c ON a.ticket_no = c.ticket_no AND c.process = 'NORMAL'
            LEFT JOIN palletizings as d ON c.ticket_no = d.ticket_no AND c.process = d.process
            LEFT JOIN dr_makings as e ON d.dr_control = e.dr_control
            LEFT JOIN delivery as f ON e.dr_control = f.dr_control
                LEFT JOIN users as g ON c.users_id = g.id
                LEFT JOIN users as h ON d.users_id = h.id
                LEFT JOIN users as i ON e.users_id = i.id
                LEFT JOIN users as j ON f.users_id = j.id
                LEFT JOIN process_masterlists as k ON a.process_masterlist_id = k.id
            ) a

            LEFT JOIN 

            (
            SELECT 
            a.ticket_no as irregularity_ticket_no, l.process as irregularity_process_masterlists_id, 
            CONCAT(g.first_name, ' ', g.last_name) as irregularity_user_id, a.created_at as irregularity_created_at,
            
            CONCAT(h.first_name, ' ', h.last_name) as checking_irregularity_user_id, c.created_at as checking_irregularity_created_at, c.process as checking_irregularity_process,
            
            CONCAT(i.first_name, ' ', i.last_name) as palletizing_irregularity_users_id, d.created_at as palletizing_irregularity_created_at, d.process as palletizing_irregularity_process,
            
            CONCAT(j.first_name, ' ', j.last_name) as dr_makings_irregularity_users_id, e.created_at as dr_makings_irregularity_created_at, e.dr_control as dr_makings_irregularity_dr_control,
            
            CONCAT(k.first_name, ' ', k.last_name) as delivery_irregularity_users_id, f.created_at as delivery_irregularity_created_at, f.recipient as delivery_irregularity_recipient,
            f.updated_at as delivery_irregularity_updated_at, f.dr_control as delivery_irregularity_dr_control
                
            FROM irregularity as a
            LEFT JOIN checkings as c ON a.ticket_no = c.ticket_no AND c.process = 'COMPLETION'
            LEFT JOIN palletizings as d ON c.ticket_no = d.ticket_no AND c.process = d.process
            LEFT JOIN dr_makings as e ON d.dr_control = e.dr_control
            LEFT JOIN delivery as f ON e.dr_control = f.dr_control
                LEFT JOIN users as g ON a.users_id = g.id
                LEFT JOIN users as h ON c.users_id = h.id
                LEFT JOIN users as i ON d.users_id = i.id
                LEFT JOIN users as j ON e.users_id = j.id
                LEFT JOIN users as k ON f.users_id = k.id
                LEFT JOIN process_masterlists as l ON a.process_masterlists_id = l.id
            ) b

            ON a.normal_ticket_no = b.irregularity_ticket_no
            WHERE a.normal_ticket_no = '$ticket_no'
            ");
    }

    public function load_current_week_status($data)
    {
        $arr['delivered'] = DB::table('master_data as a')
                ->select('a.process_masterlist_id', 'a.ticket_issue_date')
                ->whereBetween('a.ticket_issue_date', [$data['date_from'], $data['date_to']])
                ->where('a.process_masterlist_id', 6)
                ->get();

        $arr['work_in_progress'] = DB::table('master_data as a')
                ->select('a.process_masterlist_id', 'a.ticket_issue_date')
                ->whereBetween('a.ticket_issue_date', [$data['date_from'], $data['date_to']])
                ->whereIn('a.process_masterlist_id', [1,2,3,4,5])
                ->get();

        return $arr;
    }

    public function array_process_id($data)
    {
        return DB::table('master_data as a')
                ->select('a.process_masterlist_id', 'a.ticket_issue_date')
                ->where('a.warehouse_class',$data['area_code'])
                ->whereBetween('a.ticket_issue_date', [$data['date_from'], $data['date_to']])
                ->get();
    }
}
