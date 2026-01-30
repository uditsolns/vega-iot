<htmlpageheader name="page-header">
    <table width="100%" style="border-bottom: 0.5px solid #666666; margin-bottom: 5px;">
        <tr>
            <td width="15%" style="text-align: left;">
                @if(file_exists(public_path('vega-logo.png')))
                    <img src="{{ public_path('vega-logo.png') }}" style="height: 11mm; width: 22mm;" alt="Logo">
                @endif
            </td>
            <td width="70%" style="text-align: center; vertical-align: middle;">
                <div style="color: #003366; font-weight: bold; font-size: 11pt;">Data Report</div>
                <div style="color: #000000; font-size: 8pt; margin-top: 2px;">{{ $data['report_name'] ?? 'Report' }}</div>
            </td>
            <td width="15%"></td>
        </tr>
    </table>
</htmlpageheader>
