<!-- Azure Maps JS -->
<script<% if IsUserCentrics %> data-usercentrics="Azure Maps"  type="text/plain" <% end_if %> src="https://atlas.microsoft.com/sdk/javascript/mapcontrol/2/atlas.min.js"></script>

<div id="MapContainer{$ID}" style='$Styles'></div>
$Script.RAW
<% if IsUserCentrics %>
    <script data-usercentrics="Azure Maps"  type="text/plain">
        const mapScript = document.querySelector('.loadAfterUsercentrics');
        if(mapScript){
            setTimeout(() => {
                mapScript.setAttribute('type', 'text/javascript');
                eval(mapScript.textContent);
            }, 500)    
        }
    </script>
<% end_if %>