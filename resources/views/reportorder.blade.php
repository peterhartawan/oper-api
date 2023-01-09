<table>
    <thead>
        <tr>
            <th align="center">ID Order</th>
            <th align="center">Dispatcher Name</th>
            <th align="center">Driver Name</th>
            <th align="center">Booking Time</th>
            <th align="center">Origin Latitude</th>
            <th align="center">Origin Longitude</th>
            <th align="center">Destination Latitude</th>
            <th align="center">Destination Longitude</th>
            <th align="center">Client Vehicle License</th>
            <th align="center">Vehicle Brand</th>
            <th align="center">Vehicle Type</th>
            <th align="center">Vehicle Transmission</th>
            <th align="center">Vehicle Year</th>
            <th align="center">Message</th>
            <th align="center">Order Number</th>
            <th align="center">Order Type</th>
            <th align="center">Order Status</th>
            <th align="center">Task Descriptions</th>
            <th align="center">Task Images</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($orders as $order)
            <tr>
                <td>{{ $order['idorder'] }}</td>
                <td>{{ $order['dispatcher_name'] }}</td>
                <td>{{ $order['driver_name'] }}</td>
                <td>{{ $order['booking_time'] }}</td>
                <td>{{ $order['origin_latitude'] }}</td>
                <td>{{ $order['origin_longitude'] }}</td>
                <td>{{ $order['destination_latitude'] }}</td>
                <td>{{ $order['destination_longitude'] }}</td>
                <td>{{ $order['client_vehicle_license'] }}</td>
                <td>{{ $order['vehicle_brand_id'] }}</td>
                <td>{{ $order['vehicle_type'] }}</td>
                <td>{{ $order['vehicle_transmission'] }}</td>
                <td>{{ $order['vehicle_year'] }}</td>
                <td>{{ $order['message'] }}</td>
                <td>{{ $order['trx_id'] }}</td>
                <td>{{ $order['order_type_name'] }}</td>
                <td>{{ $order['status_text'] }}</td>
                <td>
                    @for ($i = 0; $i < $order['task_length']; $i++)
                        {{ $i + 1 }} - {{ $order['tasks'][$i]['description'] }}@if ($i < $order['task_length'] - 1)
                            ,
                        @endif
                    @endfor
                </td>
                <td>
                    @for ($i = 0; $i < $order['task_length']; $i++)
                        {{ $i + 1 }} -
                        {{ str_replace('public', env('BASE_API') . '/storage', $order['tasks'][$i]['attachment_url']) }}
                        @if ($i < $order['task_length'] - 1)
                            ,
                        @endif
                    @endfor
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
