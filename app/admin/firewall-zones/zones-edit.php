<?php

/**
 *	firewall zone fwzones-edit.php
 *	add, edit and delete firewall zones
 ******************************************/

# functions
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize classes
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Admin 	  = new Admin($Database);
$Subnets  = new Subnets ($Database);
$Result   = new Result ();
$Zones    = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();

# validate action
$Admin->validate_action ($_POST['action']);

# validate $_POST['id'] values
if (!preg_match('/^[0-9]+$/i', $_POST['id'])) 												 { $Result->show("danger", _("Invalid ID. Do not manipulate the POST values!"), true); }
# validate $_POST['action'] values
if ($_POST['action'] != 'add' && $_POST['action'] != 'edit' && $_POST['action'] != 'delete') { $Result->show("danger", _("Invalid action. Do not manipulate the POST values!"), true); }


# fetch module settings
$firewallZoneSettings = json_decode($User->settings->firewallZoneSettings,true);

# fetch old zone
if ($_POST['action'] != 'add') {
	$firewallZone = $Zones->get_zone($_POST['id']);
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";
?>

<script type="text/javascript">
$(document).ready(function() {
	/* bootstrap switch */
	var switch_options = {
	    onColor: 'default',
	    offColor: 'default',
	    size: "mini"
	};
	$(".input-switch").bootstrapSwitch(switch_options);
});
</script>

<!-- header  -->
<div class="pHeader"><?php print _('Add a firewall zone'); ?></div>
<!-- content -->
<div class="pContent">
<!-- form -->
<form id="zoneEdit">
<!-- table -->
<table class="table table-noborder table-condensed">
	<!-- zone name -->
	<tr>
		<td style="width:150px;">
			<?php print _('Zone name'); ?>
		</td>

		<?php
		# transmit the action and firewall zone id
		print '<input type="hidden" name="action" value="'.$_POST['action'].'">';
		print '<input type="hidden" name="id" value="'.$firewallZone->id.'">';
		# possible zoneGenerator values:
		#		0 == autogenerated decimal name
		#		1 == autogenerated hex name
		#		2 == free text name

		if ($_POST['action'] == 'add') {
			# check if we have to autogenerate a zone name or if we have to display a text box
			if ($firewallZoneSettings['zoneGenerator'] == 2) {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name (Only alphanumeric and special characters like .-_ and space.)').'" value="'.$firewallZone->zone.'" '.$readonly.'></td>';
			} else {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('The zone name will be automatically generated').'" value="'.$firewallZone->zone.'" '.$readonly.' disabled></td>';
			}
		} else {
			if ($firewallZone->generator == 1) {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name').'" readonly value="'.$firewallZone->zone.'"></td>';
			} elseif ($firewallZone->generator != 2) {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name').'" readonly value="'.$firewallZone->zone.'"></td>';
			} else {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name (Only alphanumeric and special characters like .-_ and space.)').'" value="'.$firewallZone->zone.'" '.$readonly.'></td>';
			}
		}
		?>
		<input type="hidden" name="generator" value="<?php print $firewallZoneSettings['zoneGenerator']; ?>">

	</tr>
	<tr>
		<!-- zone indicator -->
		<td rowspan="2">
			<?php print _('Indicator'); ?>
		</td>
		<td>
			<div class="radio" style="margin-top:5px;margin-bottom:2px;">
				<label>
					<input type="radio" name="indicator" value="0" <?php (($firewallZone->indicator == false) ? print 'checked' : print ''); ?> ><?php print '<span class="fa fa-home"  title="'._('Own zone').'"></span> '._('Own Zone'); ?>
				</label>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<div class="radio" style="margin-top:2px;margin-bottom:2px;">
				<label>
					<input type="radio" name="indicator" value="1" <?php (($firewallZone->indicator == true) ? print 'checked' : print ''); ?> ><?php print '<span class="fa fa-group"  title="'._('Customer zone').'"></span> '._('Customer Zone'); ?>
				</label>
			</div>
		</td>
	</tr>
	<?php if($firewallZone->generator != 2 && $firewallZoneSettings['zoneGenerator'] != 2) { ?>
		<tr>
			<td>
				<?php print _('Padding'); ?>
			</td>
			<td>
				<input type="checkbox" class="input-switch" name="padding" <?php if($_POST['action'] == 'edit' && $firewallZone->padding == 1){ print 'checked';} elseif($_POST['action'] == 'edit' && $firewallZone->padding == 0) {} elseif ($firewallZoneSettings['padding'] == 'on'){print 'checked';}?>>
			</td>
		</tr>
	<?php } ?>
	<tr>
		<!-- description -->
		<td>
			<?php print _('Description'); ?>
		</td>
		<td>
			<input type="text" class="form-control input-sm" name="description" placeholder="<?php print _('Zone description'); ?>" value="<?php print $firewallZone->description; ?>">
		</td>
	</tr>
</table>

<!-- network information -->
<span class="btn btn-sm btn-default btn-success editNetwork" style="margin-bottom:10px;margin-top: 25px;" data-action="add" data-zoneId="<?php print $firewallZone->id; ?>"><i style="padding-right:5px;" class="fa fa-plus"></i><?php print _('Add a network to the Zone'); ?></span>

<div class="zoneNetwork">
<table class="table table-noborder table-condensed" style="padding-bottom:20px;">
<?php
if ($firewallZone->network) {
	print "<tr><td colspan='2'><hr></tr>";
	$rowspan = count($firewallZone->network);
	$i = 1;
	foreach ($firewallZone->network as $network) {
		print '<tr>';
		if ($i === 1) {
			print '<td rowspan="'.$rowspan.'" style="width:150px;vertical-align:top">Networks</td>';
		}
		print '<td>';
		print '<a class="btn btn-xs btn-danger editNetwork" style="margin-right:5px;" alt="'._('Delete Network').'" title="'._('Delete Network').'" data-action="delete" data-zoneId="'.$firewallZone->id.'" data-subnetId="'.$network->subnetId.'">';
		print '<span><i class="fa fa-close"></i></span>';
		print "</a>";

		if ($network->subnetIsFolder == 1) {
			print 'Folder: '.$network->subnetDescription.'</td>';
		} else {
			# display network information with or without description
			if ($network->subnetDescription) 	{	print $Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.' ('.$network->subnetDescription.')</td>';	}
			else 								{	print $Subnets->transform_to_dotted($network->subnet).'/'.$network->subnetMask.'</td>';	}
		}
		print '</tr>';
		$i++;
	}
}
?>
</table>
</div>


</form>

<?php
#print delete warning
if($_POST['action'] == "delete"){
	$Result->show("warning", "<strong>"._('Warning').":</strong> "._("Removing this firewall zone will also remove all referenced mappings!"), false);
}
?>
</div>
<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editZoneSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="zones-edit-result"></div>
</div>