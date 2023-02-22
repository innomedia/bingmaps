<script <% if $HasLoadOnStartClass %>class='{$GetLoadOnStartClass}' type='text/plain'<% else %>type='text/javascript'<% end_if %> src='https://www.bing.com/api/maps/mapcontrol?callback=GetMap{$ID}&key=$SiteConfig.bingAPIKey' async defer></script>
<div id="MapContainer{$ID}" style='$Styles'></div>
$Script.RAW
 
