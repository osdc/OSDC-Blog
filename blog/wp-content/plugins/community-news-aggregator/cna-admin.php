<?php

function community_settings_form()
{
	//nothing really is required to render
}
function community_label_string()
{
	//not even here
}
?>
<link rel='stylesheet' type='text/css' href="<?php echo $plugin_url . '/style.css'?>"/>
<div id='cna-form-container'>
<h2>Community Settings</h2>
<?php
if(isset($_GET['settings-updated']))
{
	if($_GET['settings-updated']=="true") echo '<div class="updated">Community Label updated</div>';
}
?>
<form method='post' action='options.php'>
<?php settings_fields("cna-settings");?>
<?php do_settings_sections('cna-admin-config');?>

<p>Community Label
<input class='cna-input' name='cna-community-label' id='cna-community-label' type='text' size='25' value="<?php echo get_option('cna-community-label');?>"/>
<br/><span class="cna-info" id="cna-label-info">This is also the name of the category that your members should post their blog content in, in order to appear in this Community blog.</span>
</p>
<p><input type="submit" class="button" value="Save Settings"/></p>
</form>
</div>
<?php
//end
?>
