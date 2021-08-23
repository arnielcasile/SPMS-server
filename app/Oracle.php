<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \DB;

class Oracle extends Model
{
    public function get_oracle_data($from, $to, $area_code)
    {
       return DB::connection('oracle')->select("SELECT WH_CLASS, DELIV_FORM, ITEMNO, ITEMREV, DELIV_QTY, STOCKADDRESS, MANUF_NO, DELIV_INST_DATE, DEST_CODE, ITEMNAME, PRODUCTNO, TICKETNO, TIKETISSUEDATE, TIKETISSUETIME, STORAGELOCATION, DELIVERYDUEDATE, ORDERDOWNLOADNO FROM VW_HD180 WHERE TIKETISSUEDATE BETWEEN '$from' AND '$to'
        AND WH_CLASS = '$area_code' ");
    }
}
