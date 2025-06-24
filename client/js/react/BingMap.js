var map = {};
var popups = [];
var locs = [];
class AzureMap extends React.Component{
    constructor(props){
        super(props);
        this.mapRef = React.createRef();
        this.loadScript = this.loadScript.bind(this);
        this.MapCallBack = this.MapCallBack.bind(this);
        this.closePopup = this.closePopup.bind(this);
        this.state = {
            map: null,
            locs: []
        }
    }
    componentDidMount()
    {
        window.MapCallBack = this.MapCallBack;
        window.closePopup = this.closePopup;
        this.loadScript();
    }
    closePopup(e)
    {
        console.log(e);
    }
    loadScript()
    {
        const existingScript = document.getElementById('azureMap');
        if (!existingScript) {
            const script = document.createElement('script');
            script.src = 'https://atlas.microsoft.com/sdk/javascript/mapcontrol/3/atlas.min.js';
            script.id = 'azureMap';
            script.onload = () => {
                // Also load the CSS
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://atlas.microsoft.com/sdk/javascript/mapcontrol/3/atlas.min.css';
                document.head.appendChild(link);
                this.MapCallBack();
            };
            document.body.appendChild(script);
        } else {
            this.MapCallBack();
        }
    }
    setMapCenter(position)
    {
        map.setCamera({
            center: [position.longitude, position.latitude]
        });
    }
    setMarkers(Markers,map)
    {
        locs = [];
        //clear markers before adding them again
        if(map && Markers)
        {
            map.markers.clear();
        }
        //Add Markers
        Markers.forEach((Marker,index) => {
            let position = [Marker.coordinates.longitude, Marker.coordinates.latitude];
            locs.push(position);
            let marker = new atlas.HtmlMarker({
                position: position,
                htmlContent: Marker.icon ? `<img src="${Marker.icon}" style="width:32px;height:32px;"/>` : undefined
            });
            map.events.add('click', marker, (e) => {
                this.markerClicked(index);
            });
            map.markers.add(marker);
        });
    }
    setInfoBoxes(Markers,map)
    {
        if(map && Markers)
        {
            map.popups.clear();
        }
        Markers.forEach((Marker,index) => {
            if("infobox" in Marker && Marker.infobox != undefined)
            {
                let position = [Marker.infobox.coordinates.longitude, Marker.infobox.coordinates.latitude];
                let content = '<div style="padding:10px">';
                if(Marker.infobox.title) {
                    content += `<h3>${Marker.infobox.title}</h3>`;
                }
                if(Marker.infobox.Description) {
                    content += `<p>${Marker.infobox.Description}</p>`;
                }
                if(Marker.infobox.htmlContent) {
                    content += Marker.infobox.htmlContent;
                }
                content += '</div>';
                
                var popup = new atlas.Popup({
                    content: content,
                    position: position,
                    isVisible: Marker.infobox.initialVisibility
                });
                map.popups.add(popup);
                popups.push(popup);
            }
        });
    }
    CenterOnPins()
    {
        map.setCamera({
            bounds: atlas.data.BoundingBox.fromPositions(locs),
            padding: this.props.Data.padding
        });
    }
    markerClicked(index)
    {
        if(popups[index] != undefined)
        {
            popups[index].open(map);
        }
    }
    componentDidUpdate(prevProps,prevState,snapsho)
    {
        if(this.props.Position.latitude != 0 && this.props.Position.latitude != 0)
        {
            this.setMapCenter(this.props.Data.position);
        }
    }
    MapCallBack(e)
    {
        //TODO do away with complete initialization of map and do it step by step with functions etc that only trigger if they should be triggerd
        map = new atlas.Map(this.mapRef.current, {
            center: this.props.Data.position ? [this.props.Data.position.longitude, this.props.Data.position.latitude] : [0, 0],
            zoom: this.props.Data.zoom || 10,
            authOptions: {
                authType: 'subscriptionKey',
                subscriptionKey: this.props.APIKey
            }
        });
        
        // Wait for the map to be ready
        map.events.add('ready', () => {
            if(this.props.Position.latitude != 0 && this.props.Position.latitude != 0)
            {
                this.setMapCenter(this.props.Data.position);
            }
            if(this.props.Data.markers.length > 0)
            {
                this.setMarkers(this.props.Data.markers,map);
                this.setInfoBoxes(this.props.Data.markers,map);
            }
            if(this.props.CenterOnPins)
            {
                this.CenterOnPins();
            }
        });        
    }
    closePopup(i)
    {
        var index = i - 1;
        if(popups[index] != undefined)
        {
            popups[index].close();
        }
        
    }
    render(){
        console.log("test");
        return (
            <div>
                <div style={{width:this.props.Width,height:this.props.Height}} ref={this.mapRef}></div>
            </div>
        );
    }
}