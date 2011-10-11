define (function(){
/// XXX: make it cross browser
	var DOCUMENTATION_OPTIONS = {
			  URL_ROOT: '',
			  VERSION: '2.7.2',
			  COLLAPSE_MODINDEX: false,
			  FILE_SUFFIX: '.html',
			  HAS_SOURCE: true
			};
/**
 * make the code below compatible with browsers without
 * an installed firebug like debugger
 */
if (!window.console || !console.firebug) {
  var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
      "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];
  window.console = {};
  for (var i = 0; i < names.length; ++i)
    window.console[names[i]] = function() {}
}

/**
 * small helper function to urldecode strings
 */
jQuery.urldecode = function(x) {
  return decodeURIComponent(x).replace(/\+/g, ' ');
}

/**
 * small helper function to urlencode strings
 */
jQuery.urlencode = encodeURIComponent;

/**
 * This function returns the parsed url parameters of the
 * current request. Multiple values per key are supported,
 * it will always return arrays of strings for the value parts.
 */
jQuery.getQueryParameters = function(s) {
  if (typeof s == 'undefined')
    s = document.location.search;
  var parts = s.substr(s.indexOf('?') + 1).split('&');
  var result = {};
  for (var i = 0; i < parts.length; i++) {
    var tmp = parts[i].split('=', 2);
    var key = jQuery.urldecode(tmp[0]);
    var value = jQuery.urldecode(tmp[1]);
    if (key in result)
      result[key].push(value);
    else
      result[key] = [value];
  }
  return result;
}

/**
 * small function to check if an array contains
 * a given item.
 */
jQuery.contains = function(arr, item) {
  for (var i = 0; i < arr.length; i++) {
    if (arr[i] == item)
      return true;
  }
  return false;
}

/**
 * highlight a given string on a jquery object by wrapping it in
 * span elements with the given class name.
 */
jQuery.fn.highlightText = function(text, className) {
  function highlight(node) {
    if (node.nodeType == 3) {
      var val = node.nodeValue;
      var pos = val.toLowerCase().indexOf(text);
//      if (pos >= 0 && !jQuery.className.has(node.parentNode, className)) {
      if (pos >= 0) {
        var span = document.createElement("span");
        span.className = className;
        span.appendChild(document.createTextNode(val.substr(pos, text.length)));
        node.parentNode.insertBefore(span, node.parentNode.insertBefore(
          document.createTextNode(val.substr(pos + text.length)),
          node.nextSibling));
        node.nodeValue = val.substr(0, pos);
      }
    }
    else if (!jQuery(node).is("button, select, textarea")) {
      jQuery.each(node.childNodes, function() {
        highlight(this)
      });
    }
  }
  return this.each(function() {
    highlight(this);
  });
}


var highlightSearchWords = function() {
  var params = $.getQueryParameters();
  var terms = (params.highlight) ? params.highlight[0].split(/\s+/) : [];
  if (terms.length) {
    var body = $('#content');
    window.setTimeout(function() {
      $.each(terms, function() {
        body.highlightText(this.toLowerCase(), 'highlight');
      });
    }, 10);
  }
}

return highlightSearchWords;
});
