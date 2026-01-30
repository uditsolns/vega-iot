<div class="section-title">Observed Report Summary</div>

<div class="info-grid clearfix">
    <div class="info-left">
        @if(isset($data['minTempData']))
            <div class="info-row">
                <span class="label">Min Temperature:</span>
                <span class="value">{{ number_format($data['minTempData'], 1) }} °C</span>
            </div>
        @endif

        @if(isset($data['maxTempData']))
            <div class="info-row">
                <span class="label">Max Temperature:</span>
                <span class="value">{{ number_format($data['maxTempData'], 1) }} °C</span>
            </div>
        @endif

        @if(isset($data['mkt']))
            <div class="info-row">
                <span class="label">MKT:</span>
                <span class="value">{{ number_format($data['mkt'], 2) }} °C</span>
            </div>
        @endif

        @if(isset($data['minHumData']))
            <div class="info-row">
                <span class="label">Min Humidity:</span>
                <span class="value">{{ number_format($data['minHumData'], 1) }} %RH</span>
            </div>
        @endif

        @if(isset($data['minTempProbeData']))
            <div class="info-row">
                <span class="label">Min Temp Probe:</span>
                <span class="value">{{ number_format($data['minTempProbeData'], 1) }} °C</span>
            </div>
        @endif
    </div>

    <div class="info-right">
        @if(isset($data['maxHumData']))
            <div class="info-row">
                <span class="label">Max Humidity:</span>
                <span class="value">{{ number_format($data['maxHumData'], 1) }} %RH</span>
            </div>
        @endif

        @if(isset($data['maxTempProbeData']))
            <div class="info-row">
                <span class="label">Max Temp Probe:</span>
                <span class="value">{{ number_format($data['maxTempProbeData'], 1) }} °C</span>
            </div>
        @endif

        @if(isset($data['avgTemp']))
            <div class="info-row">
                <span class="label">Avg Temperature:</span>
                <span class="value">{{ number_format($data['avgTemp'], 2) }} °C</span>
                @if(isset($data['avgHum']))
                    <span class="label"> / Avg Humidity:</span>
                    <span class="value">{{ number_format($data['avgHum'], 2) }} %RH</span>
                @endif
            </div>
        @endif

        @if(isset($data['avgTempProbe']))
            <div class="info-row">
                <span class="label">Avg Temp Probe:</span>
                <span class="value">{{ number_format($data['avgTempProbe'], 2) }} °C</span>
            </div>
        @endif
    </div>
</div>

<div class="divider"></div>
