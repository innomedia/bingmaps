<div id="MapContainer$ID" style="$Styles"></div>
$Script

<style>
.custom-marker {
    background-size: cover;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.maplibregl-popup {
    max-width: 300px;
}

.maplibregl-popup-content {
    padding: 10px;
    border-radius: 8px;
}

#MapContainer$ID {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Error message styling */
.map-error {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    color: #6c757d;
    font-family: Arial, sans-serif;
}

.map-error::before {
    content: "⚠️";
    margin-right: 8px;
    font-size: 18px;
}
</style>

<script>
function showMapError(message) {
    const container = document.getElementById('MapContainer$ID');
    if (container) {
        container.innerHTML = '<div class="map-error">' + message + '</div>';
        container.style.display = 'flex';
        container.style.alignItems = 'center';
        container.style.justifyContent = 'center';
    }
}

// Initialize locs array for marker positioning
var locs = [];
</script>