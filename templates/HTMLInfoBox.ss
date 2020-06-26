<%-- Needs to be overwritten, needs the 'a href="javascript:closeInfoBox({ID})"' to be able to close rest is optional --%>
<div class="infoBox typography">
    <div class="infoBox_header">$Title<a class="infobox-close" href="javascript:closeInfoBox({$ID})">x</a></div>
    $HTMLContent.RAW
</div>
<style>
    .infoBox{
        padding:0 20px 20px 20px;
        background:white;
        border:1px solid black;
        border-radius:5px;
    }
</style>