{{-- resources/views/reports/audit/device-info.blade.php --}}
<h3>Device Information</h3>
<table class="info-table">
    <tr>
        <td>Device Name:</td>
        <td>{{ $resource['device_name'] }}</td>
        <td>Device Code:</td>
        <td>{{ $resource['device_code'] }}</td>
    </tr>
    <tr>
        <td>Make:</td>
        <td>{{ $resource['make'] }}</td>
        <td>Model:</td>
        <td>{{ $resource['model'] }}</td>
    </tr>
    <tr>
        <td>Serial No:</td>
        <td>{{ $resource['serial_no'] }}</td>
        <td>Temp Resolution:</td>
        <td>{{ $resource['temp_resolution'] }} °C</td>
    </tr>
    <tr>
        <td>Temp Accuracy:</td>
        <td>± {{ $resource['temp_accuracy'] }} °C</td>
        <td>Company:</td>
        <td>{{ $resource['company'] }}</td>
    </tr>
</table>
