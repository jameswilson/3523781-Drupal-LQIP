// Minimal browser-compatible BlurHash decode function (from blurhash@2.0.5)
// Exposes window.blurhashDecode(blurhash, width, height)
(function() {
  function sRGBToLinear(value) {
    var v = value / 255;
    return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
  }
  function linearTosRGB(value) {
    var v = Math.max(0, Math.min(1, value));
    return v <= 0.0031308
      ? Math.round(v * 12.92 * 255 + 0.5)
      : Math.round((1.055 * Math.pow(v, 1 / 2.4) - 0.055) * 255 + 0.5);
  }
  function signPow(val, exp) {
    return Math.sign(val) * Math.pow(Math.abs(val), exp);
  }
  function decode83(str) {
    var chars =
      '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz#$%*+,-.:;=?@[]^_{|}~';
    var value = 0;
    for (var i = 0; i < str.length; i++) {
      value = value * 83 + chars.indexOf(str[i]);
    }
    return value;
  }
  function decodeDC(value) {
    var intR = value >> 16;
    var intG = (value >> 8) & 255;
    var intB = value & 255;
    return [sRGBToLinear(intR), sRGBToLinear(intG), sRGBToLinear(intB)];
  }
  function decodeAC(value, maximumValue) {
    var quantR = Math.floor(value / (19 * 19));
    var quantG = Math.floor(value / 19) % 19;
    var quantB = value % 19;
    return [
      signPow((quantR - 9) / 9, 2.0) * maximumValue,
      signPow((quantG - 9) / 9, 2.0) * maximumValue,
      signPow((quantB - 9) / 9, 2.0) * maximumValue
    ];
  }
  function decode(blurhash, width, height, punch) {
    punch = punch || 1.0;
    var sizeFlag = decode83(blurhash[0]);
    var numY = Math.floor(sizeFlag / 9) + 1;
    var numX = (sizeFlag % 9) + 1;
    var quantisedMaximumValue = decode83(blurhash[1]);
    var maximumValue = (quantisedMaximumValue + 1) / 166;
    var colors = [];
    // DC component
    colors.push(decodeDC(decode83(blurhash.substring(2, 6))));
    // AC components
    for (var i = 1; i < numX * numY; i++) {
      colors.push(
        decodeAC(decode83(blurhash.substring(4 + i * 2, 6 + i * 2)), maximumValue * punch)
      );
    }
    var pixels = [];
    for (var y = 0; y < height; y++) {
      var row = [];
      for (var x = 0; x < width; x++) {
        var r = 0, g = 0, b = 0;
        for (var j = 0; j < numY; j++) {
          for (var i = 0; i < numX; i++) {
            var basis =
              Math.cos((Math.PI * x * i) / width) *
              Math.cos((Math.PI * y * j) / height);
            var color = colors[i + j * numX];
            r += color[0] * basis;
            g += color[1] * basis;
            b += color[2] * basis;
          }
        }
        row.push([
          linearTosRGB(r),
          linearTosRGB(g),
          linearTosRGB(b)
        ]);
      }
      pixels.push.apply(pixels, row);
    }
    return pixels;
  }
  window.blurhashDecode = decode;
})();
