<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

function add_stock_request($items, $location, $date_, $reference, $memo_)
{
	global $SysPrefs, $path_to_root, $Refs;

	begin_transaction();
	$args = func_get_args();
	$args = (object)array_combine(array('items', 'location', 'date_', 'reference', 'memo_'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_INVADJUST);

	$req_id = get_next_trans_no(ST_INVADJUST);

	foreach ($items as $line_item)
	{

		add_stock_request_item($req_id, $line_item->stock_id, $location, $date_, $reference,
			$line_item->quantity, $line_item->standard_cost, $memo_);
	}

	add_comments(ST_INVREQUEST, $req_id, $date_, $memo_);

	$Refs->save(ST_INVREQUEST, $req_id, $reference);
	add_audit_trail(ST_INVREQUEST, $req_id, $date_);

	$args->trans_no = $req_id;
	hook_db_postwrite($args, ST_INVREQUEST);
	commit_transaction();

	return $req_id;
}


//-------------------------------------------------------------------------------------------------------------

function void_stock_adjustment($type_no)
{
	hook_db_prevoid(ST_INVADJUST, $type_no);
	void_gl_trans(ST_INVADJUST, $type_no);
	void_stock_move(ST_INVADJUST, $type_no);
}

//-------------------------------------------------------------------------------------------------------------

function get_stock_request_items($trans_no)
{
	$result = get_stock_moves(ST_INVADJUST, $trans_no);

	if (db_num_rows($result) == 0)
	{
		return null;
	}

	return $result;
}

//--------------------------------------------------------------------------------------------------

function add_stock_request_item($adj_id, $stock_id, $location, $date_, $reference,
	$quantity, $standard_cost, $memo_)
{
	$mb_flag = get_mb_flag($stock_id);

    if (is_service($mb_flag))
    {
    	display_db_error("Cannot do inventory request for Service item : $stock_id", "");
    }

	update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);

    add_stock_move_req(ST_INVREQUEST, $stock_id, $adj_id, $location,
        $date_, $reference, $quantity, $standard_cost);

}
