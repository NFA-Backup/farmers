(function () {
  farmOS.map.behaviors.geojson = {
    attach: function (instance) {
      if (drupalSettings.farm_map[instance.target].geojson) {
        var url = new URL(drupalSettings.farm_map[instance.target].url, window.location.origin + drupalSettings.path.baseUrl)
        var newLayer = instance.addLayer('geojson', {
          title: drupalSettings.farm_map[instance.target].title ?? Drupal.t('geoJSON'),
          url,
          color: 'orange',
        })
        var source = newLayer.getSource()
        source.on('change', function () {
          instance.zoomToVectors()
        })
      }
    }
  }
}())
