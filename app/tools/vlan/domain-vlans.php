<?php

/**
 * Print all vlans
 */

# verify that user is logged in
$User->check_user_session();

# fetch l2 domain
$vlan_domain = $Tools->fetch_object("vlanDomains", "id", $_GET['subnetId']);
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true); }

# get all VLANs and subnet descriptions
$vlans = $Tools->fetch_vlans_and_subnets ($vlan_domain->id);

# get custom VLAN fields
$custom_fields = $Tools->fetch_custom_fields('vlans');

# set hidden fields
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['vlans']) ? $hidden_fields['vlans'] : array();

# size of custom fields
$csize = sizeof($custom_fields) - sizeof($hidden_fields);


# set disabled for non-admins
$disabled = $User->admin==true ? "" : "hidden";


# title
print "<h4>"._('Available VLANs in domain')." $vlan_domain->name</h4>";
print "<hr>";

if(sizeof($vlan_domains)>1) {
print "<div class='btn-group' style='margin-bottom:10px;'>";
print "<a class='btn btn-sm btn-default' href='".create_link("tools", "vlan")."'><i class='fa fa-angle-left'></i> "._('L2 Domains')."</a>";
print "</div>";
}

# no VLANS?
if($vlans===false) {
	$Result->show("info", _("No VLANS configured"), false);
}
else {
	# table
	print "<table class='table vlans table-condensed table-top'>";

	# headers
	print "<thead>";
	print '<tr">' . "\n";
	print ' <th data-field="number" data-sortable="true">'._('Number').'</th>' . "\n";
	print ' <th data-field="name" data-sortable="true">'._('Name').'</th>' . "\n";
	print ' <th data-field="description" data-sortable="true">'._('Description').'</th>' . "\n";
	print ' <th>'._('Belonging subnets').'</th>' . "\n";
	print ' <th>'._('Section').'</th>' . "\n";
	if(sizeof(@$custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "	<th class='hidden-xs hidden-sm hidden-md'>$field[name]</th>";
			}
		}
	}
	print "	<th></th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";
	$m = 0;
	foreach ($vlans as $vlan) {

		// show free vlans - start
		if($User->user->hideFreeRange!=1) {
			if($m==0 && $vlan->number!=1)	{
				print "<tr class='success'>";
				print "<td></td>";
				print "<td colspan='".(4+$csize)."'><btn class='btn btn-xs btn-default editVLAN $disabled' data-action='add' data-number='1'><i class='fa fa-plus'></i></btn> "._('VLAN')." 1 - ".($vlan[0]->number -1)." (".($vlan[0]->number -1)." "._('free').")</td>";
				print "</tr>";
			}
			# show free vlans - before vlan
			if($m>0)	{
				if( (($vlans[$m][0]->number)-($vlans[$m-1][0]->number)-1) > 0 ) {
				print "<tr class='success'>";
				print "<td></td>";
				# only 1?
				if( (($vlans[$m][0]->number)-($vlans[$m-1][0]->number)-1) ==1 ) {
				print "<td colspan='".(4+$csize)."'><btn class='btn btn-xs btn-default editVLAN $disabled' data-action='add' data-number='".($vlan[0]->number -1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan[0]->number -1)." (".(($vlans[$m][0]->number)-($vlans[$m-1][0]->number)-1)." "._('free').")</td>";
				} else {
				print "<td colspan='".(4+$csize)."'><btn class='btn btn-xs btn-default editVLAN $disabled' data-action='add' data-number='".($vlans[$m-1][0]->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlans[$m-1][0]->number+1)." - ".($vlan[0]->number -1)." (".(($vlans[$m][0]->number)-($vlans[$m-1][0]->number)-1)." "._('free').")</td>";
				}
				print "</tr>";
				}
			}
		}

		//save first
		$first = $vlan[0];

		//unset if no permissions
		foreach($vlan as $k=>$v) {
			if ($v->subnetId!=null) {
				$permission = $Subnets->check_permission ($User->user, $v->subnetId);
				if($permission==0) {
					unset($vlan[$k]);
				}
			}
		}

		//if none
		if(sizeof($vlan)==0) {
			$first->subnetId = null;
			$vlan[0] = $first;
		}

		//subnets
		if(sizeof($vlan)>0) {
			foreach($vlan as $k=>$v) {
				//first?
				if($k==0) {
					//set odd / even
					$n = @$n==1 ? 0 : 1;
					$class = $n==0 ? "odd" : "even";
					//start - VLAN details
					print "<tr class='$class change'>";
					print "	<td><a href='".create_link("tools","vlan", $vlan_domain->id, $vlan[0]->vlanId)."'>".$vlan[0]->number."</td>";
					print "	<td>".$vlan[0]->name."</td>";
					print "	<td>".$vlan[0]->description."</td>";
				} else {
					print "<tr class='$class'>";
					print "<td></td>";
					print "<td></td>";
					print "<td></td>";
				}
				//subnet?
				if ($v->subnetId!=null) {
					//section
					$section = $Sections->fetch_section (null, $v->sectionId);
					print " <td><a href='".create_link("subnets",$section->id,$v->subnetId)."'>". $Subnets->transform_to_dotted($v->subnet) ."/$v->mask</a></td>";
					print " <td><a href='".create_link("subnets",$section->id)."'>$section->name</a></td>";
			        //custom fields
			        if(sizeof(@$custom) > 0) {
				   		foreach($custom as $field) {
					   		# hidden
					   		if(!in_array($field['name'], $ffields)) {
								print "<td class='hidden-xs hidden-sm hidden-md'>";
								//booleans
								if($field['type']=="tinyint(1)")	{
									if($vlan[$field['name']] == "0")		{ print _("No"); }
									elseif($vlan[$field['name']] == "1")	{ print _("Yes"); }
								}
								//text
								elseif($field['type']=="text") {
									if(strlen($vlan[$field['name']])>0)		{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $vlan[$field['name']])."'>"; }
									else									{ print ""; }
								}
								else {
									print $vlan[$field['name']];

								}
								print "</td>";
							}
				    	}
				    }
				    print "</tr>";
				}
				// no subnets
				else {
					print "	<td>/</td>";
					print "	<td>/</td>";
					//custom
					for($z=0; $z<$csize; $z++) {
					print "	<td>/</td>";
					}
					print "</tr>";
				}
			}
		}

		# show free vlans - last
		if($User->user->hideFreeRange!=1) {
			if($m==(sizeof($vlans)-1)) {
				if($User->settings->vlanMax > $vlan[0]->number) {
					print "<tr class='success'>";
					print "<td></td>";
					print "<td colspan='".(4+$csize)."'><btn class='btn btn-xs btn-default editVLAN $disabled' data-action='add' data-number='".($vlan[0]->number+1)."'><i class='fa fa-plus'></i></btn> "._('VLAN')." ".($vlan[0]->number+1)." - ".$User->settings->vlanMax." (".(($User->settings->vlanMax)-($vlan[0]->number))." "._('free').")</td>";
					print "</tr>";
				}
			}
		}
		# next index
		$m++;
	}
	print "</tbody>";

	print '</table>';
}
?>
