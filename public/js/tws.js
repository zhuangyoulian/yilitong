function SealObject() {
    var $a = window.location.href;
    var $b = "https://seal.wosign.com/";
    var $c = $b + "SealAction.aspx?ref=" + new Base64().encode($a) + "&lan=cn";
    var $d = navigator.userAgent.toLowerCase();
    var $e = null;
    var $f = null;
    var $g = this;
    var $h = null;
    var $i = null;
    var $j = null;
    var $k = null;
    this.SealFloatHeight = 300;
    this.SealFloatWidth = 250;
    if ($d.indexOf("msie 5") != -1) {
        $e = 5;
    };
    if ($d.indexOf("msie 6") != -1) {
        $e = 6;
    };
    if ($d.indexOf("msie 7") != -1) {
        $e = 7;
    };
    if ($d.indexOf("msie 8") != -1) {
        $e = 8;
    };
    var $l = window.XMLHttpRequest ? false: true;
    var $m = true;
    this.setBox = function($n, $o, $p) {
        if (isNaN($p)) {
            $p = 0;
        };
        $n.style.visibility = "";
        $o.onmouseover = function() {
            $g.setPosition($n, $o);
            $m = false;
            $o.style.display = "block";
        };
        $o.onmouseout = function() {
            $m = true;
            setTimeout($h + ".delayDoEvent('" + $o.id + "','none')", $p);
        };
        $n.onmouseover = function() {
            $g.setPosition($n, $o);
            $m = false;
            $o.style.display = "block";
        };
        $n.onmouseout = function() {
            $m = true;
            setTimeout($h + ".delayDoEvent('" + $o.id + "','none')", $p);
        };
        $g.setPosition($n, $o);
    };
    this.delayDoEvent = function($q, $r) {
        if ($m) {
            if ($q == undefined || $q == "" || $r == undefined) {
                return;
            };
            var $s = document.getElementById($q);
            if ($s != undefined) {
                if ($s.style.display != $r) {
                    $s.style.display = $r;
                }
            }
        };
        clearTimeout(this);
    };
    this.getRealOffSetTop = function($t) {
        var t = $t.offsetTop;
        while ($t = $t.offsetParent) {
            t += $t.offsetTop;
        };
        return t;
    };
    this.getRealOffSetLeft = function($t) {
        var l = $t.offsetLeft;
        while ($t = $t.offsetParent) {
            l += $t.offsetLeft;
        };
        return l;
    };
    this.setPosition = function($n, $o) {
        $o.style.zIndex = 1000;
        $o.style.visibility = "hidden";
        $o.style.display = "block";
        if ($e != null && $e != undefined && $e <= 8) {
            if ($g.getRealOffSetLeft($n) <= 0 || $g.getRealOffSetLeft($n) < $o.offsetWidth) {
                $o.style.left = ($g.getRealOffSetLeft($n) + $n.offsetWidth) + "px";
            } else {
                $o.style.left = ($g.getRealOffSetLeft($n) - $o.offsetWidth) + "px";
            };
            if ($g.getRealOffSetTop($n) <= 0 || $g.getRealOffSetTop($n) < $o.offsetHeight) {
                $o.style.top = ($g.getRealOffSetTop($n) + $n.offsetHeight) + "px";
            } else {
                $o.style.top = ($g.getRealOffSetTop($n) - $o.offsetHeight) + "px";
            }
        } else {
            if ($g.getRealOffSetLeft($n) <= 0 || $g.getRealOffSetLeft($n) < $o.clientWidth) {
                $o.style.left = ($g.getRealOffSetLeft($n) + $n.clientWidth) + "px";
            } else {
                $o.style.left = ($g.getRealOffSetLeft($n) - $o.clientWidth) + "px";
            };
            if ($g.getRealOffSetTop($n) <= 0 || $g.getRealOffSetTop($n) < $o.clientHeight) {
                $o.style.top = ($g.getRealOffSetTop($n) + $n.clientHeight) + "px";
            } else {
                $o.style.top = ($g.getRealOffSetTop($n) - $o.clientHeight) + "px";
            }
        };
        $o.style.visibility = "";
        $o.style.display = "none";
    };
    this.createId = function($u) {
        return ($u + "" + new Date().getMinutes() + "" + new Date().getMilliseconds() + "" + parseInt(Math.random() * 9999));
    };
    this.iframeAutoFit = function($v, $w, $x) {
        if (typeof($v) == "string") {
            $v = document.getElementById($v);
        };
        if (!$v) {
            return;
        };
        $v.height = ($v.Document ? $v.Document.body.scrollHeight: $v.contentDocument.body.style.height) + $w;
        $v.width = ($v.Document ? $v.Document.body.scrollWidth: $v.contentDocument.body.style.width) + $x;
    };
    this.onPageLoadMethod = function($i, $j, $k, $y, $z, $A) {
        try {
            var $B = null;
            var $C = null;
            var $D = null;
            var $E = false;
            if (document.getElementById($i) != undefined) {
                $B = document.getElementById($i);
            } else {
                $B = document.createElement("div");
                $B.setAttribute("id", $i);
                $E = true;
            };
            if (document.getElementById($j) != undefined) {
                $C = document.getElementById($j);
            } else {
                $C = document.createElement("img");
                $C.setAttribute("id", $j);
            };
            if (document.getElementById($k) != undefined) {
                $D = document.getElementById($k);
            } else {
                $D = document.createElement("iframe");
                $D.setAttribute("id", $k);
            };
            if ($y == undefined || $y == "") {
                $C.setAttribute("style", "display:none;");
                $B.setAttribute("style", "display:none;");
            } else {
                $C.setAttribute("style", "display:block;");
            };
            $B.style.position = "absolute";
            if ($A != "") {
                $C.setAttribute("src", $A);
            };
            $C.setAttribute("border", "0");
            $C.setAttribute("oncontextmenu", "return false;");
            $C.oncontextmenu = function() {
                return false;
            };
            $C.setAttribute("style", "border:0px;margin:0px;padding:0px;cursor:pointer;");
            $C.style.cursor = "pointer";
            if (!$E) {
                $f = $z;
                $g.addEvent($C, "click", $g.opWindow);
            };
            $D.setAttribute("src", $y);
            $D.setAttribute("width", this.SealFloatWidth);
            $D.setAttribute("height", this.SealFloatHeight);
            $D.setAttribute("frameBorder", "0");
            $D.setAttribute("style", "border:0px;margin:0px;padding:0px;");
            $D.setAttribute("scrolling", "no");
            $C.style.visibility = "hidden";
            $B.style.display = "none";
            if ($E) {
                $B.appendChild($D);
                if ($B.outerHTML != undefined && $B.outerHTML != null) {
                    document.write($B.outerHTML);
                } else {
                    document.write("<" + $B.tagName + " " + $g.GetPropertyStrings($B) + ">" + $B.innerHTML + "</" + $B.tagName + ">");
                };
                if ($C.outerHTML != undefined && $C.outerHTML != null) {
                    document.write($C.outerHTML);
                } else {
                    document.write("<" + $C.tagName + " " + $g.GetPropertyStrings($C) + ">" + $C.innerHTML + "</" + $C.tagName + ">");
                }
            };
            if ($A != "") {
                $g.setBox($C, $B, 1000);
            }
        } catch(ex) {}
    };
    this.opWindow = function() {
        var $F = navigator.userAgent.toLowerCase();
        var $G = ($F.indexOf("msie") != -1);
        $g.opWindowEvent($f, $b);
        return false;
    };
    this.opWindowEvent = function($H, title) {
        var $F = navigator.userAgent.toLowerCase();
        var $G = ($F.indexOf("msie") != -1);
        var $I = ($F.indexOf("opera") != -1);
        var $J = null;
        var $K = 760;
        if (screen != null) {
            if (screen.height < 670) {
                $K = screen.height - 70;
            }
        };
        var $L = null;
        if ($l) {
            $L = "toolbar=0,location=1,menubar=0,status=1,directories=0,scrollbars=0,resizeable=0,width=520,height=" + $K;
        } else {
            $L = "toolbar=0,location=1,menubar=0,status=1,directories=0,scrollbars=0,resizeable=0,width=520,height=" + $K;
        };
        var $M = window.open($H, 'title', $L);
        if ($F.indexOf("msie 5") != -1) $J = 5;
        if ($F.indexOf("msie 6") != -1) $J = 6;
        if ($F.indexOf("msie 7") != -1) $J = 7;
        if ($F.indexOf("msie 8") != -1) $J = 8;
        if (($M != null) && (!$G || ($J >= 5))) {
            $M.focus();
        }
    };
    this.callScriptElement = function() {
        $c += "&callbackparam=" + $h + ".success_jsonpCallback";
        var $N = document.createElement("script");
        $N.setAttribute("type", "text/javascript");
        $N.setAttribute("src", $c);
        $N.setAttribute("id", $g.createId("script"));
        document.body.appendChild($N);
    };
    this.addEvent = function(el, name, fn) {
        if (el.addEventListener) {
            el.addEventListener(name, fn, false);
        } else {
            el.attachEvent('on' + name, fn);
        }
    };
    this.getUriValue = function() {
        $g.onPageLoadMethod($i, $j, $k, "", "", "");
        if ($e != undefined) {
            if (document.readyState != 'complete') {
                $g.addEvent(document, 'readystatechange',
                function() {
                    if (document.readyState == "complete") {
                        $g.callScriptElement();
                    }
                })
            }
        } else {
            $g.callScriptElement();
        }
    };
    this.success_jsonpCallback = function($O) {
        try {
            $g.onPageLoadMethod($i, $j, $k, $O.mediaUrl, $O.detailUrl, $O.imgUrl);
        } catch(e) {}
    };
    this.initSeal = function(Id) {
        try {
            $h = Id;
            $i = $g.createId("hideBox");
            $j = $g.createId("frameMini");
            $k = $g.createId("frameBig");
            $g.getUriValue();
        } catch(e) {}
    };
    this.GetPropertyStrings = function($P) {
        var $Q = eval($P);
        var $R = "";
        for (var $S in $Q) {
            if ($P.attributes[$S] != undefined && $P.attributes[$S] != null) {
                $R += $S + "=\"" + sourceObj.attributes[property].value + "\" ";
            }
        };
        return $R;
    }
};
try {
    var $T = new Date().getMinutes() + "" + new Date().getMilliseconds() + "" + parseInt(Math.random() * 999999);
    document.write("<script type=\"text/javascript\">try {var seal" + $T + " = new SealObject();seal" + $T + ".initSeal('seal" + $T + "');}catch(e){};</script>");
} catch(e) {};
function Base64() {
    var $U = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    this.encode = function($V) {
        if ($V == undefined || $V == null || $V == "") {
            return "";
        };
        $V = $V + "";
        var $W = "";
        var $X, $Y, $Z = "";
        var $00, $01, $02, $03 = "";
        var i = 0;
        do {
            $X = $V.charCodeAt(i++);
            $Y = $V.charCodeAt(i++);
            $Z = $V.charCodeAt(i++);
            $00 = $X >> 2;
            $01 = (($X & 3) << 4) | ($Y >> 4);
            $02 = (($Y & 15) << 2) | ($Z >> 6);
            $03 = $Z & 63;
            if (isNaN($Y)) {
                $02 = $03 = 64;
            } else if (isNaN($Z)) {
                $03 = 64;
            };
            $W = $W + $U.charAt($00) + $U.charAt($01) + $U.charAt($02) + $U.charAt($03);
            $X = $Y = $Z = "";
            $00 = $01 = $02 = $03 = "";
        } while ( i < $V . length );
        return $W;
    }
}