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
$page_security = 'SA_INVENTORYLOCATION';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");

	$help_context = "Add The New Boxes";

page(_($help_context));

simple_page_mode(true);


if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{
	//initialise no input errors assumed initially before we test
	$input_error = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
     if (!get_post('quantity') || get_post('quantity') < 1){
         $input_error = 1;
         display_error( _("Quantity field is required"));
         set_focus('quantity');
     }

     if (!get_post('stock_id_flower')){
         $input_error = 1;
         display_error( _("Flower Item ID cannot be empty."));
         set_focus('stock_id_flower');
     }


	if ($input_error != 1) 
	{
        if ($selected_id != -1) {
         //updated
            update_item_box($selected_id,$_POST['stock_id'], $_POST['stock_id_flower'], remove_commas($_POST['quantity']), $_POST['stem_size']);
            display_notification(_('New Box has been updated'));
        }else {
            if (!empty(validate_boxes(get_post('stock_id'),get_post('stock_id_flower')))){
                $input_error = 1;
                display_error( _("Duplicate Box Item ID is not allowed"));
                set_focus('stock_id');
            }
            /*selected_id is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new Location form */
            add_item_box($_POST['stock_id'], $_POST['stock_id_flower'], remove_commas($_POST['quantity']), $_POST['stem_size']);
            display_notification(_('New Box has been added'));
        }
		$Mode = 'RESET';
	}
}

if ($Mode == 'RESET')
{
    $selected_id = -1;
    unset($_POST);
}

if ($Mode == 'Delete')
{

    if (can_delete($selected_id))
    {
        delete_item_box($selected_id);
        display_notification(_('Selected BOX has been deleted'));
    } //end if Delete Box
    $Mode = 'RESET';
}

function remove_commas($num){
return str_replace( ',', '', $num);
}
function can_delete($selected_id)
{
	if (!empty(id_in_foreign_table($selected_id)))
	{
	 display_error(_("Cannot delete this Box because it is used by some related records in other tables."));
	 return false;
	}
	return true;
}
$result = get_item_boxes();

start_form();
start_table(TABLESTYLE);
$th = array(_("ID"),_("Box Item ID"), _("Stem Size"), _("Qty"),"","");
table_header($th);
$k = 0; //row colour counter
$l = 0; //row colour counter
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);
	label_cell(++$l);
	label_cell($myrow["box_item_id"]);
	label_cell($myrow["stem_size"]);
	label_cell($myrow["quantity"]);

    edit_button_cell("Edit".$myrow["id"], _("Edit"));
    delete_button_cell("Delete".$myrow["id"], _("Delete"));
	end_row();
}
	//END WHILE LIST LOOP
end_table();

echo '<br><br>';

start_table(TABLESTYLE2);

if ($selected_id != -1) {

    //editing an existing Location

    if ($Mode == 'Edit') {
        $myrow = get_box_type($selected_id);
        $_POST['stock_id'] = $myrow['box_item_id'];
        $_POST['category_id'] = get_item($myrow['box_item_id'])['category_id'];
        $_POST['category_id_flower'] = get_item($myrow['flower_item_id'])['category_id'];
        $_POST['stock_id_flower'] = $myrow['flower_item_id'];
        $_POST['quantity'] = $myrow['quantity'];
    }
    hidden("selected_id", $selected_id);
}

stock_categories_list_box_row(_("Select Box Category:"), 'category_id', null, false, true);
// all_boxes(_("Select Box:"), 'id', null, false, true);
stock_boxes_list_row(_("Select Box Item:"), 'stock_id', null, false, true);

stock_categories_list_row_(_("Select Flower Category:"), 'category_id_flower', null, false, true);
stock_boxes_list_row_(_("Select Flower Item:"), 'stock_id_flower', null, false, true);
if (isset($_POST['stock_id_flower'])){
   $item = get_item($_POST['stock_id_flower']);
   $size = get_size($item['size_id']);
}
label_row(_("Stem Size: "),$size['name']);
qty_row(_('Qty: '),'quantity');
hidden('stem_size',$size['name']);

end_table(1);
submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();