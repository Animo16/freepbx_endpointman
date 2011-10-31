<?php if(!defined('IN_RAINTPL')){exit('Hacker attempt');}?><?php
	if( isset($var["show_error_box"]) ){
?>
    <?php
		$tpl = new RainTPL( RainTPL::$tpl_dir . dirname("message_box"));
		$tpl->assign( $var );
				$tpl->draw(basename("message_box"));
?>
<?php
	}
?>
<form action='config.php?type=tool&amp;display=epm_advanced&amp;subpage=settings' method='POST'>
<table width='90%' align='center'>
<tr>
<td width='50%' align='right'><?php echo _("IP address of phone server")?>:</td>
<td width='50%' align='left'><input type='text' id='srvip' name='srvip' value='<?php echo $var["srvip"];?>'><a href='#' onclick="document.getElementById('srvip').value = '<?php echo $var["ip"];?>'; "><?php echo _("Determine for me")?></a></td>
</tr>
<tr>
  <td align='right'><?php echo _("Configuration Type")?></td>
  <td align='left'>
      <select name="cfg_type" id="cfg_type">
            <option value="file" <?php
	if( isset($var["type_file"]) ){
?>selected='selected'<?php
	}
?>>File (TFTP/FTP)</option>
            <option value="http" <?php
	if( isset($var["type_http"]) ){
?>selected='selected'<?php
	}
?>>Web (HTTP)</option>
        </select>
  </td>
</tr>
<tr>
  <td align='right'><?php echo _("Global Final Config & Firmware Directory")?></td>
  <td align='left'><label>
    <input type="text" name="config_loc" value="<?php echo $var["config_location"];?>">
  </label></td>
</tr>
<tr>
  <td align='right'><br/></td>
  <td align='left'></td>
</tr>
<tr>
<td width='50%' align='right'><?php echo _("Time Zone")?> (<?php echo _('like')?> USA-5)</td>
<td width='50%' align='left'><select name="tz" id="tz">
	<?php
	if( isset( $var["list_tz"] ) && is_array( $var["list_tz"] ) ){
		$counter1 = 0;
		foreach( $var["list_tz"] as $key1 => $value1 ){ 
?>
	<option value="<?php echo $value1["value"];?>" <?php
		if( $value1["selected"] == 1 ){
?>selected='selected'<?php
		}
?>><?php echo $value1["text"];?></option>
	<?php
			$counter1++;
		}
	}
?>
</select>
</td>
</tr>
<tr>
<td width='50%' align='right'><?php echo _("Time Server (NTP Server)")?></td>
  <td align='left'><label>
    <input type="text" name="ntp_server" value="<?php echo $var["ntp_server"];?>">
  </label></td>
</tr>
<tr>
  <td align='right'><br/></td>
  <td align='left'></td>
</tr>
<tr>
  <td align='right'>NMAP <?php echo _("executable path")?>:</td>
  <td align='left'><label>
    <input type="text" name="nmap_loc" value="<?php echo $var["nmap_location"];?>">
  </label></td>
</tr>
<tr>
  <td align='right'>ARP <?php echo _("executable path")?>:</td>
  <td align='left'><label>
    <input type="text" name="arp_loc" value="<?php echo $var["arp_location"];?>">
  </label></td>
</tr>
<tr>
  <td align='right'>Asterisk <?php echo _("executable path")?>:</td>
  <td align='left'><label>
    <input type="text" name="asterisk_loc" value="<?php echo $var["asterisk_location"];?>">
  </label></td>
</tr>
<tr>
  <td align='right'><br/></td>
  <td align='left'></td>
</tr>
<tr>
  <td align='right'>Package Server:</td>
  <td align='left'><label>
    <input type="text" name="package_server" value="<?php echo $var["package_server"];?>" size="50">
  </label></td>
</tr>
<tr>
  <td align='right'><br/></td>
  <td align='left'></td>
</tr>
<tr>
  <td align='right'><?php echo _("Enable FreePBX ARI Module")?> (<a href="http://projects.colsolgrp.net/documents/29" target="_blank">What?</a>)</td>
  <td align='left'><label>
    <input type=checkbox name="enable_ari" <?php echo $var["ari_selected"];?>>
  </label></td>
</tr>
<tr>
  <td align='right'><?php echo _("Enable Debug Mode")?></td>
  <td align='left'><label>
    <input type=checkbox name="enable_debug" <?php echo $var["debug_selected"];?>>
  </label></td>
</tr>
<tr>
  <td align='right'><?php echo _("Disable Tooltips")?></td>
  <td align='left'><label>
    <input type=checkbox name="disable_help" <?php echo $var["help_selected"];?>>
  </label></td>
</tr>
<tr>
  <td align='right'><?php echo _("Allow Duplicate Extensions")?></td>
  <td align='left'><label>
    <input type=checkbox name="allow_dupext" <?php echo $var["dupext_selected"];?>>
  </label></td>
</tr>
<tr>
  <td align='right'><?php echo _("Allow Saving to Hard-Drive Configuration Files")?></td>
  <td align='left'><label>
    <input type=checkbox name="allow_hdfiles" <?php echo $var["hdfiles_selected"];?>>
  </label></td>
</tr>
<tr>
<td colspan='2' align='center'><input type='Submit' name='button_update_globals' value='<?php echo _('Update Globals')?>'></td>
</tr>
</table>
</form>
