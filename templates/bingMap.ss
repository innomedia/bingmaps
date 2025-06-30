<!-- Azure Maps JS -->
<script<% if IsUserCentrics %> data-usercentrics="Azure Maps"  type="text/plain" <% end_if %> src="https://atlas.microsoft.com/sdk/javascript/mapcontrol/2/atlas.min.js"></script>

<div id="MapContainer{$ID}" style='$Styles'></div>
$Script.RAW
<% if IsUserCentrics %>
    <script>
        // Function to show cookie consent warning
        function showCookieConsentWarning() {
            var container = document.getElementById('MapContainer{$ID}');
            if (container) {
                container.innerHTML = '<div style="padding: 20px; text-align: center; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; color: #856404;">' +
                                      '<div style="font-size: 18px; margin-bottom: 10px;">🍪</div>' +
                                      '<strong><%t bingMap.COOKIE_CONSENT_REQUIRED "Cookie consent required" %></strong><br>' +
                                      '<%t bingMap.COOKIE_CONSENT_MESSAGE "To display the map, please accept cookies in the cookie settings. The map requires Azure Maps cookies to function properly." %>' +
                                      '</div>';
            }
        }

        // Check UserCentrics consent status
        function checkUserCentricsConsent() {
            if (typeof UC_UI !== 'undefined' && UC_UI.getServicesBaseInfo) {
                var services = UC_UI.getServicesBaseInfo();
                var azureMapsService = services.find(function(service) {
                    return service.name === 'Azure Maps' || service.id === 'Azure Maps';
                });
                
                if (azureMapsService && !azureMapsService.consent.status) {
                    console.log('Azure Maps cookies not accepted');
                    showCookieConsentWarning();
                    return false;
                }
                return true;
            }
            return false;
        }

        // Listen for UserCentrics events
        if (typeof window.addEventListener !== 'undefined') {
            window.addEventListener('UC_UI_INITIALIZED', function() {
                console.log('UserCentrics initialized, checking consent...');
                if (!checkUserCentricsConsent()) {
                    return; // Don't proceed if consent not given
                }
            });

            window.addEventListener('UC_UI_CMP_EVENT', function(event) {
                console.log('UserCentrics CMP event:', event.detail);
                if (event.detail && event.detail.type === 'consent_status_changed') {
                    if (!checkUserCentricsConsent()) {
                        return; // Don't proceed if consent not given
                    }
                }
            });
        }
    </script>
    
    <script data-usercentrics="Azure Maps"  type="text/plain">
        // Check if Azure Maps atlas library is loaded
        function checkAtlasLibrary() {
            if (typeof atlas === 'undefined') {
                return false;
            } else {
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
                    showCookieConsentWarning(); // Show cookie warning instead of library error
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
