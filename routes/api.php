<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('login','UserController@login');
Route::patch('user-all','UserController@update_all_user');
Route::patch('user-process','UserController@update_process');
Route::patch('user-approver','UserController@update_approver');
Route::patch('user-type','UserController@update_user_type');
Route::patch('edit-support', 'UserController@update_support');
Route::get('user-one', 'UserController@load_one');

Route::get('user', 'UserController@load_user');
Route::patch('user', 'UserController@update_user_area');
Route::delete('user', 'UserController@inactive_user_status');
Route::patch('user-active', 'UserController@active_user_status');
Route::get('user-overall', 'UserController@load_user_overall'); // use for testing ng active users


Route::get('area-code-all', 'AreasController@load_area_code');
Route::get('area-code-restore', 'AreasController@load_area_code_for_restore');
Route::post('area-code', 'AreasController@add_area_code');
Route::get('area-code', 'AreasController@search_area_code');
Route::patch('area-code', 'AreasController@update_area_code');
Route::delete('area-code', 'AreasController@inactive_area_code');
Route::patch('area-active', 'AreasController@active_area_code');

Route::get('delivery-type-all', 'DeliveryTypeMasterlistController@load_delivery_type');
Route::post('delivery-type', 'DeliveryTypeMasterlistController@add_delivery_type');
Route::get('delivery-type', 'DeliveryTypeMasterlistController@search_delivery_type');
Route::patch('delivery-type', 'DeliveryTypeMasterlistController@update_delivery_type');
Route::delete('delivery-type', 'DeliveryTypeMasterlistController@delete_delivery_type');
Route::get('delivery-type-all_data', 'DeliveryTypeMasterlistController@load_delivery_type_overall');
Route::patch('delivery-type-active', 'DeliveryTypeMasterlistController@active_delivery_type');

Route::post('area-payout', 'AreaPayoutController@add');
Route::get('area-payout', 'AreaPayoutController@get_all');
Route::get('area-payout-one', 'AreaPayoutController@get_one');
Route::patch('area-payout', 'AreaPayoutController@update');
Route::delete('area-payout', 'AreaPayoutController@delete');

Route::post('destination', 'DestinationMasterlistController@add_destination');
Route::get('destination-all', 'DestinationMasterlistController@load_destination');
Route::patch('destination', 'DestinationMasterlistController@update_destination');
Route::get('destination', 'DestinationMasterlistController@search_destination');
Route::delete('destination', 'DestinationMasterlistController@delete_destination');
Route::get('destination-exist', 'DestinationMasterlistController@destination_exist');
Route::get('destination-active', 'DestinationMasterlistController@active_destination');

Route::get('sync', 'SyncingController@click_sync');
Route::get('all-item', 'SyncingController@load_all_item');

Route::get('irregularity', 'IrregularityController@select_barcode');
Route::post('irregularity', 'IrregularityController@add_irregularity_item');
Route::get('control-no', 'IrregularityController@generate_control_no');

Route::patch('irregularity', 'IrregularityController@update_irregularity');
Route::get('load-irregularity', 'IrregularityController@load_irregularity_item');
Route::get('irregularity-load','IrregularityController@load_irregularity');
Route::get('irregularity-list', 'IrregularityController@load_list_irregularity');
Route::delete('irregularity', 'IrregularityController@delete_irregularity');
Route::patch('irregularity-status', 'IrregularityController@update_irregularity_status');

Route::get('palletizing-delivery-type', 'TransactionController@load_delivery_type');
Route::get('load-for-dispatch', 'TransactionController@load_for_dispatch');

Route::get('checking', 'CheckingController@load_checking');
Route::patch('checking', 'CheckingController@update_check');

// Route::get('leadtime-data', 'MonitoringController@load_leadtime');
Route::get('parts-status', 'MonitoringController@load_part_status');
Route::get('master-data', 'MonitoringController@load_master_data');

Route::get('leadtime-data', 'MonitoringController@lead_time_data');
// Route::get('leadtime-data', 'MonitoringController@manipulate_data');
Route::get('leadtime-report', 'MonitoringController@lead_time_report');
Route::get('weekly-report', 'MonitoringController@weekly_normal_report');
Route::get('weekly-horenso-report', 'MonitoringController@weekly_horenso_report');
Route::get('monthly','MonitoringController@monthly_report');
Route::get('yearly','MonitoringController@yearly_report');
Route::get('overall-graph-report', 'MonitoringController@over_all_report');


Route::get('palletizing', 'PalletizingController@load_barcode');
Route::get('load-ongoing-palletizing', 'PalletizingController@load_ongoing_palletizing');
Route::delete('remove-palletizing-item', 'PalletizingController@remove_palletizing_item');
Route::post('palletizing', 'PalletizingController@new_save_palletizing');
Route::get('palletizing-get-items', 'PalletizingController@load_palletizing_items');
Route::post('new-palletizing-item', 'PalletizingController@add_palletizing');
Route::post('finish-palletizing', 'PalletizingController@finish_palletizing');

Route::patch('print-dr-making', 'DrMakingController@update_dr_making');
Route::get('load-dr-making', 'DrMakingCOntroller@load_dr_making');

Route::patch('for-delivery-update', 'ForDeliveryController@update_for_delivery');
Route::get('for-delivery-update', 'ForDeliveryController@load_delivery_update');
Route::get('banner-details', 'ForDeliveryController@load_for_banner');


Route::get('report-delivery-status', 'MonitoringController@load_report_delivery_status');
Route::get('report-delivery-quantity', 'MonitoringController@load_report_delivery_quantity');
Route::get('report-issuance-payee', 'MonitoringController@load_issuance_payee');
Route::get('load-process', 'MonitoringController@load_process');
Route::get('load-delivery-data', 'MonitoringController@load_delivery_data');

Route::get('lead-time-report', 'ReportController@lead_time_report');
// Route::get('overall-graph-report', 'ReportController@overall_report');
Route::get('load-pallet-report', 'ReportController@load_pallet_report');

Route::post('add-remarks', 'RemarkController@add_remarks');
Route::patch('update-remarks', 'RemarkController@update_remarks');
Route::delete('remove-remarks', 'RemarkController@remove_remarks');
Route::get('load-remarks', 'RemarkController@load_remarks');

Route::get('receiving', 'ReceiveController@receive');
Route::patch('receiving', 'ReceiveController@update_receive');
Route::patch('update-receive', 'ReceiveController@update_receive_special');
Route::get('load-for-receive', 'ReceiveController@load_for_receive');

Route::get('load-dr-control', 'DRMakingController@load_dr_no');
Route::get('load-delivery-receipts', 'DRMakingController@load_dr_making_details');
Route::get('print-delivery-receipts', 'DRMakingController@load_reprint_data');

Route::get('load-control-no', 'IrregularityController@load_control_no');
Route::get('print-irregularity', 'IrregularityController@load_reprint');

Route::post('save-forecast', 'ForecastController@save_forecast');
Route::get('load-forecast', 'ForecastController@load_data');
Route::patch('edit-receiver', 'UserController@update_receive');

Route::get('load-picker', 'PickingController@load_data');
Route::post('save-picker', 'PickingController@save_picker');

Route::get('load-inhouse', 'ForDeliveryController@load_inhouse_delivery');
Route::get('search-data', 'TrackingController@load_track_items');
Route::get('week-status', 'TrackingController@load_current_week_status');

Route::get('delivery_leadtime', 'ReportController@delivery_leadtime');

Route::post('save-email', 'EmailManagementController@save_email');
Route::get('load-email', 'EmailManagementController@load_email');
Route::delete('delete-email', 'EmailManagementController@inactive_email');
Route::delete('restore-email', 'EmailManagementController@active_email');
Route::get('realtime-data', 'UserController@realtime_data');

Route::get('load-timeout', 'TimeoutController@load_data');
Route::post('save-timeout', 'TimeoutController@save_timeout');


