<!-- Azure Maps JS -->
<script<% if IsUserCentrics %> data-usercentrics="Azure Maps"  type="text/plain" <% end_if %>src="https://atlas.microsoft.com/sdk/javascript/mapcontrol/2/atlas.min.js"></script>

<div id="MapContainer{$ID}" style='$Styles'></div>
$Script.RAW
<% if IsUserCentrics %>
    <script data-usercentrics="Azure Maps"  type="text/plain">
        // Check if Azure Maps atlas library is loaded
        function checkAtlasLibrary() {
            if (typeof atlas === 'undefined') {
                console.error('Azure Maps atlas library failed to load');
                var container = document.getElementById('MapContainer{$ID}');
                if (container) {
                    container.innerHTML = '<div style="padding: 20px; text-align: center; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; color: #dc3545;">' +
                                          '<strong>Map Loading Error:</strong><br>' +
                                          'Azure Maps library (atlas.min.js) failed to load.<br>' +
                                          'Please check your internet connection and try refreshing the page.' +
                                          '</div>';
                }
                return false;
            } else {
                console.log('Azure Maps atlas library loaded successfully');
                return true;
            }
        }

        // Check atlas library on interval for 10 seconds
        var atlasCheckInterval;
        var atlasCheckStartTime = Date.now();
        var atlasCheckDuration = 10000; // 10 seconds
        var atlasLoaded = false;

        const mapScript = document.querySelector('.loadAfterUsercentrics');
        
        atlasCheckInterval = setInterval(function() {
            var elapsed = Date.now() - atlasCheckStartTime;
            
            if (elapsed >= atlasCheckDuration) {
                // Stop checking after 10 seconds
                clearInterval(atlasCheckInterval);
                if (!atlasLoaded) {
                    console.error('Atlas library check timed out after 10 seconds');
                    checkAtlasLibrary(); // Show error message
                }
                return;
            }
            
            if (checkAtlasLibrary()) {
                atlasLoaded = true;
                clearInterval(atlasCheckInterval);
                
                // Atlas library is loaded, now execute the map script
                if(mapScript){
                    console.log('Atlas library loaded, executing map script...');
                    mapScript.setAttribute('type', 'text/javascript');
                    eval(mapScript.textContent);
                }
            }
        }, 200); // Check every 200ms
    </script>
<% end_if %>