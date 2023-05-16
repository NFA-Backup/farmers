(function () {
  nfa.map.behaviors.geojson = {
    attach: function (instance) {
      if (drupalSettings.farm_map[instance.target].geojson) {
        var layers = drupalSettings.farm_map[instance.target].urls;

        for (let index = 0; index < layers.length; ++index) {
          const element = layers[index];
          var url = new URL(element.url, window.location.origin + drupalSettings.path.baseUrl)
          var newLayer = instance.addLayer('geojson', {
            title: element.title ?? Drupal.t('geoJSON'),
            url,
            color: element.color ?? 'orange',
          });
        }

        var source = newLayer.getSource()
        source.on('change', function () {
          instance.zoomToVectors()
        })
      }
      const nodes = document.querySelectorAll('tbody')[2].querySelectorAll('tr')
      const map = instance.map;
      let previousNodeId = '';
      nodes.forEach(function (node) {
        node.style.cursor = 'pointer';
        const nodeId = node.querySelectorAll('td')[1].innerText
        node.setAttribute('id', nodeId);
        node.addEventListener('click', function (event) {
          const isSameNode = previousNodeId == nodeId;
          node.style.backgroundColor = isSameNode ? '' : '#adebdb';
          const alreadyAddedLayers = map.getLayers().getArray();
          for (let i = 0; i < alreadyAddedLayers.length; i++) {
            const node = document.querySelectorAll(".layer-switcher input")[i];
            if (!isSameNode && node.checked) node.click()
            if(isSameNode && !node.checked) node.click()
          }
          if (previousNodeId != '') { 
            map.removeLayer(alreadyAddedLayers[alreadyAddedLayers.length - 1])
            const previousSelectedNode = document.getElementById(`${previousNodeId}`);
            previousSelectedNode.style.backgroundColor = '';
          }
          if (isSameNode) {
            const layerSwitcher = document.querySelector('.layer-switcher')
            layerSwitcher.style.display = "block";
            instance.zoomToVectors()
            previousNodeId = '';
            return;
          }
          fetch(`${window.origin}/sub-area/geojson/${nodeId}`).then(async (response) => {
            const geoJson = await response.json()
            console.log(geoJson)
            const layer = instance.addLayer('geojson', {
              title: `${nodeId} id subarea`,
              geojson : geoJson,
              color: 'orange',
            });
            const source = layer.getSource()
            previousNodeId = nodeId;
            const layerSwitcher = document.querySelector('.layer-switcher')
            layerSwitcher.style.display = "none"
            map.getView().fit(source.getExtent());
            map.getView().fit(geoJson, map.getSize(), {duration: 1000});
          })
        })
      })
    }
  }
}())
