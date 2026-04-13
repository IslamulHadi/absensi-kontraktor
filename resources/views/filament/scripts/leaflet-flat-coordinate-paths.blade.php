{{-- Sync filament-leaflet MapPicker with sibling TextInputs (data.latitude / data.longitude). --}}
<script>
    document.addEventListener('livewire:init', () => {
        if (window.leafletMapField?.__attendanceFlatCoordsPatched) {
            return;
        }

        const base = window.leafletMapField;

        if (typeof base !== 'function') {
            return;
        }

        window.leafletMapField = function attendanceFlatCoordsLeafletMapField($wire, config) {
            const api = base($wire, config);
            const flatLat = config.state?.flatLatitudePath;
            const flatLng = config.state?.flatLongitudePath;

            if (!flatLat || !flatLng) {
                return api;
            }

            const parseCoord = (value) => {
                if (value === null || value === undefined || value === '') {
                    return Number.NaN;
                }

                if (typeof value === 'number') {
                    return Number.isFinite(value) ? value : Number.NaN;
                }

                const n = parseFloat(String(value).replace(',', '.'));

                return Number.isFinite(n) ? n : Number.NaN;
            };

            api.getState = function () {
                if (!this.config.state) {
                    return undefined;
                }

                const lat = parseCoord(this.$wire.get(flatLat));
                const lng = parseCoord(this.$wire.get(flatLng));

                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    return { lat, lng };
                }

                return {
                    lat: this.config.defaultCoord[0],
                    lng: this.config.defaultCoord[1],
                };
            };

            api.setState = function (lat, lng) {
                if (!this.config.state) {
                    return;
                }

                this.$wire.set(flatLat, lat);
                this.$wire.set(flatLng, lng);
                this.updatePickMarker();
            };

            api.watchState = function () {
                if (!this.config.state) {
                    return;
                }

                this.$wire.watch(flatLat, () => {
                    this.updatePickMarker();
                });
                this.$wire.watch(flatLng, () => {
                    this.updatePickMarker();
                });
            };

            const baseUpdatePickMarker = api.updatePickMarker.bind(api);

            api.updatePickMarker = function () {
                baseUpdatePickMarker();

                if (this.pickMarker && this.config.state.pickMarker?.draggable) {
                    Alpine.raw(this.pickMarker).on('dragend', (e) => {
                        const ll = e.target.getLatLng();

                        this.setState(ll.lat, ll.lng);
                        this.callFieldMethod('handleMapClick', {
                            latitude: ll.lat,
                            longitude: ll.lng,
                        });
                    });
                }
            };

            return api;
        };

        window.leafletMapField.__attendanceFlatCoordsPatched = true;
    });
</script>
