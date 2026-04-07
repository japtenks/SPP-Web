document.addEventListener('DOMContentLoaded', function () {
  var frame = document.getElementById('playermapFrame');
  if (!frame) {
    return;
  }

  function resizePlayermapFrame() {
    try {
      var doc = frame.contentDocument || (frame.contentWindow ? frame.contentWindow.document : null);
      if (!doc) {
        return;
      }

      var body = doc.body;
      var html = doc.documentElement;
      var height = Math.max(
        body ? body.scrollHeight : 0,
        body ? body.offsetHeight : 0,
        html ? html.scrollHeight : 0,
        html ? html.offsetHeight : 0
      );

      var children = body && body.children ? Array.prototype.slice.call(body.children) : [];
      if (children.length) {
        var contentBottom = children.reduce(function (maxBottom, element) {
          var top = element.offsetTop || 0;
          var elementHeight = element.offsetHeight || 0;
          return Math.max(maxBottom, top + elementHeight);
        }, 0);
        if (contentBottom > 0) {
          height = Math.min(height, contentBottom);
        }
      }

      if (height > 0) {
        frame.style.height = height + 'px';
      }
    } catch (error) {
      frame.style.height = '740px';
    }
  }

  frame.addEventListener('load', function () {
    resizePlayermapFrame();
    window.setTimeout(resizePlayermapFrame, 120);
  });
});
