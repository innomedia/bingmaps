var map = {};
var popups = [];
var locs = [];

class GeoapifyMap extends React.Component {
    constructor(props) {
        super(props);
        this.mapRef = React.createRef();
        this.loadScript = this.loadScript.bind(this);
        this.MapCallBack = this.MapCallBack.bind(this);
        this.closePopup = this.closePopup.bind(this);
        this.state = {
            map: null,
            locs: [],
            markers: []
        }
    }

    componentDidMount() {
        window.GeoapifyMapCallBack = this.MapCallBack;
        window.closeGeoapifyPopup = this.closePopup;
        this.loadScript();
    }

    closePopup(e) {
        console.log(e);
    }

    loadScript() {
        const existingScript = document.getElementById('geoapifyMap');
        if (!existingScript) {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.js';
            script.id = 'geoapifyMap';
            script.onload = () => {
                // Also load the CSS
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.css';
                document.head.appendChild(link);
                this.MapCallBack();
            };
            document.body.appendChild(script);
        } else {
            this.MapCallBack();
        }
    }

    setMapCenter(position) {
        if (map && map.setCenter) {
            map.setCenter([position.longitude, position.latitude]);
        }
    }

    setMarkers(Markers, mapInstance) {
        if (mapInstance && Markers) {
            // Clear existing markers
            if (this.state.markers.length > 0) {
                this.state.markers.forEach(marker => marker.remove());
            }

            const newMarkers = [];
            Markers.forEach((Marker, index) => {
                if (Marker.coordinates) {
                    const marker = new maplibregl.Marker();
                    
                    // Set custom icon if available
                    if (Marker.icon && Marker.icon !== "") {
                        const el = document.createElement('div');
                        el.className = 'custom-marker';
                        el.style.backgroundImage = `url(${Marker.icon})`;
                        el.style.width = '32px';
                        el.style.height = '32px';
                        el.style.backgroundSize = 'cover';
                        el.style.borderRadius = '50%';
                        el.style.cursor = 'pointer';
                        marker.setElement(el);
                    }
                    
                    marker.setLngLat([Marker.coordinates.longitude, Marker.coordinates.latitude])
                          .addTo(mapInstance);
                    
                    // Add popup if available
                    if (Marker.infobox && Marker.infobox.content) {
                        const popup = new maplibregl.Popup({ offset: 25 })
                            .setHTML(Marker.infobox.content);
                        
                        marker.setPopup(popup);
                    }
                    
                    newMarkers.push(marker);
                    locs.push([Marker.coordinates.longitude, Marker.coordinates.latitude]);
                }
            });

            this.setState({ markers: newMarkers });
        }
    }

    setInfoBoxes(Markers, mapInstance) {
        // InfoBoxes are handled within setMarkers for Geoapify
        // This method is kept for API compatibility
    }

    CenterOnPins() {
        if (map && locs.length > 0) {
            const bounds = new maplibregl.LngLatBounds();
            locs.forEach(loc => bounds.extend(loc));
            map.fitBounds(bounds, { padding: this.props.Data.padding || 50 });
        }
    }

    markerClicked(index) {
        if (this.state.markers[index] && this.state.markers[index].getPopup()) {
            this.state.markers[index].togglePopup();
        }
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        if (this.props.Position.latitude != 0 && this.props.Position.longitude != 0) {
            this.setMapCenter(this.props.Data.position);
        }
    }

    MapCallBack(e) {
        // Get Geoapify style based on map type
        const getGeoapifyStyle = (mapType) => {
            switch (mapType) {
                case 'satellite':
                    return 'satellite';
                case 'light':
                    return 'positron';
                case 'grayscale':
                    return 'toner';
                case 'dark':
                    return 'dark-matter';
                default:
                    return 'osm-bright';
            }
        };

        const style = getGeoapifyStyle(this.props.Data.mapType || 'road');
        const styleUrl = `https://maps.geoapify.com/v1/styles/${style}/style.json?apiKey=${this.props.APIKey}`;

        map = new maplibregl.Map({
            container: this.mapRef.current,
            style: styleUrl,
            center: this.props.Data.position ? [this.props.Data.position.longitude, this.props.Data.position.latitude] : [0, 0],
            zoom: this.props.Data.zoom || 10
        });

        // Wait for the map to be ready
        map.on('load', () => {
            if (this.props.Position.latitude != 0 && this.props.Position.longitude != 0) {
                this.setMapCenter(this.props.Data.position);
            }
            
            if (this.props.Data.markers.length > 0) {
                this.setMarkers(this.props.Data.markers, map);
                this.setInfoBoxes(this.props.Data.markers, map);
            }
            
            if (this.props.CenterOnPins) {
                this.CenterOnPins();
            }
        });

        map.on('error', (e) => {
            console.error('Geoapify map error:', e);
        });
    }

    render() {
        return React.createElement('div', {
            ref: this.mapRef,
            style: {
                width: '100%',
                height: '500px',
                ...this.props.style
            }
        });
    }
}

// Export for use
window.GeoapifyMap = GeoapifyMap;