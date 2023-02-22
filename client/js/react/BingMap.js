var map = {};
var infoboxes = [];
var locs = [];
class BingMap extends React.Component{
    constructor(props){
        super(props);
        this.mapRef = React.createRef();
        this.GetBingJSSourceLink = this.GetBingJSSourceLink.bind(this);
        this.loadScript = this.loadScript.bind(this);
        this.MapCallBack = this.MapCallBack.bind(this);
        this.closeInfoBox = this.closeInfoBox.bind(this);
        this.state = {
            map: null,
            locs: []
        }
    }
    componentDidMount()
    {
        window.MapCallBack = this.MapCallBack;
        window.closeInfoBox = this.closeInfoBox;
        this.loadScript();
    }
    closeInfoBox(e)
    {
        console.log(e);
    }
    GetBingJSSourceLink()
    {
        return "https://www.bing.com/api/maps/mapcontrol?callback=MapCallBack&key="+this.props.APIKey;
    }
    loadScript()
    {
        const existingScript = document.getElementById('bingMap');
        if (!existingScript) {
            const script = document.createElement('script');
            script.src = this.GetBingJSSourceLink();
            script.id = 'bingMap';
            document.body.appendChild(script);
        }
    }
    setMapCenter(position)
    {
        map.setView({
            center: new Microsoft.Maps.Location(position.latitude,position.longitude)
        });
    }
    setMarkers(Markers,map)
    {
        locs = [];
        //clear pushpings before adding them again
        if(map && Markers)
        {
            for(var i = map.entities.getLength()-1;i >= 0;i--)
            {
                var pushpin = map.entities.get(i);
                if(pushpin instanceof Microsoft.Maps.Pushpin)
                {
                    map.entities.removeAt(i);
                }
            }
        }
        //Add Pushpins
        Markers.forEach((Marker,index) => {
            let location = new Microsoft.Maps.Location(Marker.coordinates.latitude,Marker.coordinates.longitude);
            locs.push(location);
            let marker = new Microsoft.Maps.Pushpin(location,{icon: Marker.icon});
            Microsoft.Maps.Events.addHandler(marker,"click",(e) => {
                this.pushpinClicked(index);
            });
            map.entities.push(marker);
        });
    }
    setInfoBoxes(Markers,map)
    {
        if(map && Markers)
        {
            for(var i = map.entities.getLength()-1;i >= 0;i--)
            {
                var infobox = map.entities.get(i);
                if(infobox instanceof Microsoft.Maps.Infobox)
                {
                    map.entities.removeAt(i);
                }
            }
        }
        Markers.forEach((Marker,index) => {
            if("infobox" in Marker && Marker.infobox != undefined)
            {
                let location = new Microsoft.Maps.Location(Marker.infobox.coordinates.latitude,Marker.infobox.coordinates.longitude);
                var options = {}
                options.title = Marker.infobox.title;
                if(Marker.infobox.Description != null)
                {
                    options.description = Marker.infobox.Description;
                }
                if(Marker.infobox.htmlContent != null)
                {
                    options.htmlContent = Marker.infobox.htmlContent;
                }
                options.visible = Marker.infobox.initialVisibility;
                var infobox = new Microsoft.Maps.Infobox(location, options);
                infobox.setMap(map);
                infoboxes.push(infobox);
            }
        });
    }
    CenterOnPins()
    {
        map.setView({
            bounds: Microsoft.Maps.LocationRect.fromLocations(locs),
            padding: this.props.Data.padding
        });
    }
    pushpinClicked(index)
    {
        if(infoboxes[index] != undefined)
        {
            infoboxes[index].setOptions({visible:true});
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
        //take https://github.com/iniamudhan/react-bingmaps/blob/dev/src/node_modules/components/ReactBingmaps/ReactBingmaps.js as an example
        map = new Microsoft.Maps.Map(this.mapRef.current);
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
    }
    closeInfoBox(i)
    {
        var index = i - 1;
        if(infoboxes[index] != undefined)
        {
            infoboxes[index].setOptions({visible:false});
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