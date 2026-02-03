{{-- resources/views/reports/audit/user-info.blade.php --}}
<h3>User Information</h3>
<table class="info-table">
    <tr>
        <td>Name:</td>
        <td>{{ $resource['name'] }}</td>
        <td>Role:</td>
        <td>{{ $resource['role'] }}</td>
    </tr>
    <tr>
        <td>Email:</td>
        <td>{{ $resource['email'] }}</td>
        <td>Company:</td>
        <td>{{ $resource['company'] }}</td>
    </tr>
</table>
