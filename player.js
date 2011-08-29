var panel_player;
YAHOO.example.panels = function(){

    panel_player = new YAHOO.widget.Panel("panel_player", {
        close:true,
            visible:false,
            draggable:true,
            modal:true,
            constraintoviewport:true
		}
    );
panel_player.render();
}


//add kaltura player stuff to region-main
var pp = document.createElement('div');
pp.setAttribute('id', 'panel_player');
var pphd = document.createElement('div');
pphd.setAttribute('class', 'hd');

pp.appendChild(pphd);
var ppbd = document.createElement('div');
ppbd.setAttribute('class', 'bd');
pp.appendChild(ppbd);

var kpdiv = document.createElement('div');
kpdiv.setAttribute('class', 'kalturaPlayer');
ppbd.appendChild(kpdiv);

var regmain = document.getElementById('region-main-box');
var regmaintop = document.getElementById('region-post-box');
regmain.insertBefore(pp, regmaintop);

var ppjs = document.createElement('script');
ppjs.setAttribute('id','kalturapodcast');
ppjs.setAttribute('type','text/javascript');
ppbd.appendChild(ppjs);

panel_playersetBody = function(entryid, playerui) {
    //remove old code.

    var playerjs = document.getElementById('kalturapodcast');
    var pn = playerjs.parentNode;
    pn.removeChild(playerjs);
    //replace with new player stuff
    var ppjs = document.createElement('script');
    ppjs.setAttribute('id','kalturapodcast');
    ppjs.setAttribute('type','text/javascript');
    ppjs.appendChild(document.createTextNode(
        'window.kaltura = {entryid: "'+entryid+'", remixuiconf: "'+playerui+'"}'
    ));
    pn.appendChild(ppjs);
    initialisepodcastvideo({playerselector:'.kalturaPlayer'});
}

YAHOO.util.Event.addListener(window,'load',YAHOO.example.panels);

function initialisepodcastvideo(obj) {
    YUI().use("swf","node","io","json-parse","event", function(Y) {
        var player = Y.one(obj.playerselector);
        if (player == undefined) {
            return false;
        }
        if (player.hasChildNodes()) {
            player.one('*').remove(true);
        }
        if (window.kaltura.remixuiconf == undefined || window.kaltura.remixuiconf == '') {
            alert("playerid isn't set");
            return false;
        }

        var datastr = '';
        datastr += 'actions[0]=playerurl';
        if (obj.entryid != undefined && obj.entryid != '') {
            datastr += '&params[0][entryid]='+obj.entryid;
        }
        else if (window.kaltura.entryid != 0 && window.kaltura.entryid != undefined) {
            datastr += '&params[0][entryid]='+window.kaltura.entryid;
        }
        else if (window.kaltura.cmid != 0 && window.kaltura.cmid != undefined) {
            datastr += '&params[0][id]='+window.kaltura.cmid;
        }
        else {
            return false;
        }
        Y.io(M.cfg.wwwroot+'/local/kaltura/ajax.php',
            {
                data: datastr,
                on: {
                    success: function(i, o, a) {
                        var data = Y.JSON.parse(o.responseText);
                        var kaltura_player = new Y.SWF(obj.playerselector, data[0].url,
                            {
                                fixedAttributes: {
                                    wmode: "opaque",
                                    allowScriptAccess: "always",
                                    allowFullScreen: true,
                                    allowNetworking: "all"
                                },
                                flashVars: {
                                    externalInterfaceDisabled: 0,
                                    uiConfId: window.kaltura.remixuiconf
                                }
                            }
                        );
                        Y.one(document.body).append('<scr' + 'ipt type="text/javascript" src="' + data[0].html5url + '"></scr' + 'ipt>');
                    }
                }
            }
        );
    });
}